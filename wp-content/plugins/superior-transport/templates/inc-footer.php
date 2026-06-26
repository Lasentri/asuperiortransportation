<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$s = st_get_settings();
?>
<footer class="st-footer">
  <div class="st-footer-links">
    <a href="<?php echo esc_url(home_url('/'));?>">Home</a>
    <a href="<?php echo esc_url(home_url('/'));?>#booking">Book a Ride</a>
    <a href="<?php echo esc_url(home_url('/gas-tracker/'));?>">⛽ Gas Tracker</a>
    <a href="<?php echo esc_url(home_url('/suggested-places/'));?>">📍 Suggested Places</a>
    <a href="<?php echo esc_url(home_url('/driver-portal/'));?>">Driver Portal</a>
    <?php if($s['facebook_url']): ?><a href="<?php echo esc_url($s['facebook_url']);?>" target="_blank" rel="noopener">Facebook</a><?php endif;?>
    <?php if($s['tiktok_url']): ?><a href="<?php echo esc_url($s['tiktok_url']);?>" target="_blank" rel="noopener">TikTok</a><?php endif;?>
  </div>
  <div class="st-footer-legal">
    A Superior Transportation &amp; Logistics &nbsp;·&nbsp; <?php echo esc_html($s['address']);?> &nbsp;·&nbsp; <?php echo esc_html($s['email']);?><br>
    All time calls and prepaid services are paid in full before service commences.<br>
    &copy; <?php echo date('Y');?> A Superior Transportation &amp; Logistics LLC
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
