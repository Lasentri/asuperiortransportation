<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* Build machine fingerprint from request */
function oct_ls_build_fingerprint( $domain, $ip, $extra = '' ) {
    return hash('sha256', strtolower(trim($domain)) . '|' . trim($ip) . '|' . trim($extra));
}

/* VALIDATE — check if a key is valid before activation */
function oct_ls_api_validate() {
    $ip  = $_SERVER['REMOTE_ADDR'];
    $key = sanitize_text_field($_POST['license_key'] ?? '');
    if ( ! $key ) { echo json_encode(['status'=>'error','message'=>'No license key provided.']); return; }
    if ( oct_ls_is_ip_blocked($ip) ) { echo json_encode(['status'=>'blocked','message'=>'This IP has been blocked due to license abuse.']); return; }
    $license = oct_ls_get_license($key);
    if ( ! $license ) { echo json_encode(['status'=>'invalid','message'=>'License key not found.']); return; }
    if ( $license->status === 'revoked' )  { echo json_encode(['status'=>'revoked', 'message'=>'This license has been revoked due to policy violation.']); return; }
    if ( $license->status === 'expired' )  { echo json_encode(['status'=>'expired', 'message'=>'This license has expired. Please renew at asuperiortransportation.com/LicenseDepartment']); return; }
    if ( strtotime($license->expires_at) < time() && $license->tier !== 'lifetime' ) {
        global $wpdb;
        $wpdb->update($wpdb->prefix.'oct_licenses', ['status'=>'expired'], ['license_key'=>$key]);
        echo json_encode(['status'=>'expired','message'=>'License expired on '.date('F j, Y',strtotime($license->expires_at))]); return;
    }
    echo json_encode(['status'=>'valid','tier'=>$license->tier,'expires'=>$license->expires_at,'email'=>$license->email]);
}

/* ACTIVATE — lock key to this machine */
function oct_ls_api_activate() {
    global $wpdb;
    $ip          = $_SERVER['REMOTE_ADDR'];
    $key         = sanitize_text_field($_POST['license_key'] ?? '');
    $domain      = sanitize_text_field($_POST['domain']      ?? '');
    $server_ip   = sanitize_text_field($_POST['server_ip']   ?? $ip);
    $extra       = sanitize_text_field($_POST['fingerprint']  ?? '');

    if ( ! $key || ! $domain ) { echo json_encode(['status'=>'error','message'=>'Missing required fields.']); return; }
    if ( oct_ls_is_ip_blocked($ip) ) { echo json_encode(['status'=>'blocked','message'=>'This IP is blocked.']); return; }

    $license = oct_ls_get_license($key);
    if ( ! $license || $license->status !== 'active' ) {
        echo json_encode(['status'=>'invalid','message'=>'Invalid or inactive license key.']); return;
    }
    if ( strtotime($license->expires_at) < time() && $license->tier !== 'lifetime' ) {
        echo json_encode(['status'=>'expired','message'=>'License has expired.']); return;
    }

    $fingerprint = oct_ls_build_fingerprint($domain, $server_ip, $extra);

    /* Check if already activated on THIS machine */
    $existing = oct_ls_get_activation($key, $fingerprint);
    if ( $existing && $existing->status === 'active' ) {
        /* Same machine re-activating — OK, update heartbeat */
        $wpdb->update($wpdb->prefix.'oct_activations', ['last_heartbeat'=>current_time('mysql'),'heartbeat_count'=>$existing->heartbeat_count+1], ['id'=>$existing->id]);
        echo json_encode(['status'=>'active','message'=>'License active.','expires'=>$license->expires_at,'tier'=>$license->tier]); return;
    }

    /* Count active activations */
    $count = oct_ls_count_activations($key);
    if ( $count >= $license->max_activations ) {
        /* VIOLATION — someone is trying to use the key on a second machine */
        oct_ls_revoke_license($key, 'License sharing detected: multiple machines.');
        oct_ls_block_ip($ip, 'License key sharing attempt.');
        oct_ls_block_ip($server_ip, 'License key sharing attempt.');
        /* Alert admin */
        wp_mail(get_option('admin_email'), 'LICENSE VIOLATION DETECTED',
            "Key: {$key}\nOriginal domain: already activated\nViolating domain: {$domain}\nViolating IP: {$ip}\nKey has been REVOKED and IPs blocked.");
        echo json_encode(['status'=>'revoked','message'=>'License violation detected. This key has been revoked and your IP blocked. One license = one installation. Contact stalcollc@gmail.com if this is an error.']); return;
    }

    /* New activation — register it */
    $wpdb->insert($wpdb->prefix.'oct_activations', [
        'license_key'    => $key,
        'domain'         => $domain,
        'server_ip'      => $server_ip,
        'fingerprint'    => $fingerprint,
        'activated_at'   => current_time('mysql'),
        'last_heartbeat' => current_time('mysql'),
        'status'         => 'active',
    ]);
    $wpdb->update($wpdb->prefix.'oct_licenses', ['activation_count'=>$license->activation_count+1], ['license_key'=>$key]);

    echo json_encode(['status'=>'active','message'=>'License successfully activated.','expires'=>$license->expires_at,'tier'=>$license->tier]);
}

