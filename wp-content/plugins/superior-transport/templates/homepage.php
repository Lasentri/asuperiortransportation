<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$s       = st_get_settings();
$open    = date('g:i A', strtotime($s['hours_open']));
$close   = date('g:i A', strtotime($s['hours_close']));
$now     = current_time('H:i');
$is_open = ($now >= $s['hours_open'] && $now <= $s['hours_close']);
include ST_DIR . 'templates/inc-header.php';
?>

<!-- HERO -->
<section class="st-hero">
  <div class="st-hero-overlay"></div>
  <div class="st-hero-content">

    <div class="st-hero-logo">
      <div class="st-hero-logo-box">
        <div class="st-hero-logo-icon"><img src="https://asuperiortransportation.com/wp-content/uploads/2026/06/logo3.png" width=80 align=left></div>
        <div class="st-hero-logo-text">
          <span class="st-hero-logo-name">A Superior Transportation</span>
          <span class="st-hero-logo-tag">&amp; Logistics · Est. 2017</span>
        </div>
      </div>
    </div>

    <h1><font color=#FFFfff>Your Ride</font>. Your Schedule. <font color=#ffffff>Your UP.</font></h1>
    <p>Professional transportation across Houghton, Hancock, Calumet &amp; the entire Western U.P. Corridor</p>
    <div class="st-hero-badges">
      <span class="st-badge">✅ Metered &amp; Flat Rate</span>
      <span class="st-badge">📍 Live GPS</span>
      <span class="st-badge">💳 Square &amp; Cash</span>
      <span class="st-badge">📞 Phone Confirmed</span>
      <span class="st-badge">🌙 Non-Midnight Daily</span>
    </div>
    <div class="st-hero-status <?php echo $is_open ? 'open' : 'closed';?>">
      <?php echo $is_open ? '🟢 Open Now' : '🔴 Currently Closed';?> &nbsp;·&nbsp; <?php echo esc_html("$open – $close");?>
    </div>
    <p class="st-hero-note">All taxis outside Houghton &amp; Hancock require payment before departure.</p>
    <a href="https://asuperiortransportation.com/76-2/"><span class="st-badge">🌙 Check Availability</span></a>
    <a href="https://asuperiortransportation.com/suggested-places/"><span class="st-badge">✅ Tour Suggestions</span></a>
    <a href="https://asuperiortransportation.com/up-gas-price-tracker/"><span class="st-badge">Transparency Promise</span></a>

  </div>
</section>

