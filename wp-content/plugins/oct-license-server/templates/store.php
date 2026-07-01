<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$s     = get_option('oct_ls_settings', []);
$tiers = OCT_LS_TIERS;
?>
<div class="oct-store-wrap">

  <!-- HERO -->
  <div class="oct-store-hero">
    <div class="oct-store-hero-inner">
      <div class="oct-store-badge">🚕 OneClick Taxi Ordering App</div>
      <h1>Professional Taxi Booking Software<br><span>for Your WordPress Site</span></h1>
      <p class="oct-store-tagline">"Install it, follow the steps, and in 20 minutes you'll have your own ordering app for taxi service."</p>
      <div class="oct-store-features">
        <span>✅ Live Google Maps</span>
        <span>✅ Square Payments</span>
        <span>✅ Calendar Scheduling</span>
        <span>✅ Push Notifications</span>
        <span>✅ Flat Rate Pricing</span>
        <span>✅ 20-Minute Setup</span>
      </div>
    </div>
  </div>

  <!-- PRICING -->
  <div class="oct-store-pricing" id="pricing">
    <h2>Choose Your License</h2>
    <p class="oct-store-subtitle">All licenses include full plugin access, setup wizard, and email support. One license = one WordPress installation.</p>

    <div class="oct-pricing-grid">
      <?php
      $icons = ['30day'=>'⏱️','6month'=>'📆','1year'=>'🗓️','lifetime'=>'♾️'];
      $popular = '1year';
      foreach($tiers as $slug => $tier): ?>
      <div class="oct-price-card <?php echo $slug===$popular?'popular':''; ?>" data-tier="<?php echo esc_attr($slug); ?>">
        <?php if($slug===$popular): ?><div class="oct-popular-badge">⭐ Most Popular</div><?php endif; ?>
        <div class="oct-price-icon"><?php echo $icons[$slug]; ?></div>
        <h3><?php echo esc_html($tier['label']); ?></h3>
        <div class="oct-price-amount"><?php echo esc_html($tier['display']); ?></div>
        <div class="oct-price-period"><?php echo $slug==='lifetime'?'One-time payment':($slug==='30day'?'30-day access':($slug==='6month'?'6 months access':'12 months access')); ?></div>
        <ul class="oct-price-features">
          <li>✅ Full booking platform</li>
          <li>✅ Google Maps routing</li>
          <li>✅ Square card payments</li>
          <li>✅ Calendar auto-scheduling</li>
          <li>✅ Push notifications</li>
          <li>✅ 4-color brand theming</li>
          <li>✅ Flat rate destinations</li>
          <li>✅ Setup wizard included</li>
          <?php if($slug==='lifetime'): ?>
          <li>✅ All future updates</li>
          <li>✅ Priority email support</li>
          <?php elseif($slug==='1year'): ?>
          <li>✅ 12 months of updates</li>
          <?php endif; ?>
        </ul>
        <button class="oct-select-tier" data-tier="<?php echo esc_attr($slug); ?>" data-label="<?php echo esc_attr($tier['label']); ?>" data-price="<?php echo esc_attr($tier['display']); ?>">
          Get <?php echo esc_html($tier['label']); ?> →
        </button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- *** DISCLAIMER *** -->
  <div class="oct-disclaimer-box" id="oct-disclaimer">
    <div class="oct-disclaimer-inner">
      <div class="oct-disclaimer-icon">⚠️</div>
      <h3>No-Refund Policy — Please Read Before Purchasing</h3>
      <div class="oct-disclaimer-text">
        <p><strong>By completing your purchase, you acknowledge and agree to the following:</strong></p>
        <ol>
          <li><strong>No Refunds.</strong> All sales are final. Once a license key is issued and the plugin is available for download, no refunds will be issued under any circumstances.</li>
          <li><strong>Tested Product.</strong> OneClick Taxi Ordering App has been thoroughly tested and is confirmed to function as described. It is your responsibility to follow the provided setup instructions precisely. Failure to follow the instructions does not constitute a product defect and will not result in a refund.</li>
          <li><strong>No Chargebacks.</strong> Initiating a chargeback or payment dispute for this digital product is considered fraud. We reserve the right to pursue all available legal remedies in the event of an unauthorized chargeback.</li>
          <li><strong>Our Error Exception.</strong> The only circumstance under which a monetary adjustment may be considered is if A Superior Transportation & Logistics has made a verifiable error on our side — such as a duplicate charge, an incorrect amount, or a proven defect in the software that prevents it from functioning at all as described, and only after our support team has attempted to resolve the issue and is unable to do so.</li>
          <li><strong>One License, One Installation.</strong> Each license key is locked to one domain and one server. Sharing, reselling, or transferring your license key is prohibited and will result in immediate permanent revocation without refund.</li>
          <li><strong>IP Blocking.</strong> Attempted misuse or sharing of a license key will result in your IP address being permanently blocked from our systems.</li>
          <li><strong>Support.</strong> Email support is available at <a href="mailto:stalcollc@gmail.com">stalcollc@gmail.com</a>. We will make reasonable efforts to assist you in getting your installation working correctly.</li>
        </ol>
        <p class="oct-disclaimer-final">If you do not agree with these terms, do not purchase. By clicking "I Agree &amp; Continue to Purchase" below, you confirm that you have read, understood, and accept this policy in full.</p>
      </div>
    </div>
  </div>

  <!-- PURCHASE FORM -->
  <div class="oct-purchase-section" id="oct-purchase-form" style="display:none">
    <div class="oct-purchase-wrap">
      <h2>Complete Your Purchase</h2>
      <div class="oct-selected-tier-display" id="oct-selected-tier-display"></div>

      <div class="oct-purchase-form">
        <div class="oct-pf-group">
          <label>Full Name *</label>
          <input type="text" id="oct-buyer-name" placeholder="Your full name">
        </div>
        <div class="oct-pf-group">
          <label>Email Address * <small>(your license key will be sent here)</small></label>
          <input type="email" id="oct-buyer-email" placeholder="your@email.com">
        </div>

        <div class="oct-card-section">
          <label>Card Details *</label>
          <div id="oct-card-container"></div>
        </div>

        <!-- Disclaimer checkbox — must agree before purchase button activates -->
        <div class="oct-agree-wrap">
          <label class="oct-agree-label">
            <input type="checkbox" id="oct-agree-disclaimer">
            <span>I have read and agree to the <a href="#oct-disclaimer">No-Refund Policy</a>. I understand that all sales are final, no chargebacks will be accepted, and this license is locked to one installation. <strong>There are no refunds.</strong></span>
          </label>
        </div>

        <div id="oct-purchase-error" class="oct-purchase-status error" style="display:none"></div>
        <div id="oct-purchase-success" class="oct-purchase-status success" style="display:none"></div>

        <button id="oct-purchase-btn" class="oct-purchase-btn" disabled>
          🔒 Complete Purchase
        </button>
        <p class="oct-secure-note">🔒 Secured by Square. Your card details never touch our server.</p>
        <button id="oct-cancel-purchase" class="oct-cancel-btn">← Back to Plans</button>
      </div>
    </div>
  </div>

  <!-- SUCCESS -->
  <div class="oct-purchase-success-wrap" id="oct-success-wrap" style="display:none">
    <div class="oct-success-inner">
      <div style="font-size:4rem">🎉</div>
      <h2>Purchase Complete!</h2>
      <p>Your license key has been emailed to <strong id="oct-success-email"></strong></p>
      <div class="oct-success-key-box">
        <label>Your License Key:</label>
        <div class="oct-key-display" id="oct-success-key"></div>
        <button onclick="navigator.clipboard.writeText(document.getElementById('oct-success-key').textContent)" class="oct-copy-btn">📋 Copy Key</button>
      </div>
      <div class="oct-success-expires">
        <strong>License:</strong> <span id="oct-success-tier"></span><br>
        <strong>Expires:</strong> <span id="oct-success-expires"></span>
      </div>
      <div class="oct-next-steps">
        <h3>Next Steps:</h3>
        <ol>
          <li>Check your email for your license key</li>
          <li>Download the plugin from the link in your email</li>
          <li>Go to WordPress → Plugins → Add New → Upload Plugin</li>
          <li>Activate and enter your license key</li>
          <li>Follow the 20-minute setup wizard</li>
        </ol>
      </div>
      <p><a href="mailto:stalcollc@gmail.com" class="oct-support-link">Need help? Email stalcollc@gmail.com</a></p>
    </div>
  </div>

</div>
