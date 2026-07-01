<?php
if ( ! defined( 'ABSPATH' ) ) exit;

define('ST_GCAL_CLIENT_ID',     get_option('st_gcal_client_id', ''));
define('ST_GCAL_CLIENT_SECRET', get_option('st_gcal_client_secret', ''));
define('ST_GCAL_CALENDAR_ID',   'driversstalco@gmail.com');
define('ST_GCAL_REDIRECT',      'https://asuperiortransportation.com/wp-admin/admin.php?page=st-google-calendar');

function st_gcal_get_token() { return get_option('st_gcal_token', []); }
function st_gcal_save_token($token) { update_option('st_gcal_token', $token); }

function st_gcal_refresh_access_token() {
    $token = st_gcal_get_token();
    if ( empty($token['refresh_token']) ) return false;
    $response = wp_remote_post('https://oauth2.googleapis.com/token', ['body' => [
        'client_id'     => ST_GCAL_CLIENT_ID,
        'client_secret' => ST_GCAL_CLIENT_SECRET,
        'refresh_token' => $token['refresh_token'],
        'grant_type'    => 'refresh_token',
    ]]);
    if ( is_wp_error($response) ) return false;
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if ( empty($data['access_token']) ) return false;
    $token['access_token'] = $data['access_token'];
    $token['expires_at']   = time() + intval($data['expires_in'] ?? 3600);
    st_gcal_save_token($token);
    return $token['access_token'];
}

function st_gcal_get_access_token() {
    $token = st_gcal_get_token();
    if ( empty($token['access_token']) ) return false;
    if ( !empty($token['expires_at']) && $token['expires_at'] < time() + 60 ) return st_gcal_refresh_access_token();
    return $token['access_token'];
}

