<?php
/**
 * Plugin Name: A Superior Transportation - Full Site v3.0
 * Plugin URI:  https://asuperiortransportation.com
 * Description: Complete website - Homepage booking, Suggested Places, Gas Tracker, Calendar, Square Payments.
 * Version:     3.0.0
 * Author:      A Superior Transportation
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ST_VERSION', '3.0.0' );
define( 'ST_DIR',     plugin_dir_path( __FILE__ ) );
define( 'ST_URL',     plugin_dir_url( __FILE__ ) );

require_once ST_DIR . 'includes/settings.php';
require_once ST_DIR . 'includes/pages.php';
require_once ST_DIR . 'includes/gas-admin.php';
require_once ST_DIR . 'includes/ajax.php';

add_action( 'wp_enqueue_scripts', 'st_enqueue_assets' );
function st_enqueue_assets() {
    $s = st_get_settings();
    wp_enqueue_style( 'st-fonts',  'https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap', [], null );
    wp_enqueue_style( 'st-style',  ST_URL . 'assets/css/style.css', ['st-fonts'], ST_VERSION );
    wp_enqueue_script( 'st-app',   ST_URL . 'assets/js/app.js', [], ST_VERSION, true );

    if ( is_front_page() || is_page('suggested-places') ) {
        $key = esc_attr( $s['google_maps_key'] ?? '' );
        wp_enqueue_script( 'google-maps',
            "https://maps.googleapis.com/maps/api/js?key={$key}&libraries=places&callback=stInitMap&loading=async",
            [], null, true
        );
    }

    wp_localize_script( 'st-app', 'ST', [
        'ajax'         => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('st_nonce'),
        'homeUrl'      => home_url('/'),
        'phone'        => $s['phone'] ?? '906-370-4094',
        'sqAppId'      => $s['square_app_id'] ?? '',
        'sqToken'      => $s['square_token'] ?? '',
        'sqLocationId' => $s['square_location'] ?? '',
        'flatMiles'    => $s['flat_rate_miles'] ?? '5',
        'flatPrice'    => $s['flat_rate_price'] ?? '10.00',
        'perMile'      => $s['per_mile'] ?? '2.50',
        'baseRate'     => $s['base_rate'] ?? '3.00',
    ]);
}
