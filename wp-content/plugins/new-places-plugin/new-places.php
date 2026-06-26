<?php
/**
 * Plugin Name: New Places — Suggested Destinations Manager
 * Description: Add, edit, remove destinations. Generate single card code or full page. Sort by category.
 * Version: 1.1.0
 * Author: superiorxport
 */
if(!defined('ABSPATH')) exit;

register_activation_hook(__FILE__, 'np_create_table');
function np_create_table(){
    global $wpdb;
    $t = $wpdb->prefix.'np_places';
    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta("CREATE TABLE IF NOT EXISTS {$t} (
        id int NOT NULL AUTO_INCREMENT,
        name varchar(200) NOT NULL DEFAULT '',
        category varchar(100) DEFAULT 'Scenic',
        description text,
        local_tip text,
        photo_url varchar(500) DEFAULT '',
        map_url varchar(500) DEFAULT '',
        sort_order int DEFAULT 0,
        active tinyint DEFAULT 1,
        PRIMARY KEY(id)
    ) ".$wpdb->get_charset_collate().";");
}

add_action('admin_menu', function(){
    add_menu_page('New Places','🗺️ New Places','manage_options','new-places','np_admin_page','dashicons-location',26);
});

function np_admin_page(){
    global $wpdb;
    $t = $wpdb->prefix.'np_places';

    /* ── Save / Update ── */
    if(isset($_POST['np_save']) && check_admin_referer('np_save')){
        $id = intval($_POST['np_id']??0);
        $data = [
            'name'        => sanitize_text_field($_POST['np_name']),
            'category'    => sanitize_text_field($_POST['np_category']),
            'description' => sanitize_textarea_field($_POST['np_desc']),
            'local_tip'   => sanitize_textarea_field($_POST['np_tip']),
            'photo_url'   => esc_url_raw($_POST['np_photo']),
            'map_url'     => esc_url_raw($_POST['np_map']),
            'sort_order'  => intval($_POST['np_order']??0),
            'active'      => isset($_POST['np_active']) ? 1 : 0,
        ];
        if($id) $wpdb->update($t,$data,['id'=>$id]);
        else    $wpdb->insert($t,$data);
        echo '<div class="notice notice-success"><p>✅ Place saved!</p></div>';
    }

    /* ── Delete ── */
    if(isset($_GET['np_delete']) && check_admin_referer('np_del_'.$_GET['np_delete'])){
        $wpdb->delete($t,['id'=>intval($_GET['np_delete'])]);
        echo '<div class="notice notice-success"><p>Place deleted.</p></div>';
    }

    /* ── Toggle active ── */
    if(isset($_GET['np_toggle']) && check_admin_referer('np_tog_'.$_GET['np_toggle'])){
        $cur = $wpdb->get_var($wpdb->prepare("SELECT active FROM {$t} WHERE id=%d",intval($_GET['np_toggle'])));
        $wpdb->update($t,['active'=>$cur?0:1],['id'=>intval($_GET['np_toggle'])]);
    }

    /* ── Edit mode ── */
    $editing = null;
    if(isset($_GET['np_edit'])){
        $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d",intval($_GET['np_edit'])));
    }

    /* ── Filter by category ── */
    $filter_cat = sanitize_text_field($_GET['np_cat'] ?? '');
    $where = $filter_cat ? $wpdb->prepare("WHERE category=%s",$filter_cat) : '';
    $places = $wpdb->get_results("SELECT * FROM {$t} {$where} ORDER BY category ASC, sort_order ASC, id ASC");
    $all_cats_used = $wpdb->get_col("SELECT DISTINCT category FROM {$t} ORDER BY category ASC");

    $cats = [
        '🌄 Scenic','💧 Waterfall','🏖️ Beach','⛏️ Historic',
        '🍺 Dining','⛷️ Adventure','🏛️ Culture','🏕️ State Park',
        '🏝️ National Park','⚓ Village','🎭 Arts','⛏️ Museum',
        '🛶 Paddling','✈️ Airport','⭐ Unique Stop','🍽️ Restaurant',
        '🏔️ Mountains','🌊 Lake','🌲 Forest','🎣 Fishing'
    ];

    /* ── Generate single card ── */
    $gen_single = isset($_GET['np_gen_single']) ? intval($_GET['np_gen_single']) : 0;
    $gen_all    = isset($_GET['np_generate']);
    $gen_cat    = sanitize_text_field($_GET['np_gen_cat'] ?? '');
    ?>
    <div class="wrap">
    <h1>🗺️ New Places — Destination Manager</h1>
    <p style="color:#555">Add places here. Use <strong>Generate Single Card</strong> to get just one card to paste into the existing page. Use <strong>Generate Full Page</strong> to rebuild everything.</p>

    <div style="display:grid;grid-template-columns:400px 1fr;gap:28px;margin-top:20px;align-items:start">

    <!-- ── FORM ── -->
    <div style="background:#fff;padding:22px;border:1px solid #ddd;border-radius:8px;position:sticky;top:32px">
    <h2 style="margin-top:0"><?php echo $editing?'✏️ Edit: '.esc_html($editing->name):'➕ Add New Place';?></h2>
    <form method="post">
    <?php wp_nonce_field('np_save');?>
    <input type="hidden" name="np_id" value="<?php echo $editing?intval($editing->id):0;?>">

    <p><label><strong>Name *</strong><br>
    <input name="np_name" style="width:100%;margin-top:4px" required value="<?php echo esc_attr($editing->name??'');?>" placeholder="e.g. Copper Harbor"></label></p>

    <p><label><strong>Category</strong><br>
    <select name="np_category" style="width:100%;margin-top:4px">
    <?php foreach($cats as $c):
        $sel=($editing && $editing->category===$c)?'selected':'';
    ?>
    <option value="<?php echo esc_attr($c);?>" <?php echo $sel;?>><?php echo $c;?></option>
    <?php endforeach;?>
    </select></label></p>

    <p><label><strong>Description</strong><br>
    <textarea name="np_desc" rows="3" style="width:100%;margin-top:4px"><?php echo esc_textarea($editing->description??'');?></textarea></label></p>

    <p><label><strong>Local Tip</strong><br>
    <input name="np_tip" style="width:100%;margin-top:4px" value="<?php echo esc_attr($editing->local_tip??'');?>" placeholder="Short insider tip..."></label></p>

    <p><label><strong>Photo URL</strong><br>
    <input name="np_photo" style="width:100%;margin-top:4px" value="<?php echo esc_attr($editing->photo_url??'');?>" placeholder="https://...">
    <small><a href="<?php echo admin_url('media-new.php');?>" target="_blank">Upload photo →</a> then copy URL</small></label></p>

    <p><label><strong>Google Maps URL</strong> <small style="color:#888">(optional)</small><br>
    <input name="np_map" style="width:100%;margin-top:4px" value="<?php echo esc_attr($editing->map_url??'');?>" placeholder="https://www.google.com/maps/..."></label></p>

    <p>
    <label><strong>Sort Order</strong> <small style="color:#888">lower = first</small><br>
    <input name="np_order" type="number" value="<?php echo intval($editing->sort_order??0);?>" style="width:70px;margin-top:4px"></label>
    &nbsp;&nbsp;
    <label style="vertical-align:middle"><input type="checkbox" name="np_active" <?php checked(!$editing||$editing->active);?>> <strong>Show on page</strong></label>
    </p>

    <p>
    <input type="submit" name="np_save" class="button button-primary" value="<?php echo $editing?'💾 Update Place':'➕ Add Place';?>">
    <?php if($editing): ?>&nbsp;<a href="?page=new-places" class="button">Cancel</a><?php endif;?>
    </p>
    </form>
    </div>

    <!-- ── LIST + GENERATE ── -->
    <div>

    <!-- Filter bar -->
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:16px">
        <strong>Filter by category:</strong>
        <a href="?page=new-places" class="button button-small <?php echo !$filter_cat?'button-primary':'';?>">All (<?php echo $wpdb->get_var("SELECT COUNT(*) FROM {$t}");?>)</a>
        <?php foreach($all_cats_used as $uc):
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE category=%s",$uc));
        ?>
        <a href="?page=new-places&np_cat=<?php echo urlencode($uc);?>"
           class="button button-small <?php echo $filter_cat===$uc?'button-primary':'';?>">
           <?php echo esc_html($uc);?> (<?php echo $count;?>)
        </a>
        <?php endforeach;?>
    </div>

    <!-- Generate buttons -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;padding:14px;background:#f0f7f0;border-radius:8px;border:1px solid #c3e6cb">
        <div>
            <strong style="display:block;margin-bottom:6px">⚙️ Generate Options</strong>
            <a href="?page=new-places&np_generate=1<?php echo $filter_cat?'&np_gen_cat='.urlencode($filter_cat):'';?>"
               class="button button-primary">
               📄 Generate Full Page Code <?php echo $filter_cat?'('.esc_html($filter_cat).' only)':'(all active)';?>
            </a>
            &nbsp;
            <small style="color:#555;display:block;margin-top:4px">Rebuilds entire Suggested Places page. Replaces everything.</small>
        </div>
    </div>

    <!-- Places table -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <h2 style="margin:0">
            <?php echo $filter_cat ? esc_html($filter_cat).' Places' : 'All Places'; ?>
            (<?php echo count($places);?>)
        </h2>
    </div>

    <?php if($places): ?>
    <table class="widefat" style="font-size:.88rem">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Category</th>
                <th>Photo</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($places as $p): ?>
        <tr style="<?php echo $p->active?'':'background:#fafafa;opacity:.6';?>">
            <td><?php echo intval($p->sort_order);?></td>
            <td><strong><?php echo esc_html($p->name);?></strong></td>
            <td><span style="font-size:.85rem"><?php echo esc_html($p->category);?></span></td>
            <td><?php echo $p->photo_url?'✅':'❌';?></td>
            <td><?php echo $p->active?'✅':'⏸️';?></td>
            <td style="white-space:nowrap">
                <a href="?page=new-places&np_edit=<?php echo $p->id;?>" class="button button-small">✏️ Edit</a>
                <a href="?page=new-places&np_gen_single=<?php echo $p->id;?>" class="button button-small" style="background:#e8f5e9;border-color:#81c784">📋 Get Card</a>
                <a href="<?php echo wp_nonce_url('?page=new-places&np_toggle='.$p->id,'np_tog_'.$p->id);?>" class="button button-small"><?php echo $p->active?'🙈 Hide':'👁️ Show';?></a>
                <a href="<?php echo wp_nonce_url('?page=new-places&np_delete='.$p->id,'np_del_'.$p->id);?>"
                   class="button button-small" style="color:#c00"
                   onclick="return confirm('Delete <?php echo esc_js($p->name);?>?')">🗑️ Del</a>
            </td>
        </tr>
        <?php endforeach;?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color:#888;padding:20px;background:#f9f9f9;border-radius:6px;text-align:center">No places found. Add your first one!</p>
    <?php endif;?>

    <!-- ── SINGLE CARD GENERATOR ── -->
    <?php if($gen_single):
        $p = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d",$gen_single));
        if($p): ?>
    <div style="margin-top:28px;padding:20px;background:#fff8e1;border:2px solid #c9a84c;border-radius:8px">
        <h3 style="margin-top:0">📋 Single Card Code — <?php echo esc_html($p->name);?></h3>
        <p style="color:#555;font-size:.9rem">Copy this one card and paste it inside the <code>&lt;div class="sp-grid"&gt;</code> on your Suggested Places page. Does NOT replace existing cards.</p>
        <?php
        $map = $p->map_url ?: 'https://www.google.com/maps/search/'.urlencode($p->name.' Michigan');
        $photo = $p->photo_url
            ? '<img class="sp-photo" src="'.esc_url($p->photo_url).'" alt="'.esc_attr($p->name).'" loading="lazy">'
            : '<div class="sp-photo-empty">📍</div>';
        $tip = $p->local_tip ? "\n      <div class=\"sp-tip\">📍 ".esc_html($p->local_tip).'</div>' : '';
        $card = '  <div class="sp-card">
    '.$photo.'
    <div class="sp-body">
      <span class="sp-tag">'.esc_html($p->category).'</span>
      <p class="sp-name">'.esc_html($p->name).'</p>
      <p class="sp-desc">'.esc_html($p->description).'</p>'.$tip.'
      <div class="sp-acts">
        <a class="sp-ride" href="/?dropoff='.urlencode($p->name).'#booking">🖊️ Book Ride Here</a>
        <a class="sp-map" href="'.esc_url($map).'" target="_blank" rel="noopener">📍 Map</a>
      </div>
    </div>
  </div>';
        ?>
        <textarea style="width:100%;height:200px;font-family:monospace;font-size:12px;border:1px solid #ccc;border-radius:4px;padding:10px" onclick="this.select()"><?php echo esc_textarea($card);?></textarea>
        <p style="margin:8px 0 0;font-size:.85rem;color:#666">
            <strong>Where to paste:</strong> Pages → Suggested Places → Code editor → find <code>&lt;/div&gt;&lt;/div&gt;&lt;/div&gt;</code> at the very end → paste this card BEFORE those closing tags → Save.
        </p>
    </div>
    <?php endif; endif;?>

    <!-- ── FULL PAGE GENERATOR ── -->
    <?php if($gen_all):
        $where2 = $gen_cat ? $wpdb->prepare("WHERE active=1 AND category=%s",$gen_cat) : "WHERE active=1";
        $gen_places = $wpdb->get_results("SELECT * FROM {$t} {$where2} ORDER BY category ASC, sort_order ASC, id ASC");
        np_generate_full_page($gen_places, $gen_cat);
    endif;?>

    </div><!-- end right col -->
    </div><!-- end grid -->
    </div><!-- end wrap -->
    <?php
}

