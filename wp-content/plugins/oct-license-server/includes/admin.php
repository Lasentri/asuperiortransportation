<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_menu', 'oct_ls_admin_menu');
function oct_ls_admin_menu() {
    add_menu_page('License Department','License Dept','manage_options','oct-license-server','oct_ls_dashboard','dashicons-lock',25);
    add_submenu_page('oct-license-server','Dashboard',   '📊 Dashboard',   'manage_options','oct-license-server','oct_ls_dashboard');
    add_submenu_page('oct-license-server','All Licenses','🔑 All Licenses','manage_options','oct-ls-licenses', 'oct_ls_licenses_page');
    add_submenu_page('oct-license-server','Blocked IPs', '🚫 Blocked IPs', 'manage_options','oct-ls-blocked',  'oct_ls_blocked_page');
    add_submenu_page('oct-license-server','Settings',    '⚙️ Settings',    'manage_options','oct-ls-settings', 'oct_ls_settings_page');
}

function oct_ls_dashboard() {
    global $wpdb;
    $total    = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}oct_licenses");
    $active   = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}oct_licenses WHERE status='active'");
    $revoked  = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}oct_licenses WHERE status='revoked'");
    $expired  = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}oct_licenses WHERE status='expired'");
    $revenue  = $wpdb->get_var("SELECT SUM(payment_amount) FROM {$wpdb->prefix}oct_licenses WHERE status IN('active','expired')");
    $blocked  = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}oct_blocked_ips");
    $recent   = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}oct_licenses ORDER BY issued_at DESC LIMIT 10");
    ?>
    <div class="wrap">
        <h1>🔑 License Department Dashboard</h1>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin:20px 0">
            <?php foreach([
                ['Total Keys', $total,   '#1a3a1a'],
                ['Active',     $active,  '#2e7d32'],
                ['Revoked',    $revoked, '#b71c1c'],
                ['Expired',    $expired, '#e65100'],
                ['Blocked IPs',$blocked, '#4a148c'],
                ['Revenue',    '$'.number_format(($revenue??0)/100,2), '#1565c0'],
            ] as [$label,$val,$color]): ?>
            <div style="background:<?php echo $color;?>;color:#fff;padding:20px;border-radius:8px;text-align:center">
                <div style="font-size:1.8rem;font-weight:700"><?php echo $val; ?></div>
                <div style="font-size:.85rem;opacity:.8"><?php echo $label; ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <h2>Recent Licenses</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr>
                <th>Key</th><th>Customer</th><th>Tier</th><th>Status</th><th>Expires</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach($recent as $l): ?>
            <tr>
                <td><code style="font-size:.78rem"><?php echo esc_html($l->license_key); ?></code></td>
                <td><?php echo esc_html($l->customer_name); ?><br><small><?php echo esc_html($l->email); ?></small></td>
                <td><?php echo esc_html(strtoupper($l->tier)); ?></td>
                <td>
                    <span style="padding:3px 8px;border-radius:4px;font-size:.78rem;font-weight:700;background:<?php echo $l->status==='active'?'#e8f5e9':($l->status==='revoked'?'#ffebee':'#fff3e0');?>;color:<?php echo $l->status==='active'?'#2e7d32':($l->status==='revoked'?'#c62828':'#e65100');?>">
                        <?php echo strtoupper($l->status); ?>
                    </span>
                </td>
                <td><?php echo $l->tier==='lifetime'?'Never':date('M j, Y',strtotime($l->expires_at)); ?></td>
                <td>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=oct-license-server&revoke='.$l->license_key),'oct_ls_revoke'); ?>" onclick="return confirm('Revoke this license?')" style="color:red">Revoke</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    /* Handle revoke action */
    if ( isset($_GET['revoke']) && check_admin_referer('oct_ls_revoke') ) {
        oct_ls_revoke_license(sanitize_text_field($_GET['revoke']), 'Manual revoke by admin');
        echo '<div class="notice notice-success"><p>License revoked.</p></div>';
    }
}

