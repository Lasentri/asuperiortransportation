<?php
/**
 * Plugin Name: Superior Transportation Gas Price Tracker
 * Plugin URI: https://asuperiortransportation.com
 * Description: UP Michigan Gas Price Tracker 2013-2026, color coded by administration, matching site colors. Visit /up-gas-price-tracker/ after activating.
 * Version: 2.0.0
 * Author: A Superior Transportation
 */
if (!defined('ABSPATH')) exit;

register_activation_hook(__FILE__, 'sgt_create_page');
function sgt_create_page() {
    $slug = 'up-gas-price-tracker';
    if (get_page_by_path($slug)) return;
    wp_insert_post([
        'post_title'   => 'UP Michigan Gas Price Tracker 2013-2026',
        'post_name'    => $slug,
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'meta_input'   => ['_sgt_page' => '1'],
    ]);
}
register_deactivation_hook(__FILE__, 'sgt_remove_page');
function sgt_remove_page() {
    $page = get_page_by_path('up-gas-price-tracker');
    if ($page) wp_delete_post($page->ID, true);
}
add_filter('template_include', 'sgt_template_override');
function sgt_template_override($template) {
    if (is_page('up-gas-price-tracker')) {
        $custom = plugin_dir_path(__FILE__) . 'template-gas-tracker.php';
        if (file_exists($custom)) return $custom;
    }
    return $template;
}
