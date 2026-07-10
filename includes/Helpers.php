<?php

namespace MediaCategoryManager;

if (!defined('ABSPATH')) {
    exit;
}

final class Helpers
{
    public static function is_media_library_screen(): bool
    {
        if (!function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();

        return $screen && 'upload' === $screen->id;
    }

    public static function absint_list($values): array
    {
        if (!is_array($values)) {
            $values = array_filter(array_map('trim', explode(',', (string) $values)));
        }

        $values = array_map('absint', $values);
        $values = array_filter($values);

        return array_values(array_unique($values));
    }

    public static function current_filter(): array
    {
        $category_id = isset($_GET[Settings::QUERY_CATEGORY]) ? absint(wp_unslash($_GET[Settings::QUERY_CATEGORY])) : 0;
        $view = isset($_GET[Settings::QUERY_VIEW]) ? sanitize_key(wp_unslash($_GET[Settings::QUERY_VIEW])) : Settings::VIEW_ALL;

        return self::normalize_filter(
            array(
                'category_id' => $category_id,
                'view'        => $view,
            )
        );
    }

    public static function current_filter_from_request(array $query_args = array()): array
    {
        $request_query = isset($_REQUEST['query']) && is_array($_REQUEST['query']) ? wp_unslash($_REQUEST['query']) : array();
        $category_id = 0;
        $view = Settings::VIEW_ALL;

        if (!empty($query_args[Settings::QUERY_CATEGORY])) {
            $category_id = absint($query_args[Settings::QUERY_CATEGORY]);
        } elseif (!empty($request_query[Settings::QUERY_CATEGORY])) {
            $category_id = absint($request_query[Settings::QUERY_CATEGORY]);
        } elseif (!empty($_REQUEST[Settings::QUERY_CATEGORY])) {
            $category_id = absint(wp_unslash($_REQUEST[Settings::QUERY_CATEGORY]));
        }

        if (!empty($query_args[Settings::QUERY_VIEW])) {
            $view = sanitize_key($query_args[Settings::QUERY_VIEW]);
        } elseif (!empty($request_query[Settings::QUERY_VIEW])) {
            $view = sanitize_key($request_query[Settings::QUERY_VIEW]);
        } elseif (!empty($_REQUEST[Settings::QUERY_VIEW])) {
            $view = sanitize_key(wp_unslash($_REQUEST[Settings::QUERY_VIEW]));
        }

        if (!$category_id && Settings::VIEW_ALL === $view) {
            $referrer = wp_get_referer();

            if ($referrer) {
                $parts = wp_parse_url($referrer);

                if (!empty($parts['query'])) {
                    parse_str($parts['query'], $referrer_query);

                    if (!empty($referrer_query[Settings::QUERY_CATEGORY])) {
                        $category_id = absint($referrer_query[Settings::QUERY_CATEGORY]);
                    }

                    if (!empty($referrer_query[Settings::QUERY_VIEW])) {
                        $view = sanitize_key($referrer_query[Settings::QUERY_VIEW]);
                    }
                }
            }
        }

        return self::normalize_filter(
            array(
                'category_id' => $category_id,
                'view'        => $view,
            )
        );
    }

    private static function normalize_filter(array $filter): array
    {
        $category_id = !empty($filter['category_id']) ? absint($filter['category_id']) : 0;
        $view = !empty($filter['view']) ? sanitize_key((string) $filter['view']) : Settings::VIEW_ALL;

        if (Settings::VIEW_UNCATEGORIZED !== $view) {
            $view = Settings::VIEW_ALL;
        }

        return array(
            'category_id' => $category_id,
            'view'        => $view,
        );
    }

    public static function render_view(string $relative_path, array $args = array()): string
    {
        $path = trailingslashit(MCM_PLUGIN_DIR) . ltrim($relative_path, '/');

        if (!file_exists($path)) {
            return '';
        }

        ob_start();
        extract($args, EXTR_SKIP);
        include $path;

        return (string) ob_get_clean();
    }

    public static function attachment_total_count(): int
    {
        $counts = wp_count_attachments();
        $total = 0;

        foreach ((array) $counts as $count) {
            $total += (int) $count;
        }

        return $total;
    }

    public static function build_filter_url(array $args = array()): string
    {
        $base = admin_url('upload.php');
        $query_args = array();

        if (!empty($_GET['mode'])) {
            $query_args['mode'] = sanitize_key(wp_unslash($_GET['mode']));
        }

        foreach ($args as $key => $value) {
            if (null === $value || '' === $value || false === $value) {
                continue;
            }

            $query_args[$key] = $value;
        }

        return add_query_arg($query_args, $base);
    }
}