/* HEARTBEAT — plugin phones home every 24hrs */
function oct_ls_api_heartbeat() {
    global $wpdb;
    $ip          = $_SERVER['REMOTE_ADDR'];
    $key         = sanitize_text_field($_POST['license_key'] ?? '');
    $domain      = sanitize_text_field($_POST['domain']      ?? '');
    $server_ip   = sanitize_text_field($_POST['server_ip']   ?? $ip);
    $extra       = sanitize_text_field($_POST['fingerprint']  ?? '');

    if ( oct_ls_is_ip_blocked($ip) ) { echo json_encode(['status'=>'blocked','action'=>'self_destruct']); return; }

    $license = oct_ls_get_license($key);
    if ( ! $license ) { echo json_encode(['status'=>'invalid','action'=>'self_destruct']); return; }
    if ( $license->status === 'revoked' ) { echo json_encode(['status'=>'revoked','action'=>'self_destruct']); return; }
    if ( $license->status === 'expired' || (strtotime($license->expires_at) < time() && $license->tier !== 'lifetime') ) {
        $wpdb->update($wpdb->prefix.'oct_licenses',['status'=>'expired'],['license_key'=>$key]);
        echo json_encode(['status'=>'expired','action'=>'deactivate','message'=>'License expired. Please renew at asuperiortransportation.com/LicenseDepartment']); return;
    }

    $fingerprint = oct_ls_build_fingerprint($domain, $server_ip, $extra);
    $activation  = oct_ls_get_activation($key, $fingerprint);

    if ( ! $activation || $activation->status !== 'active' ) {
        /* Heartbeat from unknown machine — violation */
        oct_ls_block_ip($ip, 'Unauthorized heartbeat.');
        echo json_encode(['status'=>'blocked','action'=>'self_destruct']); return;
    }

    $wpdb->update($wpdb->prefix.'oct_activations', [
        'last_heartbeat'  => current_time('mysql'),
        'heartbeat_count' => $activation->heartbeat_count + 1,
    ], ['id'=>$activation->id]);

    $days_left = ceil((strtotime($license->expires_at) - time()) / 86400);
    echo json_encode([
        'status'   => 'active',
        'action'   => 'continue',
        'tier'     => $license->tier,
        'expires'  => $license->expires_at,
        'days_left'=> $license->tier === 'lifetime' ? 99999 : $days_left,
    ]);
}

/* DEACTIVATE — clean uninstall */
function oct_ls_api_deactivate() {
    global $wpdb;
    $key         = sanitize_text_field($_POST['license_key'] ?? '');
    $domain      = sanitize_text_field($_POST['domain']      ?? '');
    $server_ip   = sanitize_text_field($_POST['server_ip']   ?? $_SERVER['REMOTE_ADDR']);
    $extra       = sanitize_text_field($_POST['fingerprint']  ?? '');
    $fingerprint = oct_ls_build_fingerprint($domain, $server_ip, $extra);
    $wpdb->update($wpdb->prefix.'oct_activations', ['status'=>'revoked'], ['license_key'=>$key,'fingerprint'=>$fingerprint]);
    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}oct_licenses SET activation_count = GREATEST(0, activation_count-1) WHERE license_key=%s",$key));
    echo json_encode(['status'=>'deactivated','message'=>'License deactivated. You can now activate on another machine.']);
}
