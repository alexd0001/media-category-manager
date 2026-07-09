<?php

namespace MediaCategoryManager\Admin;

use MediaCategoryManager\Helpers;
use MediaCategoryManager\Taxonomies;

if (!defined('ABSPATH')) {
    exit;
}

final class BulkEdit
{
    private Taxonomies $taxonomies;

    public function __construct(Taxonomies $taxonomies)
    {
        $this->taxonomies = $taxonomies;
    }

    public function hooks(): void
    {
    }

    public function render_panel(): string
    {
        return Helpers::render_view(
            'admin/Views/bulk-panel.php',
            array(
                'term_choices' => $this->taxonomies->get_category_choices(),
            )
        );
    }
}
