<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function oct_get_settings() {
    return wp_parse_args( get_option('oct_settings', []), [
        'business_name'   => '',
        'phone'           => '',
        'email'           => '',
        'address'         => '',
        'hours_open'      => '06:00',
        'hours_close'     => '23:59',
        'per_mile'        => '2.50',
        'base_rate'       => '3.00',
        'flat_rate_miles' => '5',
        'flat_rate_price' => '10.00',
        'google_maps_key' => '',
        'square_app_id'   => '',
        'square_token'    => '',
        'square_location' => '',
        'pushover_email'  => '',
        'gcal_id'         => '',
    ]);
}

add_action( 'admin_menu', 'oct_admin_menu' );
function oct_admin_menu() {
    add_menu_page(
        'OneClick Taxi',
        'OneClick Taxi',
        'manage_options',
        'oneclick-taxi',
        'oct_wizard_page',
        'dashicons-car',
        30
    );
    add_submenu_page( 'oneclick-taxi', 'Setup Wizard', '🚀 Setup Wizard', 'manage_options', 'oneclick-taxi', 'oct_wizard_page' );
    add_submenu_page( 'oneclick-taxi', 'General Settings', 'General Settings', 'manage_options', 'oct-settings', 'oct_settings_page' );
    add_submenu_page( 'oneclick-taxi', 'Brand & Colors', '🎨 Brand & Colors', 'manage_options', 'oct-colors', 'oct_colors_page' );
    add_submenu_page( 'oneclick-taxi', 'Flat Rates', '🗺️ Flat Rates', 'manage_options', 'oct-flatrates', 'oct_flatrates_page' );
    add_submenu_page( 'oneclick-taxi', 'Calendar Auth', '📅 Calendar Auth', 'manage_options', 'oct-calendar', 'oct_calendar_page' );
}

function oct_settings_page() {
    if ( isset($_POST['oct_save']) ) {
        check_admin_referer('oct_save');
        $fields = ['business_name','phone','email','address','hours_open','hours_close',
                   'per_mile','base_rate','flat_rate_miles','flat_rate_price',
                   'google_maps_key','square_app_id','square_token','square_location',
                   'pushover_email','gcal_id'];
        $data = [];
        foreach($fields as $f) $data[$f] = sanitize_text_field($_POST[$f] ?? '');
        update_option('oct_settings', $data);
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }
    $s = oct_get_settings();
    ?>
    <div class="wrap oct-admin-wrap">
        <h1>⚙️ General Settings</h1>
        <form method="post"><?php wp_nonce_field('oct_save'); ?>
        <table class="form-table">
            <tr><th>Business Name</th><td><input name="business_name" value="<?php echo esc_attr($s['business_name']);?>" class="regular-text"></td></tr>
            <tr><th>Phone</th><td><input name="phone" value="<?php echo esc_attr($s['phone']);?>" class="regular-text"></td></tr>
            <tr><th>Email</th><td><input name="email" value="<?php echo esc_attr($s['email']);?>" class="regular-text"></td></tr>
            <tr><th>Address</th><td><input name="address" value="<?php echo esc_attr($s['address']);?>" class="regular-text"></td></tr>
            <tr><th>Hours Open</th><td><input type="time" name="hours_open" value="<?php echo esc_attr($s['hours_open']);?>"></td></tr>
            <tr><th>Hours Close</th><td><input type="time" name="hours_close" value="<?php echo esc_attr($s['hours_close']);?>"></td></tr>
            <tr><th>Per Mile Rate ($)</th><td><input name="per_mile" value="<?php echo esc_attr($s['per_mile']);?>" style="width:100px"></td></tr>
            <tr><th>Base Rate ($)</th><td><input name="base_rate" value="<?php echo esc_attr($s['base_rate']);?>" style="width:100px"></td></tr>
            <tr><th>Flat Rate (under X miles)</th><td><input name="flat_rate_miles" value="<?php echo esc_attr($s['flat_rate_miles']);?>" style="width:60px"> miles at $<input name="flat_rate_price" value="<?php echo esc_attr($s['flat_rate_price']);?>" style="width:80px"></td></tr>
            <tr><th colspan="2" style="background:#f0f0f0"><strong>API Keys</strong></th></tr>
            <tr><th>Google Maps API Key</th><td><input name="google_maps_key" value="<?php echo esc_attr($s['google_maps_key']);?>" class="regular-text"></td></tr>
            <tr><th>Square App ID</th><td><input name="square_app_id" value="<?php echo esc_attr($s['square_app_id']);?>" class="regular-text"></td></tr>
            <tr><th>Square Access Token</th><td><input name="square_token" value="<?php echo esc_attr($s['square_token']);?>" class="regular-text"></td></tr>
            <tr><th>Square Location ID</th><td><input name="square_location" value="<?php echo esc_attr($s['square_location']);?>" class="regular-text"></td></tr>
            <tr><th>Pushover Email</th><td><input name="pushover_email" value="<?php echo esc_attr($s['pushover_email']);?>" class="regular-text" placeholder="yourkey@pomail.net"></td></tr>
            <tr><th>Google Calendar ID</th><td><input name="gcal_id" value="<?php echo esc_attr($s['gcal_id']);?>" class="regular-text" placeholder="you@gmail.com"></td></tr>
        </table>
        <p><input type="submit" name="oct_save" class="button button-primary" value="Save Settings"></p>
        </form>
    </div>
    <?php
}

