<?php
/**
 * Plugin Name: A Superior Transportation - Full Site v3.0
 * Plugin URI:  https://asuperiortransportation.com
 * Description: Complete website - Homepage booking, Suggested Places, Gas Tracker, Calendar, Square Payments.
 * Version:     3.3.1
 * Author:      A Superior Transportation
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* Force WordPress mail to send from asuperiortransportation.com domain */
add_filter( 'wp_mail_from',      function() { return 'noreply@asuperiortransportation.com'; } );
add_filter( 'wp_mail_from_name', function() { return 'A Superior Transportation'; } );

define( 'ST_VERSION', '3.3.1' );
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

/**
 * CSP covering Square Web Payments SDK + Google Calendar embed + Google Maps
 */
add_filter( 'wp_headers', 'st_csp_headers', 99 );
function st_csp_headers( $headers ) {
    unset( $headers['Content-Security-Policy'] );
    unset( $headers['content-security-policy'] );

    $csp = implode( '; ', [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: https://web.squarecdn.com https://squareup.com https://maps.googleapis.com https://maps.gstatic.com https://cdn.jsdelivr.net https://apis.google.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://web.squarecdn.com",
        "font-src 'self' data: https://fonts.gstatic.com https://web.squarecdn.com",
        "frame-src 'self' https://web.squarecdn.com https://squareup.com https://pci-connect.squareup.com https://calendar.google.com https://www.google.com",
        "img-src 'self' data: blob: https://maps.googleapis.com https://maps.gstatic.com https://www.gstatic.com https://calendar.google.com",
        "connect-src 'self' https://web.squarecdn.com https://squareup.com https://pci-connect.squareup.com https://connect.squareup.com https://maps.googleapis.com https://csp-report.browser-intake-datadoghq.com https://o160250.ingest.sentry.io https://apis.google.com",
        "worker-src 'self' blob:",
        "child-src 'self' blob: https://web.squarecdn.com https://squareup.com https://calendar.google.com",
    ]);

    $headers['Content-Security-Policy'] = $csp;
    return $headers;
}
