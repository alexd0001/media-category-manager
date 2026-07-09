<?php

if (!defined('ABSPATH')) {
    exit;
}

$all_active = empty($current_filter['category_id']) && 'uncategorized' !== $current_filter['view'];
$uncategorized_active = 'uncategorized' === $current_filter['view'];
?>
<aside class="mcm-sidebar">
    <div class="mcm-panel">
        <h2><?php esc_html_e('Media Categories', 'media-category-manager'); ?></h2>
        <label class="screen-reader-text" for="mcm-category-search"><?php esc_html_e('Search categories', 'media-category-manager'); ?></label>
        <input id="mcm-category-search" class="mcm-search" type="search" placeholder="<?php esc_attr_e('Search categories', 'media-category-manager'); ?>">

        <nav class="mcm-sidebar-nav" aria-label="<?php esc_attr_e('Media categories', 'media-category-manager'); ?>">
            <ul class="mcm-tree-list mcm-tree-list-root">
                <li class="mcm-tree-item">
                    <span class="mcm-tree-toggle-placeholder"></span>
                    <a class="mcm-tree-link<?php echo $all_active ? ' is-active' : ''; ?>" href="<?php echo esc_url(\MediaCategoryManager\Helpers::build_filter_url()); ?>">
                        <?php esc_html_e('All images', 'media-category-manager'); ?>
                        <span class="count"><?php echo esc_html((string) $all_count); ?></span>
                    </a>
                </li>
                <li class="mcm-tree-item">
                    <span class="mcm-tree-toggle-placeholder"></span>
                    <a class="mcm-tree-link<?php echo $uncategorized_active ? ' is-active' : ''; ?>" href="<?php echo esc_url(\MediaCategoryManager\Helpers::build_filter_url(array(\MediaCategoryManager\Settings::QUERY_VIEW => \MediaCategoryManager\Settings::VIEW_UNCATEGORIZED))); ?>">
                        <?php esc_html_e('Uncategorized', 'media-category-manager'); ?>
                        <span class="count"><?php echo esc_html((string) $uncategorized_count); ?></span>
                    </a>
                </li>
            </ul>
            <?php echo $tree_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </nav>
    </div>
</aside>