function oct_colors_page() {
    if ( isset($_POST['oct_save_colors']) ) {
        check_admin_referer('oct_colors');
        update_option('oct_colors', [
            'primary'    => sanitize_hex_color($_POST['primary']    ?? '#c8a84b'),
            'secondary'  => sanitize_hex_color($_POST['secondary']  ?? '#1a3a1a'),
            'accent'     => sanitize_hex_color($_POST['accent']     ?? '#f5c518'),
            'background' => sanitize_hex_color($_POST['background'] ?? '#0f2a0f'),
        ]);
        echo '<div class="notice notice-success"><p>Colors saved. Refresh the front page to see changes.</p></div>';
    }
    $c = oct_get_colors();
    ?>
    <div class="wrap oct-admin-wrap">
        <h1>🎨 Brand Colors</h1>
        <p>Choose up to 4 colors to match your brand. Changes apply instantly to the booking form.</p>
        <form method="post"><?php wp_nonce_field('oct_colors'); ?>
        <table class="form-table">
            <tr>
                <th>Primary Color <small>(buttons, highlights)</small></th>
                <td><input type="text" name="primary" value="<?php echo esc_attr($c['primary']);?>" class="oct-color-picker"></td>
            </tr>
            <tr>
                <th>Secondary Color <small>(header, nav)</small></th>
                <td><input type="text" name="secondary" value="<?php echo esc_attr($c['secondary']);?>" class="oct-color-picker"></td>
            </tr>
            <tr>
                <th>Accent Color <small>(prices, totals)</small></th>
                <td><input type="text" name="accent" value="<?php echo esc_attr($c['accent']);?>" class="oct-color-picker"></td>
            </tr>
            <tr>
                <th>Background Color <small>(page background)</small></th>
                <td><input type="text" name="background" value="<?php echo esc_attr($c['background']);?>" class="oct-color-picker"></td>
            </tr>
        </table>
        <div style="margin:20px 0;padding:20px;border:1px solid #ddd;border-radius:6px;background:#fff;">
            <strong>Live Preview:</strong>
            <div id="oct-color-preview" style="margin-top:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <div style="width:80px;height:40px;border-radius:4px;background:<?php echo esc_attr($c['primary']);?>;display:flex;align-items:center;justify-content:center;color:#000;font-size:11px">Primary</div>
                <div style="width:80px;height:40px;border-radius:4px;background:<?php echo esc_attr($c['secondary']);?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px">Secondary</div>
                <div style="width:80px;height:40px;border-radius:4px;background:<?php echo esc_attr($c['accent']);?>;display:flex;align-items:center;justify-content:center;color:#000;font-size:11px">Accent</div>
                <div style="width:80px;height:40px;border-radius:4px;background:<?php echo esc_attr($c['background']);?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px">Background</div>
            </div>
        </div>
        <p><input type="submit" name="oct_save_colors" class="button button-primary" value="💾 Save Colors"></p>
        </form>
    </div>
    <?php
}

