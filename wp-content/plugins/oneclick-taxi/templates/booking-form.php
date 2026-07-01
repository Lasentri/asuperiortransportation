<?php
/* OneClick Taxi — Booking Form Template */
if ( ! defined( 'ABSPATH' ) ) exit;
$s     = oct_get_settings();
$now   = current_time('H:i');
$open  = date('g:i A', strtotime($s['hours_open']));
$close = date('g:i A', strtotime($s['hours_close']));
$is_open = ($now >= $s['hours_open'] && $now <= $s['hours_close']);
?>
<div class="oct-booking-wrap" id="oct-booking">

  <div class="oct-hero-status <?php echo $is_open ? 'open' : 'closed'; ?>">
    <?php echo $is_open ? '🟢 Open Now' : '🔴 Currently Closed'; ?> &nbsp;·&nbsp; <?php echo esc_html("$open – $close"); ?>
  </div>

  <div class="oct-steps">
    <div class="oct-step active" id="oct-step-ind-1">① Ride Details</div>
    <div class="oct-step" id="oct-step-ind-2">② Contact &amp; Book</div>
    <div class="oct-step" id="oct-step-ind-3">③ Done</div>
  </div>

  <div class="oct-booking-layout">
    <div class="oct-form-wrap">

      <!-- STEP 1 -->
      <div class="oct-form-step" id="oct-step-1">
        <div class="oct-form-group">
          <label>PICKUP DATE *</label>
          <input type="date" id="oct-date" min="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="oct-form-group">
          <label>PICKUP TIME *</label>
          <select id="oct-time" required>
            <option value="">Select time</option>
            <?php for($h=6;$h<24;$h++){for($m=0;$m<60;$m+=15){$val=sprintf('%02d:%02d',$h,$m);$disp=date('g:i A',strtotime($val));echo "<option value='{$val}'>{$disp}</option>";}} ?>
          </select>
        </div>
        <div class="oct-form-group">
          <label>PICKUP LOCATION *</label>
          <div style="position:relative">
            <input type="text" id="oct-pickup" placeholder="Enter pickup address" autocomplete="off" spellcheck="false" required style="padding-right:40px">
            <button type="button" id="oct-locate-me" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;">📍</button>
          </div>
        </div>
        <div class="oct-form-group">
          <label>DROP-OFF LOCATION *</label>
          <input type="text" id="oct-dropoff" placeholder="Enter drop-off address" autocomplete="off" spellcheck="false" required>
        </div>

        <!-- Flat Rate Link -->
        <div class="oct-flatrate-hint">
          <span class="oct-flatrate-arrow">&#9658;</span>
          <a href="#" id="oct-flatrate-link" class="oct-flatrate-link">View Flat Rate Destinations</a>
          <span style="font-size:.72rem;color:rgba(255,255,255,.4)">(fixed prices for popular routes)</span>
        </div>

        <!-- Exact address for flat rate -->
        <div id="oct-exact-wrap" style="display:none;margin-bottom:12px">
          <div class="oct-form-group">
            <label>EXACT DROP-OFF ADDRESS *</label>
            <input type="text" id="oct-dropoff-exact" placeholder="Specific street address at destination" autocomplete="off">
          </div>
        </div>

        <div class="oct-form-group">
          <label>PASSENGERS</label>
          <select id="oct-passengers">
            <?php for($i=1;$i<=8;$i++) echo "<option value='{$i}'>{$i} passenger".($i>1?'s':'')."</option>"; ?>
          </select>
        </div>

        <div id="oct-fare-box" style="display:none;background:rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.15);border-radius:6px;padding:14px 18px;margin:14px 0">
          <div class="oct-fare-row"><span>Distance</span><span id="oct-fare-miles">—</span></div>
          <div class="oct-fare-row oct-fare-total"><span>Total</span><span id="oct-fare-amount">—</span></div>
        </div>

        <div id="oct-form-error-1" style="display:none;color:#ef9a9a;background:rgba(198,40,40,.2);padding:9px 13px;border-radius:4px;margin-top:10px;font-size:.85rem"></div>
        <div class="oct-form-actions">
          <button type="button" id="oct-next-1" class="oct-btn-primary">Next: Contact Info →</button>
        </div>
      </div>

      <!-- STEP 2 -->
      <div class="oct-form-step" id="oct-step-2" style="display:none">
        <div class="oct-form-group">
          <label>FULL NAME *</label>
          <input type="text" id="oct-name" placeholder="Your full name" required>
        </div>
        <div class="oct-form-group">
          <label>PHONE *</label>
          <input type="tel" id="oct-phone" placeholder="555-123-4567" required>
        </div>
        <div class="oct-form-group">
          <label>EMAIL</label>
          <input type="email" id="oct-email" placeholder="your@email.com">
        </div>
        <div class="oct-form-group">
          <label>NOTES</label>
          <textarea id="oct-notes" rows="2" placeholder="Luggage, special requests…"></textarea>
        </div>
        <div style="background:rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.1);border-radius:6px;padding:12px 16px;margin:14px 0;font-size:.88rem">
          <div style="display:flex;justify-content:space-between;color:rgba(255,255,255,.6);padding:3px 0"><span>Estimated Fare</span><span id="oct-sum-fare">—</span></div>
        </div>
        <div class="oct-form-group">
          <label>COUPON CODE</label>
          <div style="display:flex;gap:8px">
            <input type="text" id="oct-coupon" placeholder="Enter code" style="flex:1">
            <button type="button" id="oct-apply-coupon" class="oct-btn-back">Apply</button>
          </div>
          <div id="oct-coupon-msg" style="font-size:.82rem;margin-top:6px"></div>
        </div>
        <p style="font-size:.78rem;color:#81c784;text-align:center;margin-bottom:10px">✅ Don't worry if payment doesn't go through — we'll contact you with a payment link.</p>
        <div id="oct-form-error-2" style="display:none;color:#ef9a9a;background:rgba(198,40,40,.2);padding:9px 13px;border-radius:4px;margin-top:10px;font-size:.85rem"></div>
        <div class="oct-form-actions">
          <button type="button" id="oct-back-2" class="oct-btn-back">← Back</button>
          <button type="button" id="oct-confirm-btn" class="oct-btn-primary">Confirm &amp; Book →</button>
        </div>
      </div>

      <!-- STEP 3: Done -->
      <div class="oct-form-step" id="oct-step-3" style="display:none;text-align:center;padding:24px">
        <div style="font-size:3rem;margin-bottom:12px">✅</div>
        <h3 style="color:#81c784;font-size:1.4rem;margin-bottom:10px">Booking Confirmed!</h3>
        <p id="oct-success-msg" style="color:rgba(255,255,255,.7)">We will contact you shortly to confirm your ride.</p>
        <p style="margin-top:16px"><a href="tel:<?php echo preg_replace('/\D/','',$s['phone']); ?>" class="oct-btn-primary">📞 <?php echo esc_html($s['phone']); ?></a></p>
      </div>

    </div>

    <div>
      <div id="oct-map" style="height:500px;border-radius:6px"></div>
      <p style="font-size:.75rem;color:rgba(255,255,255,.5);padding:8px 0">Tip: click anywhere on the map to set pickup or dropoff location.</p>
    </div>

  </div>
</div>
