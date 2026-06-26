<?php /* Full-page template for UP Gas Price Tracker v2 */ ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UP Gas Price Tracker 2013-2026 | A Superior Transportation</title>
<?php wp_head(); ?>
<style>
:root{
  --sb:#1a8fc1;--sr:#cc0000;--sw:#ffffff;--sl:#f4f4f4;--sd:#222222;--sm:#555555;--sbr:#dddddd;
  --oh:#2e7d32;--ob:#e8f5e9;--od:#1b5e20;
  --th:#b71c1c;--tb:#ffebee;--td:#7f0000;
  --bh:#1565c0;--bb:#e3f2fd;--bd:#0d47a1;
  --t2h:#880e4f;--t2b:#fce4ec;--t2d:#560027;
}
*{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;overflow-x:hidden;font-family:Arial,Helvetica,sans-serif;background:#fff;color:var(--sd);}
.topbar{background:var(--sb);color:#fff;padding:6px 20px;font-size:.78rem;display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px;}
.topbar a{color:#fff;text-decoration:none;}
.sitenav{background:#fff;border-bottom:2px solid var(--sbr);padding:14px 30px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;box-shadow:0 2px 8px rgba(0,0,0,.07);}
.sitenav-brand{font-size:1.15rem;font-weight:700;color:var(--sb);text-decoration:none;}
.sitenav-links{display:flex;gap:22px;flex-wrap:wrap;}
.sitenav-links a{color:var(--sd);font-size:.88rem;text-decoration:none;font-weight:500;}
.sitenav-links a:hover{color:var(--sb);}
.hero{background:var(--sb);padding:36px 20px;text-align:center;}
.hero h1{color:#fff;font-size:clamp(1.3rem,3.5vw,2.1rem);font-weight:700;letter-spacing:1px;margin-bottom:8px;}
.hero p{color:rgba(255,255,255,.85);font-size:.93rem;}
.legend{background:var(--sl);border-bottom:2px solid var(--sbr);padding:12px 20px;display:flex;justify-content:center;flex-wrap:wrap;gap:18px;}
.legend-item{display:flex;align-items:center;gap:7px;font-size:.8rem;color:var(--sm);}
.legend-sw{width:15px;height:15px;border-radius:3px;border:1px solid rgba(0,0,0,.15);flex-shrink:0;}
.statsrow{background:var(--sd);padding:22px 20px;display:flex;justify-content:center;flex-wrap:wrap;gap:0;}
.sbox{text-align:center;padding:10px 28px;border-right:1px solid rgba(255,255,255,.15);}
.sbox:last-child{border-right:none;}
.snum{font-size:1.65rem;font-weight:700;color:var(--sb);display:block;}
.slbl{font-size:.65rem;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:1px;margin-top:2px;line-height:1.4;}
.pagemain{max-width:1400px;margin:0 auto;padding:28px 16px 56px;}
.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:26px;}
.card{border:1px solid var(--sbr);border-radius:4px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);}
.card-hdr{padding:11px 14px;font-weight:700;font-size:.82rem;letter-spacing:.5px;text-transform:uppercase;color:#fff;text-align:center;}
.card-hdr.obama{background:var(--oh);}
.card-hdr.trump{background:var(--th);}
.card-hdr.biden{background:var(--bh);}
.card-hdr.trump2{background:var(--t2h);}
.card-body{padding:10px 14px;background:#fff;}
.crow{display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid #f0f0f0;font-size:.8rem;}
.crow:last-child{border-bottom:none;}
.crow .lbl{color:var(--sm);}
.crow .val{font-weight:700;}
.crow .lo{color:var(--od);}
.crow .hi{color:var(--sr);}
.tablewrap{overflow-x:auto;border:1px solid var(--sbr);box-shadow:0 2px 12px rgba(0,0,0,.08);margin-bottom:22px;border-radius:4px;}
table.gt{border-collapse:collapse;width:100%;min-width:1150px;font-size:.82rem;}
.gt th,.gt td{border:1px solid var(--sbr);padding:7px 8px;text-align:center;white-space:nowrap;}
.gt .era-row th{font-size:.72rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:8px;color:#fff;}
.era-blank{background:var(--sd);color:rgba(255,255,255,.4);font-size:.62rem !important;}
.era-obama{background:var(--od);}
.era-trump{background:var(--td);}
.era-biden{background:var(--bd);}
.era-trump2{background:var(--t2d);}
.gt .yr-row th{font-size:.82rem;font-weight:700;padding:9px 8px;color:#fff;}
.th-month{background:var(--sd);color:#fff;min-width:86px;text-align:left !important;padding-left:12px !important;}
.th-obama{background:var(--oh);}
.th-trump{background:var(--th);}
.th-biden{background:var(--bh);}
.th-trump2{background:var(--t2h);}
.td-month{background:var(--sd);color:#fff;font-weight:600;text-align:left !important;padding-left:12px !important;font-size:.82rem;}
.td-obama{background:var(--ob);}
.td-trump{background:var(--tb);}
.td-biden{background:var(--bb);}
.td-trump2{background:var(--t2b);}
.td-future{background:#f9f9f9;color:#aaa;font-style:italic;}
.cell-high{font-weight:700;color:var(--td);}
.cell-low{font-weight:700;color:var(--od);}
.gt .avg-row td{font-weight:700;border-top:3px solid var(--sd);font-size:.84rem;padding:9px 8px;}
.avg-month{background:var(--sd);color:var(--sb);text-align:left !important;padding-left:12px !important;}
.avg-obama{background:#b2dfdb;color:var(--od);}
.avg-trump{background:#ffcdd2;color:var(--td);}
.avg-biden{background:#bbdefb;color:var(--bd);}
.avg-trump2{background:#f8bbd0;color:var(--t2d);}
.srcnote{background:var(--sl);border-left:4px solid var(--sb);padding:14px 18px;font-size:.79rem;color:var(--sm);line-height:1.7;border-radius:0 4px 4px 0;}
.srcnote strong{color:var(--sd);}
.srcnote a{color:var(--sb);}
.ctabar{background:var(--sb);padding:28px 20px;text-align:center;margin-top:38px;}
.ctabar h2{color:#fff;font-size:1.35rem;margin-bottom:8px;}
.ctabar p{color:rgba(255,255,255,.85);font-size:.9rem;margin-bottom:16px;}
.ctabtn{display:inline-block;background:var(--sr);color:#fff;padding:12px 34px;font-weight:700;font-size:.93rem;text-decoration:none;border-radius:30px;}
.ctabtn:hover{background:#a30000;}
.sitefooter{background:var(--sd);padding:18px 30px;text-align:center;border-top:3px solid var(--sb);}
.sitefooter p{color:rgba(255,255,255,.35);font-size:.7rem;line-height:1.6;}
@media(max-width:900px){
  .cards{grid-template-columns:repeat(2,1fr);}
  .statsrow{flex-direction:column;align-items:center;}
  .sbox{border-right:none;border-bottom:1px solid rgba(255,255,255,.1);width:100%;}
  .sitenav-links{display:none;}
}
@media(max-width:480px){
  .cards{grid-template-columns:1fr;}
  .legend{flex-direction:column;align-items:flex-start;padding-left:28px;}
}
</style>
</head>
<body>
<?php
$months=['January','February','March','April','May','June','July','August','September','October','November','December'];
function sgt_era($y){
    if($y>=2013&&$y<=2016)return'obama';
    if($y>=2017&&$y<=2020)return'trump';
    if($y>=2021&&$y<=2024)return'biden';
    return'trump2';
}
$data=[
  2013=>[3.42,3.68,3.72,3.55,3.61,3.65,3.58,3.52,3.45,3.38,3.22,3.18],
  2014=>[3.25,3.38,3.52,3.58,3.65,3.68,3.62,3.55,3.42,3.18,2.92,2.65],
  2015=>[2.28,2.45,2.62,2.68,2.72,2.85,2.82,2.72,2.55,2.42,2.18,1.98],
  2016=>[1.85,1.78,1.88,2.05,2.18,2.38,2.42,2.32,2.22,2.15,2.08,2.12],
  2017=>[2.25,2.38,2.42,2.38,2.28,2.35,2.38,2.42,2.52,2.48,2.38,2.42],
  2018=>[2.58,2.62,2.68,2.75,2.92,2.98,2.95,2.88,2.82,2.68,2.45,2.32],
  2019=>[2.28,2.35,2.52,2.65,2.78,2.82,2.75,2.68,2.62,2.55,2.42,2.35],
  2020=>[2.42,2.38,2.15,1.88,1.78,1.92,2.05,2.12,2.08,2.02,1.98,2.05],
  2021=>[2.28,2.42,2.68,2.88,2.95,3.08,3.18,3.12,3.05,3.22,3.38,3.28],
  2022=>[3.42,3.55,3.85,3.92,4.28,5.18,4.75,4.38,3.95,3.72,3.45,3.18],
  2023=>[3.25,3.38,3.45,3.52,3.65,3.72,3.82,3.88,3.75,3.55,3.32,3.18],
  2024=>[3.15,3.22,3.38,3.72,3.65,3.55,3.88,3.62,3.42,3.28,3.12,2.98],
  2025=>[2.95,2.88,2.98,3.11,3.06,3.18,3.28,3.42,3.13,3.05,2.98,2.64],
  2026=>[2.92,2.91,3.64,3.89,4.62,null,null,null,null,null,null,null],
];
$years=array_keys($data);
$all=[];
foreach($data as $yr=>$pp) foreach($pp as $p) if($p!==null)$all[]=$p;
$all_high=max($all); $all_low=min($all);
$ep=['obama'=>[],'trump'=>[],'biden'=>[],'trump2'=>[]];
foreach($data as $yr=>$pp){$e=sgt_era($yr);foreach($pp as $p)if($p!==null)$ep[$e][]=$p;}
$es=[];
foreach($ep as $e=>$pp){
  if(empty($pp))continue;
  $es[$e]=['avg'=>array_sum($pp)/count($pp),'low'=>min($pp),'high'=>max($pp),'swing'=>max($pp)-min($pp)];
}
$elabels=['obama'=>['Obama','2013-2016'],'trump'=>['Trump','2017-2020'],'biden'=>['Biden','2021-2024'],'trump2'=>['Trump 2nd','2025-2026']];
?>
<div class="topbar">
  <span>📞 <a href="tel:+19063704094">Call Us : +1 (906) 370-4094</a> &nbsp;/&nbsp; ✉ <a href="mailto:staicollo@gmail.com">Mail us : staicollo@gmail.com</a></span>
  <span>🕐 Time: Sun-Sat 6am – 1am Or by Appointment late night.</span>
</div>
<nav class="sitenav">
  <a href="<?php echo home_url();?>" class="sitenav-brand">A Superior Transportation &amp; Logistics</a>
  <div class="sitenav-links">
    <a href="<?php echo home_url();?>">Home</a>
    <a href="<?php echo home_url('/faq');?>">FAQs</a>
    <a href="https://www.facebook.com" target="_blank">Facebook</a>
    <a href="https://www.tiktok.com" target="_blank">TikTok</a>
    <a href="<?php echo home_url('/suggested-places');?>">Our Suggested Places To Visit</a>
  </div>
</nav>
<div class="hero">
  <h1>UPPER PENINSULA GAS PRICE TRACKER</h1>
  <p>Monthly averages 2013–2026 &nbsp;·&nbsp; Marquette area &nbsp;·&nbsp; Regular unleaded &nbsp;·&nbsp; Source: AAA Michigan</p>
</div>
<div class="legend">
  <div class="legend-item"><div class="legend-sw" style="background:#e8f5e9;border-color:#2e7d32;"></div>Obama 2013-2016</div>
  <div class="legend-item"><div class="legend-sw" style="background:#ffebee;border-color:#b71c1c;"></div>Trump 2017-2020</div>
  <div class="legend-item"><div class="legend-sw" style="background:#e3f2fd;border-color:#1565c0;"></div>Biden 2021-2024</div>
  <div class="legend-item"><div class="legend-sw" style="background:#fce4ec;border-color:#880e4f;"></div>Trump 2025-2026</div>
  <div class="legend-item"><div class="legend-sw" style="background:#ffebee;border-color:#7f0000;"></div><strong>Bold red</strong>&nbsp;= Record High</div>
  <div class="legend-item"><div class="legend-sw" style="background:#e8f5e9;border-color:#1b5e20;"></div><strong>Bold green</strong>&nbsp;= Record Low</div>
  <div class="legend-item"><div class="legend-sw" style="background:#f9f9f9;border-color:#ccc;"></div><em>Italic TBD</em>&nbsp;= Pending</div>
</div>
<div class="statsrow">
  <div class="sbox"><span class="snum">$<?php echo number_format($all_high,2);?></span><span class="slbl">Record High<br>Jun 2022 · Biden</span></div>
  <div class="sbox"><span class="snum">$<?php echo number_format($all_low,2);?></span><span class="slbl">Record Low<br>May 2020 · Trump</span></div>
  <?php foreach(['trump'=>'Trump 1st Term','biden'=>'Biden Term','trump2'=>'Trump 2nd Term'] as $e=>$lbl):if(empty($es[$e]))continue;?>
  <div class="sbox"><span class="snum">$<?php echo number_format($es[$e]['avg'],2);?></span><span class="slbl"><?php echo $lbl;?><br>Avg $/gal</span></div>
  <?php endforeach;?>
  <div class="sbox"><span class="snum">$4.62</span><span class="slbl">Marquette May 2026<br>Current Price</span></div>
</div>
<div class="pagemain">
  <div class="cards">
    <?php foreach(['obama','trump','biden','trump2'] as $e):if(empty($es[$e]))continue;[$n,$y]=$elabels[$e];$s=$es[$e];?>
    <div class="card">
      <div class="card-hdr <?php echo $e;?>"><?php echo $n;?> &nbsp;·&nbsp; <?php echo $y;?></div>
      <div class="card-body">
        <div class="crow"><span class="lbl">Avg Price</span><span class="val">$<?php echo number_format($s['avg'],2);?>/gal</span></div>
        <div class="crow"><span class="lbl">Lowest</span><span class="val lo">$<?php echo number_format($s['low'],2);?>/gal</span></div>
        <div class="crow"><span class="lbl">Highest</span><span class="val hi">$<?php echo number_format($s['high'],2);?>/gal</span></div>
        <div class="crow"><span class="lbl">Price Swing</span><span class="val">$<?php echo number_format($s['swing'],2);?></span></div>
      </div>
    </div>
    <?php endforeach;?>
  </div>
  <div class="tablewrap">
  <table class="gt">
    <thead>
      <tr class="era-row">
        <th class="era-blank">Month / Year</th>
        <th class="era-obama" colspan="4">Obama Administration</th>
        <th class="era-trump" colspan="4">Trump Administration</th>
        <th class="era-biden" colspan="4">Biden Administration</th>
        <th class="era-trump2" colspan="2">Trump 2nd Term</th>
      </tr>
      <tr class="yr-row">
        <th class="th-month">Month</th>
        <?php foreach($years as $yr):$e=sgt_era($yr);?><th class="th-<?php echo $e;?>"><?php echo $yr;?></th><?php endforeach;?>
      </tr>
    </thead>
    <tbody>
      <?php foreach($months as $mi=>$month):?>
      <tr>
        <td class="td-month"><?php echo $month;?></td>
        <?php foreach($years as $yr):$e=sgt_era($yr);$p=$data[$yr][$mi];
          if($p===null):?><td class="td-future">TBD</td>
          <?php else:$x='';if($p==$all_high)$x=' cell-high';elseif($p==$all_low)$x=' cell-low';?>
          <td class="td-<?php echo $e.$x;?>">$<?php echo number_format($p,2);?></td>
          <?php endif;endforeach;?>
      </tr>
      <?php endforeach;?>
    </tbody>
    <tfoot>
      <tr class="avg-row">
        <td class="avg-month">Annual Avg</td>
        <?php foreach($years as $yr):$e=sgt_era($yr);$pp=array_filter($data[$yr],fn($v)=>$v!==null);?>
        <td class="avg-<?php echo $e;?>"><?php echo empty($pp)?'—':'$'.number_format(array_sum($pp)/count($pp),2);?></td>
        <?php endforeach;?>
      </tr>
    </tfoot>
  </table>
  </div>
  <div class="srcnote">
    <strong>Data Sources &amp; Notes :</strong> Prices are regular unleaded averages for the Marquette / Upper Peninsula Michigan area ($/gallon) sourced from AAA Michigan monthly reports and U.S. Energy Information Administration (EIA).
    The Upper Peninsula runs $0.10–$0.25 above the statewide average due to remote geography.
    <strong>2026 data :</strong> January–May verified from AAA Marquette reports. June–December listed as TBD and updated monthly.
    Record high : $5.18/gal (June 2022 · Biden) &nbsp;·&nbsp; Record low : $1.78/gal (May 2020 · Trump, COVID collapse).
    Michigan gas prices rose 53.8% year-over-year May 2025 to May 2026, third largest increase nationally (AAA / LendingTree).
    Page maintained by <a href="https://asuperiortransportation.com">A Superior Transportation</a>.
  </div>
</div>
<div class="ctabar">
  <h2>CALL US or Make Your Online Reservation</h2>
  <p>Serving Houghton · Hancock · Calumet · Lake Linden and the entire Western U.P. Corridor &nbsp;·&nbsp; Sun–Sat 6am–1am</p>
  <a href="tel:+19063704094" class="ctabtn">📞 906-370-4094</a>
</div>
<div class="sitefooter">
  <p>A Superior Transportation &amp; Logistics &nbsp;|&nbsp; Houghton, Michigan &nbsp;|&nbsp; asuperiortransportation.com<br>
  All time calls and prepaid services are paid in full before service commences. No refunds available unless discussed prior to pickup time.</p>
</div>
<?php wp_footer();?>
</body>
</html>
