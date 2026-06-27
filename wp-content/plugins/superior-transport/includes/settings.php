<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function st_get_settings() {
    return wp_parse_args( get_option('st_settings', []), [
        'phone'           => '906-370-4094',
        'email'           => 'stalcollc@gmail.com',
        'address'         => '1002 2nd St, Hancock, MI',
        'hours_open'      => '00:00',
        'hours_close'     => '23:59',
        'base_rate'       => '3.00',
        'per_mile'        => '2.50',
        'flat_rate_miles' => '5',
        'flat_rate_price' => '10.00',
        'google_maps_key' => 'AIzaSyCGSeyieg7WChmATmrgBf3e5yd_QRX8_p0',
        'square_app_id'   => '',
        'square_token'    => 'EAAAlwDmSll4rclj6L4zbDEmrRTGujaQ6VcDSUcJv7r8KOfpMrGdD3tqA-U9XcJf',
        'square_location' => '',
        'facebook_url'    => 'https://www.facebook.com/groups/2735985776774953',
        'tiktok_url'      => 'https://www.tiktok.com/@gov.26.ceric',
        'require_name'    => '1', 'require_phone'   => '1', 'require_email'  => '0',
        'require_date'    => '1', 'require_time'    => '1', 'require_pickup' => '1',
        'require_dropoff' => '1', 'require_passengers' => '0', 'require_notes' => '0',
        'show_email'      => '1', 'show_passengers' => '1', 'show_notes'     => '1',
    ]);
}