/* -------------------------------------------------------
   CALENDAR EVENT — NOW WITH FULL PICKUP/DROPOFF DETAILS
------------------------------------------------------- */
function st_gcal_create_event($name, $phone, $pickup, $dropoff, $date, $time, $fare, $notes, $passengers = 1, $email = '', $miles = 0) {
    $access_token = st_gcal_get_access_token() ?: st_gcal_refresh_access_token();
    if (!$access_token) return false;

    $dt       = $date && $time ? $date.'T'.$time.':00' : date('Y-m-d\TH:i:s');
    // 3 min/mile rule; minimum 30 min
    $duration_min = $miles > 0 ? max(30, (int) ceil($miles * 3)) : 60;
    $end      = date('Y-m-d\TH:i:s', strtotime($dt) + ($duration_min * 60));
    $date_fmt = date('l, F j, Y', strtotime($date ?: 'today'));
    $time_fmt = $time ? date('g:i A', strtotime($dt)) : '—';

    $description  = "=== A SUPERIOR TRANSPORTATION ===\n\n";
    $description .= "PICKUP:   {$pickup}\n";
    $description .= "DROPOFF:  {$dropoff}\n\n";
    $description .= "CUSTOMER: {$name}\n";
    $description .= "PHONE:    {$phone}\n";
    if ($email) $description .= "EMAIL:    {$email}\n";
    $description .= "PASSENGERS: {$passengers}\n";
    $description .= "FARE:     \${$fare}\n";
    $description .= "DATE:     {$date_fmt} at {$time_fmt}\n";
    if ($notes) $description .= "\nNOTES: {$notes}\n";
    $description .= "\nCall to confirm: 906-370-4094";

    $event = [
        'summary'     => "RIDE: {$name} | {$time_fmt} | {$pickup} → " . (strlen($dropoff) > 30 ? substr($dropoff,0,30).'…' : $dropoff),
        'location'    => $pickup,
        'description' => $description,
        'start'       => ['dateTime' => $dt,  'timeZone' => 'America/Detroit'],
        'end'         => ['dateTime' => $end, 'timeZone' => 'America/Detroit'],
        'colorId'     => '2',
    ];

    $response = wp_remote_post(
        'https://www.googleapis.com/calendar/v3/calendars/'.urlencode(ST_GCAL_CALENDAR_ID).'/events',
        [
            'headers' => [
                'Authorization' => 'Bearer '.$access_token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => json_encode($event),
            'timeout' => 20,
        ]
    );

    if (is_wp_error($response)) return false;
    $data = json_decode(wp_remote_retrieve_body($response), true);
    return !empty($data['id']) ? $data['id'] : false;
}

/* -------------------------------------------------------
   CALENDAR AUTH PAGE — WITH OPEN CALENDAR LINK
------------------------------------------------------- */
add_action('admin_menu', function(){
    add_submenu_page('st-transport','Calendar Auth','📅 Calendar Auth','manage_options','st-google-calendar','st_gcal_auth_page');
});

function st_gcal_auth_page() {
    if (isset($_GET['code'])) {
        $response = wp_remote_post('https://oauth2.googleapis.com/token', ['body' => [
            'code'          => sanitize_text_field($_GET['code']),
            'client_id'     => ST_GCAL_CLIENT_ID,
            'client_secret' => ST_GCAL_CLIENT_SECRET,
            'redirect_uri'  => ST_GCAL_REDIRECT,
            'grant_type'    => 'authorization_code',
        ]]);
        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($data['access_token'])) {
                st_gcal_save_token([
                    'access_token'  => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? '',
                    'expires_at'    => time() + intval($data['expires_in'] ?? 3600),
                ]);
                echo '<div class="notice notice-success"><p><strong>Google Calendar connected.</strong> Bookings will now post with full pickup/dropoff details.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Auth failed: '.esc_html(json_encode($data)).'</p></div>';
            }
        }
    }
    if (isset($_GET['disconnect']) && check_admin_referer('st_gcal_disconnect')) {
        delete_option('st_gcal_token');
        echo '<div class="notice notice-success"><p>Disconnected.</p></div>';
    }
    $token     = st_gcal_get_token();
    $connected = !empty($token['refresh_token']);
    $auth_url  = 'https://accounts.google.com/o/oauth2/auth?'.http_build_query([
        'client_id'     => ST_GCAL_CLIENT_ID,
        'redirect_uri'  => ST_GCAL_REDIRECT,
        'response_type' => 'code',
        'scope'         => 'https://www.googleapis.com/auth/calendar.events',
        'access_type'   => 'offline',
        'prompt'        => 'consent',
    ]);
    ?>
    <div class="wrap">
    <h1>📅 Google Calendar Authorization</h1>

    <p style="margin:12px 0">
        <a href="https://calendar.google.com/calendar/r" target="_blank" class="button button-primary" style="background:#1a73e8;border-color:#1a73e8;color:#fff;font-weight:700;padding:8px 18px">
            📅 Open Google Calendar
        </a>
    </p>

    <?php if ($connected): ?>
        <div style="background:#d4edda;border:1px solid #28a745;padding:15px 20px;border-radius:4px;margin:16px 0;max-width:600px">
            <strong style="color:#155724">✅ Connected.</strong> Bookings post to driversstalco@gmail.com with full pickup/dropoff details.
        </div>
        <p>
            <a href="<?php echo esc_url($auth_url);?>" class="button">Re-authorize</a>
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=st-google-calendar&disconnect=1'),'st_gcal_disconnect');?>" class="button" style="color:red" onclick="return confirm('Disconnect?')">Disconnect</a>
        </p>
    <?php else: ?>
        <div style="background:#fff3cd;border:1px solid #ffc107;padding:15px 20px;border-radius:4px;margin:16px 0;max-width:600px">
            <strong>Not connected.</strong> Bookings save to database and email only.
        </div>
        <p>Log in with <strong>driversstalco@gmail.com</strong> when prompted.</p>
        <a href="<?php echo esc_url($auth_url);?>" class="button button-primary button-large">Connect Google Calendar</a>
    <?php endif; ?>

    <hr style="margin:28px 0;max-width:600px">
    <h3>Test Connection</h3>
    <p><a href="<?php echo wp_nonce_url(admin_url('admin.php?page=st-google-calendar&test=1'),'st_gcal_test');?>" class="button">Send Test Event</a></p>
    <?php
    if (isset($_GET['test']) && check_admin_referer('st_gcal_test')) {
        $result = st_gcal_create_event(
            'Test Customer', '906-370-4094',
            '1002 2nd St, Hancock, MI',
            'Houghton County Memorial Airport, Houghton, MI',
            date('Y-m-d'), date('H:i'),
            '15.00', 'Test booking — verifying pickup/dropoff display.',
            2, 'test@test.com'
        );
        if ($result) {
            echo '<div class="notice notice-success" style="margin-top:10px"><p>✅ Test event created. <a href="https://calendar.google.com/calendar/r" target="_blank">Open Google Calendar</a> and check the event for full details.</p></div>';
        } else {
            echo '<div class="notice notice-error" style="margin-top:10px"><p>❌ Test failed. Click Connect Google Calendar above.</p></div>';
        }
    }
    echo '</div>';
}

