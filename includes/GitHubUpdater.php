<?php

namespace MediaCategoryManager;

if (!defined('ABSPATH')) {
    exit;
}

final class GitHubUpdater
{
    private const CACHE_TTL = 600;

    private string $plugin_file;
    private string $plugin_basename;
    private string $plugin_slug;
    private string $plugin_main_file;
    private string $current_version;

    public function __construct(string $plugin_file)
    {
        $this->plugin_file = $plugin_file;
        $this->plugin_basename = plugin_basename($plugin_file);
        $this->plugin_slug = dirname($this->plugin_basename);
        $this->plugin_main_file = basename($plugin_file);

        $plugin_data = get_file_data(
            $plugin_file,
            array(
                'Version' => 'Version',
            ),
            'plugin'
        );

        $this->current_version = (string) ($plugin_data['Version'] ?? '0.0.0');
    }

    public function hooks(): void
    {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'inject_update'));
        add_filter('plugins_api', array($this, 'plugins_api'), 10, 3);
        add_filter('upgrader_source_selection', array($this, 'normalize_extracted_directory'), 10, 4);
        add_filter('http_request_args', array($this, 'maybe_add_github_headers'), 10, 2);
    }

    public static function cache_key(): string
    {
        return self::cache_key_for_settings((array) Settings::get());
    }

    public static function cache_key_for_settings(array $settings): string
    {
        $repo = (string) ($settings['github_repository'] ?? '');
        $branch = (string) ($settings['update_branch'] ?? '');

        return 'mcm_github_update_' . md5($repo . '|' . $branch);
    }

    public function inject_update($transient)
    {
        if (!is_object($transient)) {
            return $transient;
        }

        $remote = $this->get_remote_data();

        if (!$remote || version_compare($remote['version'], $this->current_version, '<=')) {
            return $transient;
        }

        $transient->response[$this->plugin_basename] = (object) array(
            'slug'        => $this->plugin_slug,
            'plugin'      => $this->plugin_basename,
            'new_version' => $remote['version'],
            'package'     => $remote['package'],
            'url'         => $remote['homepage'],
            'icons'       => array(),
            'tested'      => '',
            'requires'    => '',
            'requires_php'=> '',
        );

        return $transient;
    }

    public function plugins_api($result, string $action, $args)
    {
        if ('plugin_information' !== $action || empty($args->slug) || $args->slug !== $this->plugin_slug) {
            return $result;
        }

        $remote = $this->get_remote_data();

        if (!$remote) {
            return $result;
        }

        return (object) array(
            'name'          => 'Media Category Manager',
            'slug'          => $this->plugin_slug,
            'version'       => $remote['version'],
            'author'        => '<a href="' . esc_url($remote['homepage']) . '">GitHub</a>',
            'homepage'      => $remote['homepage'],
            'download_link' => $remote['package'],
            'sections'      => array(
                'description' => wp_kses_post($remote['description']),
                'updates'     => wp_kses_post($remote['updates']),
            ),
        );
    }

    public function normalize_extracted_directory($source, $remote_source, $upgrader, $hook_extra)
    {
        if (!$this->matches_update_context((array) $hook_extra)) {
            return $source;
        }

        $source = $this->locate_plugin_source_directory((string) $source);
        $target = trailingslashit(dirname($source)) . $this->plugin_slug;

        if ($source === $target) {
            return $source;
        }

        if (file_exists($target)) {
            global $wp_filesystem;

            if ($wp_filesystem) {
                $wp_filesystem->delete($target, true);
            }
        }

        $renamed = $this->move_source_to_target($source, $target);

        if ($renamed) {
            return $target;
        }

        return new \WP_Error(
            'mcm_update_move_failed',
            __('The update package could not be moved into the plugin directory.', 'media-category-manager')
        );
    }

    public function maybe_add_github_headers(array $args, string $url): array
    {
        if (false === strpos($url, 'api.github.com')) {
            return $args;
        }

        $args['headers']['Accept'] = 'application/vnd.github+json';
        $args['headers']['User-Agent'] = 'WordPress/' . get_bloginfo('version') . '; ' . home_url('/');

        return $args;
    }

    private function get_remote_data(): ?array
    {
        $cache_key = self::cache_key();
        $cached = get_site_transient($cache_key);

        if (is_array($cached)) {
            return $cached;
        }

        $repo = $this->parse_repository((string) Settings::get('github_repository'));

        if (!$repo) {
            return null;
        }

        $branch = (string) Settings::get('update_branch');
        if (!in_array($branch, Settings::branch_choices(), true)) {
            $branch = Settings::UPDATE_BRANCH_MAIN;
        }

        $plugin_header_url = sprintf(
            'https://raw.githubusercontent.com/%1$s/%2$s/%3$s/%4$s',
            rawurlencode($repo['owner']),
            rawurlencode($repo['repo']),
            rawurlencode($branch),
            $this->plugin_main_file
        );

        $response = wp_remote_get(
            $plugin_header_url,
            array(
                'timeout' => 15,
            )
        );

        if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
            return null;
        }

        $body = (string) wp_remote_retrieve_body($response);
        $version = $this->parse_plugin_header($body, 'Version');

        if (!$version) {
            return null;
        }

        $description = $this->parse_plugin_header($body, 'Description') ?: __('GitHub-based update channel for Media Category Manager.', 'media-category-manager');

        $data = array(
            'version'     => $version,
            'package'     => sprintf(
                'https://codeload.github.com/%1$s/%2$s/zip/refs/heads/%3$s',
                rawurlencode($repo['owner']),
                rawurlencode($repo['repo']),
                rawurlencode($branch)
            ),
            'homepage'    => $repo['url'],
            'description' => '<p>' . esc_html($description) . '</p>',
            'updates'     => '<p>' . sprintf(
                esc_html__('Update source: %1$s branch from %2$s.', 'media-category-manager'),
                esc_html($branch),
                esc_html($repo['url'])
            ) . '</p>',
        );

        set_site_transient($cache_key, $data, self::CACHE_TTL);

        return $data;
    }

    private function parse_repository(string $url): ?array
    {
        if ('' === $url) {
            return null;
        }

        $pattern = '#^https://github\.com/([^/]+)/([^/]+?)(?:\.git|/)?$#i';

        if (!preg_match($pattern, $url, $matches)) {
            return null;
        }

        return array(
            'owner' => $matches[1],
            'repo'  => $matches[2],
            'url'   => 'https://github.com/' . $matches[1] . '/' . $matches[2],
        );
    }

    private function matches_update_context(array $hook_extra): bool
    {
        if (!empty($hook_extra['plugin']) && $hook_extra['plugin'] === $this->plugin_basename) {
            return true;
        }

        if (empty($hook_extra['package']) || !is_string($hook_extra['package'])) {
            return false;
        }

        $repo = $this->parse_repository((string) Settings::get('github_repository'));

        if (!$repo) {
            return false;
        }

        $expected = 'https://codeload.github.com/' . $repo['owner'] . '/' . $repo['repo'] . '/zip/refs/heads/';

        return 0 === strpos($hook_extra['package'], $expected);
    }

    private function parse_plugin_header(string $contents, string $field): ?string
    {
        $pattern = '/^[ \t\/*#@]*' . preg_quote($field, '/') . ':\s*(.+)$/mi';

        if (!preg_match($pattern, $contents, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private function locate_plugin_source_directory(string $source): string
    {
        $main_file = trailingslashit($source) . $this->plugin_main_file;

        if (is_file($main_file)) {
            return $source;
        }

        $directories = glob(trailingslashit($source) . '*', GLOB_ONLYDIR);

        if (empty($directories)) {
            return $source;
        }

        foreach ($directories as $directory) {
            if (is_file(trailingslashit($directory) . $this->plugin_main_file)) {
                return untrailingslashit($directory);
            }
        }

        return $source;
    }

    private function move_source_to_target(string $source, string $target): bool
    {
        if (@rename($source, $target)) {
            return true;
        }

        if (function_exists('move_dir')) {
            $result = move_dir($source, $target, true);

            if (!is_wp_error($result)) {
                return true;
            }
        }

        global $wp_filesystem;

        if ($wp_filesystem && method_exists($wp_filesystem, 'move') && $wp_filesystem->move($source, $target, true)) {
            return true;
        }

        return false;
    }
}
