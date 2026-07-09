<?php

namespace MediaCategoryManager\Admin;

use MediaCategoryManager\Helpers;
use MediaCategoryManager\Settings;
use MediaCategoryManager\Taxonomies;

if (!defined('ABSPATH')) {
    exit;
}

final class Sidebar
{
    private Taxonomies $taxonomies;

    public function __construct(Taxonomies $taxonomies)
    {
        $this->taxonomies = $taxonomies;
    }

    public function render(array $current_filter): string
    {
        return Helpers::render_view(
            'admin/Views/sidebar.php',
            array(
                'all_count'           => Helpers::attachment_total_count(),
                'uncategorized_count' => $this->taxonomies->get_uncategorized_count(),
                'current_filter'      => $current_filter,
                'tree_html'           => $this->render_tree($this->taxonomies->get_category_tree(), $current_filter),
            )
        );
    }

    private function render_tree(array $items, array $current_filter): string
    {
        if (empty($items)) {
            return '';
        }

        $html = '<ul class="mcm-tree-list">';

        foreach ($items as $item) {
            $is_active = !empty($current_filter['category_id']) && (int) $current_filter['category_id'] === (int) $item['id'];
            $has_children = !empty($item['children']);

            $html .= '<li class="mcm-tree-item' . ($has_children ? ' has-children' : '') . '">';

            if ($has_children) {
                $html .= '<button type="button" class="mcm-tree-toggle" aria-expanded="' . ($is_active ? 'true' : 'false') . '">+</button>';
            } else {
                $html .= '<span class="mcm-tree-toggle-placeholder"></span>';
            }

            $html .= sprintf(
                '<a class="mcm-tree-link%s" href="%s" data-category-id="%d">%s <span class="count">%d</span></a>',
                $is_active ? ' is-active' : '',
                esc_url(
                    Helpers::build_filter_url(
                        array(
                            Settings::QUERY_CATEGORY => (int) $item['id'],
                        )
                    )
                ),
                (int) $item['id'],
                esc_html($item['name']),
                (int) $item['count']
            );

            if ($has_children) {
                $html .= '<div class="mcm-tree-children"' . ($is_active ? '' : ' hidden') . '>';
                $html .= $this->render_tree($item['children'], $current_filter);
                $html .= '</div>';
            }

            $html .= '</li>';
        }

        $html .= '</ul>';

        return $html;
    }
}
