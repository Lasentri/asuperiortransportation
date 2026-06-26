<?php
/**
 * Plugin Name: Cab Orders
 * Description: Full ride management - pickup, dropoff, phone, fare, distance, passenger star ratings. With retry for calendar/payment.
 * Version:     2.1.0
 * Author:      A Superior Transportation
 */
if ( ! defined( 'ABSPATH' ) ) exit;

register_activation_hook( __FILE__, 'co_create_table' );
function co_create_table() {
    global $wpdb;
    $t  = $wpdb->prefix . 'st_bookings';
    $cc = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( "CREATE TABLE IF NOT EXISTS `{$t}` (
        id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
        confirmation     VARCHAR(32)   DEFAULT '',
        customer_name    VARCHAR(160)  DEFAULT '',
        customer_email   VARCHAR(200)  DEFAULT '',
        customer_phone   VARCHAR(30)   DEFAULT '',
        pickup           VARCHAR(400)  DEFAULT '',
        dropoff          VARCHAR(400)  DEFAULT '',
        pickup_date      DATE          DEFAULT NULL,
        pickup_time      VARCHAR(20)   DEFAULT '',
        passengers       TINYINT       DEFAULT 1,
        distance_miles   DECIMAL(6,1)  DEFAULT 0.0,
        fare             DECIMAL(8,2)  DEFAULT 0.00,
        payment_method   VARCHAR(60)   DEFAULT '',
        payment_status   VARCHAR(40)   DEFAULT 'unpaid',
        square_receipt   VARCHAR(200)  DEFAULT '',
        passenger_rating TINYINT       DEFAULT 0,
        rating_note      VARCHAR(400)  DEFAULT '',
        notes            TEXT,
        status           VARCHAR(40)   DEFAULT 'pending',
        created_at       DATETIME      DEFAULT CURRENT_TIMESTAMP,
        confirmation     VARCHAR(32)   DEFAULT '',
        PRIMARY KEY (id)
    ) {$cc};" );
    // Add cols if upgrading
    $cols = $wpdb->get_col("DESCRIBE `{$t}`");
    $add  = [
        'distance_miles'   => 'DECIMAL(6,1) DEFAULT 0.0 AFTER passengers',
        'passenger_rating' => 'TINYINT DEFAULT 0 AFTER square_receipt',
        'rating_note'      => "VARCHAR(400) DEFAULT '' AFTER passenger_rating",
        'customer_email'   => "VARCHAR(200) DEFAULT '' AFTER customer_name",
        'confirmation'     => "VARCHAR(32) DEFAULT '' AFTER id",
    ];
    foreach($add as $col=>$def) {
        if(!in_array($col,$cols)) $wpdb->query("ALTER TABLE `{$t}` ADD COLUMN {$col} {$def}");
    }
}

add_action( 'admin_menu', 'co_admin_menu' );
function co_admin_menu() {
    add_menu_page( 'Cab Orders', '🚖 Cab Orders', 'manage_options', 'cab-orders', 'co_page_list', 'dashicons-calendar-alt', 25 );
    add_submenu_page( 'cab-orders', 'All Orders',    'All Orders',    'manage_options', 'cab-orders',        'co_page_list' );
    add_submenu_page( 'cab-orders', "Today's Rides", "Today's Rides", 'manage_options', 'cab-orders-today',  'co_page_today' );
    add_submenu_page( 'cab-orders', 'Add Order',     'Add Order',     'manage_options', 'cab-orders-add',    'co_page_add' );
    add_submenu_page( 'cab-orders', 'Ratings',       '⭐ Ratings',    'manage_options', 'cab-orders-ratings','co_page_ratings' );
    add_submenu_page( 'cab-orders', 'Export CSV',    'Export CSV',    'manage_options', 'cab-orders-export', 'co_page_export' );
}

