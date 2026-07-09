<?php
/**
 * Plugin Name: Media Category Manager
 * Description: Folder-like media category management for WordPress attachments.
 * Version: 0.1.0
 * Author: Waldorfshop.eu / Kreativkombinat
 * License: GPL-2.0-or-later
 * Text Domain: media-category-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MCM_VERSION', '0.1.0');
define('MCM_PLUGIN_FILE', __FILE__);
define('MCM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MCM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once MCM_PLUGIN_DIR . 'includes/Plugin.php';

add_action('plugins_loaded', static function () {
    if (class_exists('MediaCategoryManager\\Plugin')) {
        MediaCategoryManager\\Plugin::instance()->boot();
    }
});
