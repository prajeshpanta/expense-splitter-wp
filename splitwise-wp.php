<?php
/**
 * Plugin Name:       Splitwise WP
 * Plugin URI:        https://github.com/prajeshpanta/expense-splitter-wp
 * Description:       Splitwise-like group expense sharing and balance management for WordPress. 
 *                    Track who owes whom, split expenses easily, and view clear balances.
 * Version:           1.1.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Prajesh Bilash Panta
 * Author URI:        https://github.com/prajeshpanta
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       splitwise-wp
 * Domain Path:       /languages
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

// ==================================================================
// Helper Functions (used by templates)
// ==================================================================

/**
 * Get the currency symbol.
 * Default: Rs (Nepali Rupees). Override via 'splitwise_currency_symbol' filter.
 *
 * @return string
 */
function splitwise_get_currency_symbol() {
    return apply_filters( 'splitwise_currency_symbol', 'Rs' );
}

/**
 * Get the URL of a frontend page containing a specific Splitwise shortcode.
 * Searches published pages for the given shortcode tag.
 *
 * @param string $page_type  One of: 'dashboard', 'add_expense', 'balance', 'expenses'.
 * @return string  The page URL, or '#' if not found.
 */
function splitwise_get_page_url( $page_type ) {
    $shortcode_map = [
        'dashboard'   => 'splitwise_dashboard',
        'add_expense' => 'splitwise_add_expense',
        'balance'     => 'splitwise_balance',
        'expenses'    => 'splitwise_dashboard', // fallback to dashboard
    ];

    $shortcode = isset( $shortcode_map[ $page_type ] ) ? $shortcode_map[ $page_type ] : '';

    if ( empty( $shortcode ) ) {
        return '#';
    }

    // Try to find a published page containing the shortcode
    $pages = get_posts( [
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        's'              => '[' . $shortcode,
        'fields'         => 'ids',
    ] );

    if ( ! empty( $pages ) ) {
        return get_permalink( $pages[0] );
    }

    return '#';
}

/**
 * Get the URL for the Add Expense page.
 *
 * @return string
 */
function splitwise_get_add_expense_url() {
    return splitwise_get_page_url( 'add_expense' );
}

/**
 * Get the URL for the Balance page.
 *
 * @return string
 */
function splitwise_get_balance_url() {
    return splitwise_get_page_url( 'balance' );
}

/**
 * Get the URL for the Expenses / Dashboard page.
 *
 * @return string
 */
function splitwise_get_expenses_url() {
    return splitwise_get_page_url( 'expenses' );
}