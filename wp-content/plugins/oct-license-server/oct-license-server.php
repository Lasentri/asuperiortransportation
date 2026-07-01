<?php
/**
 * Plugin Name: OneClick Taxi License Department
 * Description: License server for OneClick Taxi Ordering App. Issues, validates, and revokes license keys.
 * Version:     1.0.0
 * Author:      A Superior Transportation & Logistics
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'OCT_LS_VERSION', '1.0.0' );
define( 'OCT_LS_DIR',     plugin_dir_path( __FILE__ ) );
define( 'OCT_LS_URL',     plugin_dir_url( __FILE__ ) );

/* License tiers */
define( 'OCT_LS_TIERS', [
    '30day'    => [ 'label'=>'30-Day License',     'days'=>30,    'price'=>10000,  'display'=>'$100.00'  ],
    '6month'   => [ 'label'=>'6-Month License',    'days'=>180,   'price'=>55000,  'display'=>'$550.00'  ],
    '1year'    => [ 'label'=>'Annual License',      'days'=>365,   'price'=>100000, 'display'=>'$1,000.00'],
    'lifetime' => [ 'label'=>'Lifetime License',   'days'=>99999, 'price'=>350000, 'display'=>'$3,500.00'],
]);

require_once OCT_LS_DIR . 'includes/db.php';
require_once OCT_LS_DIR . 'includes/api.php';
require_once OCT_LS_DIR . 'includes/admin.php';
require_once OCT_LS_DIR . 'includes/purchase.php';

register_activation_hook( __FILE__, 'oct_ls_activate' );
function oct_ls_activate() {
    oct_ls_create_tables();
    /* Create the public sales/license page */
    if ( ! get_page_by_path('LicenseDepartment') ) {
        wp_insert_post([
            'post_title'   => 'License Department',
            'post_name'    => 'LicenseDepartment',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '[oct_license_store]',
        ]);
    }
}

/* Flush rewrite rules on activation */
add_action( 'init', 'oct_ls_rewrite_rules' );
function oct_ls_rewrite_rules() {
    add_rewrite_rule('^LicenseDepartment/api/([a-z_]+)/?$', 'index.php?oct_ls_action=$matches[1]', 'top');
    add_rewrite_tag('%oct_ls_action%', '([a-z_]+)');
}

/* Route API calls */
add_action( 'template_redirect', 'oct_ls_handle_api' );
function oct_ls_handle_api() {
    $action = get_query_var('oct_ls_action');
    if ( ! $action ) return;
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    switch($action) {
        case 'validate':  oct_ls_api_validate();  break;
        case 'activate':  oct_ls_api_activate();  break;
        case 'heartbeat': oct_ls_api_heartbeat(); break;
        case 'deactivate':oct_ls_api_deactivate();break;
        default: echo json_encode(['status'=>'error','message'=>'Unknown action']); break;
    }
    exit;
}

/* Enqueue assets */
add_action( 'wp_enqueue_scripts', 'oct_ls_enqueue' );
function oct_ls_enqueue() {
    if ( ! is_page('LicenseDepartment') ) return;
    wp_enqueue_style(  'oct-ls-style', OCT_LS_URL.'assets/css/store.css', [], OCT_LS_VERSION );
    wp_enqueue_script( 'oct-ls-square', 'https://web.squarecdn.com/v1/square.js', [], null, true );
    wp_enqueue_script( 'oct-ls-app',    OCT_LS_URL.'assets/js/store.js', ['jquery'], OCT_LS_VERSION, true );
    $s = get_option('oct_ls_settings', []);
    wp_localize_script('oct-ls-app', 'OCT_LS', [
        'ajax'        => admin_url('admin-ajax.php'),
        'nonce'       => wp_create_nonce('oct_ls_nonce'),
        'sqAppId'     => $s['square_app_id']   ?? '',
        'sqLocationId'=> $s['square_location'] ?? '',
        'tiers'       => OCT_LS_TIERS,
    ]);
}

/* Shortcode for the public store page */
add_shortcode( 'oct_license_store', function() {
    ob_start();
    include OCT_LS_DIR . 'templates/store.php';
    return ob_get_clean();
});
