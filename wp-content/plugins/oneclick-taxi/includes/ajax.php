<?php
/* OneClick Taxi — AJAX Handlers */
if ( ! defined( 'ABSPATH' ) ) exit;

/* Flat rates endpoint */
add_action('wp_ajax_oct_get_flat_rates',        'oct_ajax_get_flat_rates');
add_action('wp_ajax_nopriv_oct_get_flat_rates', 'oct_ajax_get_flat_rates');
function oct_ajax_get_flat_rates() {
    $rates = get_option('oct_flat_rates', []);
    $out = [];
    foreach($rates as $zone => $destinations) {
        foreach((array)$destinations as $d) {
            if(!empty($d['active'])) {
                $out[] = ['name'=>$d['name'],'address'=>$d['address'],'price'=>floatval($d['price']),'block'=>$zone];
            }
        }
    }
    wp_send_json_success($out);
}

/* Book ride */
add_action('wp_ajax_oct_book_ride',        'oct_book_ride_handler');
add_action('wp_ajax_nopriv_oct_book_ride', 'oct_book_ride_handler');
function oct_book_ride_handler() {
    if(!check_ajax_referer('oct_nonce','nonce',false)) wp_send_json_error('Security check failed.');
    $s          = oct_get_settings();
    $name       = sanitize_text_field($_POST['name']       ?? '');
    $phone      = sanitize_text_field($_POST['phone']      ?? '');
    $email      = sanitize_email($_POST['email']           ?? '');
    $pickup     = sanitize_text_field($_POST['pickup']     ?? '');
    $dropoff    = sanitize_text_field($_POST['dropoff']    ?? '');
    $date       = sanitize_text_field($_POST['date']       ?? '');
    $time       = sanitize_text_field($_POST['time']       ?? '');
    $passengers = intval($_POST['passengers']              ?? 1);
    $fare       = floatval($_POST['fare']                  ?? 0);
    $distance   = floatval($_POST['distance']              ?? 0);
    $payment_id = sanitize_text_field($_POST['payment_id'] ?? '');
    $notes      = sanitize_textarea_field($_POST['notes']  ?? '');

    if(!$name || !$phone || !$pickup || !$dropoff || !$date || !$time)
        wp_send_json_error(['message'=>'Please fill in all required fields.']);

    /* Save to DB */
    global $wpdb;
    $table = $wpdb->prefix.'oct_bookings';
    $wpdb->insert($table, [
        'customer_name'   => $name,
        'customer_phone'  => $phone,
        'customer_email'  => $email,
        'pickup'          => $pickup,
        'dropoff'         => $dropoff,
        'ride_date'       => $date,
        'ride_time'       => $time,
        'passengers'      => $passengers,
        'fare'            => $fare,
        'distance_miles'  => $distance,
        'payment_id'      => $payment_id,
        'payment_method'  => $payment_id ? 'square' : 'cash',
        'notes'           => $notes,
        'created_at'      => current_time('mysql'),
    ]);

    /* Calendar event */
    oct_gcal_create_event($name,$phone,$pickup,$dropoff,$date,$time,$fare,$notes,$passengers,$email,$distance);

    /* Emails */
    $subject = "New Ride Booking: {$name} | {$date} {$time}";
    $body = "PICKUP:     {$pickup}\nDROPOFF:    {$dropoff}\nCUSTOMER:   {$name}\nPHONE:      {$phone}\nPASSENGERS: {$passengers}\nFARE:       \${$fare}\nDATE:       {$date} at {$time}\nPAYMENT:    {$payment_id}\nNOTES:      {$notes}";

    if($s['email']) wp_mail($s['email'], $subject, $body);

    /* Pushover */
    if($s['pushover_email']) {
        $short = "RIDE: {$name} | {$phone}\n{$pickup} to {$dropoff}\n{$passengers}pax | \${$fare} | {$date} {$time}";
        wp_mail($s['pushover_email'], "RIDE: {$name} | {$date} {$time}", $short);
    }

    wp_send_json_success(['message'=>"Booking confirmed! We will contact you at {$phone} to confirm your ride."]);
}

/* Charge Square */
add_action('wp_ajax_oct_charge_square',        'oct_charge_square_handler');
add_action('wp_ajax_nopriv_oct_charge_square', 'oct_charge_square_handler');
function oct_charge_square_handler() {
    if(!check_ajax_referer('oct_nonce','nonce',false)) wp_send_json_error('Security check failed.');
    $s      = oct_get_settings();
    $token  = sanitize_text_field($_POST['token']  ?? '');
    $amount = intval($_POST['amount']              ?? 0);
    if(!$token || !$amount) wp_send_json_error(['message'=>'Invalid payment data.']);

    $response = wp_remote_post('https://connect.squareup.com/v2/payments', [
        'headers' => [
            'Authorization' => 'Bearer '.$s['square_token'],
            'Content-Type'  => 'application/json',
            'Square-Version'=> '2024-01-18',
        ],
        'body' => json_encode([
            'source_id'       => $token,
            'amount_money'    => ['amount'=>$amount,'currency'=>'USD'],
            'location_id'     => $s['square_location'],
            'idempotency_key' => uniqid('oct_',true),
            'note'            => sanitize_text_field($_POST['note'] ?? 'Ride booking'),
        ]),
    ]);

    if(is_wp_error($response)) wp_send_json_error(['message'=>'Payment service unavailable.']);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if(isset($body['payment']['id'])) {
        wp_send_json_success(['payment_id'=>$body['payment']['id']]);
    } else {
        $msg = $body['errors'][0]['detail'] ?? 'Payment failed.';
        wp_send_json_error(['message'=>$msg]);
    }
}

/* Create DB table on activation */
register_activation_hook(OCT_DIR.'oneclick-taxi.php', 'oct_create_tables');
function oct_create_tables() {
    global $wpdb;
    $table   = $wpdb->prefix.'oct_bookings';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        customer_name   VARCHAR(100) DEFAULT '',
        customer_phone  VARCHAR(30)  DEFAULT '',
        customer_email  VARCHAR(100) DEFAULT '',
        pickup          TEXT         DEFAULT '',
        dropoff         TEXT         DEFAULT '',
        ride_date       DATE,
        ride_time       VARCHAR(10)  DEFAULT '',
        passengers      INT          DEFAULT 1,
        fare            DECIMAL(8,2) DEFAULT 0,
        distance_miles  DECIMAL(6,1) DEFAULT 0,
        payment_id      VARCHAR(100) DEFAULT '',
        payment_method  VARCHAR(20)  DEFAULT 'cash',
        notes           TEXT         DEFAULT '',
        created_at      DATETIME
    ) {$charset};";
    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
