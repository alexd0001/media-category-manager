<?php

namespace MediaCategoryManager;

if (!defined('ABSPATH')) {
    exit;
}

final class Settings
{
    public const OPTION_KEY = 'mcm_settings';
    public const SETTINGS_GROUP = 'mcm_settings_group';
    public const SETTINGS_PAGE = 'mcm-settings';
    public const SETTINGS_CAPABILITY = 'manage_options';
    public const CLEAR_CACHE_ACTION = 'mcm_clear_update_cache';

    public const TAXONOMY_CATEGORY = 'attachment_category';
    public const TAXONOMY_TAG = 'attachment_tag';

    public const QUERY_CATEGORY = 'mcm_category';
    public const QUERY_VIEW = 'mcm_view';
    public const VIEW_ALL = 'all';
    public const VIEW_UNCATEGORIZED = 'uncategorized';

    public const NONCE_ACTION = 'mcm_admin_nonce';
    public const NONCE_NAME = 'mcm_nonce';

    public const AJAX_BULK_ASSIGN = 'mcm_bulk_assign';

    public const UPDATE_BRANCH_MAIN = 'main';
    public const UPDATE_BRANCH_DEVELOP = 'develop';

    private function __construct()
    {
    }

    public static function hooks(): void
    {
        add_action('admin_init', array(__CLASS__, 'register'));
        add_action('admin_init', array(__CLASS__, 'handle_clear_cache'));
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
        add_action('update_option_' . self::OPTION_KEY, array(__CLASS__, 'flush_update_cache'), 10, 2);
    }

    public static function register(): void
    {
        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_KEY,
            array(
                'type'              => 'array',
                'sanitize_callback' => array(__CLASS__, 'sanitize'),
                'default'           => self::defaults(),
            )
        );

        add_settings_section(
            'mcm_updates',
            __('GitHub Updates', 'media-category-manager'),
            static function (): void {
                echo '<p>' . esc_html__('Configure the GitHub repository and branch this plugin should use for update checks.', 'media-category-manager') . '</p>';
            },
            self::SETTINGS_PAGE
        );

        add_settings_field(
            'github_repository',
            __('Repository URL', 'media-category-manager'),
            array(__CLASS__, 'render_repository_field'),
            self::SETTINGS_PAGE,
            'mcm_updates'
        );

        add_settings_field(
            'update_branch',
            __('Update Channel', 'media-category-manager'),
            array(__CLASS__, 'render_branch_field'),
            self::SETTINGS_PAGE,
            'mcm_updates'
        );
    }

    public static function add_settings_page(): void
    {
        add_options_page(
            __('Media Category Manager', 'media-category-manager'),
            __('Media Category Manager', 'media-category-manager'),
            self::SETTINGS_CAPABILITY,
            self::SETTINGS_PAGE,
            array(__CLASS__, 'render_settings_page')
        );
    }

    public static function render_settings_page(): void
    {
        if (!current_user_can(self::SETTINGS_CAPABILITY)) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Media Category Manager', 'media-category-manager') . '</h1>';

        if (!empty($_GET['mcm_cache_cleared'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Update cache cleared.', 'media-category-manager') . '</p></div>';
        }

        echo '<form action="options.php" method="post">';
        settings_fields(self::SETTINGS_GROUP);
        do_settings_sections(self::SETTINGS_PAGE);
        submit_button();
        echo '</form>';

        echo '<hr>';
        echo '<h2>' . esc_html__('Maintenance', 'media-category-manager') . '</h2>';
        echo '<p>' . esc_html__('If WordPress shows an outdated plugin version, clear the cached update data and run a fresh check.', 'media-category-manager') . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('options-general.php?page=' . self::SETTINGS_PAGE)) . '">';
        wp_nonce_field(self::CLEAR_CACHE_ACTION);
        echo '<input type="hidden" name="mcm_action" value="clear_update_cache">';
        submit_button(__('Clear Update Cache', 'media-category-manager'), 'secondary', 'submit', false);
        echo '</form>';
        echo '</div>';
    }

    public static function get(?string $key = null)
    {
        $settings = wp_parse_args((array) get_option(self::OPTION_KEY, array()), self::defaults());

        if (null === $key) {
            return $settings;
        }

        return $settings[$key] ?? null;
    }

    public static function defaults(): array
    {
        return array(
            'github_repository' => '',
            'update_branch'     => self::UPDATE_BRANCH_MAIN,
        );
    }

    public static function sanitize($value): array
    {
        $value = is_array($value) ? $value : array();
        $branch = sanitize_key($value['update_branch'] ?? self::UPDATE_BRANCH_MAIN);

        if (!in_array($branch, self::branch_choices(), true)) {
            $branch = self::UPDATE_BRANCH_MAIN;
        }

        return array(
            'github_repository' => esc_url_raw(trim((string) ($value['github_repository'] ?? ''))),
            'update_branch'     => $branch,
        );
    }

    public static function flush_update_cache($old_value = null, $value = null): void
    {
        delete_site_transient(GitHubUpdater::cache_key());
        delete_site_transient(GitHubUpdater::cache_key_for_settings((array) $old_value));
        delete_site_transient(GitHubUpdater::cache_key_for_settings((array) $value));
        delete_site_transient('update_plugins');
    }

    public static function handle_clear_cache(): void
    {
        if (!is_admin()) {
            return;
        }

        if (!current_user_can(self::SETTINGS_CAPABILITY)) {
            return;
        }

        if (empty($_POST['mcm_action']) || 'clear_update_cache' !== wp_unslash($_POST['mcm_action'])) {
            return;
        }

        check_admin_referer(self::CLEAR_CACHE_ACTION);

        self::flush_update_cache();
        wp_update_plugins();

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page'              => self::SETTINGS_PAGE,
                    'mcm_cache_cleared' => '1',
                ),
                admin_url('options-general.php')
            )
        );
        exit;
    }

    public static function branch_choices(): array
    {
        return array(
            self::UPDATE_BRANCH_MAIN,
            self::UPDATE_BRANCH_DEVELOP,
        );
    }

    public static function render_repository_field(): void
    {
        $value = (string) self::get('github_repository');

        printf(
            '<input type="url" class="regular-text code" name="%1$s[github_repository]" value="%2$s" placeholder="%3$s">',
            esc_attr(self::OPTION_KEY),
            esc_attr($value),
            esc_attr('https://github.com/your-org/media-category-manager')
        );
        echo '<p class="description">' . esc_html__('Public GitHub repository URL for this plugin.', 'media-category-manager') . '</p>';
    }

    public static function render_branch_field(): void
    {
        $value = (string) self::get('update_branch');
        $labels = array(
            self::UPDATE_BRANCH_MAIN    => __('main (release)', 'media-category-manager'),
            self::UPDATE_BRANCH_DEVELOP => __('develop (testing)', 'media-category-manager'),
        );

        echo '<select name="' . esc_attr(self::OPTION_KEY) . '[update_branch]">';

        foreach (self::branch_choices() as $branch) {
            printf(
                '<option value="%1$s"%2$s>%3$s</option>',
                esc_attr($branch),
                selected($value, $branch, false),
                esc_html($labels[$branch] ?? $branch)
            );
        }

        echo '</select>';
        echo '<p class="description">' . esc_html__('Choose which branch WordPress should use for update checks.', 'media-category-manager') . '</p>';
    }
}
