<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* AJAX: process purchase */
add_action('wp_ajax_oct_ls_purchase',        'oct_ls_handle_purchase');
add_action('wp_ajax_nopriv_oct_ls_purchase', 'oct_ls_handle_purchase');

function oct_ls_handle_purchase() {
    if ( ! check_ajax_referer('oct_ls_nonce','nonce',false) ) {
        wp_send_json_error(['message'=>'Security check failed.']); return;
    }

    $tier         = sanitize_text_field($_POST['tier']          ?? '');
    $name         = sanitize_text_field($_POST['name']          ?? '');
    $email        = sanitize_email($_POST['email']              ?? '');
    $square_token = sanitize_text_field($_POST['square_token']  ?? '');
    $agreed       = $_POST['agreed_disclaimer'] ?? '0';

    /* Validate inputs */
    if ( ! $tier || ! $name || ! $email || ! $square_token ) {
        wp_send_json_error(['message'=>'Please fill in all fields.']); return;
    }
    if ( ! $agreed || $agreed !== '1' ) {
        wp_send_json_error(['message'=>'You must agree to the No-Refund Policy before purchasing.']); return;
    }

    $tiers = OCT_LS_TIERS;
    if ( ! isset($tiers[$tier]) ) {
        wp_send_json_error(['message'=>'Invalid license tier.']); return;
    }

    $s      = get_option('oct_ls_settings', []);
    $amount = $tiers[$tier]['price']; /* in cents */

    /* Charge Square */
    $response = wp_remote_post('https://connect.squareup.com/v2/payments', [
        'headers' => [
            'Authorization' => 'Bearer '.($s['square_token'] ?? ''),
            'Content-Type'  => 'application/json',
            'Square-Version'=> '2024-01-18',
        ],
        'body' => json_encode([
            'source_id'       => $square_token,
            'amount_money'    => ['amount'=>$amount, 'currency'=>'USD'],
            'location_id'     => $s['square_location'] ?? '',
            'idempotency_key' => uniqid('oct_lic_', true),
            'buyer_email_address' => $email,
            'note'            => "OneClick Taxi License - {$tiers[$tier]['label']} - {$name}",
        ]),
        'timeout' => 15,
    ]);

    if ( is_wp_error($response) ) {
        wp_send_json_error(['message'=>'Payment service unavailable. Please try again.']); return;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ( isset($body['payment']['id']) && $body['payment']['status'] === 'COMPLETED' ) {
        $payment_id = $body['payment']['id'];

        /* Generate license key */
        $key = oct_ls_create_license([
            'tier'       => $tier,
            'email'      => $email,
            'name'       => $name,
            'payment_id' => $payment_id,
            'amount'     => $amount,
        ]);

        /* Log purchase */
        global $wpdb;
        $wpdb->insert($wpdb->prefix.'oct_purchases', [
            'license_key'       => $key,
            'email'             => $email,
            'customer_name'     => $name,
            'tier'              => $tier,
            'amount_cents'      => $amount,
            'square_payment_id' => $payment_id,
            'purchased_at'      => current_time('mysql'),
            'ip'                => $_SERVER['REMOTE_ADDR'],
        ]);

        /* Send license key email */
        oct_ls_send_license_email($email, $name, $key, $tier);

        /* Notify admin */
        wp_mail(get_option('admin_email'),
            "NEW LICENSE SOLD: {$tiers[$tier]['label']} - {$name}",
            "Customer: {$name}\nEmail: {$email}\nTier: {$tiers[$tier]['label']}\nAmount: {$tiers[$tier]['display']}\nKey: {$key}\nPayment ID: {$payment_id}"
        );

        wp_send_json_success([
            'message' => "Payment successful! Your license key has been sent to {$email}. Check your inbox (and spam folder).",
            'key'     => $key,
            'tier'    => $tiers[$tier]['label'],
            'expires' => $tiers[$tier]['days'] >= 99999 ? 'Never (Lifetime)' : date('F j, Y', strtotime('+'.$tiers[$tier]['days'].' days')),
        ]);

    } else {
        $err = $body['errors'][0]['detail'] ?? 'Payment was declined. Please check your card details.';
        wp_send_json_error(['message'=>$err]);
    }
}
