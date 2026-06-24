<?php
if ( ! defined( 'ABSPATH' ) ) exit;

register_activation_hook( ST_DIR . 'superior-transport.php', 'st_create_pages' );
function st_create_pages() {
    $pages = [
        'gas-tracker'      => ['title' => '⛽ Gas Tracker',      'template' => 'gas-tracker'],
        'suggested-places' => ['title' => '📍 Suggested Places', 'template' => 'suggested-places'],
        'driver-portal'    => ['title' => 'Driver Portal',       'template' => 'driver-portal'],
    ];
    foreach ( $pages as $slug => $data ) {
        if ( ! get_page_by_path($slug) ) {
            wp_insert_post(['post_title'=>$data['title'],'post_name'=>$slug,'post_status'=>'publish','post_type'=>'page','meta_input'=>['_st_template'=>$data['template']]]);
        }
    }
}

add_filter( 'template_include', 'st_template_loader', 99 );
function st_template_loader( $template ) {
    if ( is_front_page() || is_home() ) {
        $t = ST_DIR . 'templates/homepage.php';
        if ( file_exists($t) ) return $t;
    }
    $slug_map = ['gas-tracker'=>'gas-tracker-page.php','suggested-places'=>'suggested-places.php','driver-portal'=>'driver-portal.php'];
    if ( is_page() ) {
        global $post;
        $slug = $post->post_name;
        if ( isset($slug_map[$slug]) ) { $t = ST_DIR.'templates/'.$slug_map[$slug]; if(file_exists($t)) return $t; }
        $meta = get_post_meta($post->ID,'_st_template',true);
        if ($meta) { $t = ST_DIR.'templates/'.$meta.'.php'; if(file_exists($t)) return $t; }
    }
    return $template;
}
