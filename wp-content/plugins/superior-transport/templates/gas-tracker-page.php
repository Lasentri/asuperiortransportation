<?php
if ( ! defined( 'ABSPATH' ) ) exit;
include ST_DIR . 'templates/inc-header.php';
$gas_data  = get_option('st_gas_data', []);
$months    = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$all_prices = [];
foreach($gas_data as $yr=>$pp) foreach($pp as $p) if($p) $all_prices[] = floatval($p);
$all_high   = $all_prices ? max($all_prices) : 0;
$all_low    = $all_prices ? min($all_prices) : 0;
$cur_price  = $gas_data[date('Y')][date('n')] ?? null;
?>
<div class="st-page-hero st-page-hero--gas">
  <div class="st-page-hero-content">
    <h1>⛽ U.P. Gas Price Tracker</h1>
    <p>Houghton County · Keweenaw Peninsula · Recorded local prices</p>
  </div>
</div>
<div class="st-container st-page-body">
  <?php if($cur_price): ?>
  <div class="st-gas-current">
    <div class="st-gas-current-label">Current Price (<?php echo $months[date('n')].' '.date('Y');?>)</div>
    <div class="st-gas-current-price">$<?php echo number_format($cur_price,3);?></div>
    <div class="st-gas-stats">Record High: <strong>$<?php echo number_format($all_high,3);?></strong> &nbsp;·&nbsp; Record Low: <strong>$<?php echo number_format($all_low,3);?></strong></div>
  </div>
  <?php endif;?>
  <?php if($gas_data): ?>
  <div class="st-gas-chart-wrap"><canvas id="st-gas-chart" height="300"></canvas></div>
  <div class="st-gas-table-wrap">
    <h2>Full Price History</h2>
    <table class="st-gas-table">
      <thead><tr><th>Year</th><?php for($i=1;$i<=12;$i++) echo "<th>{$months[$i]}</th>";?></tr></thead>
      <tbody>
      <?php krsort($gas_data); foreach($gas_data as $yr=>$pp): ?>
      <tr><td><strong><?php echo esc_html($yr);?></strong></td>
      <?php for($i=1;$i<=12;$i++):
        $p   = $pp[$i] ?? null;
        $cls = '';
        if($p && $all_high && $p==$all_high) $cls='gas-high';
        if($p && $all_low  && $p==$all_low)  $cls='gas-low';
      ?><td class="<?php echo $cls;?>"><?php echo $p?'$'.number_format($p,3):'—';?></td>
      <?php endfor;?></tr>
      <?php endforeach;?>
      </tbody>
    </table>
  </div>
  <script>window.stGasData=<?php echo json_encode($gas_data);?>;window.stGasMonths=<?php echo json_encode($months);?>;</script>
  <?php else: ?>
  <p class="st-empty-notice">No gas prices recorded yet. Check back soon!</p>
  <?php endif;?>
</div>
<?php include ST_DIR . 'templates/inc-footer.php'; ?>