<!-- BOOKING FORM -->
<section class="st-booking" id="booking">
  <div class="st-container">

    <div class="st-steps">
      <div class="st-step active" id="step-ind-1">① Ride Details</div>
      <div class="st-step" id="step-ind-2">② Contact &amp; Book</div>
      <div class="st-step" id="step-ind-3">③ Done</div>
    </div>

    <div class="st-booking-layout">

      <div class="st-booking-form-wrap">

        <!-- STEP 1 -->
        <div class="st-form-step" id="step-1">
          <h3>Ride Details</h3>
          <div class="st-form-row">
            <div class="st-form-group">
              <label>PICKUP DATE <?php if($s['require_date']==='1') echo '<span class="req">*</span>';?></label>
              <input type="date" id="st-date" name="date" min="<?php echo date('Y-m-d');?>" <?php if($s['require_date']==='1') echo 'required';?>>
            </div>
            <div class="st-form-group">
              <label>PICKUP TIME <?php if($s['require_time']==='1') echo '<span class="req">*</span>';?></label>
              <select id="st-time" name="time" <?php if($s['require_time']==='1') echo 'required';?>>
                <option value="">Select time</option>
                <?php for($h=0;$h<24;$h++){for($m=0;$m<60;$m+=15){$val=sprintf('%02d:%02d',$h,$m);$disp=date('g:i A',strtotime($val));echo "<option value='{$val}'>{$disp}</option>";}}?>
              </select>
            </div>
          </div>
          <div class="st-form-group">
            <label>PICKUP LOCATION <?php if($s['require_pickup']==='1') echo '<span class="req">*</span>';?></label>
            <div class="st-input-wrap">
              <input type="text" id="st-pickup" name="pickup" placeholder="Enter pickup address" autocomplete="off" spellcheck="false" <?php if($s['require_pickup']==='1') echo 'required';?>>
              <button type="button" id="st-locate-me" class="st-locate-btn" title="Use my location">📍</button>
            </div>
          </div>
          <div class="st-form-group">
            <label>DROP-OFF LOCATION <?php if($s['require_dropoff']==='1') echo '<span class="req">*</span>';?></label>
            <input type="text" id="st-dropoff" name="dropoff" placeholder="Enter drop-off address" autocomplete="off" spellcheck="false" <?php if($s['require_dropoff']==='1') echo 'required';?>>
          </div>
          <?php if($s['show_passengers']==='1'): ?>
          <div class="st-form-group">
            <label>PASSENGERS <?php if($s['require_passengers']==='1') echo '<span class="req">*</span>';?></label>
            <select id="st-passengers" name="passengers">
              <?php for($i=1;$i<=8;$i++) echo "<option value='{$i}'>{$i} passenger".($i>1?'s':'')."</option>";?>
            </select>
          </div>
          <?php endif;?>
          <div class="st-av-note">
            📅 <strong>Check our schedule</strong> then call to confirm.<br>
            <a href="#" id="st-show-calendar">View open times below</a> · <a href="tel:9063704094">Call 906-370-4094</a>
          </div>
          <div class="st-fare-box" id="st-fare-box" style="display:none">
            <div class="st-fare-row"><span>Distance</span><span id="st-fare-miles">—</span></div>
            <div class="st-fare-row st-fare-total"><span>Total</span><span id="st-fare-amount">—</span></div>
          </div>
          <div class="st-form-error" id="st-form-error-1" style="display:none"></div>
          <div class="st-form-actions">
            <button type="button" id="st-next-1" class="st-btn-primary">Next: Contact Info →</button>
          </div>
        </div>

        <!-- STEP 2 - Contact + Confirm -->
        <div class="st-form-step" id="step-2" style="display:none">
          <h3>Contact Info</h3>
          <div class="st-form-group">
            <label>FULL NAME <?php if($s['require_name']==='1') echo '<span class="req">*</span>';?></label>
            <input type="text" id="st-name" name="name" placeholder="Your full name" <?php if($s['require_name']==='1') echo 'required';?>>
          </div>
          <div class="st-form-group">
            <label>PHONE <?php if($s['require_phone']==='1') echo '<span class="req">*</span>';?></label>
            <input type="tel" id="st-phone" name="phone" placeholder="906-555-0000" <?php if($s['require_phone']==='1') echo 'required';?>>
          </div>
          <?php if($s['show_email']==='1'): ?>
          <div class="st-form-group">
            <label>EMAIL <?php if($s['require_email']==='1') echo '<span class="req">*</span>';?></label>
            <input type="email" id="st-email" name="email" placeholder="your@email.com" <?php if($s['require_email']==='1') echo 'required';?>>
          </div>
          <?php endif;?>
          <?php if($s['show_notes']==='1'): ?>
          <div class="st-form-group">
            <label>NOTES <?php if($s['require_notes']==='1') echo '<span class="req">*</span>';?></label>
            <textarea id="st-notes" name="notes" rows="3" placeholder="Luggage, special requests…" <?php if($s['require_notes']==='1') echo 'required';?>></textarea>
          </div>
          <?php endif;?>
          <div class="st-summary-box">
            <div class="st-summary-row"><span>Distance</span><span id="st-sum-miles">—</span></div>
            <div class="st-summary-row st-summary-total"><span>Estimated Fare</span><span id="st-sum-fare">—</span></div>
          </div>
          <div class="st-form-group" style="margin-top:10px">
            <label>COUPON CODE</label>
            <div class="st-coupon-wrap">
              <input type="text" id="st-coupon" placeholder="Enter coupon code" style="text-transform:uppercase">
              <button type="button" id="st-apply-coupon" class="st-btn-secondary">Apply</button>
            </div>
            <div id="st-coupon-msg" style="margin-top:6px;font-size:.85rem"></div>
          </div>
          <div class="st-form-error" id="st-form-error-2" style="display:none"></div>
          <p style="font-size:.78rem;color:#81c784;margin-bottom:10px;text-align:center;">✅ Don't worry if your CC doesn't go through — our dispatcher will reach out with a secure payment link.</p>
          <div class="st-form-actions">
            <button type="button" id="st-back-2" class="st-btn-back">← Back</button>
            <button type="button" id="st-confirm-btn" class="st-btn-primary">Confirm &amp; Book →</button>
          </div>
        </div>

        <!-- STEP 3 - Done -->
        <div class="st-form-step" id="step-3" style="display:none">
          <div class="st-success-box">
            <div class="st-success-icon">✅</div>
            <h3>Booking Confirmed!</h3>
            <p id="st-success-msg">We will call you shortly to confirm your ride.</p>
            <div class="st-av-info-card">
              <div class="st-av-info-row">📍 Houghton · Hancock · Calumet</div>
              <div class="st-av-info-row">💳 Dispatcher will send payment link if needed</div>
              <div class="st-av-info-row">💵 Cash paid at time of service</div>
            </div>
            <a href="tel:9063704094" class="st-btn-primary" style="display:inline-block;margin-top:12px">📞 906-370-4094</a>
          </div>
        </div>

      </div><!-- /.st-booking-form-wrap -->

      <div class="st-map-wrap">
        <div id="st-map"></div>
        <p class="st-map-hint">Tip: click anywhere on the map to set pickup or dropoff location.</p>
      </div>

    </div><!-- /.st-booking-layout -->

    <div id="st-cal-wrap" style="display:none;margin-top:30px">
      <iframe src="https://calendar.google.com/calendar/embed?src=driversstalco%40gmail.com&ctz=America%2FDetroit&mode=WEEK&showTitle=0&showNav=1&showDate=1&showPrint=0&showTabs=0&showCalendars=0"
              style="border:0;width:100%;min-height:500px" frameborder="0" scrolling="no"></iframe>
    </div>

  </div>