/* -------------------------------------------------------
   BOOKING AJAX HANDLER
------------------------------------------------------- */
add_action('wp_ajax_st_book_ride',        'st_book_ride_handler');
add_action('wp_ajax_nopriv_st_book_ride', 'st_book_ride_handler');
function st_book_ride_handler(){
    check_ajax_referer('st_nonce','nonce');
    $name       = sanitize_text_field($_POST['name']       ?? '');
    $phone      = sanitize_text_field($_POST['phone']      ?? '');
    $email      = sanitize_email($_POST['email']           ?? '');
    $pickup     = sanitize_text_field($_POST['pickup']     ?? '');
    $dropoff    = sanitize_text_field($_POST['dropoff']    ?? '');
    $date       = sanitize_text_field($_POST['date']       ?? '');
    $time       = sanitize_text_field($_POST['time']       ?? '');
    $passengers = intval($_POST['passengers']              ?? 1);
    $notes      = sanitize_textarea_field($_POST['notes']  ?? '');
    $distance   = floatval($_POST['distance']              ?? 0);
    $fare       = floatval($_POST['fare']                  ?? 0);
    $coupon     = sanitize_text_field($_POST['coupon']     ?? '');
    $payment_id = sanitize_text_field($_POST['payment_id'] ?? '');

    if (!$name || !$phone || !$pickup || !$dropoff) {
        wp_send_json_error(['message' => 'Please fill in all required fields.']);
    }

    $s = st_get_settings();

    $discount = 0;
    if ($coupon) {
        $cv = st_validate_coupon($coupon, $fare);
        if ($cv['valid']) {
            $discount = $cv['discount'];
            $fare     = $cv['new_fare'];
            st_increment_coupon_use($coupon);
        }
    }

    $subject = "New Ride Booking: {$name}";
    $body    = "Name: {$name}\nPhone: {$phone}\nEmail: {$email}\nDate: {$date}\nTime: {$time}\nPassengers: {$passengers}\nPickup: {$pickup}\nDropoff: {$dropoff}\nDistance: {$distance} miles\nFare: \${$fare}\nCoupon: {$coupon}\nDiscount: \${$discount}\nPayment ID: {$payment_id}\nNotes: {$notes}";
    wp_mail($s['email'], $subject, $body);

    global $wpdb;
    $t    = $wpdb->prefix . 'st_bookings';
    $cols = $wpdb->get_col("DESCRIBE `{$t}`");
    $data = [
        'customer_name'  => $name,
        'customer_phone' => $phone,
        'customer_email' => $email,
        'pickup'         => $pickup,
        'dropoff'        => $dropoff,
        'pickup_date'    => $date,
        'pickup_time'    => $time,
        'passengers'     => $passengers,
        'distance_miles' => $distance,
        'fare'           => $fare,
        'notes'          => $notes,
        'payment_method' => $payment_id ? 'square' : 'cash',
        'payment_status' => $payment_id ? 'paid' : 'unpaid',
        'square_receipt' => $payment_id,
        'status'         => 'pending',
        'created_at'     => current_time('mysql'),
        'confirmation'   => strtoupper(substr(md5(uniqid(rand(),true)),0,8)),
    ];
    if (!in_array('customer_name', $cols)) {
        unset($data['customer_phone'], $data['customer_email']);
        $data['phone'] = $phone;
    }
    $wpdb->insert($t, array_intersect_key($data, array_flip($cols)));

    // Fire calendar with full details + distance for 3-min/mile duration
    st_gcal_create_event($name, $phone, $pickup, $dropoff, $date, $time, $fare, $notes, $passengers, $email, $distance);

    wp_send_json_success(['message' => "Booking confirmed. We will call {$phone} shortly to confirm your ride."]);
}