function oct_ls_licenses_page() {
    global $wpdb;
    /* Handle manual key creation */
    if ( isset($_POST['oct_create_key']) && check_admin_referer('oct_create_key') ) {
        $key = oct_ls_create_license([
            'tier'  => sanitize_text_field($_POST['tier']),
            'email' => sanitize_email($_POST['email']),
            'name'  => sanitize_text_field($_POST['name']),
            'payment_id' => 'MANUAL',
            'amount'     => 0,
        ]);
        echo '<div class="notice notice-success"><p>License created: <strong>'.$key.'</strong></p></div>';
    }
    $licenses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}oct_licenses ORDER BY issued_at DESC");
    $tiers    = OCT_LS_TIERS;
    ?>
    <div class="wrap">
        <h1>🔑 All Licenses</h1>

        <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:20px;margin-bottom:24px;max-width:600px">
            <h3>Create Manual License Key</h3>
            <form method="post"><?php wp_nonce_field('oct_create_key'); ?>
            <table class="form-table" style="margin:0">
                <tr><th>Customer Name</th><td><input name="name" class="regular-text" required></td></tr>
                <tr><th>Email</th><td><input type="email" name="email" class="regular-text" required></td></tr>
                <tr><th>Tier</th><td>
                    <select name="tier">
                        <?php foreach($tiers as $k=>$t): ?>
                        <option value="<?php echo $k; ?>"><?php echo $t['label'].' — '.$t['display']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td></tr>
            </table>
            <p><input type="submit" name="oct_create_key" class="button button-primary" value="Generate Key"></p>
            </form>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>License Key</th><th>Customer</th><th>Tier</th><th>Status</th><th>Activated</th><th>Issued</th><th>Expires</th><th>Amount</th></tr></thead>
            <tbody>
            <?php foreach($licenses as $l): ?>
            <tr>
                <td><code style="font-size:.75rem"><?php echo esc_html($l->license_key); ?></code></td>
                <td><?php echo esc_html($l->customer_name.'<br><small>'.esc_html($l->email).'</small>'); ?></td>
                <td><?php echo esc_html(strtoupper($l->tier)); ?></td>
                <td><?php echo strtoupper($l->status); ?></td>
                <td><?php echo $l->activation_count.'/'.$l->max_activations; ?></td>
                <td><?php echo date('M j Y', strtotime($l->issued_at)); ?></td>
                <td><?php echo $l->tier==='lifetime'?'Never':date('M j Y',strtotime($l->expires_at)); ?></td>
                <td>$<?php echo number_format($l->payment_amount/100,2); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function oct_ls_blocked_page() {
    global $wpdb;
    if ( isset($_GET['unblock']) && check_admin_referer('oct_ls_unblock') ) {
        $wpdb->delete($wpdb->prefix.'oct_blocked_ips', ['ip'=>sanitize_text_field($_GET['unblock'])]);
        echo '<div class="notice notice-success"><p>IP unblocked.</p></div>';
    }
    $blocked = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}oct_blocked_ips ORDER BY blocked_at DESC");
    ?>
    <div class="wrap">
        <h1>🚫 Blocked IPs</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>IP Address</th><th>Reason</th><th>Blocked At</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($blocked as $b): ?>
            <tr>
                <td><?php echo esc_html($b->ip); ?></td>
                <td><?php echo esc_html($b->reason); ?></td>
                <td><?php echo date('M j Y g:i A', strtotime($b->blocked_at)); ?></td>
                <td><a href="<?php echo wp_nonce_url(admin_url('admin.php?page=oct-ls-blocked&unblock='.urlencode($b->ip)),'oct_ls_unblock'); ?>" style="color:green">Unblock</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function oct_ls_settings_page() {
    if ( isset($_POST['oct_ls_save']) && check_admin_referer('oct_ls_settings') ) {
        update_option('oct_ls_settings', [
            'square_app_id'  => sanitize_text_field($_POST['square_app_id']  ?? ''),
            'square_token'   => sanitize_text_field($_POST['square_token']   ?? ''),
            'square_location'=> sanitize_text_field($_POST['square_location']?? ''),
        ]);
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }
    $s = get_option('oct_ls_settings', []);
    ?>
    <div class="wrap"><h1>⚙️ License Server Settings</h1>
    <form method="post"><?php wp_nonce_field('oct_ls_settings'); ?>
    <table class="form-table">
        <tr><th>Square App ID</th><td><input name="square_app_id" value="<?php echo esc_attr($s['square_app_id']??''); ?>" class="regular-text"></td></tr>
        <tr><th>Square Access Token</th><td><input name="square_token" value="<?php echo esc_attr($s['square_token']??''); ?>" class="regular-text"></td></tr>
        <tr><th>Square Location ID</th><td><input name="square_location" value="<?php echo esc_attr($s['square_location']??''); ?>" class="regular-text"></td></tr>
    </table>
    <p><input type="submit" name="oct_ls_save" class="button button-primary" value="Save"></p>
    </form>
    <hr>
    <h2>API Endpoints</h2>
    <p>Your license validation API is live at:</p>
    <ul>
        <li><code><?php echo home_url('/LicenseDepartment/api/validate'); ?></code></li>
        <li><code><?php echo home_url('/LicenseDepartment/api/activate'); ?></code></li>
        <li><code><?php echo home_url('/LicenseDepartment/api/heartbeat'); ?></code></li>
        <li><code><?php echo home_url('/LicenseDepartment/api/deactivate'); ?></code></li>
    </ul>
    </div>
    <?php
}