</section>

<!-- WHY RIDE -->
<section class="st-why">
  <div class="st-container">
    <h2>Why Ride With Us</h2>
    <p class="st-why-sub">Serving the Keweenaw Peninsula professionally since 2017</p>
    <div class="st-why-grid">
      <div class="st-why-card"><div class="st-why-icon">📍</div><strong>Live GPS Routes</strong><p>Real-time navigation and route optimization.</p></div>
      <div class="st-why-card"><div class="st-why-icon">💰</div><strong>Transparent Pricing</strong><p>$4.20/mile .</p></div>
      <div class="st-why-card"><div class="st-why-icon">🕐</div><strong>7 Days a Week</strong><p>6:00 AM to 11:59 PM daily or by arrangement. <br>(Call for Midnight Flights)</p></div>
    </div>
  </div>
</section>

<!-- INFO STRIP -->
<section class="st-info-strip">
  <div class="st-container st-info-grid">
    <div class="st-info-item"><strong>📞 Phone</strong><a href="tel:<?php echo preg_replace('/\D/','',$s['phone']);?>"><?php echo esc_html($s['phone']);?></a></div>
    <div class="st-info-item"><strong>📧 Email</strong><a href="mailto:<?php echo esc_attr($s['email']);?>"><?php echo esc_html($s['email']);?></a></div>
    <div class="st-info-item"><strong>📍 Address</strong><span><?php echo esc_html($s['address']);?></span></div>
    <div class="st-info-item"><strong>🕐 Hours</strong><span><?php echo esc_html("$open – $close");?></span></div>
  </div>
</section>

<?php include ST_DIR . 'templates/inc-footer.php'; ?>