/* ── Full page code generator ─────────────────────────── */
function np_generate_full_page($places, $filter_cat=''){
    $cards = '';
    foreach($places as $p){
        $map = $p->map_url ?: 'https://www.google.com/maps/search/'.urlencode($p->name.' Michigan');
        $photo = $p->photo_url
            ? '<img class="sp-photo" src="'.esc_url($p->photo_url).'" alt="'.esc_attr($p->name).'" loading="lazy">'
            : '<div class="sp-photo-empty">📍</div>';
        $tip = $p->local_tip ? "\n      <div class=\"sp-tip\">📍 ".esc_html($p->local_tip).'</div>' : '';
        $cards .= '
  <div class="sp-card">
    '.$photo.'
    <div class="sp-body">
      <span class="sp-tag">'.esc_html($p->category).'</span>
      <p class="sp-name">'.esc_html($p->name).'</p>
      <p class="sp-desc">'.esc_html($p->description).'</p>'.$tip.'
      <div class="sp-acts">
        <a class="sp-ride" href="/?dropoff='.urlencode($p->name).'#booking">🖊️ Book Ride Here</a>
        <a class="sp-map" href="'.esc_url($map).'" target="_blank" rel="noopener">📍 Map</a>
      </div>
    </div>
  </div>';
    }

    $code = '<!-- wp:html -->
<style>
body,.site,.site-content,.entry-content,.wp-site-blocks,#page,#content,#primary,#main,.main-content,.page-content,.entry,.hentry{background:transparent !important}
.sp-fullbg{position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:-1;background:linear-gradient(rgba(10,24,10,.83) 0%,rgba(15,38,16,.79) 40%,rgba(10,24,10,.89) 100%),url(\'https://asuperiortransportation.com/wp-content/uploads/2026/05/brockway-mountain.jpg\') center top/cover no-repeat fixed}
.sp-page{min-height:100vh;padding:40px 16px 60px;position:relative;z-index:1}
.sp-wrap{max-width:1100px;margin:0 auto}
.sp-hero{text-align:center;padding:40px 20px 36px;color:#fff}
.sp-hero h1{font-size:clamp(1.8rem,5vw,2.8rem);font-weight:800;margin-bottom:10px;color:#fff}
.sp-hero h1 span{color:#c9a84c}
.sp-hero p{color:rgba(255,255,255,.75);font-size:1.05rem}
.sp-intro{background:rgba(201,168,76,.15);border-left:4px solid #c9a84c;border-radius:6px;padding:16px 20px;margin-bottom:36px;font-size:1rem;color:#fff}
.sp-intro a{color:#c9a84c;font-weight:700}
.sp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px}
.sp-card{border-radius:10px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.4);background:#162518;border:1px solid rgba(255,255,255,.07);display:flex;flex-direction:column;transition:transform .15s,box-shadow .15s}
.sp-card:hover{transform:translateY(-4px);box-shadow:0 10px 32px rgba(0,0,0,.5)}
.sp-photo{width:100%;height:185px;object-fit:cover;display:block}
.sp-photo-empty{width:100%;height:185px;background:rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center;font-size:2.5rem}
.sp-body{padding:16px 18px 20px;display:flex;flex-direction:column;gap:7px;flex:1}
.sp-tag{display:inline-block;background:rgba(201,168,76,.2);color:#c9a84c;border:1px solid rgba(201,168,76,.3);border-radius:4px;padding:2px 10px;font-size:.78rem;font-weight:700;width:fit-content}
.sp-name{font-size:1.1rem;font-weight:700;color:#c9a84c;margin:0}
.sp-desc{font-size:.88rem;color:rgba(255,255,255,.72);line-height:1.6;margin:0;flex:1}
.sp-tip{font-size:.82rem;color:rgba(255,255,255,.45);font-style:italic;border-left:3px solid #c9a84c;padding-left:10px}
.sp-acts{display:flex;gap:10px;margin-top:8px}
.sp-ride{flex:1;background:#c9a84c;color:#111;padding:9px 14px;border-radius:6px;font-weight:700;font-size:.88rem;text-align:center;text-decoration:none;transition:background .15s}
.sp-ride:hover{background:#f0c040;color:#111;text-decoration:none}
.sp-map{background:rgba(255,255,255,.1);color:rgba(255,255,255,.8);padding:9px 13px;border-radius:6px;font-size:.85rem;text-decoration:none;border:1px solid rgba(255,255,255,.15);white-space:nowrap;transition:background .15s}
.sp-map:hover{background:rgba(255,255,255,.2);color:#fff;text-decoration:none}
@media(max-width:600px){.sp-grid{grid-template-columns:1fr}}
</style>
<script>document.addEventListener("DOMContentLoaded",function(){document.body.style.background="transparent";["#page","#content","#primary","#main",".site",".site-content",".entry-content",".hentry"].forEach(function(s){var e=document.querySelector(s);if(e)e.style.background="transparent";});});</script>
<div class="sp-fullbg"></div>
<div class="sp-page"><div class="sp-wrap">
<div class="sp-hero"><h1>📍 Suggested <span>Places</span></h1><p>Copper Country\'s best spots — we will take you there</p></div>
<div class="sp-intro">Need a ride? <strong>Call <a href="tel:9063704094">906-370-4094</a></strong> or <a href="/#booking">book online</a>.</div>
<div class="sp-grid">'.$cards.'
</div></div></div>
<!-- /wp:html -->';

    $title = $filter_cat ? 'Full Page Code — '.esc_html($filter_cat).' only ('.count($places).' places)' : 'Full Page Code — All '.count($places).' active places';

    echo '<div style="margin-top:28px;padding:20px;background:#e8f5e9;border:2px solid #2e7d32;border-radius:8px">';
    echo '<h3 style="margin-top:0">📄 '.$title.'</h3>';
    echo '<p style="color:#555;font-size:.9rem"><strong>⚠️ This replaces everything on the page.</strong> Click inside box → Ctrl+A → Ctrl+C → go to Pages → Suggested Places → Code editor → Ctrl+A → Ctrl+V → Save.</p>';
    echo '<textarea style="width:100%;height:340px;font-family:monospace;font-size:11px;border:1px solid #ccc;border-radius:4px;padding:10px" onclick="this.select()">'.esc_textarea($code).'</textarea>';
    echo '</div>';
}
