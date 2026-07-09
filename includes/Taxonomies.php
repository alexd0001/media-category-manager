<?php

namespace MediaCategoryManager;

if (!defined('ABSPATH')) {
    exit;
}

final class Taxonomies
{
    public function hooks(): void
    {
        add_action('init', array($this, 'register'));
    }

    public function register(): void
    {
        register_taxonomy(
            Settings::TAXONOMY_CATEGORY,
            'attachment',
            array(
                'labels'            => array(
                    'name'          => __('Media Categories', 'media-category-manager'),
                    'singular_name' => __('Media Category', 'media-category-manager'),
                ),
                'public'            => false,
                'hierarchical'      => true,
                'show_ui'           => true,
                'show_admin_column' => false,
                'show_in_rest'      => true,
                'query_var'         => true,
                'rewrite'           => false,
            )
        );

        register_taxonomy(
            Settings::TAXONOMY_TAG,
            'attachment',
            array(
                'labels'            => array(
                    'name'          => __('Media Tags', 'media-category-manager'),
                    'singular_name' => __('Media Tag', 'media-category-manager'),
                ),
                'public'            => false,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_admin_column' => false,
                'show_in_rest'      => true,
                'query_var'         => true,
                'rewrite'           => false,
            )
        );
    }

    public function get_category_tree(): array
    {
        $terms = get_terms(
            array(
                'taxonomy'   => Settings::TAXONOMY_CATEGORY,
                'hide_empty' => false,
                'pad_counts' => true,
                'orderby'    => 'name',
                'order'      => 'ASC',
            )
        );

        if (is_wp_error($terms)) {
            return array();
        }

        return $this->build_tree($terms, 0);
    }

    public function get_category_choices(): array
    {
        $terms = get_terms(
            array(
                'taxonomy'   => Settings::TAXONOMY_CATEGORY,
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            )
        );

        if (is_wp_error($terms)) {
            return array();
        }

        $choices = array();

        foreach ($terms as $term) {
            $choices[] = array(
                'id'   => (int) $term->term_id,
                'name' => $term->name,
            );
        }

        return $choices;
    }

    public function get_attachment_category_names(int $attachment_id): array
    {
        $terms = get_the_terms($attachment_id, Settings::TAXONOMY_CATEGORY);

        if (empty($terms) || is_wp_error($terms)) {
            return array();
        }

        return array_values(wp_list_pluck($terms, 'name'));
    }

    public function get_attachment_category_ids(int $attachment_id): array
    {
        $terms = get_the_terms($attachment_id, Settings::TAXONOMY_CATEGORY);

        if (empty($terms) || is_wp_error($terms)) {
            return array();
        }

        return array_map('intval', wp_list_pluck($terms, 'term_id'));
    }

    public function replace_attachment_categories(int $attachment_id, array $term_ids): array
    {
        $term_ids = Helpers::absint_list($term_ids);

        wp_set_object_terms($attachment_id, $term_ids, Settings::TAXONOMY_CATEGORY, false);

        return $this->get_attachment_category_ids($attachment_id);
    }

    public function merge_attachment_categories(int $attachment_id, array $add_term_ids = array(), array $remove_term_ids = array()): array
    {
        $current = $this->get_attachment_category_ids($attachment_id);
        $add_term_ids = Helpers::absint_list($add_term_ids);
        $remove_term_ids = Helpers::absint_list($remove_term_ids);

        $final = array_values(array_unique(array_merge($current, $add_term_ids)));

        if (!empty($remove_term_ids)) {
            $final = array_values(array_diff($final, $remove_term_ids));
        }

        wp_set_object_terms($attachment_id, $final, Settings::TAXONOMY_CATEGORY, false);

        return $this->get_attachment_category_ids($attachment_id);
    }

    public function get_uncategorized_count(): int
    {
        $query = new \WP_Query(
            array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'tax_query'      => array(
                    array(
                        'taxonomy' => Settings::TAXONOMY_CATEGORY,
                        'operator' => 'NOT EXISTS',
                    ),
                ),
            )
        );

        return (int) $query->found_posts;
    }

    public function apply_filter_to_query(\WP_Query $query, array $filter): void
    {
        $tax_query = (array) $query->get('tax_query');
        $new_rule = array();

        if (!empty($filter['category_id'])) {
            $new_rule = array(
                'taxonomy'         => Settings::TAXONOMY_CATEGORY,
                'field'            => 'term_id',
                'terms'            => array((int) $filter['category_id']),
                'include_children' => true,
            );
        } elseif (Settings::VIEW_UNCATEGORIZED === $filter['view']) {
            $new_rule = array(
                'taxonomy' => Settings::TAXONOMY_CATEGORY,
                'operator' => 'NOT EXISTS',
            );
        }

        if (empty($new_rule)) {
            return;
        }

        $tax_query[] = $new_rule;
        $query->set('tax_query', $tax_query);
    }

    private function build_tree(array $terms, int $parent_id): array
    {
        $branch = array();

        foreach ($terms as $term) {
            if ((int) $term->parent !== $parent_id) {
                continue;
            }

            $branch[] = array(
                'id'       => (int) $term->term_id,
                'name'     => $term->name,
                'count'    => (int) $term->count,
                'children' => $this->build_tree($terms, (int) $term->term_id),
            );
        }

        return $branch;
    }
}