add_action( 'admin_head', 'co_admin_styles' );
function co_admin_styles() {
    if ( strpos( $_GET['page'] ?? '', 'cab-orders' ) === false ) return;
    ?>
    <style>
    .co-star-rating{display:inline-flex;gap:2px;font-size:1.1rem;line-height:1;}
    .co-star-rating .star{color:#ddd;}
    .co-star-rating .star.on{color:#f0c040;}
    .co-star-input{display:inline-flex;gap:4px;font-size:1.6rem;flex-direction:row-reverse;}
    .co-star-input label{cursor:pointer;color:#ddd;}
    .co-star-input input{display:none;}
    .co-star-input input:checked ~ label{color:#f0c040;}
    .co-badge{display:inline-block;padding:2px 8px;border-radius:3px;font-size:.72rem;font-weight:700;color:#fff;white-space:nowrap;}
    .co-badge-cc{background:#1565c0;}.co-badge-cash{background:#2e7d32;}.co-badge-square{background:#6a1b9a;}.co-badge-venmo{background:#008CFF;}.co-badge-other{background:#888;}
    .co-badge-paid{background:#2e7d32;}.co-badge-unpaid{background:#e65100;}
    .co-badge-pending{background:#e65100;}.co-badge-confirmed{background:#1565c0;}.co-badge-completed{background:#2e7d32;}.co-badge-cancelled{background:#888;}
    .co-stat-box{background:#fff;border:1px solid #ddd;border-radius:4px;padding:14px 16px;text-align:center;}
    .co-stat-num{font-size:1.7rem;font-weight:800;line-height:1;display:block;}
    .co-stat-lbl{font-size:.68rem;color:#888;text-transform:uppercase;letter-spacing:.06em;margin-top:3px;display:block;}
    .co-card{background:#fff;border:1px solid #ddd;border-radius:6px;padding:20px 22px;margin-bottom:14px;}
    .co-card h3{margin:0 0 12px;padding-bottom:8px;border-bottom:2px solid #c8a84b;color:#0d1f3c;font-size:.95rem;}
    .co-route-cell{font-size:.8rem;line-height:1.5;}
    .co-route-cell .label{font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;color:#aaa;display:block;}
    .co-route-cell .val{font-weight:600;color:#222;}
    .co-conf-code{font-family:monospace;font-size:.85rem;font-weight:700;background:#0d1f3c;color:#c8a84b;padding:3px 8px;border-radius:3px;}
    .co-retry-btn{background:#e65100;color:#fff;border:none;padding:5px 12px;border-radius:3px;cursor:pointer;font-size:.78rem;font-weight:700;}
    .co-retry-btn:hover{background:#bf360c;}
    .widefat td,.widefat th{vertical-align:middle !important;}
    </style>
    <?php
}

// Helper: format time 12hr
function co_format_time($t) {
    if(!$t) return '—';
    return date('g:i A', strtotime($t));
}

// Helper: format date readable
function co_format_date($d) {
    if(!$d) return '—';
    return date('M j, Y', strtotime($d));
}

add_action( 'admin_init', 'co_handle_actions' );
function co_handle_actions() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    global $wpdb;
    $t = $wpdb->prefix . 'st_bookings';

    // Save order
    if ( isset( $_POST['co_save'] ) && check_admin_referer( 'co_save_order' ) ) {
        $id   = intval( $_POST['co_id'] ?? 0 );
        $data = [
            'customer_name'   => sanitize_text_field( $_POST['customer_name']   ?? '' ),
            'customer_email'  => sanitize_email( $_POST['customer_email']        ?? '' ),
            'customer_phone'  => sanitize_text_field( $_POST['customer_phone']  ?? '' ),
            'pickup'          => sanitize_text_field( $_POST['pickup']           ?? '' ),
            'dropoff'         => sanitize_text_field( $_POST['dropoff']          ?? '' ),
            'pickup_date'     => sanitize_text_field( $_POST['pickup_date']      ?? '' ),
            'pickup_time'     => sanitize_text_field( $_POST['pickup_time']      ?? '' ),
            'passengers'      => intval( $_POST['passengers']                    ?? 1 ),
            'distance_miles'  => floatval( $_POST['distance_miles']              ?? 0 ),
            'fare'            => floatval( $_POST['fare']                        ?? 0 ),
            'payment_method'  => sanitize_text_field( $_POST['payment_method']  ?? '' ),
            'payment_status'  => sanitize_text_field( $_POST['payment_status']  ?? 'unpaid' ),
            'square_receipt'  => sanitize_text_field( $_POST['square_receipt']  ?? '' ),
            'passenger_rating'=> min(5,max(0,intval($_POST['passenger_rating']  ?? 0))),
            'rating_note'     => sanitize_text_field( $_POST['rating_note']     ?? '' ),
            'notes'           => sanitize_textarea_field( $_POST['notes']       ?? '' ),
            'status'          => sanitize_text_field( $_POST['status']          ?? 'pending' ),
        ];
        if ($id) {
            $wpdb->update($t,$data,['id'=>$id]);
        } else {
            $data['confirmation'] = strtoupper(substr(md5(uniqid(rand(),true)),0,8));
            $data['created_at']   = current_time('mysql');
            $wpdb->insert($t,$data);
        }
        wp_redirect(admin_url('admin.php?page=cab-orders&saved=1'));
        exit;
    }

    // Retry calendar
    if ( isset($_GET['co_retry_calendar']) && check_admin_referer('co_rc_'.$_GET['co_retry_calendar']) ) {
        $id = intval($_GET['co_retry_calendar']);
        $o  = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$t}` WHERE id=%d",$id));
        if ($o && function_exists('st_gcal_create_event')) {
            $result = st_gcal_create_event(
                $o->customer_name, $o->customer_phone,
                $o->pickup, $o->dropoff,
                $o->pickup_date, $o->pickup_time,
                $o->fare, $o->notes
            );
            $msg = $result ? 'cal_ok' : 'cal_fail';
        } else {
            $msg = 'cal_fail';
        }
        wp_redirect(admin_url('admin.php?page=cab-orders&edit='.$id.'&'.$msg.'=1'));
        exit;
    }

    // Quick rate
    if ( isset($_POST['co_quick_rate']) && check_admin_referer('co_qr_'.intval($_POST['co_id'])) ) {
        $wpdb->update($t,
            ['passenger_rating'=>min(5,max(0,intval($_POST['passenger_rating']))),'rating_note'=>sanitize_text_field($_POST['rating_note']??'')],
            ['id'=>intval($_POST['co_id'])]
        );
        wp_redirect(wp_get_referer()?:admin_url('admin.php?page=cab-orders'));
        exit;
    }

    // Delete
    if ( isset($_GET['co_delete']) && check_admin_referer('co_del_'.$_GET['co_delete']) ) {
        $wpdb->delete($t,['id'=>intval($_GET['co_delete'])]);
        wp_redirect(admin_url('admin.php?page=cab-orders&deleted=1'));
        exit;
    }

    // Toggle paid
    if ( isset($_GET['co_toggle_paid']) && check_admin_referer('co_tp_'.$_GET['co_toggle_paid']) ) {
        $id  = intval($_GET['co_toggle_paid']);
        $cur = $wpdb->get_var($wpdb->prepare("SELECT payment_status FROM `{$t}` WHERE id=%d",$id));
        $wpdb->update($t,['payment_status'=>$cur==='paid'?'unpaid':'paid'],['id'=>$id]);
        wp_redirect(wp_get_referer()?:admin_url('admin.php?page=cab-orders'));
        exit;
    }

    // Toggle status
    if ( isset($_GET['co_toggle_status']) && check_admin_referer('co_ts_'.$_GET['co_toggle_status']) ) {
        $id   = intval($_GET['co_toggle_status']);
        $cur  = $wpdb->get_var($wpdb->prepare("SELECT status FROM `{$t}` WHERE id=%d",$id));
        $next = ['pending'=>'confirmed','confirmed'=>'completed','completed'=>'pending','cancelled'=>'pending'];
        $wpdb->update($t,['status'=>$next[$cur]??'pending'],['id'=>$id]);
        wp_redirect(wp_get_referer()?:admin_url('admin.php?page=cab-orders'));
        exit;
    }

    // CSV Export
    if ( isset($_GET['page'],$_GET['do_export']) && $_GET['page']==='cab-orders-export' ) {
        $rows = $wpdb->get_results("SELECT * FROM `{$t}` ORDER BY pickup_date DESC,pickup_time ASC");
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="cab-orders-'.date('Y-m-d').'.csv"');
        $out = fopen('php://output','w');
        fputcsv($out,['Confirmation','Name','Phone','Email','Date','Time','Pickup','Dropoff','Miles','Pax','Fare','Payment','Paid','Receipt','Rating','Rating Note','Status','Notes','Created']);
        foreach($rows as $r){
            fputcsv($out,[
                $r->confirmation,$r->customer_name,$r->customer_phone,$r->customer_email,
                $r->pickup_date,co_format_time($r->pickup_time),$r->pickup,$r->dropoff,
                $r->distance_miles,$r->passengers,'$'.number_format($r->fare,2),
                $r->payment_method,$r->payment_status,$r->square_receipt,
                $r->passenger_rating.'/5',$r->rating_note,$r->status,$r->notes,$r->created_at,
            ]);
        }
        fclose($out); exit;
    }
}

function co_stars_display($rating,$max=5){
    $out='<span class="co-star-rating">';
    for($i=1;$i<=$max;$i++) $out.='<span class="star '.($i<=$rating?'on':'').'">★</span>';
    return $out.'</span>';
}

function co_stars_input($name,$current=0){
    $out='<div class="co-star-input">';
    for($i=5;$i>=1;$i--){
        $checked=checked($current,$i,false);
        $out.="<input type='radio' name='{$name}' id='{$name}_{$i}' value='{$i}' {$checked}>";
        $out.="<label for='{$name}_{$i}' title='{$i} stars'>★</label>";
    }
    return $out.'</div>';
}

function co_pay_badge($method){
    $map=['cash'=>'cash','credit card'=>'cc','square'=>'square','venmo'=>'venmo'];
    $cls=$map[strtolower($method)]??'other';
    return "<span class='co-badge co-badge-{$cls}'>".esc_html($method?:'Not set')."</span>";
}

/* ═══════════════════════════════════════
   PAGE: ALL ORDERS
═══════════════════════════════════════ */
function co_page_list(){
    global $wpdb;
    $t=$wpdb->prefix.'st_bookings';
    $search  = sanitize_text_field($_GET['co_search']??'');
    $fstatus = sanitize_text_field($_GET['co_status']??'');
    $fpay    = sanitize_text_field($_GET['co_payment']??'');
    $fdate   = sanitize_text_field($_GET['co_date']??'');
    $frating = intval($_GET['co_rating']??0);
    $where   = 'WHERE 1=1';
    if($search)  $where.=$wpdb->prepare(' AND (customer_name LIKE %s OR customer_phone LIKE %s OR confirmation LIKE %s OR pickup LIKE %s OR dropoff LIKE %s)',"%{$search}%","%{$search}%","%{$search}%","%{$search}%","%{$search}%");
    if($fstatus) $where.=$wpdb->prepare(' AND status=%s',$fstatus);
    if($fpay)    $where.=$wpdb->prepare(' AND payment_status=%s',$fpay);
    if($fdate)   $where.=$wpdb->prepare(' AND pickup_date=%s',$fdate);
    if($frating) $where.=$wpdb->prepare(' AND passenger_rating=%d',$frating);
    $orders=$wpdb->get_results("SELECT * FROM `{$t}` {$where} ORDER BY pickup_date DESC,pickup_time ASC");
    $s_total=$wpdb->get_var("SELECT COUNT(*) FROM `{$t}`");
    $s_today=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$t}` WHERE pickup_date=%s",date('Y-m-d')));
    $s_paid=$wpdb->get_var("SELECT COUNT(*) FROM `{$t}` WHERE payment_status='paid'");
    $s_rev=(float)$wpdb->get_var("SELECT SUM(fare) FROM `{$t}` WHERE payment_status='paid'");
    $s_miles=(float)$wpdb->get_var("SELECT SUM(distance_miles) FROM `{$t}`");
    $s_lowrate=$wpdb->get_var("SELECT COUNT(*) FROM `{$t}` WHERE passenger_rating>0 AND passenger_rating<=2");
    ?>
    <div class="wrap">
    <h1 style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        🚖 Cab Orders
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="<?php echo admin_url('admin.php?page=cab-orders-add');?>" class="button button-primary">+ Add Order</a>
            <a href="<?php echo admin_url('admin.php?page=cab-orders-today');?>" class="button">📅 Today</a>
            <a href="<?php echo admin_url('admin.php?page=cab-orders-ratings');?>" class="button">⭐ Ratings</a>
            <a href="<?php echo admin_url('admin.php?page=cab-orders-export&do_export=1');?>" class="button">⬇️ CSV</a>
        </div>
    </h1>
    <?php if(isset($_GET['saved']))   echo '<div class="notice notice-success is-dismissible"><p>Order saved.</p></div>';?>
    <?php if(isset($_GET['deleted'])) echo '<div class="notice notice-success is-dismissible"><p>Order deleted.</p></div>';?>
    <?php if(isset($_GET['cal_ok']))  echo '<div class="notice notice-success is-dismissible"><p>✅ Calendar event created successfully.</p></div>';?>
    <?php if(isset($_GET['cal_fail']))echo '<div class="notice notice-error is-dismissible"><p>❌ Calendar retry failed. Check Calendar Auth under Superior Transport.</p></div>';?>

    <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:10px;margin:14px 0 18px">
        <div class="co-stat-box" style="border-left:4px solid #0d1f3c"><span class="co-stat-num" style="color:#0d1f3c"><?php echo $s_total;?></span><span class="co-stat-lbl">Total Orders</span></div>
        <div class="co-stat-box" style="border-left:4px solid #1565c0"><span class="co-stat-num" style="color:#1565c0"><?php echo $s_today;?></span><span class="co-stat-lbl">Today</span></div>
        <div class="co-stat-box" style="border-left:4px solid #2e7d32"><span class="co-stat-num" style="color:#2e7d32">$<?php echo number_format($s_rev,2);?></span><span class="co-stat-lbl">Revenue (paid)</span></div>
        <div class="co-stat-box" style="border-left:4px solid #c8a84b"><span class="co-stat-num" style="color:#c8a84b"><?php echo $s_paid;?></span><span class="co-stat-lbl">Paid Orders</span></div>
        <div class="co-stat-box" style="border-left:4px solid #555"><span class="co-stat-num" style="color:#555"><?php echo number_format($s_miles,1);?> mi</span><span class="co-stat-lbl">Total Miles</span></div>
        <div class="co-stat-box" style="border-left:4px solid #c62828"><span class="co-stat-num" style="color:#c62828"><?php echo $s_lowrate;?></span><span class="co-stat-lbl">Low Rated ≤2★</span></div>
    </div>

    <form method="get" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:14px;background:#f9f9f9;padding:10px 12px;border:1px solid #ddd;border-radius:4px">
        <input type="hidden" name="page" value="cab-orders">
        <input type="text" name="co_search" value="<?php echo esc_attr($search);?>" placeholder="Name, phone, code, address..." style="width:220px">
        <input type="date" name="co_date" value="<?php echo esc_attr($fdate);?>">
        <select name="co_status">
            <option value="">All Statuses</option>
            <?php foreach(['pending','confirmed','completed','cancelled'] as $s): ?>
            <option value="<?php echo $s;?>" <?php selected($fstatus,$s);?>><?php echo ucfirst($s);?></option>
            <?php endforeach;?>
        </select>
        <select name="co_payment">
            <option value="">All Payments</option>
            <option value="paid" <?php selected($fpay,'paid');?>>Paid</option>
            <option value="unpaid" <?php selected($fpay,'unpaid');?>>Unpaid</option>
        </select>
        <select name="co_rating">
            <option value="0">All Ratings</option>
            <?php for($i=1;$i<=5;$i++):?><option value="<?php echo $i;?>" <?php selected($frating,$i);?>><?php echo $i;?>★</option><?php endfor;?>
        </select>
        <button type="submit" class="button">Filter</button>
        <a href="<?php echo admin_url('admin.php?page=cab-orders');?>" class="button">Clear</a>
        <span style="margin-left:auto;color:#888;font-size:.82rem"><?php echo count($orders);?> orders</span>
    </form>

    <?php if(!$orders):?>
    <p style="color:#888;padding:24px;background:#f9f9f9;border-radius:4px;text-align:center">No orders found.</p>
    <?php else:?>
    <table class="wp-list-table widefat fixed" style="font-size:.8rem;border-collapse:collapse">
        <thead>
            <tr style="background:#0d1f3c;color:#fff">
                <th style="width:90px;color:#c8a84b;padding:10px 8px">Confirm #</th>
                <th style="width:120px;color:#fff;padding:10px 8px">Customer</th>
                <th style="width:80px;color:#fff;padding:10px 8px">📞 Phone</th>
                <th style="width:70px;color:#fff;padding:10px 8px">Date</th>
                <th style="width:55px;color:#fff;padding:10px 8px">Time</th>
                <th style="color:#fff;padding:10px 8px">📍 Pickup</th>
                <th style="color:#fff;padding:10px 8px">🏁 Dropoff</th>
                <th style="width:52px;color:#fff;padding:10px 8px">Miles</th>
                <th style="width:50px;color:#fff;padding:10px 8px">Pax</th>
                <th style="width:62px;color:#c8a84b;padding:10px 8px">💰 Fare</th>
                <th style="width:82px;color:#fff;padding:10px 8px">Payment</th>
                <th style="width:60px;color:#fff;padding:10px 8px">Paid?</th>
                <th style="width:75px;color:#fff;padding:10px 8px">⭐ Rating</th>
                <th style="width:72px;color:#fff;padding:10px 8px">Status</th>
                <th style="width:130px;color:#fff;padding:10px 8px">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($orders as $o):
            $is_today=$o->pickup_date===date('Y-m-d');
            $is_paid=$o->payment_status==='paid';
            $low_rated=$o->passenger_rating>0&&$o->passenger_rating<=2;
            $edit_url=admin_url('admin.php?page=cab-orders-add&edit='.$o->id);
            $del_url=wp_nonce_url(admin_url('admin.php?page=cab-orders&co_delete='.$o->id),'co_del_'.$o->id);
            $paid_url=wp_nonce_url(admin_url('admin.php?page=cab-orders&co_toggle_paid='.$o->id),'co_tp_'.$o->id);
            $stat_url=wp_nonce_url(admin_url('admin.php?page=cab-orders&co_toggle_status='.$o->id),'co_ts_'.$o->id);
            $retry_url=wp_nonce_url(admin_url('admin.php?page=cab-orders&co_retry_calendar='.$o->id),'co_rc_'.$o->id);
            $row_bg=$is_today?'#fff8e1':($low_rated?'#fff5f5':'#fff');
        ?>
        <tr style="background:<?php echo $row_bg;?>;border-bottom:1px solid #eee">
            <td style="padding:8px">
                <span class="co-conf-code"><?php echo esc_html($o->confirmation);?></span>
                <?php if($is_today):?><br><span style="color:#e65100;font-size:.65rem;font-weight:800">◆TODAY</span><?php endif;?>
                <?php if($low_rated):?><br><span style="color:#c62828;font-size:.65rem;font-weight:800">⚠ LOW</span><?php endif;?>
            </td>
            <td style="padding:8px"><strong><?php echo esc_html($o->customer_name);?></strong><br><span style="font-size:.75rem;color:#888"><?php echo esc_html($o->customer_email);?></span></td>
            <td style="padding:8px;font-weight:600;color:#1565c0"><a href="tel:<?php echo preg_replace('/\D/','',$o->customer_phone);?>"><?php echo esc_html($o->customer_phone?:'-');?></a></td>
            <td style="padding:8px;white-space:nowrap"><?php echo $o->pickup_date?date('M j',strtotime($o->pickup_date)):'—';?></td>
            <td style="padding:8px;font-weight:700;color:#0d1f3c"><?php echo co_format_time($o->pickup_time);?></td>
            <td style="padding:8px" class="co-route-cell">
                <span class="label">FROM</span>
                <span class="val"><?php echo esc_html($o->pickup?:'—');?></span>
            </td>
            <td style="padding:8px" class="co-route-cell">
                <span class="label">TO</span>
                <span class="val"><?php echo esc_html($o->dropoff?:'—');?></span>
            </td>
            <td style="padding:8px;text-align:center;font-weight:600"><?php echo $o->distance_miles?number_format($o->distance_miles,1).' mi':'—';?></td>
            <td style="padding:8px;text-align:center"><?php echo intval($o->passengers);?></td>
            <td style="padding:8px;font-weight:800;font-size:.92rem;color:#0d1f3c"><?php echo $o->fare?'$'.number_format($o->fare,2):'—';?></td>
            <td style="padding:8px"><?php echo co_pay_badge($o->payment_method);?></td>
            <td style="padding:8px;text-align:center">
                <a href="<?php echo esc_url($paid_url);?>" class="co-badge <?php echo $is_paid?'co-badge-paid':'co-badge-unpaid';?>" style="text-decoration:none;cursor:pointer">
                    <?php echo $is_paid?'✔ Paid':'Unpaid';?>
                </a>
            </td>
            <td style="padding:8px;text-align:center">
                <?php if($o->passenger_rating): echo co_stars_display($o->passenger_rating);
                else: ?><span style="color:#ccc;font-size:.75rem">—</span><?php endif;?>
            </td>
            <td style="padding:8px">
                <a href="<?php echo esc_url($stat_url);?>" class="co-badge co-badge-<?php echo $o->status;?>" style="text-decoration:none;cursor:pointer">
                    <?php echo ucfirst($o->status);?>
                </a>
            </td>
            <td style="padding:8px;white-space:nowrap">
                <a href="<?php echo esc_url($edit_url);?>" class="button button-small" title="Edit">✏️</a>
                <a href="<?php echo esc_url($del_url);?>" class="button button-small" style="color:#c00" title="Delete" onclick="return confirm('Delete?')">🗑️</a>
                <br><a href="<?php echo esc_url($retry_url);?>" class="co-retry-btn" style="margin-top:4px;display:inline-block" title="Retry Calendar">📅 Retry</a>
            </td>
        </tr>
        <?php if($o->notes):?>
        <tr style="background:<?php echo $row_bg;?>"><td colspan="15" style="padding:3px 8px 6px 16px;color:#888;font-size:.75rem;font-style:italic;border-bottom:2px solid #eee">📝 <?php echo esc_html($o->notes);?></td></tr>
        <?php endif;?>
        <?php endforeach;?>
        </tbody>
    </table>
    <?php endif;?>
    </div>
    <?php
}

/* ═══════════════════════════════════════
   PAGE: TODAY'S RIDES
═══════════════════════════════════════ */
function co_page_today(){
    global $wpdb;
    $t=$wpdb->prefix.'st_bookings';
    $today=date('Y-m-d');
    $orders=$wpdb->get_results($wpdb->prepare("SELECT * FROM `{$t}` WHERE pickup_date=%s ORDER BY pickup_time ASC",$today));
    ?>
    <div class="wrap">
    <h1>🚖 Today's Rides — <?php echo date('l, F j, Y');?></h1>
    <p style="color:#888;margin-bottom:20px"><?php echo count($orders);?> ride(s) scheduled today</p>
    <?php if(!$orders):?>
    <p style="color:#888;padding:30px;background:#f9f9f9;border-radius:8px;text-align:center;font-size:1.1rem">No rides scheduled for today.</p>
    <?php else: foreach($orders as $o):
        $is_paid=$o->payment_status==='paid';
        $paid_url=wp_nonce_url(admin_url('admin.php?page=cab-orders&co_toggle_paid='.$o->id),'co_tp_'.$o->id);
        $edit_url=admin_url('admin.php?page=cab-orders-add&edit='.$o->id);
        $retry_url=wp_nonce_url(admin_url('admin.php?page=cab-orders&co_retry_calendar='.$o->id),'co_rc_'.$o->id);
    ?>
    <div style="background:#fff;border:1px solid #ddd;border-left:6px solid <?php echo $is_paid?'#2e7d32':'#c8a84b';?>;border-radius:4px;padding:20px 24px;margin-bottom:14px">
        <div style="display:grid;grid-template-columns:100px 1fr 1fr 1fr auto;gap:16px;align-items:start;flex-wrap:wrap">
            <div style="text-align:center">
                <div style="font-size:1.8rem;font-weight:800;color:#0d1f3c;line-height:1"><?php echo co_format_time($o->pickup_time);?></div>
                <div style="margin-top:6px"><span class="co-conf-code"><?php echo esc_html($o->confirmation);?></span></div>
                <?php if($o->passenger_rating):?><div style="margin-top:6px"><?php echo co_stars_display($o->passenger_rating);?></div><?php endif;?>
            </div>
            <div>
                <div style="font-size:.68rem;text-transform:uppercase;color:#aaa;letter-spacing:.06em;margin-bottom:4px">Customer</div>
                <div style="font-weight:800;font-size:1rem"><?php echo esc_html($o->customer_name);?></div>
                <div style="color:#1565c0;font-weight:700;font-size:.92rem"><?php echo esc_html($o->customer_phone);?></div>
                <div style="color:#888;font-size:.8rem"><?php echo esc_html($o->customer_email);?></div>
                <div style="margin-top:4px;font-size:.78rem">👥 <?php echo $o->passengers;?> passenger<?php echo $o->passengers>1?'s':'';?></div>
            </div>
            <div>
                <div style="font-size:.68rem;text-transform:uppercase;color:#aaa;letter-spacing:.06em;margin-bottom:4px">Route<?php echo $o->distance_miles?' · '.number_format($o->distance_miles,1).' mi':'';?></div>
                <div style="font-size:.88rem;margin-bottom:6px"><strong style="color:#555">FROM:</strong><br><?php echo esc_html($o->pickup);?></div>
                <div style="font-size:.88rem"><strong style="color:#555">TO:</strong><br><?php echo esc_html($o->dropoff);?></div>
                <?php if($o->notes):?><div style="margin-top:8px;font-size:.78rem;color:#888;font-style:italic;background:#f9f9f9;padding:6px 8px;border-radius:3px">📝 <?php echo esc_html($o->notes);?></div><?php endif;?>
            </div>
            <div style="text-align:center">
                <div style="font-size:1.7rem;font-weight:800;color:#0d1f3c;margin-bottom:8px"><?php echo $o->fare?'$'.number_format($o->fare,2):'—';?></div>
                <div style="margin-bottom:10px"><?php echo co_pay_badge($o->payment_method);?></div>
                <a href="<?php echo esc_url($paid_url);?>" style="display:inline-block;padding:8px 16px;border-radius:4px;font-weight:800;font-size:.82rem;text-decoration:none;background:<?php echo $is_paid?'#2e7d32':'#e65100';?>;color:#fff">
                   <?php echo $is_paid?'✔ Paid':'Mark Paid';?>
                </a>
                <br><a href="<?php echo esc_url($retry_url);?>" class="co-retry-btn" style="margin-top:8px;display:inline-block">📅 Retry Calendar</a>
            </div>
            <div style="text-align:right">
                <a href="<?php echo esc_url($edit_url);?>" class="button">✏️ Edit / Rate</a>
            </div>
        </div>
    </div>
    <?php endforeach; endif;?>
    </div>
    <?php
}

/* ═══════════════════════════════════════
   PAGE: RATINGS
═══════════════════════════════════════ */
function co_page_ratings(){
    global $wpdb;
    $t=$wpdb->prefix.'st_bookings';
    $rated=$wpdb->get_results("SELECT * FROM `{$t}` WHERE passenger_rating>0 ORDER BY passenger_rating ASC,pickup_date DESC");
    $unrated=$wpdb->get_results("SELECT * FROM `{$t}` WHERE passenger_rating=0 ORDER BY pickup_date DESC LIMIT 30");
    $avg=$wpdb->get_var("SELECT AVG(passenger_rating) FROM `{$t}` WHERE passenger_rating>0");
    $low=$wpdb->get_var("SELECT COUNT(*) FROM `{$t}` WHERE passenger_rating>0 AND passenger_rating<=2");
    ?>
    <div class="wrap">
    <h1>⭐ Passenger Ratings</h1>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:14px 0 24px">
        <div class="co-stat-box" style="border-left:4px solid #f0c040"><span class="co-stat-num" style="color:#f0c040"><?php echo $avg?number_format($avg,1):'—';?>/5</span><span class="co-stat-lbl">Avg Rating</span></div>
        <div class="co-stat-box" style="border-left:4px solid #2e7d32"><span class="co-stat-num" style="color:#2e7d32"><?php echo count($rated);?></span><span class="co-stat-lbl">Rated</span></div>
        <div class="co-stat-box" style="border-left:4px solid #c62828"><span class="co-stat-num" style="color:#c62828"><?php echo $low;?></span><span class="co-stat-lbl">Low Rated ≤2★</span></div>
        <div class="co-stat-box" style="border-left:4px solid #888"><span class="co-stat-num" style="color:#888"><?php echo count($unrated);?></span><span class="co-stat-lbl">Unrated</span></div>
    </div>
    <?php
    $bad=array_filter($rated,fn($r)=>$r->passenger_rating<=2);
    if($bad):?>
    <div style="background:#fff5f5;border:2px solid #c62828;border-radius:6px;padding:16px 20px;margin-bottom:24px">
        <h3 style="margin:0 0 12px;color:#c62828">⚠️ Flagged Passengers (1–2 Stars)</h3>
        <table class="widefat striped" style="font-size:.82rem">
            <thead><tr><th>Name</th><th>Phone</th><th>Date</th><th>Rating</th><th>Note</th><th>Edit</th></tr></thead>
            <tbody>
            <?php foreach($bad as $o):?>
            <tr style="background:#fff8f8">
                <td><strong><?php echo esc_html($o->customer_name);?></strong></td>
                <td style="color:#1565c0;font-weight:700"><?php echo esc_html($o->customer_phone);?></td>
                <td><?php echo co_format_date($o->pickup_date);?></td>
                <td><?php echo co_stars_display($o->passenger_rating);?></td>
                <td style="color:#888;font-style:italic"><?php echo esc_html($o->rating_note?:'—');?></td>
                <td><a href="<?php echo admin_url('admin.php?page=cab-orders-add&edit='.$o->id);?>" class="button button-small">Edit</a></td>
            </tr>
            <?php endforeach;?>
            </tbody>
        </table>
    </div>
    <?php endif;?>
    <?php if($unrated):?>
    <h2>Rate Recent Rides</h2>
    <?php foreach($unrated as $o):?>
    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:14px 18px;margin-bottom:10px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <div style="flex:0 0 80px"><span class="co-conf-code"><?php echo esc_html($o->confirmation);?></span><br><span style="font-size:.75rem;color:#888"><?php echo co_format_date($o->pickup_date);?></span></div>
        <div style="flex:0 0 140px"><strong><?php echo esc_html($o->customer_name);?></strong><br><span style="color:#1565c0;font-size:.85rem;font-weight:700"><?php echo esc_html($o->customer_phone);?></span></div>
        <div style="flex:1;font-size:.8rem;color:#555"><?php echo esc_html(substr($o->pickup,0,30));?> → <?php echo esc_html(substr($o->dropoff,0,30));?></div>
        <form method="post" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <?php wp_nonce_field('co_qr_'.$o->id);?>
            <input type="hidden" name="co_quick_rate" value="1">
            <input type="hidden" name="co_id" value="<?php echo $o->id;?>">
            <?php echo co_stars_input('passenger_rating',0);?>
            <input type="text" name="rating_note" placeholder="Optional note..." style="width:180px;font-size:.82rem">
            <button type="submit" class="button button-primary button-small">Save Rating</button>
        </form>
    </div>
    <?php endforeach;?>
    <?php endif;?>
    </div>
    <?php
}

/* ═══════════════════════════════════════
   PAGE: ADD / EDIT ORDER
═══════════════════════════════════════ */
function co_page_add(){
    global $wpdb;
    $t=$wpdb->prefix.'st_bookings';
    $eid=intval($_GET['edit']??0);
    $o=$eid?$wpdb->get_row($wpdb->prepare("SELECT * FROM `{$t}` WHERE id=%d",$eid)):null;
    $gv=fn($f,$d='')=>esc_attr($o?($o->$f??$d):$d);

    if(isset($_GET['cal_ok']))   echo '<div class="notice notice-success"><p>✅ Calendar event created.</p></div>';
    if(isset($_GET['cal_fail'])) echo '<div class="notice notice-error"><p>❌ Calendar retry failed. Check Superior Transport > Calendar Auth.</p></div>';
    ?>
    <div class="wrap" style="max-width:880px">
    <h1><?php echo $o?'✏️ Edit Order — <span class="co-conf-code">'.esc_html($o->confirmation).'</span>':'➕ Add New Cab Order';?></h1>
    <?php if($o):?>
    <div style="background:#f0f8ff;border:1px solid #90caf9;border-radius:6px;padding:10px 16px;margin-bottom:20px;font-size:.88rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <span>Confirmation: <span class="co-conf-code"><?php echo esc_html($o->confirmation);?></span> &nbsp;|&nbsp; Created: <?php echo esc_html($o->created_at);?></span>
        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cab-orders&co_retry_calendar='.$o->id),'co_rc_'.$o->id);?>" class="co-retry-btn">📅 Retry Calendar</a>
    </div>
    <?php endif;?>

    <form method="post">
        <?php wp_nonce_field('co_save_order');?>
        <input type="hidden" name="co_save" value="1">
        <input type="hidden" name="co_id" value="<?php echo $eid;?>">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <div class="co-card">
                <h3>👤 Customer</h3>
                <p><label><strong>Full Name *</strong><br><input name="customer_name" required class="regular-text" value="<?php echo $gv('customer_name');?>"></label></p>
                <p><label><strong>Phone</strong><br><input name="customer_phone" class="regular-text" value="<?php echo $gv('customer_phone');?>" placeholder="906-555-0100"></label></p>
                <p><label><strong>Email</strong><br><input name="customer_email" type="email" class="regular-text" value="<?php echo $gv('customer_email');?>"></label></p>
                <p><label><strong>Passengers</strong><br><input name="passengers" type="number" min="1" max="20" style="width:80px" value="<?php echo $gv('passengers','1');?>"></label></p>
            </div>
            <div class="co-card">
                <h3>📅 Ride Details</h3>
                <p><label><strong>Pickup Date *</strong><br><input name="pickup_date" type="date" required value="<?php echo $gv('pickup_date');?>"></label></p>
                <p><label><strong>Pickup Time</strong><br><input name="pickup_time" type="time" value="<?php echo $gv('pickup_time');?>"></label>
                <?php if($o && $o->pickup_time):?> <em style="color:#666;font-size:.85rem">(<?php echo co_format_time($o->pickup_time);?>)</em><?php endif;?></p>
                <p><label><strong>Distance (miles)</strong><br><input name="distance_miles" type="number" step="0.1" min="0" style="width:100px" value="<?php echo $gv('distance_miles','0');?>"> mi</label></p>
            </div>
        </div>

        <div class="co-card" style="margin-bottom:16px">
            <h3>📍 Route</h3>
            <p><label><strong>Pickup Location *</strong><br><input name="pickup" required class="large-text" value="<?php echo $gv('pickup');?>" placeholder="1002 2nd St, Hancock MI"></label></p>
            <p><label><strong>Dropoff Location *</strong><br><input name="dropoff" required class="large-text" value="<?php echo $gv('dropoff');?>" placeholder="Houghton County Airport"></label></p>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <div class="co-card">
                <h3>💰 Payment</h3>
                <p><label><strong>Fare ($)</strong><br><input name="fare" type="number" step="0.01" min="0" style="width:110px" value="<?php echo $gv('fare','0');?>"></label></p>
                <p><label><strong>Payment Method</strong><br>
                <select name="payment_method" style="width:100%">
                    <?php foreach(['' => '— Select —','Cash'=>'Cash','Credit Card'=>'Credit Card','Square'=>'Square','Venmo'=>'Venmo','Other'=>'Other'] as $v=>$l):?>
                    <option value="<?php echo $v;?>" <?php selected($gv('payment_method'),$v);?>><?php echo $l;?></option>
                    <?php endforeach;?>
                </select></label></p>
                <p><label><strong>Payment Status</strong><br>
                <select name="payment_status" style="width:100%">
                    <option value="unpaid" <?php selected($gv('payment_status','unpaid'),'unpaid');?>>Unpaid</option>
                    <option value="paid" <?php selected($gv('payment_status'),'paid');?>>Paid</option>
                </select></label></p>
                <p><label><strong>Square Receipt #</strong><br><input name="square_receipt" class="regular-text" value="<?php echo $gv('square_receipt');?>" placeholder="Optional"></label></p>
            </div>
            <div class="co-card">
                <h3>⭐ Rating &amp; Status</h3>
                <p><strong>Star Rating</strong><br><?php echo co_stars_input('passenger_rating',intval($o->passenger_rating??0));?>
                <label style="margin-top:8px;display:block"><input type="radio" name="passenger_rating" value="0" <?php checked(intval($o->passenger_rating??0),0);?>> Clear rating</label></p>
                <p><label><strong>Rating Note</strong><br><input name="rating_note" class="large-text" value="<?php echo $gv('rating_note');?>" placeholder="e.g. Rude, great tipper..."></label></p>
                <p><label><strong>Order Status</strong><br>
                <select name="status" style="width:100%">
                    <?php foreach(['pending','confirmed','completed','cancelled'] as $s):?>
                    <option value="<?php echo $s;?>" <?php selected($gv('status','pending'),$s);?>><?php echo ucfirst($s);?></option>
                    <?php endforeach;?>
                </select></label></p>
            </div>
        </div>

        <div class="co-card" style="margin-bottom:20px">
            <h3>📝 Notes</h3>
            <textarea name="notes" rows="3" class="large-text" placeholder="Internal notes..."><?php echo esc_textarea($o->notes??'');?></textarea>
        </div>

        <p>
            <input type="submit" class="button button-primary button-large" value="<?php echo $o?'💾 Update Order':'➕ Create Order';?>">
            &nbsp;<a href="<?php echo admin_url('admin.php?page=cab-orders');?>" class="button button-large">Cancel</a>
        </p>
    </form>
    </div>
    <?php
}

function co_page_export(){?>
    <div class="wrap">
    <h1>📊 Export Cab Orders</h1>
    <p>Download all orders as CSV — includes every field.</p>
    <a href="<?php echo admin_url('admin.php?page=cab-orders-export&do_export=1');?>" class="button button-primary button-large">⬇️ Download CSV</a>
    </div>
    <?php
}
