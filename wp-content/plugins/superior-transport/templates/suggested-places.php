<?php
if ( ! defined( 'ABSPATH' ) ) exit;
include ST_DIR . 'templates/inc-header.php';
$places = [
  ['name'=>'Brockway Mountain Drive','desc'=>'Iconic scenic overlook with sweeping views of Lake Superior and Keweenaw.','tag'=>'🌄 Scenic','lat'=>47.4658,'lng'=>-87.9778],
  ['name'=>'Copper Harbor','desc'=>'Charming village at the tip of the Keweenaw — lighthouse, trails, and great food.','tag'=>'⚓ Village','lat'=>47.4678,'lng'=>-87.8826],
  ['name'=>'Houghton–Hancock Bridge','desc'=>'The iconic lift bridge connecting the twin cities across Portage Lake.','tag'=>'🌉 Landmark','lat'=>47.1211,'lng'=>-88.5694],
  ['name'=>'Michigan Tech University','desc'=>'Home of the Huskies — campus tours, events, and the A.E. Seaman Mineral Museum.','tag'=>'🎓 Campus','lat'=>47.1165,'lng'=>-88.5441],
  ['name'=>'Keweenaw Brewing Company','desc'=>'Award-winning craft brews in downtown Houghton. Try the Widow Maker Black Ale.','tag'=>'🍺 Brewery','lat'=>47.1220,'lng'=>-88.5702],
  ['name'=>'Fort Wilkins State Park','desc'=>'Historic 1844 Army fort near Copper Harbor with camping and shoreline trails.','tag'=>'🏕️ State Park','lat'=>47.4681,'lng'=>-87.8672],
  ['name'=>'Quincy Mine Hoist','desc'=>'Massive steam-powered mine hoist and underground tours into the copper country history.','tag'=>'⛏️ Historic','lat'=>47.1468,'lng'=>-88.5703],
  ['name'=>'McLain State Park','desc'=>'Stunning Lake Superior sunsets, beach, and camping just north of Hancock.','tag'=>'🏖️ Beach','lat'=>47.2736,'lng'=>-88.6319],
  ['name'=>'Houghton County Memorial Airport','desc'=>'Regional airport — we do airport runs anytime. Call ahead for scheduling.','tag'=>'✈️ Airport','lat'=>47.1681,'lng'=>-88.4894],
  ['name'=>'Downtown Calumet','desc'=>'Copper Country UNESCO World Heritage site with Victorian architecture and history.','tag'=>'🏛️ Historic','lat'=>47.2428,'lng'=>-88.4569],
  ['name'=>'Isle Royale Sea Planes','desc'=>'Floatplane service to Isle Royale National Park departing from Copper Harbor dock.','tag'=>'🛥️ Transport','lat'=>47.4678,'lng'=>-87.8826],
  ['name'=>'Keweenaw Water Trail','desc'=>'176 miles connecting lakes across the Keweenaw Peninsula. Paddling paradise.','tag'=>'🚣 Paddling','lat'=>47.3500,'lng'=>-88.2000],
  ['name'=>'Mount Bohemia','desc'=>"Michigan's most extreme ski resort — steep terrain, deep powder, no snowmaking.","tag"=>'🎿 Ski','lat'=>47.3915,'lng'=>-88.0056],
];
?>
<div class="st-page-hero st-page-hero--places">
  <div class="st-page-hero-content">
    <h1>📍 Suggested Places</h1>
    <p>Copper Country's best spots — we'll take you there</p>
  </div>
</div>
<div class="st-container st-page-body">
  <p class="st-places-intro">Need a ride to any of these destinations? <strong>Call us at <a href="tel:9063704094">906-370-4094</a></strong> or <a href="<?php echo esc_url(home_url('/'));?>#booking">book online</a>.</p>
  <div class="st-places-grid">
    <?php foreach($places as $place): ?>
    <div class="st-place-card">
      <div class="st-place-tag"><?php echo esc_html($place['tag']);?></div>
      <h3><?php echo esc_html($place['name']);?></h3>
      <p><?php echo esc_html($place['desc']);?></p>
      <a href="<?php echo esc_url(home_url('/'));?>#booking" class="st-place-book" data-place="<?php echo esc_attr($place['name']);?>">🚖 Book Ride Here</a>
    </div>
    <?php endforeach;?>
  </div>
  <div class="st-places-map-wrap">
    <h2>Map of Destinations</h2>
    <div id="st-places-map"></div>
  </div>
  <script>window.stPlacesData = <?php echo json_encode($places);?>;</script>
</div>
<?php include ST_DIR . 'templates/inc-footer.php'; ?>