/* -------------------------------------------------------
   AVAILABILITY CHECK — returns busy windows for a date
------------------------------------------------------- */
add_action('wp_ajax_st_check_availability',        'st_check_availability_handler');
add_action('wp_ajax_nopriv_st_check_availability', 'st_check_availability_handler');
function st_check_availability_handler() {
    check_ajax_referer('st_nonce', 'nonce');

    $date = sanitize_text_field($_POST['date'] ?? '');
    if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        wp_send_json_error(['message' => 'Invalid date.']);
    }

    $access_token = st_gcal_get_access_token() ?: st_gcal_refresh_access_token();
    if (!$access_token) {
        // No calendar connected — return empty (no blocks)
        wp_send_json_success(['busy' => []]);
    }

    $time_min = $date . 'T00:00:00-05:00'; // Eastern
    $time_max = $date . 'T23:59:59-05:00';

    $url = add_query_arg([
        'timeMin'      => $time_min,
        'timeMax'      => $time_max,
        'singleEvents' => 'true',
        'orderBy'      => 'startTime',
    ], 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode(ST_GCAL_CALENDAR_ID) . '/events');

    $response = wp_remote_get($url, [
        'headers' => ['Authorization' => 'Bearer ' . $access_token],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        wp_send_json_success(['busy' => []]); // Fail open
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $busy = [];

    if (!empty($data['items'])) {
        foreach ($data['items'] as $event) {
            $start = $event['start']['dateTime'] ?? '';
            $end   = $event['end']['dateTime']   ?? '';
            if ($start && $end) {
                $busy[] = [
                    'start' => date('H:i', strtotime($start)),
                    'end'   => date('H:i', strtotime($end)),
                    'title' => $event['summary'] ?? 'Booked',
                ];
            }
        }
    }

    wp_send_json_success(['busy' => $busy]);
}

/* -------------------------------------------------------
   TABLE CREATE
------------------------------------------------------- */
register_activation_hook(ST_DIR.'superior-transport.php', 'st_create_bookings_table');
function st_create_bookings_table(){
    global $wpdb;
    $t  = $wpdb->prefix.'st_bookings';
    $cc = $wpdb->get_charset_collate();
    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta("CREATE TABLE IF NOT EXISTS `{$t}` (
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
        notes            TEXT,
        status           VARCHAR(40)   DEFAULT 'pending',
        created_at       DATETIME      DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) {$cc};");
}

/* -------------------------------------------------------
   SQUARE PAYMENT CHARGE HANDLER
------------------------------------------------------- */
add_action('wp_ajax_st_charge_square',        'st_charge_square_handler');
add_action('wp_ajax_nopriv_st_charge_square', 'st_charge_square_handler');
function st_charge_square_handler(){
    check_ajax_referer('st_nonce','nonce');
    $token  = sanitize_text_field($_POST['token']  ?? '');
    $amount = intval($_POST['amount'] ?? 0);
    $note   = sanitize_text_field($_POST['note']   ?? 'Ride payment');

    if (!$token || $amount <= 0) {
        wp_send_json_error(['message' => 'Invalid payment data.']);
    }

    $s            = st_get_settings();
    $access_token = $s['square_token']    ?? '';
    $location_id  = $s['square_location'] ?? '';

    if (!$access_token || !$location_id) {
        wp_send_json_error(['message' => 'Payment not configured. Please call us to pay.']);
    }

    $body = [
        'source_id'       => $token,
        'idempotency_key' => uniqid('st_', true),
        'amount_money'    => ['amount' => $amount, 'currency' => 'USD'],
        'location_id'     => $location_id,
        'note'            => $note,
    ];

    $response = wp_remote_post('https://connect.squareup.com/v2/payments', [
        'headers' => [
            'Authorization'  => 'Bearer '.$access_token,
            'Content-Type'   => 'application/json',
            'Square-Version' => '2024-01-18',
        ],
        'body'    => json_encode($body),
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Payment connection failed. Please call us.']);
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($data['payment']['id']) && $data['payment']['status'] === 'COMPLETED') {
        wp_send_json_success(['payment_id' => $data['payment']['id'], 'message' => 'Payment successful.']);
    } else {
        $err = $data['errors'][0]['detail'] ?? 'Payment declined.';
        wp_send_json_error(['message' => $err]);
    }
}
