<?php

namespace MediaCategoryManager\Admin;

use MediaCategoryManager\Helpers;
use MediaCategoryManager\Settings;
use MediaCategoryManager\Taxonomies;

if (!defined('ABSPATH')) {
    exit;
}

final class Ajax
{
    private Taxonomies $taxonomies;

    public function __construct(Taxonomies $taxonomies)
    {
        $this->taxonomies = $taxonomies;
    }

    public function hooks(): void
    {
        add_action('wp_ajax_' . Settings::AJAX_BULK_ASSIGN, array($this, 'bulk_assign'));
    }

    public function bulk_assign(): void
    {
        check_ajax_referer(Settings::NONCE_ACTION, 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Missing permissions.', 'media-category-manager')), 403);
        }

        $attachment_ids = Helpers::absint_list(wp_unslash($_POST['attachment_ids'] ?? array()));
        $add_ids = Helpers::absint_list(wp_unslash($_POST['add_term_ids'] ?? array()));
        $remove_ids = Helpers::absint_list(wp_unslash($_POST['remove_term_ids'] ?? array()));

        if (empty($attachment_ids)) {
            wp_send_json_error(array('message' => __('No media items selected.', 'media-category-manager')), 400);
        }

        foreach ($attachment_ids as $attachment_id) {
            if (!current_user_can('edit_post', $attachment_id)) {
                continue;
            }

            $this->taxonomies->merge_attachment_categories($attachment_id, $add_ids, $remove_ids);
        }

        wp_send_json_success(
            array(
                'message' => __('Categories updated.', 'media-category-manager'),
            )
        );
    }
}
