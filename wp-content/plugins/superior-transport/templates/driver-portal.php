<?php
if ( ! defined( 'ABSPATH' ) ) exit;
include ST_DIR . 'templates/inc-header.php';
if(!session_id()) session_start();
$portal_pass = get_option('st_driver_password','superior2024');
$authed      = ($_SESSION['st_driver'] ?? false) || (isset($_POST['dp_pass']) && $_POST['dp_pass'] === $portal_pass);
if($authed) $_SESSION['st_driver'] = true;
?>
<div class="st-page-hero st-page-hero--driver">
  <div class="st-page-hero-content"><h1>Driver Portal</h1><p>A Superior Transportation — Staff Access</p></div>
</div>
<div class="st-container st-page-body">
<?php if(!$authed): ?>
  <div class="st-driver-login">
    <h2>Driver Login</h2>
    <form method="post">
      <div class="st-form-group"><label>Password</label><input type="password" name="dp_pass" placeholder="Enter driver password"></div>
      <button type="submit" class="st-btn-primary">Login</button>
    </form>
  </div>
<?php else:
  global $wpdb;
  $bookings = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}st_bookings ORDER BY created_at DESC LIMIT 50");
?>
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <h2>Recent Bookings</h2>
    <a href="<?php echo admin_url('admin.php?page=cab-orders');?>" class="st-btn-primary" style="text-decoration:none">Open Full Admin</a>
  </div>
  <?php if($bookings): ?>
  <div class="st-booking-table-wrap">
  <table class="st-booking-table">
    <thead><tr><th>Date</th><th>Time</th><th>Name</th><th>Phone</th><th>Pickup</th><th>Dropoff</th><th>Fare</th><th>Payment</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach($bookings as $b): 
      $name  = $b->customer_name ?? $b->customer_name ?? '';
      $phone = $b->customer_phone ?? $b->phone ?? '';
    ?>
    <tr>
      <td><?php echo esc_html($b->pickup_date ?? date('m/d/y',strtotime($b->created_at)));?></td>
      <td><?php echo esc_html($b->pickup_time ?? '');?></td>
      <td><?php echo esc_html($name);?></td>
      <td><a href="tel:<?php echo preg_replace('/\D/','',$phone);?>"><?php echo esc_html($phone);?></a></td>
      <td><?php echo esc_html($b->pickup);?></td>
      <td><?php echo esc_html($b->dropoff);?></td>
      <td>$<?php echo number_format($b->fare,2);?></td>
      <td><?php echo esc_html($b->payment_method ?? '');?></td>
      <td><span class="st-status-badge st-status-<?php echo esc_attr($b->status);?>"><?php echo esc_html($b->status);?></span></td>
    </tr>
    <?php endforeach;?>
    </tbody>
  </table>
  </div>
  <?php else: ?><p>No bookings yet.</p><?php endif;?>
<?php endif;?>
</div>
<?php include ST_DIR . 'templates/inc-footer.php'; ?>