/* -------------------------------------------------------
   FLAT RATES
------------------------------------------------------- */
function st_get_flat_rates() {
    return get_option('st_flat_rates', [
        'north_bound' => [
            ['name'=>'Lake Linden',                          'address'=>'Lake Linden, MI',              'price'=>25.00,  'active'=>1],
            ['name'=>'Laurium',                              'address'=>'Laurium, MI',                  'price'=>30.00,  'active'=>1],
            ['name'=>'Calumet (Downtown)',                   'address'=>'Calumet, MI 49913',            'price'=>30.00,  'active'=>1],
            ['name'=>'Calumet Visitor Center / Agassiz Park','address'=>'Calumet, MI 49913',            'price'=>30.00,  'active'=>1],
            ['name'=>'Swedetown Trails / Recreation Area',   'address'=>'Swedetown Rd, Calumet, MI',    'price'=>31.00,  'active'=>1],
            ['name'=>'Mohawk',                               'address'=>'Mohawk, MI',                   'price'=>55.00,  'active'=>1],
            ['name'=>'Ahmeek',                               'address'=>'Ahmeek, MI',                   'price'=>65.00,  'active'=>1],
            ['name'=>'Allouez',                              'address'=>'Allouez, MI',                  'price'=>75.00,  'active'=>1],
            ['name'=>'Centennial Heights',                   'address'=>'Centennial Heights, MI',       'price'=>35.00,  'active'=>1],
            ['name'=>'Gay / Eagle River Junction',           'address'=>'Gay, MI',                      'price'=>119.00, 'active'=>1],
            ['name'=>'Phoenix',                              'address'=>'Phoenix, MI',                  'price'=>127.50, 'active'=>1],
            ['name'=>'Keweenaw County Courthouse (Eagle River)','address'=>'Eagle River, MI',           'price'=>131.75, 'active'=>1],
            ['name'=>'Delaware Copper Mine',                 'address'=>'Delaware, MI',                 'price'=>140.25, 'active'=>1],
            ['name'=>'Copper Harbor (Downtown)',             'address'=>'Copper Harbor, MI',            'price'=>199.75, 'active'=>1],
            ['name'=>'Fort Wilkins State Park',              'address'=>'Copper Harbor, MI 49918',      'price'=>201.88, 'active'=>1],
            ['name'=>'Brockway Mountain Drive',              'address'=>'Brockway Mountain Dr, Copper Harbor, MI','price'=>195.50,'active'=>1],
        ],
        'west_bound' => [
            ['name'=>'South Range',                          'address'=>'South Range, MI 49963',                                       'price'=>25.83,  'active'=>1],
            ['name'=>'Atlantic Mine',                        'address'=>'Atlantic Mine, MI 49905',                                     'price'=>14.76,  'active'=>1],
            ['name'=>'Painesdale',                           'address'=>'Painesdale, MI 49955',                                        'price'=>29.52,  'active'=>1],
            ['name'=>'Adams Township / Bootjack',            'address'=>'Bootjack, MI 49911',                                          'price'=>44.28,  'active'=>1],
            ['name'=>'Trimountain',                          'address'=>'Trimountain, MI 49960',                                       'price'=>44.28,  'active'=>1],
            ['name'=>'Twin Lakes (Village)',                 'address'=>'Twin Lakes, MI 49962',                                        'price'=>95.94,  'active'=>1],
            ['name'=>'Twin Lakes State Park',                'address'=>'32650 N Hwy M-26, Toivola, MI 49965',                        'price'=>95.94,  'active'=>1],
            ['name'=>'Toivola',                              'address'=>'Toivola, MI 49965',                                           'price'=>55.35,  'active'=>1],
            ['name'=>"Krupp's Resort",                       'address'=>'32170 Emily Lake Rd, Toivola, MI 49965',                     'price'=>92.25,  'active'=>1],
            ['name'=>'Greenland',                            'address'=>'Greenland, MI 49929',                                         'price'=>129.15, 'active'=>1],
            ['name'=>'Mass City',                            'address'=>'Mass City, MI 49948',                                         'price'=>147.60, 'active'=>1],
            ['name'=>'Bergland',                             'address'=>'Bergland, MI 49910',                                          'price'=>306.27, 'active'=>1],
            ['name'=>'Lake Gogebic State Park',              'address'=>'Lake Gogebic State Park, Marenisco, MI',                     'price'=>317.34, 'active'=>1],
            ['name'=>'Ewen',                                 'address'=>'Ewen, MI 49925',                                             'price'=>239.85, 'active'=>1],
            ['name'=>'Trout Creek',                          'address'=>'Trout Creek, MI 49967',                                      'price'=>221.40, 'active'=>1],
            ['name'=>'Rockland',                             'address'=>'Rockland, MI 49960',                                         'price'=>177.12, 'active'=>1],
            ['name'=>'Victoria Dam',                         'address'=>'Victoria Dam Rd, Rockland, MI 49960',                        'price'=>184.50, 'active'=>1],
            ['name'=>'Ontonagon (Downtown)',                 'address'=>'Ontonagon, MI 49953',                                        'price'=>191.88, 'active'=>1],
            ['name'=>'Ontonagon Lighthouse',                 'address'=>'Ontonagon Lighthouse, Ontonagon, MI 49953',                  'price'=>191.88, 'active'=>1],
            ['name'=>'Porcupine Mountains Wilderness SP',    'address'=>'Porcupine Mountains Wilderness State Park, Ontonagon, MI',  'price'=>258.30, 'active'=>1],
            ['name'=>'Lake of the Clouds',                   'address'=>'Lake of the Clouds, Ontonagon County, MI',                  'price'=>265.68, 'active'=>1],
        ],
    ]);
}

/* Return flat rates as JSON for the booking form */
add_action('wp_ajax_st_get_flat_rates',        'st_ajax_get_flat_rates');
add_action('wp_ajax_nopriv_st_get_flat_rates', 'st_ajax_get_flat_rates');
function st_ajax_get_flat_rates() {
    $rates = st_get_flat_rates();
    $out = [];
    foreach ($rates as $block => $destinations) {
        foreach ($destinations as $d) {
            if (!empty($d['active'])) {
                $out[] = [
                    'name'    => $d['name'],
                    'address' => $d['address'],
                    'price'   => floatval($d['price']),
                    'block'   => $block,
                ];
            }
        }
    }
    wp_send_json_success($out);
}

