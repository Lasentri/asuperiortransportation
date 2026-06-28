<?php
/**
 * Plugin Name: OneClick Taxi Ordering App
 * Plugin URI:  https://yoursite.com/oneclick-taxi
 * Description: Complete taxi ordering platform. Install, follow the setup wizard, and have your own ordering app in 20 minutes.
 * Version:     1.0.0
 * Author:      OneClick Taxi
 * License:     GPL2
 * Text Domain: oneclick-taxi
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'OCT_VERSION',  '1.0.0' );
define( 'OCT_DIR',      plugin_dir_path( __FILE__ ) );
define( 'OCT_URL',      plugin_dir_url( __FILE__ ) );

add_filter( 'wp_mail_from',      function() { return 'noreply@' . parse_url( home_url(), PHP_URL_HOST ); } );
add_filter( 'wp_mail_from_name', function() { return get_option('oct_business_name', 'Taxi Service'); } );

require_once OCT_DIR . 'includes/settings.php';
require_once OCT_DIR . 'includes/wizard.php';
require_once OCT_DIR . 'includes/ajax.php';
require_once OCT_DIR . 'includes/gcal.php';
require_once OCT_DIR . 'includes/license-client.php';

function oct_get_colors() {
    return wp_parse_args( get_option('oct_colors', []), [
        'primary'    => '#c8a84b',
        'secondary'  => '#1a3a1a',
        'accent'     => '#f5c518',
        'background' => '#0f2a0f',
    ]);
}

add_action( 'wp_enqueue_scripts', 'oct_enqueue_frontend' );
function oct_enqueue_frontend() {
    $colors = oct_get_colors();
    $s      = oct_get_settings();
    wp_enqueue_style(  'oct-style', OCT_URL . 'assets/css/style.css', [], OCT_VERSION );
    wp_enqueue_script( 'oct-app',   OCT_URL . 'assets/js/app.js', [], OCT_VERSION, true );
    $inline = ":root {
        --oct-primary:    {$colors['primary']};
        --oct-secondary:  {$colors['secondary']};
        --oct-accent:     {$colors['accent']};
        --oct-background: {$colors['background']};
    }";
    wp_add_inline_style( 'oct-style', $inline );
    $key = esc_attr( $s['google_maps_key'] ?? '' );
    if ( $key ) {
        wp_enqueue_script( 'google-maps',
            "https://maps.googleapis.com/maps/api/js?key={$key}&libraries=places&callback=octInitMap&loading=async",
            [], null, true
        );
    }
    wp_localize_script( 'oct-app', 'OCT', [
        'ajax'         => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('oct_nonce'),
        'phone'        => $s['phone']           ?? '',
        'sqAppId'      => $s['square_app_id']   ?? '',
        'sqLocationId' => $s['square_location'] ?? '',
        'perMile'      => $s['per_mile']        ?? '2.50',
        'baseRate'     => $s['base_rate']       ?? '3.00',
        'flatMiles'    => $s['flat_rate_miles'] ?? '5',
        'flatPrice'    => $s['flat_rate_price'] ?? '10.00',
        'colors'       => $colors,
    ]);
}

add_action( 'admin_enqueue_scripts', 'oct_enqueue_admin' );
function oct_enqueue_admin( $hook ) {
    if ( strpos($hook, 'oneclick-taxi') === false ) return;
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_style(  'oct-admin', OCT_URL . 'assets/css/admin.css', [], OCT_VERSION );
    wp_enqueue_script( 'oct-admin', OCT_URL . 'assets/js/admin.js', ['jquery','wp-color-picker'], OCT_VERSION, true );
}

register_activation_hook( __FILE__, 'oct_activate' );
function oct_activate() {
    if ( ! get_page_by_path('book-a-ride') ) {
        wp_insert_post([
            'post_title'   => 'Book a Ride',
            'post_name'    => 'book-a-ride',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '[oct_booking_form]',
        ]);
    }
    if ( ! get_option('oct_setup_step') ) update_option('oct_setup_step', 1);
}

add_shortcode( 'oct_booking_form', function() {
    ob_start();
    include OCT_DIR . 'templates/booking-form.php';
    return ob_get_clean();
});
