<?php

namespace MediaCategoryManager;

use MediaCategoryManager\Admin\Ajax;
use MediaCategoryManager\Admin\BulkEdit;
use MediaCategoryManager\Admin\MediaLibrary;
use MediaCategoryManager\Admin\QuickEdit;
use MediaCategoryManager\Admin\Sidebar;
use MediaCategoryManager\Compatibility\ACF;
use MediaCategoryManager\Compatibility\Divi;
use MediaCategoryManager\Compatibility\Gutenberg;
use MediaCategoryManager\Compatibility\MLA;

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    private static ?self $instance = null;
    private Taxonomies $taxonomies;
    private GitHubUpdater $updater;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void
    {
        Settings::hooks();

        $this->taxonomies = new Taxonomies();
        $this->taxonomies->hooks();
        $this->updater = new GitHubUpdater(MCM_PLUGIN_FILE);
        $this->updater->hooks();

        if (!is_admin()) {
            return;
        }

        $sidebar = new Sidebar($this->taxonomies);
        $bulk_edit = new BulkEdit($this->taxonomies);
        $quick_edit = new QuickEdit($this->taxonomies);
        $ajax = new Ajax($this->taxonomies);
        $media_library = new MediaLibrary($this->taxonomies, $sidebar, $bulk_edit, $quick_edit);

        $media_library->hooks();
        $bulk_edit->hooks();
        $quick_edit->hooks();
        $ajax->hooks();

        (new Divi())->hooks();
        (new Gutenberg())->hooks();
        (new MLA())->hooks();
        (new ACF())->hooks();
    }
}