/* -------------------------------------------------------
   COUPONS
------------------------------------------------------- */
function st_get_coupons() { return get_option('st_coupons', []); }

function st_validate_coupon( $code, $fare ) {
    $coupons = st_get_coupons();
    $code = strtoupper(trim($code));
    foreach ($coupons as $c) {
        if (strtoupper($c['code']) !== $code) continue;
        if (empty($c['active'])) return ['valid'=>false,'msg'=>'Coupon is inactive.'];
        if (!empty($c['expires']) && strtotime($c['expires']) < time()) return ['valid'=>false,'msg'=>'Coupon has expired.'];
        if (!empty($c['uses_max']) && intval($c['uses_count']) >= intval($c['uses_max'])) return ['valid'=>false,'msg'=>'Coupon usage limit reached.'];
        $discount = $c['type'] === 'percent' ? round($fare * (floatval($c['amount'])/100), 2) : min(floatval($c['amount']), $fare);
        $new_fare = max(0, $fare - $discount);
        return ['valid'=>true,'code'=>$code,'discount'=>$discount,'new_fare'=>$new_fare,'msg'=>'Coupon applied: -$'.number_format($discount,2)];
    }
    return ['valid'=>false,'msg'=>'Invalid coupon code.'];
}

add_action('wp_ajax_st_check_coupon',        'st_ajax_check_coupon');
add_action('wp_ajax_nopriv_st_check_coupon', 'st_ajax_check_coupon');
function st_ajax_check_coupon() {
    check_ajax_referer('st_nonce','nonce');
    $code = sanitize_text_field($_POST['code'] ?? '');
    $fare = floatval($_POST['fare'] ?? 0);
    wp_send_json(st_validate_coupon($code, $fare));
}

function st_increment_coupon_use($code) {
    $code = strtoupper(trim($code));
    $coupons = st_get_coupons();
    foreach ($coupons as &$c) {
        if (strtoupper($c['code']) === $code) { $c['uses_count'] = intval($c['uses_count']) + 1; break; }
    }
    update_option('st_coupons', $coupons);
}

/* -------------------------------------------------------
   ADMIN MENU
------------------------------------------------------- */
add_action('admin_menu', function(){
    add_menu_page('Superior Transport','Superior Transport','manage_options','st-transport','st_settings_page','dashicons-car',30);
    add_submenu_page('st-transport','General','General','manage_options','st-transport','st_settings_page');
    add_submenu_page('st-transport','Form Fields','Form Fields','manage_options','st-transport-fields','st_fields_page');
    add_submenu_page('st-transport','Flat Rates','🗺️ Flat Rates','manage_options','st-transport-flatrates','st_flat_rates_page');
    add_submenu_page('st-transport','Coupons','Coupons','manage_options','st-transport-coupons','st_coupons_page');
});