function oct_flatrates_page() {
    $rates = get_option('oct_flat_rates', ['zone_1' => []]);
    if ( isset($_POST['oct_save_rates']) ) {
        check_admin_referer('oct_rates');
        $new = [];
        $names   = $_POST['rate_name']    ?? [];
        $addrs   = $_POST['rate_address'] ?? [];
        $prices  = $_POST['rate_price']   ?? [];
        $actives = $_POST['rate_active']  ?? [];
        $zones   = $_POST['rate_zone']    ?? [];
        foreach($names as $i => $name){
            if(!trim($name)) continue;
            $zone = sanitize_key($zones[$i] ?? 'zone_1');
            $new[$zone][] = [
                'name'    => sanitize_text_field($name),
                'address' => sanitize_text_field($addrs[$i] ?? ''),
                'price'   => floatval($prices[$i] ?? 0),
                'active'  => isset($actives[$i]) ? 1 : 0,
            ];
        }
        update_option('oct_flat_rates', $new);
        $rates = $new;
        echo '<div class="notice notice-success"><p>Flat rates saved.</p></div>';
    }
    ?>
    <div class="wrap oct-admin-wrap">
        <h1>🗺️ Flat Rate Destinations</h1>
        <div class="oct-notice-info">
            <strong>Pricing Policy:</strong> Rates shown are for 1–2 passengers. 3+ passengers: flat rate + 40% surcharge (auto-calculated at booking).
        </div>
        <form method="post"><?php wp_nonce_field('oct_rates'); ?>
        <table class="wp-list-table widefat fixed" style="max-width:900px">
            <thead>
                <tr style="background:#1a3a1a;color:#fff">
                    <th style="color:#fff">Destination Name</th>
                    <th style="color:#fff">Address</th>
                    <th style="color:#fff">Zone / Direction</th>
                    <th style="color:#fff">Base Rate (1-2 pax)</th>
                    <th style="color:#fff">Active</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $row = 0;
            foreach($rates as $zone => $destinations) {
                foreach($destinations as $d) {
                    echo '<tr style="background:'.($row%2?'#f9f9f9':'#fff').'">';
                    echo '<td><input type="text" name="rate_name[]" value="'.esc_attr($d['name']).'" style="width:98%"></td>';
                    echo '<td><input type="text" name="rate_address[]" value="'.esc_attr($d['address']).'" style="width:98%"></td>';
                    echo '<td><input type="text" name="rate_zone[]" value="'.esc_attr($zone).'" style="width:98%" placeholder="zone_1"></td>';
                    echo '<td>$<input type="number" step="0.01" name="rate_price[]" value="'.esc_attr($d['price']).'" style="width:80px"></td>';
                    echo '<td><input type="checkbox" name="rate_active['.$row.']" '.checked($d['active'],1,false).'></td>';
                    echo '</tr>';
                    $row++;
                }
            }
            ?>
            <tr style="background:#f0fff0;border-top:2px dashed #2e7d32">
                <td><input type="text" name="rate_name[]" placeholder="New destination" style="width:98%"></td>
                <td><input type="text" name="rate_address[]" placeholder="Full address" style="width:98%"></td>
                <td><input type="text" name="rate_zone[]" placeholder="zone_1" style="width:98%"></td>
                <td>$<input type="number" step="0.01" name="rate_price[]" style="width:80px"></td>
                <td><input type="checkbox" name="rate_active[<?php echo $row;?>]" checked></td>
            </tr>
            </tbody>
        </table>
        <p><input type="submit" name="oct_save_rates" class="button button-primary" value="💾 Save Flat Rates"></p>
        </form>
    </div>
    <?php
}

function oct_calendar_page() {
    include OCT_DIR . 'includes/gcal.php';
    oct_gcal_admin_page();
}

// License page menu item
add_action('admin_menu','oct_add_license_menu');
function oct_add_license_menu() {
    add_submenu_page('oneclick-taxi','License','🔑 License','manage_options','oct-license','oct_license_admin_page');
}
