<?php
/* OneClick Taxi — Google Calendar Integration */
if ( ! defined( 'ABSPATH' ) ) exit;

function oct_gcal_get_token() { return get_option('oct_gcal_token', []); }
function oct_gcal_save_token($t) { update_option('oct_gcal_token', $t); }

function oct_gcal_refresh_token() {
    $token = oct_gcal_get_token();
    if(empty($token['refresh_token'])) return false;
    $r = wp_remote_post('https://oauth2.googleapis.com/token', ['body'=>[
        'client_id'     => get_option('oct_gcal_client_id'),
        'client_secret' => get_option('oct_gcal_client_secret'),
        'refresh_token' => $token['refresh_token'],
        'grant_type'    => 'refresh_token',
    ]]);
    if(is_wp_error($r)) return false;
    $data = json_decode(wp_remote_retrieve_body($r), true);
    if(isset($data['access_token'])) {
        $token['access_token'] = $data['access_token'];
        $token['expires_at']   = time() + intval($data['expires_in'] ?? 3600) - 60;
        oct_gcal_save_token($token);
        return $data['access_token'];
    }
    return false;
}

function oct_gcal_get_access_token() {
    $token = oct_gcal_get_token();
    if(empty($token['access_token'])) return false;
    if(time() >= intval($token['expires_at'] ?? 0)) return oct_gcal_refresh_token();
    return $token['access_token'];
}

function oct_gcal_create_event($name,$phone,$pickup,$dropoff,$date,$time,$fare,$notes,$passengers=1,$email='',$distance=0) {
    $access = oct_gcal_get_access_token();
    if(!$access) return false;

    $s            = oct_get_settings();
    $calendar_id  = $s['gcal_id'] ?? get_option('oct_gcal_client_id','');
    $dt           = $date && $time ? $date.'T'.$time.':00' : date('Y-m-d\TH:i:s');

    /* Drive-time duration: round trip at 30mph, round up to 5 min, min 20 */
    $one_way  = $distance > 0 ? ($distance/30)*60 : 30;
    $rounded  = ceil(($one_way*2)/5)*5;
    $duration = max(20, $rounded);
    $end      = date('Y-m-d\TH:i:s', strtotime($dt) + ($duration*60));

    $title = "RIDE: {$name} | {$time} | {$pickup} → {$dropoff}";
    $desc  = "=== ONECLICK TAXI ===\n\nPICKUP:    {$pickup}\nDROPOFF:   {$dropoff}\nCUSTOMER:  {$name}\nPHONE:     {$phone}\nPASSENGERS:{$passengers}\nFARE:      \${$fare}\nDURATION:  {$duration} min\nDATE:      ".date('l, F j, Y \a\t g:i A', strtotime($dt))."\n\nCall to confirm: ".$s['phone'];

    $timezone = get_option('timezone_string') ?: 'America/Chicago';
    $body = [
        'summary'     => $title,
        'description' => $desc,
        'location'    => $pickup,
        'start'       => ['dateTime'=>$dt, 'timeZone'=>$timezone],
        'end'         => ['dateTime'=>$end,'timeZone'=>$timezone],
        'reminders'   => ['useDefault'=>false,'overrides'=>[['method'=>'popup','minutes'=>31]]],
    ];

    wp_remote_post(
        'https://www.googleapis.com/calendar/v3/calendars/'.urlencode($calendar_id).'/events',
        ['headers'=>['Authorization'=>"Bearer {$access}",'Content-Type'=>'application/json'],'body'=>json_encode($body),'timeout'=>10]
    );
}

/* Admin page for Calendar OAuth */
function oct_gcal_admin_page() {
    $redirect = admin_url('admin.php?page=oct-calendar');
    $client_id     = get_option('oct_gcal_client_id','');
    $client_secret = get_option('oct_gcal_client_secret','');
    $token         = oct_gcal_get_token();
    $connected     = !empty($token['access_token']);

    /* Handle OAuth callback */
    if(isset($_GET['code']) && $client_id && $client_secret) {
        $r = wp_remote_post('https://oauth2.googleapis.com/token',['body'=>[
            'code'          => sanitize_text_field($_GET['code']),
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri'  => $redirect,
            'grant_type'    => 'authorization_code',
        ]]);
        if(!is_wp_error($r)) {
            $data = json_decode(wp_remote_retrieve_body($r),true);
            if(isset($data['access_token'])) {
                oct_gcal_save_token([
                    'access_token'  => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? '',
                    'expires_at'    => time() + intval($data['expires_in']??3600) - 60,
                ]);
                $connected = true;
                echo '<div class="notice notice-success"><p>✅ Google Calendar connected!</p></div>';
            }
        }
    }

    /* Handle disconnect */
    if(isset($_GET['disconnect'])) { oct_gcal_save_token([]); $connected=false; }
    ?>
    <div class="wrap oct-admin-wrap">
        <h1>📅 Google Calendar Authorization</h1>
        <?php if(!$client_id || !$client_secret): ?>
        <div class="notice notice-warning"><p>⚠️ Please complete <strong><a href="<?php echo admin_url('admin.php?page=oneclick-taxi'); ?>">Setup Wizard Step 4</a></strong> to enter your Google OAuth credentials first.</p></div>
        <?php elseif($connected): ?>
        <div class="notice notice-success"><p>✅ Connected. Bookings post to your Google Calendar automatically.</p></div>
        <a href="<?php echo esc_url($redirect.'&disconnect=1'); ?>" class="button button-secondary">Disconnect</a>
        <p style="margin-top:12px">
            <a href="<?php
            $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
                'client_id'     => $client_id,
                'redirect_uri'  => $redirect,
                'response_type' => 'code',
                'scope'         => 'https://www.googleapis.com/auth/calendar',
                'access_type'   => 'offline',
                'prompt'        => 'consent',
            ]);
            echo esc_url($auth_url);
            ?>" class="button">Re-authorize</a>
        </p>
        <?php else: ?>
        <div class="notice notice-warning"><p>Not connected. Click below to connect your Google Calendar.</p></div>
        <p>Sign in with the Google account whose calendar you want to use for bookings.</p>
        <a href="<?php
        $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id'     => $client_id,
            'redirect_uri'  => $redirect,
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/calendar',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ]);
        echo esc_url($auth_url);
        ?>" class="button button-primary">Connect Google Calendar</a>
        <?php endif; ?>
    </div>
    <?php
}
