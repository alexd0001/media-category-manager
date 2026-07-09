<?php

namespace MediaCategoryManager\Admin;

use MediaCategoryManager\Helpers;
use MediaCategoryManager\Settings;
use MediaCategoryManager\Taxonomies;

if (!defined('ABSPATH')) {
    exit;
}

final class MediaLibrary
{
    private Taxonomies $taxonomies;
    private Sidebar $sidebar;
    private BulkEdit $bulk_edit;
    private QuickEdit $quick_edit;

    public function __construct(Taxonomies $taxonomies, Sidebar $sidebar, BulkEdit $bulk_edit, QuickEdit $quick_edit)
    {
        $this->taxonomies = $taxonomies;
        $this->sidebar = $sidebar;
        $this->bulk_edit = $bulk_edit;
        $this->quick_edit = $quick_edit;
    }

    public function hooks(): void
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_footer-upload.php', array($this, 'print_templates'));
        add_action('pre_get_posts', array($this, 'filter_list_query'));
        add_filter('ajax_query_attachments_args', array($this, 'filter_grid_query'));
        add_filter('wp_prepare_attachment_for_js', array($this, 'prepare_attachment_for_js'), 10, 2);
        add_filter('manage_upload_columns', array($this, 'add_columns'));
        add_action('manage_media_custom_column', array($this, 'render_column'), 10, 2);
    }

    public function enqueue_assets(): void
    {
        if (!Helpers::is_media_library_screen()) {
            return;
        }

        wp_enqueue_style('mcm-admin', MCM_PLUGIN_URL . 'assets/css/admin.css', array(), MCM_VERSION);
        wp_enqueue_style('mcm-tree', MCM_PLUGIN_URL . 'assets/css/tree.css', array('mcm-admin'), MCM_VERSION);

        wp_enqueue_script('mcm-admin', MCM_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'inline-edit-post'), MCM_VERSION, true);
        wp_enqueue_script('mcm-tree', MCM_PLUGIN_URL . 'assets/js/tree.js', array('mcm-admin'), MCM_VERSION, true);
        wp_enqueue_script('mcm-bulk', MCM_PLUGIN_URL . 'assets/js/bulk.js', array('mcm-admin'), MCM_VERSION, true);

        wp_localize_script(
            'mcm-admin',
            'mcmAdmin',
            array(
                'ajaxUrl'       => admin_url('admin-ajax.php'),
                'nonce'         => wp_create_nonce(Settings::NONCE_ACTION),
                'action'        => Settings::AJAX_BULK_ASSIGN,
                'currentFilter' => Helpers::current_filter(),
                'strings'       => array(
                    'selectionRequired' => __('Select at least one media item.', 'media-category-manager'),
                    'bulkSuccess'       => __('Categories updated.', 'media-category-manager'),
                    'bulkError'         => __('The bulk action could not be completed.', 'media-category-manager'),
                    'uncategorized'     => __('Uncategorized', 'media-category-manager'),
                ),
            )
        );
    }

    public function print_templates(): void
    {
        if (!Helpers::is_media_library_screen()) {
            return;
        }

        echo '<div id="mcm-sidebar-template" hidden>';
        echo $this->sidebar->render(Helpers::current_filter());
        echo $this->bulk_edit->render_panel();
        echo '</div>';
    }

    public function filter_list_query(\WP_Query $query): void
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ('upload.php' !== $GLOBALS['pagenow']) {
            return;
        }

        $this->taxonomies->apply_filter_to_query($query, Helpers::current_filter());
    }

    public function filter_grid_query(array $query): array
    {
        if (!is_admin()) {
            return $query;
        }

        $filter = array(
            'category_id' => isset($_REQUEST[Settings::QUERY_CATEGORY]) ? absint(wp_unslash($_REQUEST[Settings::QUERY_CATEGORY])) : 0,
            'view'        => isset($_REQUEST[Settings::QUERY_VIEW]) ? sanitize_key(wp_unslash($_REQUEST[Settings::QUERY_VIEW])) : Settings::VIEW_ALL,
        );

        $wp_query = new \WP_Query();
        $this->taxonomies->apply_filter_to_query($wp_query, $filter);
        $tax_query = $wp_query->get('tax_query');

        if (!empty($tax_query)) {
            $query['tax_query'] = $tax_query;
        }

        return $query;
    }

    public function prepare_attachment_for_js(array $response, \WP_Post $attachment): array
    {
        $response['mcmCategories'] = $this->taxonomies->get_attachment_category_names((int) $attachment->ID);

        return $response;
    }

    public function add_columns(array $columns): array
    {
        $columns['mcm_categories'] = __('Categories', 'media-category-manager');

        return $columns;
    }

    public function render_column(string $column_name, int $post_id): void
    {
        if ('mcm_categories' !== $column_name) {
            return;
        }

        $names = $this->taxonomies->get_attachment_category_names($post_id);
        $ids = $this->taxonomies->get_attachment_category_ids($post_id);

        echo '<div class="mcm-list-badges">';

        if (empty($names)) {
            echo '<span class="mcm-badge mcm-badge-empty">' . esc_html__('Uncategorized', 'media-category-manager') . '</span>';
        } else {
            foreach ($names as $name) {
                echo '<span class="mcm-badge">' . esc_html($name) . '</span>';
            }
        }

        echo '<span class="mcm-term-ids" hidden data-term-ids="' . esc_attr(implode(',', $ids)) . '"></span>';
        echo '</div>';
    }
}
