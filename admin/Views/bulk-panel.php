<?php

if (!defined('ABSPATH')) {
    exit;
}

?>
<section class="mcm-panel mcm-bulk-panel">
    <h2><?php esc_html_e('Bulk Categories', 'media-category-manager'); ?></h2>
    <p class="description"><?php esc_html_e('Adds and removes categories without replacing the existing assignments.', 'media-category-manager'); ?></p>

    <label for="mcm-bulk-add"><?php esc_html_e('Add categories', 'media-category-manager'); ?></label>
    <select id="mcm-bulk-add" class="mcm-multiselect" multiple>
        <?php foreach ($term_choices as $choice) : ?>
            <option value="<?php echo esc_attr((string) $choice['id']); ?>"><?php echo esc_html($choice['name']); ?></option>
        <?php endforeach; ?>
    </select>

    <label for="mcm-bulk-remove"><?php esc_html_e('Remove categories', 'media-category-manager'); ?></label>
    <select id="mcm-bulk-remove" class="mcm-multiselect" multiple>
        <?php foreach ($term_choices as $choice) : ?>
            <option value="<?php echo esc_attr((string) $choice['id']); ?>"><?php echo esc_html($choice['name']); ?></option>
        <?php endforeach; ?>
    </select>

    <button type="button" class="button button-primary" id="mcm-bulk-apply"><?php esc_html_e('Apply to selection', 'media-category-manager'); ?></button>
    <p class="mcm-bulk-status" id="mcm-bulk-status" aria-live="polite"></p>
</section>
