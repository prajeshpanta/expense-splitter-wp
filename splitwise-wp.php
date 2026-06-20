<?php
/**
 * Plugin Name: Splitwise
 * Plugin URI:
 * Description: A plugin to manage group expenses.
 * Version: 1.1.2
 * Author: Prajesh Bilash Panta
 * Author URI: https://github.com/prajeshpanta
 * Text Domain: splitwise-wp
 */

if(!defined('ABSPATH')){
    exit;
}

define('SPLITWISE_WP_VERSION', '1.1.2');
define('SPLITWISE_WP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPLITWISE_WP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once SPLITWISE_WP_PLUGIN_DIR . 'includes/class-splitwise-activator.php';
require_once SPLITWISE_WP_PLUGIN_DIR . 'includes/class-splitwise-deactivator.php';
require_once SPLITWISE_WP_PLUGIN_DIR . 'includes/class-splitwise-db.php';
require_once SPLITWISE_WP_PLUGIN_DIR . 'includes/class-splitwise-expenses.php';
require_once SPLITWISE_WP_PLUGIN_DIR . 'includes/class-splitwise-balance.php';
require_once SPLITWISE_WP_PLUGIN_DIR . 'includes/class-splitwise-shortcodes.php';
require_once SPLITWISE_WP_PLUGIN_DIR . 'admin/class-splitwise-admin.php';

// Registering Activation and Deactivation hooks
register_activation_hook(__FILE__, array('Splitwise_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('Splitwise_Deactivator', 'deactivate'));

// Initialization of the plugin
function splitwise_wp_init(){

    // Load text domain for translations
    load_plugin_textdomain(
        'splitwise-wp',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );

    // Frontend shortcodes - always needed
    $plugin_shortcodes = new Splitwise_Shortcodes();
    $plugin_shortcodes->init();

    // Admin functionality - only load in wp-admin
    if(is_admin()){
        $plugin_admin = new Splitwise_Admin();
        $plugin_admin->init();
    }
}
add_action('plugins_loaded', 'splitwise_wp_init');

// Enqueue frontend assets - only on frontend pages
function splitwise_wp_frontend_assets(){
    wp_enqueue_style(
        'splitwise-frontend',
        SPLITWISE_WP_PLUGIN_URL . 'assets/css/splitwise-frontend.css',
        array(),
        SPLITWISE_WP_VERSION
    );

    wp_enqueue_script(
        'splitwise-frontend',
        SPLITWISE_WP_PLUGIN_URL . 'assets/js/splitwise-frontend.js',
        array(),
        SPLITWISE_WP_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'splitwise_wp_frontend_assets');