/* -------------------------------------------------------
   GENERAL SETTINGS PAGE
------------------------------------------------------- */
function st_settings_page(){
    if(isset($_POST['st_save_settings'])){
        check_admin_referer('st_save_settings');
        $fields = ['phone','email','address','hours_open','hours_close','base_rate','per_mile',
                   'flat_rate_miles','flat_rate_price','google_maps_key','square_app_id',
                   'square_token','square_location','facebook_url','tiktok_url'];
        $toggles = ['require_name','require_phone','require_email','require_date','require_time',
                    'require_pickup','require_dropoff','require_passengers','require_notes',
                    'show_email','show_passengers','show_notes'];
        $data = [];
        foreach($fields as $f) $data[$f] = sanitize_text_field($_POST[$f] ?? '');
        foreach($toggles as $f) $data[$f] = isset($_POST[$f]) ? '1' : '0';
        update_option('st_settings', $data);
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }
    $s = st_get_settings();
    ?>
    <div class="wrap"><h1>Superior Transport - General Settings</h1>
    <form method="post"><?php wp_nonce_field('st_save_settings'); ?>
    <table class="form-table">
        <tr><th>Phone</th><td><input name="phone" value="<?php echo esc_attr($s['phone']);?>" class="regular-text"></td></tr>
        <tr><th>Email</th><td><input name="email" value="<?php echo esc_attr($s['email']);?>" class="regular-text"></td></tr>
        <tr><th>Address</th><td><input name="address" value="<?php echo esc_attr($s['address']);?>" class="regular-text"></td></tr>
        <tr><th>Hours Open</th><td><input name="hours_open" type="time" value="<?php echo esc_attr($s['hours_open']);?>"></td></tr>
        <tr><th>Hours Close</th><td><input name="hours_close" type="time" value="<?php echo esc_attr($s['hours_close']);?>"></td></tr>
        <tr><th>Flat Rate (under X miles)</th><td><input name="flat_rate_miles" value="<?php echo esc_attr($s['flat_rate_miles']);?>" style="width:80px"> miles at $<input name="flat_rate_price" value="<?php echo esc_attr($s['flat_rate_price']);?>" style="width:80px"></td></tr>
        <tr><th>Per Mile Rate</th><td>$<input name="per_mile" value="<?php echo esc_attr($s['per_mile']);?>" style="width:80px"></td></tr>
        <tr><th>Base Rate</th><td>$<input name="base_rate" value="<?php echo esc_attr($s['base_rate']);?>" style="width:80px"></td></tr>
        <tr><th>Google Maps API Key</th><td><input name="google_maps_key" value="<?php echo esc_attr($s['google_maps_key']);?>" class="regular-text"></td></tr>
        <tr><th colspan="2" style="background:#f0f0f0;padding:10px"><strong>Square Payment Settings</strong></th></tr>
        <tr><th>Square App ID</th><td><input name="square_app_id" value="<?php echo esc_attr($s['square_app_id']);?>" class="regular-text" placeholder="sq0idp-..."></td></tr>
        <tr><th>Square Access Token</th><td><input name="square_token" value="<?php echo esc_attr($s['square_token']);?>" class="regular-text"></td></tr>
        <tr><th>Square Location ID</th><td><input name="square_location" value="<?php echo esc_attr($s['square_location']);?>" class="regular-text"></td></tr>
        <tr><th colspan="2" style="background:#f0f0f0;padding:10px"><strong>Social Media</strong></th></tr>
        <tr><th>Facebook URL</th><td><input name="facebook_url" value="<?php echo esc_attr($s['facebook_url']);?>" class="regular-text"></td></tr>
        <tr><th>TikTok URL</th><td><input name="tiktok_url" value="<?php echo esc_attr($s['tiktok_url']);?>" class="regular-text"></td></tr>
    </table>
    <p><input type="submit" name="st_save_settings" class="button button-primary" value="Save Settings"></p>
    </form></div>
    <?php
}

