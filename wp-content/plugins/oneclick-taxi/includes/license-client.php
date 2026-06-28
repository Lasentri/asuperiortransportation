<?php
/**
 * OneClick Taxi — License Client
 * Phones home to asuperiortransportation.com/LicenseDepartment/api/
 * Validates key, locks to domain/IP, sends heartbeat, self-destructs on violation.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'OCT_LICENSE_SERVER', 'https://asuperiortransportation.com/LicenseDepartment/api/' );
define( 'OCT_LICENSE_OPTION',  'oct_license_data' );

/* Build machine fingerprint */
function oct_client_fingerprint() {
    $domain = parse_url(home_url(), PHP_URL_HOST);
    $ip     = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
    $salt   = defined('AUTH_KEY') ? substr(AUTH_KEY, 0, 16) : 'oct_default_salt';
    return hash('sha256', $domain.'|'.$ip.'|'.$salt);
}

/* Get stored license data */
function oct_get_license_data() {
    return get_option(OCT_LICENSE_OPTION, []);
}

/* Check if license is currently valid (local check first, then remote if needed) */
function oct_license_is_valid() {
    $data = oct_get_license_data();
    if ( empty($data['key']) || empty($data['status']) ) return false;
    if ( $data['status'] !== 'active' ) return false;
    if ( isset($data['expires_at']) && $data['expires_at'] !== '2099-12-31 23:59:59' ) {
        if ( strtotime($data['expires_at']) < time() ) {
            update_option(OCT_LICENSE_OPTION, array_merge($data,['status'=>'expired']));
            return false;
        }
    }
    return true;
}

/* Activate a license key */
function oct_activate_license( $key ) {
    $domain      = parse_url(home_url(), PHP_URL_HOST);
    $server_ip   = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
    $fingerprint = oct_client_fingerprint();

    $response = wp_remote_post( OCT_LICENSE_SERVER.'activate', [
        'body'    => [
            'license_key' => $key,
            'domain'      => $domain,
            'server_ip'   => $server_ip,
            'fingerprint' => $fingerprint,
        ],
        'timeout' => 15,
    ]);

    if ( is_wp_error($response) ) return ['status'=>'error','message'=>'Could not reach license server. Check your internet connection.'];

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if ( isset($data['status']) && $data['status'] === 'active' ) {
        update_option(OCT_LICENSE_OPTION, [
            'key'        => $key,
            'status'     => 'active',
            'tier'       => $data['tier']    ?? 'unknown',
            'expires_at' => $data['expires'] ?? '2099-12-31 23:59:59',
            'activated'  => time(),
            'fingerprint'=> $fingerprint,
            'domain'     => $domain,
        ]);
    } elseif ( isset($data['status']) && in_array($data['status'], ['revoked','blocked']) ) {
        oct_self_destruct($data['message'] ?? 'License violation.');
    }

    return $data;
}

/* Heartbeat — called daily via WP cron */
add_action( 'oct_license_heartbeat', 'oct_send_heartbeat' );
function oct_send_heartbeat() {
    $data = oct_get_license_data();
    if ( empty($data['key']) ) return;

    $domain      = parse_url(home_url(), PHP_URL_HOST);
    $server_ip   = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
    $fingerprint = oct_client_fingerprint();

    $response = wp_remote_post( OCT_LICENSE_SERVER.'heartbeat', [
        'body'    => [
            'license_key' => $data['key'],
            'domain'      => $domain,
            'server_ip'   => $server_ip,
            'fingerprint' => $fingerprint,
        ],
        'timeout' => 10,
    ]);

    if ( is_wp_error($response) ) return; /* Network error — don't punish, try again tomorrow */

    $result = json_decode(wp_remote_retrieve_body($response), true);

    if ( ! isset($result['status']) ) return;

    switch( $result['status'] ) {
        case 'active':
            update_option(OCT_LICENSE_OPTION, array_merge($data,[
                'status'     => 'active',
                'expires_at' => $result['expires'] ?? $data['expires_at'],
                'days_left'  => $result['days_left'] ?? 999,
                'last_check' => time(),
            ]));
            break;
        case 'expired':
            update_option(OCT_LICENSE_OPTION, array_merge($data,['status'=>'expired','last_check'=>time()]));
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-error"><p>⚠️ <strong>OneClick Taxi License Expired.</strong> '
                    .esc_html($result['message'] ?? 'Please renew at asuperiortransportation.com/LicenseDepartment')
                    .'</p></div>';
            });
            break;
        case 'revoked':
        case 'blocked':
            oct_self_destruct($result['message'] ?? 'License revoked.');
            break;
    }
}

/* Schedule daily heartbeat */
add_action( 'init', 'oct_schedule_heartbeat' );
function oct_schedule_heartbeat() {
    if ( ! wp_next_scheduled('oct_license_heartbeat') ) {
        wp_schedule_event(time(), 'daily', 'oct_license_heartbeat');
    }
}

