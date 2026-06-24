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

add_action('admin_menu', function(){
    add_menu_page('Superior Transport','Superior Transport','manage_options','st-transport','st_settings_page','dashicons-car',30);
    add_submenu_page('st-transport','General','General','manage_options','st-transport','st_settings_page');
    add_submenu_page('st-transport','Form Fields','Form Fields','manage_options','st-transport-fields','st_fields_page');
    add_submenu_page('st-transport','Coupons','Coupons','manage_options','st-transport-coupons','st_coupons_page');
});

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
