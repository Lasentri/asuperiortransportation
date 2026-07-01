<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function oct_ls_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    /* Licenses table */
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}oct_licenses (
        id              BIGINT AUTO_INCREMENT PRIMARY KEY,
        license_key     VARCHAR(64)  NOT NULL UNIQUE,
        tier            VARCHAR(20)  NOT NULL,
        email           VARCHAR(150) NOT NULL,
        customer_name   VARCHAR(100) DEFAULT '',
        status          ENUM('pending','active','revoked','expired') DEFAULT 'pending',
        issued_at       DATETIME,
        expires_at      DATETIME,
        payment_id      VARCHAR(100) DEFAULT '',
        payment_amount  INT          DEFAULT 0,
        max_activations INT          DEFAULT 1,
        activation_count INT         DEFAULT 0,
        notes           TEXT,
        INDEX(license_key),
        INDEX(email),
        INDEX(status)
    ) {$charset}");

    /* Activations table - one row per activated machine */
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}oct_activations (
        id              BIGINT AUTO_INCREMENT PRIMARY KEY,
        license_key     VARCHAR(64)  NOT NULL,
        domain          VARCHAR(255) NOT NULL,
        server_ip       VARCHAR(45)  NOT NULL,
        fingerprint     VARCHAR(128) NOT NULL,
        activated_at    DATETIME,
        last_heartbeat  DATETIME,
        heartbeat_count INT          DEFAULT 0,
        status          ENUM('active','revoked') DEFAULT 'active',
        INDEX(license_key),
        INDEX(fingerprint),
        INDEX(domain)
    ) {$charset}");

    /* Blocked IPs table */
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}oct_blocked_ips (
        id          BIGINT AUTO_INCREMENT PRIMARY KEY,
        ip          VARCHAR(45) NOT NULL UNIQUE,
        reason      VARCHAR(255) DEFAULT '',
        blocked_at  DATETIME,
        INDEX(ip)
    ) {$charset}");

    /* Purchase log */
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}oct_purchases (
        id              BIGINT AUTO_INCREMENT PRIMARY KEY,
        license_key     VARCHAR(64)  NOT NULL,
        email           VARCHAR(150) NOT NULL,
        customer_name   VARCHAR(100) DEFAULT '',
        tier            VARCHAR(20)  NOT NULL,
        amount_cents    INT          DEFAULT 0,
        square_payment_id VARCHAR(100) DEFAULT '',
        purchased_at    DATETIME,
        ip              VARCHAR(45)  DEFAULT '',
        INDEX(license_key),
        INDEX(email)
    ) {$charset}");
}

/* Generate a unique license key */
function oct_ls_generate_key( $tier ) {
    $prefix = strtoupper(substr($tier, 0, 2));
    $key    = $prefix . '-' .
              strtoupper(bin2hex(random_bytes(4))) . '-' .
              strtoupper(bin2hex(random_bytes(4))) . '-' .
              strtoupper(bin2hex(random_bytes(4))) . '-' .
              strtoupper(bin2hex(random_bytes(4)));
    return $key; /* e.g. LI-A3F2B1C4-D5E6F7A8-B9C0D1E2-F3A4B5C6 */
}

/* Create a new license after successful payment */
function oct_ls_create_license( $args ) {
    global $wpdb;
    $tiers = OCT_LS_TIERS;
    $tier  = $args['tier'];
    if ( ! isset($tiers[$tier]) ) return false;

    $key        = oct_ls_generate_key($tier);
    $issued_at  = current_time('mysql');
    $days       = $tiers[$tier]['days'];
    $expires_at = $days >= 99999
        ? '2099-12-31 23:59:59'
        : date('Y-m-d H:i:s', strtotime("+{$days} days"));

    $wpdb->insert( $wpdb->prefix.'oct_licenses', [
        'license_key'    => $key,
        'tier'           => $tier,
        'email'          => sanitize_email($args['email']),
        'customer_name'  => sanitize_text_field($args['name'] ?? ''),
        'status'         => 'active',
        'issued_at'      => $issued_at,
        'expires_at'     => $expires_at,
        'payment_id'     => sanitize_text_field($args['payment_id'] ?? ''),
        'payment_amount' => intval($args['amount'] ?? 0),
        'max_activations'=> 1,
    ]);

    return $key;
}

/* Get license by key */
function oct_ls_get_license( $key ) {
    global $wpdb;
    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}oct_licenses WHERE license_key = %s", $key)
    );
}

/* Get activation by license key + fingerprint */
function oct_ls_get_activation( $key, $fingerprint ) {
    global $wpdb;
    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}oct_activations WHERE license_key = %s AND fingerprint = %s",
            $key, $fingerprint
        )
    );
}

/* Count active activations for a key */
function oct_ls_count_activations( $key ) {
    global $wpdb;
    return intval($wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}oct_activations WHERE license_key = %s AND status = 'active'",
            $key
        )
    ));
}

/* Check if IP is blocked */
function oct_ls_is_ip_blocked( $ip ) {
    global $wpdb;
    return (bool)$wpdb->get_var(
        $wpdb->prepare("SELECT id FROM {$wpdb->prefix}oct_blocked_ips WHERE ip = %s", $ip)
    );
}

/* Block an IP */
function oct_ls_block_ip( $ip, $reason = '' ) {
    global $wpdb;
    $wpdb->replace( $wpdb->prefix.'oct_blocked_ips', [
        'ip'         => $ip,
        'reason'     => $reason,
        'blocked_at' => current_time('mysql'),
    ]);
}

/* Revoke a license */
function oct_ls_revoke_license( $key, $reason = '' ) {
    global $wpdb;
    $wpdb->update( $wpdb->prefix.'oct_licenses',   ['status'=>'revoked'],           ['license_key'=>$key] );
    $wpdb->update( $wpdb->prefix.'oct_activations',['status'=>'revoked'],           ['license_key'=>$key] );
}

/* Send license key email to customer */
function oct_ls_send_license_email( $email, $name, $key, $tier ) {
    $tiers   = OCT_LS_TIERS;
    $label   = $tiers[$tier]['label'] ?? $tier;
    $expires = $tiers[$tier]['days'] >= 99999 ? 'Never (Lifetime)' : date('F j, Y', strtotime('+'.$tiers[$tier]['days'].' days'));
    $subject = "Your OneClick Taxi License Key";
    $body    = "Hello {$name},\n\n"
             . "Thank you for purchasing OneClick Taxi Ordering App!\n\n"
             . "YOUR LICENSE KEY:\n"
             . "================\n"
             . "{$key}\n"
             . "================\n\n"
             . "License Type: {$label}\n"
             . "Expires: {$expires}\n\n"
             . "INSTALLATION INSTRUCTIONS:\n"
             . "1. Download the plugin from asuperiortransportation.com/LicenseDepartment\n"
             . "2. Install it in WordPress: Plugins → Add New → Upload Plugin\n"
             . "3. Activate the plugin\n"
             . "4. Enter your license key when prompted\n"
             . "5. Follow the 10-step setup wizard\n\n"
             . "Your license is locked to ONE domain and ONE server IP.\n"
             . "Do not share this key — sharing will result in immediate revocation.\n\n"
             . "Need help? Email stalcollc@gmail.com\n\n"
             . "— A Superior Transportation & Logistics";
    wp_mail( $email, $subject, $body );
}