/* -------------------------------------------------------
   FORM FIELDS PAGE
------------------------------------------------------- */
function st_fields_page(){
    if(isset($_POST['st_save_settings'])){
        check_admin_referer('st_save_settings');
        $toggles = ['require_name','require_phone','require_email','require_date','require_time',
                    'require_pickup','require_dropoff','require_passengers','require_notes',
                    'show_email','show_passengers','show_notes'];
        $s = st_get_settings();
        foreach($toggles as $f) $s[$f] = isset($_POST[$f]) ? '1' : '0';
        update_option('st_settings', $s);
        echo '<div class="notice notice-success"><p>Field settings saved.</p></div>';
    }
    $s = st_get_settings();
    ?>
    <div class="wrap"><h1>Form Field Settings</h1>
    <form method="post"><?php wp_nonce_field('st_save_settings'); ?>
    <table class="form-table">
        <tr style="background:#f0f0f0"><th>Field</th><th>Show</th><th>Required</th></tr>
        <tr><th>Full Name</th><td>Always</td><td><input type="checkbox" name="require_name" <?php checked($s['require_name'],'1');?>> Required</td></tr>
        <tr><th>Phone</th><td>Always</td><td><input type="checkbox" name="require_phone" <?php checked($s['require_phone'],'1');?>> Required</td></tr>
        <tr><th>Email</th><td><input type="checkbox" name="show_email" <?php checked($s['show_email'],'1');?>> Show</td><td><input type="checkbox" name="require_email" <?php checked($s['require_email'],'1');?>> Required</td></tr>
        <tr><th>Pickup Date</th><td>Always</td><td><input type="checkbox" name="require_date" <?php checked($s['require_date'],'1');?>> Required</td></tr>
        <tr><th>Pickup Time</th><td>Always</td><td><input type="checkbox" name="require_time" <?php checked($s['require_time'],'1');?>> Required</td></tr>
        <tr><th>Pickup Location</th><td>Always</td><td><input type="checkbox" name="require_pickup" <?php checked($s['require_pickup'],'1');?>> Required</td></tr>
        <tr><th>Dropoff Location</th><td>Always</td><td><input type="checkbox" name="require_dropoff" <?php checked($s['require_dropoff'],'1');?>> Required</td></tr>
        <tr><th>Passengers</th><td><input type="checkbox" name="show_passengers" <?php checked($s['show_passengers'],'1');?>> Show</td><td><input type="checkbox" name="require_passengers" <?php checked($s['require_passengers'],'1');?>> Required</td></tr>
        <tr><th>Notes</th><td><input type="checkbox" name="show_notes" <?php checked($s['show_notes'],'1');?>> Show</td><td><input type="checkbox" name="require_notes" <?php checked($s['require_notes'],'1');?>> Required</td></tr>
    </table>
    <p><input type="submit" name="st_save_settings" class="button button-primary" value="Save Field Settings"></p>
    </form></div>
    <?php
}

