<?php

namespace MediaCategoryManager\Admin;

use MediaCategoryManager\Helpers;
use MediaCategoryManager\Settings;
use MediaCategoryManager\Taxonomies;

if (!defined('ABSPATH')) {
    exit;
}

final class QuickEdit
{
    private Taxonomies $taxonomies;

    public function __construct(Taxonomies $taxonomies)
    {
        $this->taxonomies = $taxonomies;
    }

    public function hooks(): void
    {
        add_action('quick_edit_custom_box', array($this, 'render'), 10, 2);
        add_action('save_post_attachment', array($this, 'save'));
    }

    public function render(string $column_name, string $post_type): void
    {
        if ('attachment' !== $post_type || 'mcm_categories' !== $column_name) {
            return;
        }

        $choices = $this->taxonomies->get_category_choices();

        echo '<fieldset class="inline-edit-col-right mcm-quick-edit">';
        echo '<div class="inline-edit-col">';
        echo '<span class="title">' . esc_html__('Media Categories', 'media-category-manager') . '</span>';
        echo '<input type="hidden" name="mcm_quick_edit_present" value="1">';
        echo '<div class="mcm-quick-edit-list">';

        foreach ($choices as $choice) {
            printf(
                '<label><input type="checkbox" name="mcm_attachment_categories[]" value="%d"> %s</label>',
                (int) $choice['id'],
                esc_html($choice['name'])
            );
        }

        echo '</div>';
        wp_nonce_field(Settings::NONCE_ACTION, Settings::NONCE_NAME);
        echo '</div>';
        echo '</fieldset>';
    }

    public function save(int $post_id): void
    {
        if (!isset($_POST[Settings::NONCE_NAME])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[Settings::NONCE_NAME])), Settings::NONCE_ACTION)) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (!isset($_POST['mcm_quick_edit_present'])) {
            return;
        }

        $term_ids = Helpers::absint_list(wp_unslash($_POST['mcm_attachment_categories'] ?? array()));
        $this->taxonomies->replace_attachment_categories($post_id, $term_ids);
    }
}
