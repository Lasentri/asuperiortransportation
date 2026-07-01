<?php
/**
 * Plugin Name: Superior Transportation Gas Price Tracker
 * Plugin URI: https://asuperiortransportation.com
 * Description: UP Michigan Gas Price Tracker 2013-2026, color coded by administration, matching site colors. Visit /up-gas-price-tracker/ after activating.
 * Version: 2.1.0
 * Author: A Superior Transportation
 */
if (!defined('ABSPATH')) exit;

register_activation_hook(__FILE__, 'sgt_create_page');
function sgt_create_page() {
    $slug = 'up-gas-price-tracker';
    if (get_page_by_path($slug)) return;
    wp_insert_post([
        'post_title'   => 'UP Michigan Gas Price Tracker 2013-2026',
        'post_name'    => $slug,
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'meta_input'   => ['_sgt_page' => '1'],
    ]);
    // Schedule daily cron on activation
    if (!wp_next_scheduled('sgt_daily_price_check')) {
        wp_schedule_event(strtotime('today 08:00:00'), 'daily', 'sgt_daily_price_check');
    }
}
register_deactivation_hook(__FILE__, 'sgt_remove_page');
function sgt_remove_page() {
    $page = get_page_by_path('up-gas-price-tracker');
    if ($page) wp_delete_post($page->ID, true);
    wp_clear_scheduled_hook('sgt_daily_price_check');
}
add_filter('template_include', 'sgt_template_override');
function sgt_template_override($template) {
    if (is_page('up-gas-price-tracker')) {
        $custom = plugin_dir_path(__FILE__) . 'template-gas-tracker.php';
        if (file_exists($custom)) return $custom;
    }
    return $template;
}

/* -------------------------------------------------------
   CRON: Schedule if not already running (handles sites
   that were active before v2.1)
------------------------------------------------------- */
add_action('init', function () {
    if (!wp_next_scheduled('sgt_daily_price_check')) {
        wp_schedule_event(time(), 'daily', 'sgt_daily_price_check');
    }
});

/* -------------------------------------------------------
   CRON HANDLER: Runs daily, acts only on the 15th
------------------------------------------------------- */
add_action('sgt_daily_price_check', 'sgt_maybe_update_price');
function sgt_maybe_update_price() {
    // Only run on the 15th of each month
    if ((int) date('j') !== 15) return;

    $price = sgt_fetch_marquette_price();
    if ($price === false || $price <= 0) {
        error_log('[SGT] Auto price fetch failed on ' . date('Y-m-d'));
        return;
    }

    $year  = (int) date('Y');
    $month = (int) date('n');

    $gas_data = get_option('st_gas_data', []);
    $gas_data[$year][$month] = round($price, 3);
    update_option('st_gas_data', $gas_data);

    error_log("[SGT] Auto-updated gas price: \${$price} for {$year}-{$month} (Marquette avg)");
}

/* -------------------------------------------------------
   FETCH AAA MARQUETTE REGULAR UNLEADED AVERAGE
------------------------------------------------------- */
function sgt_fetch_marquette_price() {
    $response = wp_remote_get('https://gasprices.aaa.com/?state=MI', [
        'timeout'    => 30,
        'user-agent' => 'Mozilla/5.0 (compatible; ASTGasFetcher/1.0; +https://asuperiortransportation.com)',
    ]);

    if (is_wp_error($response)) return false;

    $body = wp_remote_retrieve_body($response);
    if (empty($body)) return false;

    // Look for Marquette section then first Regular price (Current Avg)
    // AAA renders: <h3>Marquette</h3> … <td>$X.XXXX</td>
    if (preg_match('/Marquette[\s\S]{0,2000}?Current Avg\.?[\s\S]{0,500}?\$\s*(\d+\.\d+)/i', $body, $m)) {
        return floatval($m[1]);
    }

    // Fallback: any JSON embedded with marquette key
    if (preg_match('/"marquette"[\s\S]{0,200}?"regular"[\s\S]{0,100}?"current"[\s\S]{0,50}?:[\s"]*(\d+\.\d+)/i', $body, $m)) {
        return floatval($m[1]);
    }

    return false;
}

