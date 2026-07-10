<?php
/**
 * Plugin Name: Media Category Manager
 * Description: Folder-like media category management for WordPress attachments.
 * Version: 0.1.5
 * Author: dMAD alex deutschenbaur
 * License: GPL-2.0-or-later
 * Text Domain: media-category-manager
 * Update URI: media-category-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MCM_VERSION', '0.1.5');
define('MCM_PLUGIN_FILE', __FILE__);
define('MCM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MCM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once MCM_PLUGIN_DIR . 'includes/Settings.php';
require_once MCM_PLUGIN_DIR . 'includes/Helpers.php';
require_once MCM_PLUGIN_DIR . 'includes/Taxonomies.php';
require_once MCM_PLUGIN_DIR . 'includes/GitHubUpdater.php';
require_once MCM_PLUGIN_DIR . 'includes/Compatibility/Divi.php';
require_once MCM_PLUGIN_DIR . 'includes/Compatibility/Gutenberg.php';
require_once MCM_PLUGIN_DIR . 'includes/Compatibility/MLA.php';
require_once MCM_PLUGIN_DIR . 'includes/Compatibility/ACF.php';
require_once MCM_PLUGIN_DIR . 'includes/Plugin.php';
require_once MCM_PLUGIN_DIR . 'admin/Sidebar.php';
require_once MCM_PLUGIN_DIR . 'admin/MediaLibrary.php';
require_once MCM_PLUGIN_DIR . 'admin/BulkEdit.php';
require_once MCM_PLUGIN_DIR . 'admin/QuickEdit.php';
require_once MCM_PLUGIN_DIR . 'admin/Ajax.php';

add_action('plugins_loaded', static function () {
    if (class_exists('MediaCategoryManager\\Plugin')) {
        \MediaCategoryManager\Plugin::instance()->boot();
    }
});