/* -------------------------------------------------------
   FLAT RATES PAGE
------------------------------------------------------- */
function st_flat_rates_page(){
    $rates = st_get_flat_rates();

    /* Save */
    if(isset($_POST['st_save_flat_rates'])){
        check_admin_referer('st_save_flat_rates');
        $blocks = ['north_bound', 'west_bound'];
        $new_rates = [];
        foreach($blocks as $block){
            $names    = $_POST[$block.'_name']    ?? [];
            $addrs    = $_POST[$block.'_address'] ?? [];
            $prices   = $_POST[$block.'_price']   ?? [];
            $actives  = $_POST[$block.'_active']  ?? [];
            $new_rates[$block] = [];
            foreach($names as $i => $name){
                if(empty(trim($name))) continue;
                $new_rates[$block][] = [
                    'name'    => sanitize_text_field($name),
                    'address' => sanitize_text_field($addrs[$i] ?? ''),
                    'price'   => floatval($prices[$i] ?? 0),
                    'active'  => isset($actives[$i]) ? 1 : 0,
                ];
            }
        }
        update_option('st_flat_rates', $new_rates);
        $rates = $new_rates;
        echo '<div class="notice notice-success"><p>Flat rates saved.</p></div>';
    }

    /* Delete */
    if(isset($_GET['delete_fr']) && isset($_GET['block']) && check_admin_referer('st_delete_fr')){
        $block = sanitize_key($_GET['block']);
        $idx   = intval($_GET['delete_fr']);
        if(isset($rates[$block][$idx])){ array_splice($rates[$block], $idx, 1); update_option('st_flat_rates',$rates); }
        echo '<div class="notice notice-success"><p>Destination removed.</p></div>';
    }

    $base_url = admin_url('admin.php?page=st-transport-flatrates');
    $nb = $rates['north_bound'] ?? [];
    ?>
    <div class="wrap">
    <h1>🗺️ Flat Rate Destinations</h1>

    <div style="background:#fff3cd;border-left:4px solid #c8a84b;padding:14px 18px;margin-bottom:20px;border-radius:4px;max-width:700px;">
        <strong>📋 Pricing Policy — North Bound Routes:</strong><br>
        Flat rates listed below apply to <strong>1–2 passengers</strong>.<br>
        <strong>3 or more passengers</strong> are charged the flat rate + <strong>40% surcharge</strong> (automatically calculated at booking).<br>
        Rates are one-way from Houghton/Hancock unless otherwise noted.
    </div>

    <form method="post"><?php wp_nonce_field('st_save_flat_rates'); ?>

    <h2 style="border-bottom:2px solid #1a3a1a;padding-bottom:6px;color:#1a3a1a;">🧭 North Bound Block (Houghton → Copper Harbor via US-41)</h2>

    <table class="wp-list-table widefat fixed" style="max-width:900px">
        <thead>
            <tr style="background:#1a3a1a;color:#fff">
                <th style="color:#fff;width:28%">Destination Name</th>
                <th style="color:#fff;width:30%">Address / Search Term</th>
                <th style="color:#fff;width:12%">Base Rate (1-2 pax)</th>
                <th style="color:#fff;width:14%">3+ pax (+40%)</th>
                <th style="color:#fff;width:8%">Active</th>
                <th style="color:#fff;width:8%">Remove</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($nb as $i => $d): $plus40 = number_format($d['price'] * 1.40, 2); ?>
        <tr style="background:<?php echo $i%2?'#f9f9f9':'#fff';?>">
            <td><input type="text" name="north_bound_name[]" value="<?php echo esc_attr($d['name']);?>" style="width:98%"></td>
            <td><input type="text" name="north_bound_address[]" value="<?php echo esc_attr($d['address']);?>" style="width:98%"></td>
            <td><div style="display:flex;align-items:center;gap:4px">$<input type="number" step="0.01" min="0" name="north_bound_price[]" value="<?php echo esc_attr($d['price']);?>" style="width:75px"></div></td>
            <td style="color:#888;font-style:italic">$<?php echo $plus40;?></td>
            <td style="text-align:center"><input type="checkbox" name="north_bound_active[<?php echo $i;?>]" <?php checked($d['active'],1);?>></td>
            <td style="text-align:center"><a href="<?php echo wp_nonce_url($base_url.'&delete_fr='.$i.'&block=north_bound','st_delete_fr');?>" onclick="return confirm('Remove this destination?')" style="color:red;font-weight:700">✕</a></td>
        </tr>
        <?php endforeach; ?>
        <!-- Add new row -->
        <tr style="background:#f0fff0;border-top:2px dashed #2e7d32">
            <td><input type="text" name="north_bound_name[]" placeholder="New destination name" style="width:98%"></td>
            <td><input type="text" name="north_bound_address[]" placeholder="Address or search term" style="width:98%"></td>
            <td><div style="display:flex;align-items:center;gap:4px">$<input type="number" step="0.01" min="0" name="north_bound_price[]" value="" style="width:75px"></div></td>
            <td style="color:#aaa;font-style:italic">auto</td>
            <td style="text-align:center"><input type="checkbox" name="north_bound_active[<?php echo count($nb);?>]" checked></td>
            <td></td>
        </tr>
        </tbody>
    </table>


    <h2 style="border-bottom:2px solid #0a1a3a;padding-bottom:6px;color:#0a1a3a;margin-top:32px;">🧭 West Bound Block (Houghton → Lake of the Clouds via M-26 / US-45)</h2>
    <?php $wb = $rates['west_bound'] ?? []; ?>
    <table class="wp-list-table widefat fixed" style="max-width:900px">
        <thead>
            <tr style="background:#0a1a3a;color:#fff">
                <th style="color:#fff;width:28%">Destination Name</th>
                <th style="color:#fff;width:30%">Address / Search Term</th>
                <th style="color:#fff;width:12%">Base Rate (1-2 pax)</th>
                <th style="color:#fff;width:14%">3+ pax (+40%)</th>
                <th style="color:#fff;width:8%">Active</th>
                <th style="color:#fff;width:8%">Remove</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($wb as $i => $d): $plus40 = number_format($d['price'] * 1.40, 2); ?>
        <tr style="background:<?php echo $i%2?'#f9f9f9':'#fff';?>">
            <td><input type="text" name="west_bound_name[]" value="<?php echo esc_attr($d['name']);?>" style="width:98%"></td>
            <td><input type="text" name="west_bound_address[]" value="<?php echo esc_attr($d['address']);?>" style="width:98%"></td>
            <td><div style="display:flex;align-items:center;gap:4px">$<input type="number" step="0.01" min="0" name="west_bound_price[]" value="<?php echo esc_attr($d['price']);?>" style="width:75px"></div></td>
            <td style="color:#888;font-style:italic">$<?php echo $plus40;?></td>
            <td style="text-align:center"><input type="checkbox" name="west_bound_active[<?php echo $i;?>]" <?php checked($d['active'],1);?>></td>
            <td style="text-align:center"><a href="<?php echo wp_nonce_url($base_url.'&delete_fr='.$i.'&block=west_bound','st_delete_fr');?>" onclick="return confirm('Remove this destination?')" style="color:red;font-weight:700">✕</a></td>
        </tr>
        <?php endforeach; ?>
        <tr style="background:#f0fff0;border-top:2px dashed #0a1a3a">
            <td><input type="text" name="west_bound_name[]" placeholder="New destination name" style="width:98%"></td>
            <td><input type="text" name="west_bound_address[]" placeholder="Address or search term" style="width:98%"></td>
            <td><div style="display:flex;align-items:center;gap:4px">$<input type="number" step="0.01" min="0" name="west_bound_price[]" value="" style="width:75px"></div></td>
            <td style="color:#aaa;font-style:italic">auto</td>
            <td style="text-align:center"><input type="checkbox" name="west_bound_active[<?php echo count($wb);?>]" checked></td>
            <td></td>
        </tr>
        </tbody>
    </table>

    <p style="margin-top:16px">
        <input type="submit" name="st_save_flat_rates" class="button button-primary" value="💾 Save Flat Rates">
        <span style="margin-left:12px;color:#666;font-size:.85rem">The 3+ passenger rate is calculated automatically — no need to enter it separately.</span>
    </p>
    </form>
    </div>
    <?php
}