/* SELF DESTRUCT — called when license is revoked or shared */
function oct_self_destruct( $reason = '' ) {
    /* Log the violation */
    update_option( OCT_LICENSE_OPTION, [
        'status'  => 'revoked',
        'reason'  => $reason,
        'revoked' => time(),
    ]);

    /* Delete plugin files */
    $plugin_dir = OCT_DIR;
    if ( function_exists('WP_Filesystem') ) {
        global $wp_filesystem;
        WP_Filesystem();
        $wp_filesystem->rmdir($plugin_dir, true);
    } else {
        /* Fallback recursive delete */
        oct_recursive_delete($plugin_dir);
    }

    /* Deactivate plugin */
    if ( function_exists('deactivate_plugins') ) {
        deactivate_plugins(plugin_basename(OCT_DIR.'oneclick-taxi.php'));
    }

    /* Block frontend */
    if ( ! is_admin() ) {
        wp_die(
            '<h1>License Violation</h1><p>This installation of OneClick Taxi has been deactivated due to a license policy violation: '
            .esc_html($reason)
            .'</p><p>Contact <a href="mailto:stalcollc@gmail.com">stalcollc@gmail.com</a> if you believe this is an error.</p>',
            'License Revoked',
            ['response'=>403]
        );
    }
}

function oct_recursive_delete( $dir ) {
    if ( ! is_dir($dir) ) return;
    $files = array_diff(scandir($dir), ['.','..']);
    foreach($files as $file) {
        $path = $dir.DIRECTORY_SEPARATOR.$file;
        is_dir($path) ? oct_recursive_delete($path) : @unlink($path);
    }
    @rmdir($dir);
}

/* Deactivate cleanly (when user uninstalls properly) */
function oct_deactivate_license() {
    $data = oct_get_license_data();
    if ( empty($data['key']) ) return;
    wp_remote_post( OCT_LICENSE_SERVER.'deactivate', [
        'body'    => [
            'license_key' => $data['key'],
            'domain'      => parse_url(home_url(), PHP_URL_HOST),
            'server_ip'   => $_SERVER['SERVER_ADDR'] ?? '',
            'fingerprint' => oct_client_fingerprint(),
        ],
        'timeout' => 8,
    ]);
    delete_option(OCT_LICENSE_OPTION);
    wp_clear_scheduled_hook('oct_license_heartbeat');
}
register_deactivation_hook(OCT_DIR.'oneclick-taxi.php', 'oct_deactivate_license');

/* License activation admin page */
function oct_license_admin_page() {
    $data    = oct_get_license_data();
    $valid   = oct_license_is_valid();
    $message = '';

    if ( isset($_POST['oct_activate_key']) && check_admin_referer('oct_activate') ) {
        $key    = sanitize_text_field(trim($_POST['license_key'] ?? ''));
        $result = oct_activate_license($key);
        $message = $result['message'] ?? ($result['status'] === 'active' ? '✅ License activated!' : '❌ Activation failed.');
    }
    ?>
    <div class="wrap">
        <h1>🔑 OneClick Taxi License</h1>

        <?php if($valid): ?>
        <div class="notice notice-success" style="padding:16px">
            <p><strong>✅ License Active</strong></p>
            <p><strong>Key:</strong> <code><?php echo esc_html(substr($data['key'],0,20)).'...'; ?></code></p>
            <p><strong>Tier:</strong> <?php echo esc_html(strtoupper($data['tier']??'')); ?></p>
            <p><strong>Expires:</strong> <?php echo $data['tier']==='lifetime'?'Never (Lifetime)':date('F j, Y',strtotime($data['expires_at']??'now')); ?></p>
            <p><strong>Domain:</strong> <?php echo esc_html($data['domain']??''); ?></p>
            <p style="margin-top:8px;font-size:.82rem;color:#666">Your license is locked to this domain and server. To move to a new server, deactivate the plugin here first, then reinstall on the new server.</p>
        </div>
        <?php elseif(!empty($data['status']) && $data['status'] === 'expired'): ?>
        <div class="notice notice-error"><p>⚠️ Your license has expired. <a href="https://asuperiortransportation.com/LicenseDepartment" target="_blank">Renew here →</a></p></div>
        <?php endif; ?>

        <?php if($message): ?>
        <div class="notice <?php echo strpos($message,'✅')!==false?'notice-success':'notice-error'; ?>"><p><?php echo esc_html($message); ?></p></div>
        <?php endif; ?>

        <?php if(!$valid): ?>
        <div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:24px;max-width:500px;margin-top:20px">
            <h3>Enter Your License Key</h3>
            <p style="color:#666;font-size:.9rem">Purchase a license at <a href="https://asuperiortransportation.com/LicenseDepartment" target="_blank">asuperiortransportation.com/LicenseDepartment</a></p>
            <form method="post">
                <?php wp_nonce_field('oct_activate'); ?>
                <input type="text" name="license_key" placeholder="XX-XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-family:monospace;margin:10px 0" required>
                <input type="submit" name="oct_activate_key" class="button button-primary" value="Activate License">
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/* Gate the plugin — show license page if not valid */
add_action('admin_init', 'oct_license_gate');
function oct_license_gate() {
    if ( ! is_admin() ) return;
    $screen = get_current_screen();
    /* Allow license page and settings pages */
    $allowed = ['plugins','plugin-install','update','update-core','oct-license'];
    if ( $screen && strpos($screen->id, 'oneclick-taxi') !== false ) return;
    if ( $screen && in_array($screen->id, $allowed) ) return;
    if ( ! oct_license_is_valid() ) {
        /* Redirect to license page */
        if ( strpos($_SERVER['REQUEST_URI'] ?? '', 'oct-license') === false ) {
            wp_redirect(admin_url('admin.php?page=oct-license'));
            exit;
        }
    }
}
