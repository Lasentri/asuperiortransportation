<?php
/**
 * Plugin Name: Superior Site Setup
 * Description: Companion setup plugin for the Superior Spartan theme. Creates pages/menus, provides settings for booking + Square, and can auto-install recommended plugins from WordPress.org.
 * Version: 1.0.0
 * Author: Rick (build pack)
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) { exit; }

define('SUPERIOR_SETUP_VER', '1.0.0');

// Activation: create key pages and a primary menu.
function superior_setup_activate() {
  // Create pages (only if they don't exist)
  $pages = array(
    'home' => array('title' => 'Home', 'slug' => 'home', 'content' => ''),
    'book' => array('title' => 'Book a Ride', 'slug' => 'book-a-ride', 'content' => '[superior_booking]'),
    'rates' => array('title' => 'Rates', 'slug' => 'rates', 'content' => 'Add your rates here. Keep it simple.'),
    'pay' => array('title' => 'Pay', 'slug' => 'pay', 'content' => '[superior_square_pay]'),
    'contact' => array('title' => 'Contact', 'slug' => 'contact', 'content' => '[superior_contact]'),
  );

  $created = array();
  foreach ($pages as $key => $p) {
    $existing = get_page_by_path($p['slug']);
    if ($existing) {
      $created[$key] = $existing->ID;
      continue;
    }
    $id = wp_insert_post(array(
      'post_type' => 'page',
      'post_title' => $p['title'],
      'post_name' => $p['slug'],
      'post_status' => 'publish',
      'post_content' => $p['content'],
    ));
    if (!is_wp_error($id) && $id) {
      $created[$key] = $id;
    }
  }

  // Set front page to Home (if present)
  if (!empty($created['home'])) {
    update_option('show_on_front', 'page');
    update_option('page_on_front', (int)$created['home']);
  }

  // Create Primary menu with common items if no menu exists.
  $menu_name = 'Primary';
  $menu = wp_get_nav_menu_object($menu_name);
  if (!$menu) {
    $menu_id = wp_create_nav_menu($menu_name);
    $menu = wp_get_nav_menu_object($menu_id);

    $map = array('book','rates','pay','contact');
    foreach ($map as $k) {
      if (!empty($created[$k])) {
        wp_update_nav_menu_item($menu->term_id, 0, array(
          'menu-item-title' => get_the_title($created[$k]),
          'menu-item-object' => 'page',
          'menu-item-object-id' => $created[$k],
          'menu-item-type' => 'post_type',
          'menu-item-status' => 'publish'
        ));
      }
    }
    // Assign to theme location if theme supports it.
    $locations = get_theme_mod('nav_menu_locations');
    if (!is_array($locations)) { $locations = array(); }
    $locations['primary'] = $menu->term_id;
    set_theme_mod('nav_menu_locations', $locations);
  }

  // Default options
  add_option('superior_phone', '');
  add_option('superior_square_pay_url', '');
  add_option('superior_booking_mode', 'shortcode'); // shortcode | url
  add_option('superior_booking_shortcode', '');
  add_option('superior_booking_url', home_url('/book-a-ride/'));
  add_option('superior_home_headline', 'Reliable rides. Clean service. No nonsense.');
  add_option('superior_home_subheadline', 'Book a taxi fast, pay easily with Square, and get where you need to go.');
}
register_activation_hook(__FILE__, 'superior_setup_activate');

// Admin menu settings page.
add_action('admin_menu', function(){
  add_menu_page(
    'Superior Setup',
    'Superior Setup',
    'manage_options',
    'superior-setup',
    'superior_setup_render_settings',
    'dashicons-admin-generic',
    58
  );
});

function superior_setup_render_settings() {
  if (!current_user_can('manage_options')) { return; }

  if (isset($_POST['superior_setup_save']) && check_admin_referer('superior_setup_save_action', 'superior_setup_nonce')) {
    update_option('superior_phone', sanitize_text_field($_POST['superior_phone'] ?? ''));
    update_option('superior_square_pay_url', esc_url_raw($_POST['superior_square_pay_url'] ?? ''));
    update_option('superior_booking_mode', in_array(($_POST['superior_booking_mode'] ?? ''), array('shortcode','url'), true) ? $_POST['superior_booking_mode'] : 'shortcode');
    update_option('superior_booking_shortcode', wp_kses_post($_POST['superior_booking_shortcode'] ?? ''));
    update_option('superior_booking_url', esc_url_raw($_POST['superior_booking_url'] ?? ''));
    update_option('superior_home_headline', sanitize_text_field($_POST['superior_home_headline'] ?? ''));
    update_option('superior_home_subheadline', sanitize_text_field($_POST['superior_home_subheadline'] ?? ''));
    echo '<div class="updated notice"><p><strong>Saved.</strong></p></div>';
  }

  $phone = esc_attr(get_option('superior_phone',''));
  $pay = esc_attr(get_option('superior_square_pay_url',''));
  $mode = esc_attr(get_option('superior_booking_mode','shortcode'));
  $sc = esc_textarea(get_option('superior_booking_shortcode',''));
  $url = esc_attr(get_option('superior_booking_url', home_url('/book-a-ride/')));
  $h1 = esc_attr(get_option('superior_home_headline',''));
  $h2 = esc_attr(get_option('superior_home_subheadline',''));

  ?>
  <div class="wrap">
    <h1>Superior Setup</h1>
    <p>Set your booking and Square payment options. Keep it simple.</p>

    <form method="post">
      <?php wp_nonce_field('superior_setup_save_action', 'superior_setup_nonce'); ?>

      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><label for="superior_phone">Business Phone</label></th>
          <td><input type="text" class="regular-text" id="superior_phone" name="superior_phone" value="<?php echo $phone; ?>" placeholder="+1 (xxx) xxx-xxxx"></td>
        </tr>

        <tr>
          <th scope="row"><label for="superior_square_pay_url">Square Payment Link</label></th>
          <td>
            <input type="url" class="regular-text" id="superior_square_pay_url" name="superior_square_pay_url" value="<?php echo $pay; ?>" placeholder="https://square.link/u/...">
            <p class="description">Paste your Square Checkout/Payment Link here. The theme will show a “Pay with Square” button in the top bar.</p>
          </td>
        </tr>

        <tr>
          <th scope="row">Booking Mode</th>
          <td>
            <fieldset>
              <label><input type="radio" name="superior_booking_mode" value="shortcode" <?php checked($mode,'shortcode'); ?>> Use a booking shortcode (recommended if your chauffeur/taxi plugin provides one)</label><br>
              <label><input type="radio" name="superior_booking_mode" value="url" <?php checked($mode,'url'); ?>> Use a booking URL (if you use an external booking page)</label>
            </fieldset>
          </td>
        </tr>

        <tr>
          <th scope="row"><label for="superior_booking_shortcode">Booking Shortcode</label></th>
          <td>
            <textarea class="large-text code" rows="3" id="superior_booking_shortcode" name="superior_booking_shortcode" placeholder="[your_booking_shortcode]"><?php echo $sc; ?></textarea>
            <p class="description">Example: <code>[chauffeur_booking]</code> or whatever your booking plugin uses.</p>
          </td>
        </tr>

        <tr>
          <th scope="row"><label for="superior_booking_url">Booking URL</label></th>
          <td>
            <input type="url" class="regular-text" id="superior_booking_url" name="superior_booking_url" value="<?php echo $url; ?>">
          </td>
        </tr>

        <tr>
          <th scope="row"><label for="superior_home_headline">Homepage Headline</label></th>
          <td><input type="text" class="regular-text" id="superior_home_headline" name="superior_home_headline" value="<?php echo $h1; ?>"></td>
        </tr>

        <tr>
          <th scope="row"><label for="superior_home_subheadline">Homepage Subheadline</label></th>
          <td><input type="text" class="regular-text" id="superior_home_subheadline" name="superior_home_subheadline" value="<?php echo $h2; ?>"></td>
        </tr>
      </table>

      <p class="submit"><button type="submit" class="button button-primary" name="superior_setup_save" value="1">Save Settings</button></p>
    </form>

    <hr>
    <h2>Recommended Plugins (auto-install)</h2>
    <p>These are installed from WordPress.org. Your “chauffeur / bakery / yeo” plugins can be added manually if they are premium or custom.</p>
    <form method="post">
      <?php wp_nonce_field('superior_setup_plugins_action', 'superior_setup_plugins_nonce'); ?>
      <p><button type="submit" class="button" name="superior_setup_install_plugins" value="1">Install/Activate Recommended Plugins</button></p>
    </form>
  </div>
  <?php
}

// Shortcodes
add_shortcode('superior_square_pay', function(){
  $pay = get_option('superior_square_pay_url','');
  if (empty($pay)) {
    return '<p><strong>Square link not set yet.</strong> Go to <em>Superior Setup</em> in the admin and paste your Square payment link.</p>';
  }
  return '<p><a class="btn-primary" href="'.esc_url($pay).'">Pay with Square</a></p>';
});

add_shortcode('superior_booking', function(){
  $mode = get_option('superior_booking_mode','shortcode');
  if ($mode === 'url') {
    $url = get_option('superior_booking_url', home_url('/book-a-ride/'));
    return '<p><a class="btn-primary" href="'.esc_url($url).'">Book a Ride</a></p>';
  }
  $sc = get_option('superior_booking_shortcode','');
  if (empty($sc)) {
    return '<p><strong>Booking shortcode not set yet.</strong> Paste it in <em>Superior Setup</em>. For now, use the Contact page.</p>';
  }
  return do_shortcode($sc);
});

add_shortcode('superior_contact', function(){
  $phone = get_option('superior_phone','');
  $phone_href = $phone ? 'tel:' . preg_replace('/[^0-9\+]/', '', $phone) : '';
  $out = '<p>Use this page to add your contact form (WPForms recommended).</p>';
  if ($phone && $phone_href) {
    $out .= '<p><a class="btn-primary" href="'.esc_url($phone_href).'">Call '.$phone.'</a></p>';
  }
  $out .= '<p><em>Tip:</em> If you install WPForms Lite, create a form, then paste its shortcode here.</p>';
  return $out;
});

// Handle plugin install request
add_action('admin_init', function(){
  if (!is_admin()) return;
  if (!current_user_can('install_plugins')) return;

  if (isset($_POST['superior_setup_install_plugins']) && check_admin_referer('superior_setup_plugins_action', 'superior_setup_plugins_nonce')) {
    superior_setup_install_and_activate_recommended();
  }
});

function superior_setup_install_and_activate_recommended() {
  require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
  require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
  require_once ABSPATH . 'wp-admin/includes/plugin.php';
  require_once ABSPATH . 'wp-admin/includes/file.php';

  $recommended = array(
    'woocommerce',
    'woocommerce-square',
    'wpforms-lite',
    'wordfence',
    'updraftplus',
  );

  $results = array();
  foreach ($recommended as $slug) {
    $installed = superior_setup_is_plugin_installed($slug);
    if (!$installed) {
      $api = plugins_api('plugin_information', array('slug' => $slug, 'fields' => array('sections' => false)));
      if (is_wp_error($api)) { $results[] = "Failed to fetch $slug"; continue; }
      $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
      $install = $upgrader->install($api->download_link);
      if (is_wp_error($install) || !$install) { $results[] = "Install failed: $slug"; continue; }
    }
    $plugin_file = superior_setup_get_plugin_file_from_slug($slug);
    if ($plugin_file && file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
      $activate = activate_plugin($plugin_file);
      if (is_wp_error($activate)) {
        $results[] = "Installed but not activated: $slug";
      } else {
        $results[] = "Installed/activated: $slug";
      }
    } else {
      $results[] = "Installed but plugin file not found: $slug";
    }
  }

  add_action('admin_notices', function() use ($results){
    echo '<div class="notice notice-success"><p><strong>Plugin setup results:</strong><br>'.implode('<br>', array_map('esc_html', $results)).'</p></div>';
  });
}

function superior_setup_is_plugin_installed($slug) {
  $plugins = get_plugins();
  foreach ($plugins as $file => $data) {
    if (strpos($file, $slug . '/') === 0) return true;
  }
  return false;
}
function superior_setup_get_plugin_file_from_slug($slug) {
  $plugins = get_plugins();
  foreach ($plugins as $file => $data) {
    if (strpos($file, $slug . '/') === 0) return $file;
  }
  return null;
}