/* -------------------------------------------------------
   COUPONS PAGE
------------------------------------------------------- */
function st_coupons_page(){
    if(isset($_POST['st_save_coupon'])){
        check_admin_referer('st_save_coupon');
        $coupons = st_get_coupons();
        $edit_idx = isset($_POST['edit_index']) && $_POST['edit_index'] !== '' ? intval($_POST['edit_index']) : null;
        $coupon = [
            'code'        => strtoupper(sanitize_text_field($_POST['coupon_code'] ?? '')),
            'type'        => in_array($_POST['coupon_type'],['flat','percent']) ? $_POST['coupon_type'] : 'flat',
            'amount'      => floatval($_POST['coupon_amount'] ?? 0),
            'expires'     => sanitize_text_field($_POST['coupon_expires'] ?? ''),
            'uses_max'    => intval($_POST['coupon_uses_max'] ?? 0),
            'uses_count'  => 0,
            'active'      => isset($_POST['coupon_active']) ? 1 : 0,
            'description' => sanitize_text_field($_POST['coupon_description'] ?? ''),
        ];
        if($edit_idx !== null && isset($coupons[$edit_idx])){ $coupon['uses_count']=$coupons[$edit_idx]['uses_count']; $coupons[$edit_idx]=$coupon; } else { $coupons[]=$coupon; }
        update_option('st_coupons', $coupons);
        echo '<div class="notice notice-success"><p>Coupon saved.</p></div>';
    }
    if(isset($_GET['delete_coupon']) && check_admin_referer('st_delete_coupon')){ $coupons=st_get_coupons(); array_splice($coupons,intval($_GET['delete_coupon']),1); update_option('st_coupons',$coupons); echo '<div class="notice notice-success"><p>Coupon deleted.</p></div>'; }
    $coupons=st_get_coupons(); $edit_idx=isset($_GET['edit_coupon'])?intval($_GET['edit_coupon']):null; $edit_c=($edit_idx!==null&&isset($coupons[$edit_idx]))?$coupons[$edit_idx]:null;
    $base_url=admin_url('admin.php?page=st-transport-coupons');
    ?>
    <div class="wrap"><h1>Coupon Manager</h1>
    <form method="post"><?php wp_nonce_field('st_save_coupon'); if($edit_idx!==null) echo '<input type="hidden" name="edit_index" value="'.$edit_idx.'">'; ?>
    <table class="form-table">
        <tr><th>Code</th><td><input name="coupon_code" value="<?php echo esc_attr($edit_c['code']??'');?>" class="regular-text" style="text-transform:uppercase"></td></tr>
        <tr><th>Description</th><td><input name="coupon_description" value="<?php echo esc_attr($edit_c['description']??'');?>" class="regular-text"></td></tr>
        <tr><th>Type</th><td><select name="coupon_type"><option value="flat" <?php selected($edit_c['type']??'flat','flat');?>>Flat $ off</option><option value="percent" <?php selected($edit_c['type']??'','percent');?>>Percent % off</option></select></td></tr>
        <tr><th>Amount</th><td><input name="coupon_amount" type="number" step="0.01" min="0" value="<?php echo esc_attr($edit_c['amount']??'');?>" style="width:100px"></td></tr>
        <tr><th>Expiry Date</th><td><input name="coupon_expires" type="date" value="<?php echo esc_attr($edit_c['expires']??'');?>"></td></tr>
        <tr><th>Max Uses</th><td><input name="coupon_uses_max" type="number" min="0" value="<?php echo esc_attr($edit_c['uses_max']??'0');?>" style="width:80px"> <span style="color:#666">0 = unlimited</span></td></tr>
        <tr><th>Active</th><td><input type="checkbox" name="coupon_active" <?php checked($edit_c['active']??1,1);?>></td></tr>
    </table>
    <p><input type="submit" name="st_save_coupon" class="button button-primary" value="<?php echo $edit_c?'Update':'Add Coupon';?>"> <?php if($edit_c) echo '<a href="'.$base_url.'" class="button">Cancel</a>'; ?></p>
    </form>
    <?php if($coupons): ?>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr><th>Code</th><th>Type</th><th>Amount</th><th>Expires</th><th>Uses</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody><?php foreach($coupons as $i=>$c): $expired=!empty($c['expires'])&&strtotime($c['expires'])<time(); ?>
        <tr><td><strong><?php echo esc_html($c['code']);?></strong></td><td><?php echo $c['type']==='percent'?'%':'$';?></td>
        <td><?php echo $c['type']==='percent'?esc_html($c['amount']).'%':'$'.number_format($c['amount'],2);?></td>
        <td><?php echo $c['expires']?esc_html($c['expires']):'No expiry';?><?php if($expired) echo ' <span style="color:red">(Expired)</span>';?></td>
        <td><?php echo intval($c['uses_count']).'/'.(intval($c['uses_max'])?:'-');?></td>
        <td><?php echo $c['active']&&!$expired?'<span style="color:green">Active</span>':'<span style="color:#999">Inactive</span>';?></td>
        <td><a href="<?php echo $base_url.'&edit_coupon='.$i;?>">Edit</a> | <a href="<?php echo wp_nonce_url($base_url.'&delete_coupon='.$i,'st_delete_coupon');?>" onclick="return confirm('Delete?')" style="color:red">Delete</a></td>
        </tr><?php endforeach; ?></tbody>
    </table>
    <?php endif; ?></div>
    <?php
}