/* -------------------------------------------------------
   ADMIN: Manual trigger + last-fetch status
------------------------------------------------------- */
add_action('admin_menu', function () {
    add_submenu_page(
        'st-transport',
        'Gas Price Updater',
        '⛽ Gas Prices',
        'manage_options',
        'sgt-gas-updater',
        'sgt_admin_page'
    );
});

function sgt_admin_page() {
    // Manual fetch trigger
    if (isset($_POST['sgt_fetch_now']) && check_admin_referer('sgt_fetch_now')) {
        $price = sgt_fetch_marquette_price();
        if ($price) {
            $year  = (int) date('Y');
            $month = (int) date('n');
            $gas_data = get_option('st_gas_data', []);
            $gas_data[$year][$month] = round($price, 3);
            update_option('st_gas_data', $gas_data);
            echo '<div class="notice notice-success"><p>✅ Updated ' . date('F Y') . ' price to $' . number_format($price, 3) . ' (Marquette AAA avg)</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Could not fetch price from AAA. Enter manually below.</p></div>';
        }
    }

    // Manual price entry
    if (isset($_POST['sgt_manual_price']) && check_admin_referer('sgt_manual_price')) {
        $year  = intval($_POST['sgt_year']  ?? date('Y'));
        $month = intval($_POST['sgt_month'] ?? date('n'));
        $price = floatval($_POST['sgt_price'] ?? 0);
        if ($price > 0 && $year > 2000 && $month >= 1 && $month <= 12) {
            $gas_data = get_option('st_gas_data', []);
            $gas_data[$year][$month] = round($price, 3);
            update_option('st_gas_data', $gas_data);
            echo '<div class="notice notice-success"><p>✅ Saved $' . number_format($price, 3) . ' for ' . date('F Y', mktime(0,0,0,$month,1,$year)) . '</p></div>';
        }
    }

    $next = wp_next_scheduled('sgt_daily_price_check');
    $gas_data = get_option('st_gas_data', []);
    $months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    ?>
    <div class="wrap">
    <h1>⛽ Gas Price Updater</h1>
    <p>Prices auto-fetch from AAA Michigan on the <strong>15th of each month</strong> (Marquette regular unleaded avg).</p>
    <p>Next scheduled run: <strong><?php echo $next ? date('M j, Y g:i A', $next) : 'Not scheduled'; ?></strong></p>

    <h2>Fetch Now</h2>
    <form method="post">
        <?php wp_nonce_field('sgt_fetch_now'); ?>
        <p><button name="sgt_fetch_now" class="button button-primary button-large">⛽ Fetch Current Marquette Price from AAA</button></p>
    </form>

    <h2>Manual Entry</h2>
    <form method="post">
        <?php wp_nonce_field('sgt_manual_price'); ?>
        <table class="form-table"><tr>
            <th>Year</th><td><input name="sgt_year" type="number" value="<?php echo date('Y');?>" min="2013" max="2040" style="width:90px"></td>
            <th>Month</th><td><select name="sgt_month"><?php for($i=1;$i<=12;$i++) echo "<option value='$i'".($i==(int)date('n')?' selected':'').">$months[$i]</option>";?></select></td>
            <th>Price ($/gal)</th><td><input name="sgt_price" type="number" step="0.001" min="0" placeholder="3.750" style="width:100px"></td>
        </tr></table>
        <p><button name="sgt_manual_price" class="button button-primary">Save Price</button></p>
    </form>

    <h2>Current Price Table</h2>
    <table class="widefat" style="max-width:800px">
        <thead><tr><th>Year</th><?php for($i=1;$i<=12;$i++) echo "<th>$months[$i]</th>";?></tr></thead>
        <tbody>
        <?php krsort($gas_data); foreach($gas_data as $yr=>$pp): ?>
        <tr><td><strong><?php echo esc_html($yr);?></strong></td>
        <?php for($i=1;$i<=12;$i++): $p=$pp[$i]??null; ?>
        <td><?php echo $p ? '$'.number_format($p,3) : '—'; ?></td>
        <?php endfor; ?></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php
}
