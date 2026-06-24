<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_menu', function(){
    add_submenu_page('st-transport','Gas Prices','⛽ Gas Prices','manage_options','st-gas-tracker','st_gas_admin_page');
});

function st_gas_admin_page(){
    if(isset($_POST['st_save_gas'])){
        check_admin_referer('st_save_gas');
        $year=$_POST['gas_year']; $month=$_POST['gas_month']; $price=floatval($_POST['gas_price']);
        $data=get_option('st_gas_data',[]);
        if(!isset($data[$year])) $data[$year]=array_fill(1,12,null);
        $data[$year][$month]=$price;
        update_option('st_gas_data',$data);
        echo '<div class="notice notice-success"><p>Gas price saved.</p></div>';
    }
    $gas_data=get_option('st_gas_data',[]); $months=['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    ?>
    <div class="wrap"><h1>⛽ Gas Price Tracker</h1>
    <form method="post"><?php wp_nonce_field('st_save_gas');?>
    <table class="form-table">
        <tr><th>Year</th><td><input name="gas_year" type="number" value="<?php echo date('Y');?>" style="width:100px"></td></tr>
        <tr><th>Month</th><td><select name="gas_month"><?php for($i=1;$i<=12;$i++) echo "<option value='$i'>{$months[$i]}</option>";?></select></td></tr>
        <tr><th>Price per gallon ($)</th><td><input name="gas_price" type="number" step="0.001" style="width:120px"></td></tr>
    </table>
    <input type="submit" name="st_save_gas" class="button button-primary" value="Save Gas Price">
    </form>
    <?php if($gas_data): ?>
    <table class="widefat striped"><thead><tr><th>Year</th><?php for($i=1;$i<=12;$i++) echo "<th>{$months[$i]}</th>";?></tr></thead><tbody>
    <?php krsort($gas_data); foreach($gas_data as $yr=>$pp): ?><tr><td><strong><?php echo $yr;?></strong></td>
    <?php for($i=1;$i<=12;$i++) echo '<td>'.(isset($pp[$i])&&$pp[$i]?'$'.number_format($pp[$i],3):'—').'</td>';?></tr>
    <?php endforeach;?></tbody></table>
    <?php endif;?></div>
    <?php
}
