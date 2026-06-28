<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function oct_wizard_page() {
    $step     = intval( get_option('oct_setup_step', 1) );
    $complete = intval( get_option('oct_setup_complete', 0) );
    $steps = [
        1  => ['icon'=>'👋', 'title'=>'Welcome',           'desc'=>'Get your taxi app ready in 20 minutes'],
        2  => ['icon'=>'🏢', 'title'=>'Business Info',     'desc'=>'Your company name, phone & hours'],
        3  => ['icon'=>'🗺️', 'title'=>'Google Maps',       'desc'=>'Enable live routing & address search'],
        4  => ['icon'=>'📅', 'title'=>'Google Calendar',   'desc'=>'Auto-schedule rides on your calendar'],
        5  => ['icon'=>'💳', 'title'=>'Square Payments',   'desc'=>'Accept card payments at booking'],
        6  => ['icon'=>'📲', 'title'=>'Pushover Alerts',   'desc'=>'Get instant notifications on your phone'],
        7  => ['icon'=>'🎨', 'title'=>'Brand Colors',      'desc'=>'Match your company colors'],
        8  => ['icon'=>'🖼️', 'title'=>'Logo & Name',       'desc'=>'Upload your logo'],
        9  => ['icon'=>'🗺️', 'title'=>'Flat Rate Zones',   'desc'=>'Set fixed prices for popular destinations'],
        10 => ['icon'=>'🚀', 'title'=>'Launch!',           'desc'=>'Your app is ready to take bookings'],
    ];
    ?>
    <div class="wrap oct-wizard-wrap">
        <div class="oct-wizard-header">
            <div class="oct-wizard-logo">🚕</div>
            <h1>OneClick Taxi Ordering App</h1>
            <p class="oct-wizard-tagline">Setup Wizard — Complete all steps to launch your booking app</p>
        </div>

        <!-- Progress Bar -->
        <div class="oct-progress-bar">
            <div class="oct-progress-fill" style="width:<?php echo min(100, (($step-1)/9)*100); ?>%"></div>
        </div>
        <p class="oct-progress-text">Step <?php echo $step; ?> of 10 — <?php echo $steps[$step]['title']; ?></p>

        <!-- Step Nav -->
        <div class="oct-step-nav">
            <?php foreach($steps as $n => $s): ?>
            <div class="oct-step-dot <?php echo $n < $step ? 'done' : ($n === $step ? 'active' : 'pending'); ?>" title="<?php echo esc_attr($s['title']); ?>">
                <?php echo $n < $step ? '✅' : $s['icon']; ?>
                <span><?php echo $s['title']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Current Step Content -->
        <div class="oct-wizard-card">
            <?php
            switch($step) {
                case 1:  oct_wizard_step_1();  break;
                case 2:  oct_wizard_step_2();  break;
                case 3:  oct_wizard_step_3();  break;
                case 4:  oct_wizard_step_4();  break;
                case 5:  oct_wizard_step_5();  break;
                case 6:  oct_wizard_step_6();  break;
                case 7:  oct_wizard_step_7();  break;
                case 8:  oct_wizard_step_8();  break;
                case 9:  oct_wizard_step_9();  break;
                case 10: oct_wizard_step_10(); break;
            }
            ?>
        </div>
    </div>
    <?php
}

/* AJAX: advance wizard step */
add_action('wp_ajax_oct_wizard_next', function(){
    check_ajax_referer('oct_nonce','nonce');
    $next = intval($_POST['next'] ?? 2);
    update_option('oct_setup_step', $next);
    wp_send_json_success(['step' => $next]);
});

/* AJAX: verify Google Maps key */
add_action('wp_ajax_oct_verify_maps', function(){
    check_ajax_referer('oct_nonce','nonce');
    $key = sanitize_text_field($_POST['key'] ?? '');
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=New+York&key={$key}";
    $r   = wp_remote_get($url, ['timeout'=>8]);
    if(is_wp_error($r)) wp_send_json_error('Could not reach Google API.');
    $body = json_decode(wp_remote_retrieve_body($r), true);
    if(($body['status'] ?? '') === 'OK') {
        $s = oct_get_settings(); $s['google_maps_key'] = $key; update_option('oct_settings',$s);
        wp_send_json_success('✅ Google Maps API key verified and saved!');
    } else {
        wp_send_json_error('❌ Invalid key: '.($body['error_message'] ?? $body['status']));
    }
});

/* AJAX: verify Square */
add_action('wp_ajax_oct_verify_square', function(){
    check_ajax_referer('oct_nonce','nonce');
    $token    = sanitize_text_field($_POST['token']    ?? '');
    $location = sanitize_text_field($_POST['location'] ?? '');
    $app_id   = sanitize_text_field($_POST['app_id']   ?? '');
    $r = wp_remote_get("https://connect.squareup.com/v2/locations/{$location}", [
        'headers' => ['Authorization'=>"Bearer {$token}", 'Square-Version'=>'2024-01-18'],
        'timeout' => 8,
    ]);
    if(is_wp_error($r)) wp_send_json_error('Could not reach Square API.');
    $body = json_decode(wp_remote_retrieve_body($r), true);
    if(isset($body['location']['id'])) {
        $s = oct_get_settings();
        $s['square_app_id']   = $app_id;
        $s['square_token']    = $token;
        $s['square_location'] = $location;
        update_option('oct_settings', $s);
        wp_send_json_success('✅ Square connected! Location: '.$body['location']['name']);
    } else {
        wp_send_json_error('❌ Square error: '.($body['errors'][0]['detail'] ?? 'Invalid credentials'));
    }
});

/* AJAX: verify Pushover */
add_action('wp_ajax_oct_verify_pushover', function(){
    check_ajax_referer('oct_nonce','nonce');
    $email = sanitize_email($_POST['email'] ?? '');
    if(!$email) wp_send_json_error('Please enter your Pushover email alias.');
    $sent = wp_mail($email, 'OneClick Taxi - Test Alert', 'Setup wizard test: your alerts are working!');
    if($sent) {
        $s = oct_get_settings(); $s['pushover_email'] = $email; update_option('oct_settings',$s);
        wp_send_json_success('✅ Test notification sent! Check your Pushover app.');
    } else {
        wp_send_json_error('❌ Could not send test email. Check your server mail settings.');
    }
});

function oct_wizard_step_1() { ?>
    <div class="oct-step-welcome">
        <div class="oct-step-icon">🚕</div>
        <h2>Welcome to OneClick Taxi!</h2>
        <p>You're about to set up a complete taxi ordering app for your business. This wizard will walk you through every step.</p>
        <div class="oct-checklist">
            <div class="oct-check-item">✅ Live Google Maps routing</div>
            <div class="oct-check-item">✅ Online booking form</div>
            <div class="oct-check-item">✅ Square card payments</div>
            <div class="oct-check-item">✅ Google Calendar auto-scheduling</div>
            <div class="oct-check-item">✅ Instant phone notifications</div>
            <div class="oct-check-item">✅ Flat rate destination pricing</div>
        </div>
        <p class="oct-time-estimate">⏱️ Estimated setup time: <strong>20 minutes</strong></p>
        <button class="oct-btn-primary oct-next-step" data-next="2">Let's Get Started →</button>
    </div>
<?php }

function oct_wizard_step_2() {
    $s = oct_get_settings(); ?>
    <div class="oct-step-content">
        <div class="oct-step-icon">🏢</div>
        <h2>Business Information</h2>
        <p>Enter your company details. This appears on your booking page and customer emails.</p>
        <div class="oct-form-group">
            <label>Business Name *</label>
            <input type="text" id="w-business-name" value="<?php echo esc_attr($s['business_name']);?>" placeholder="e.g. City Cab Co." class="oct-input">
        </div>
        <div class="oct-form-group">
            <label>Phone Number *</label>
            <input type="text" id="w-phone" value="<?php echo esc_attr($s['phone']);?>" placeholder="555-123-4567" class="oct-input">
        </div>
        <div class="oct-form-group">
            <label>Email Address *</label>
            <input type="email" id="w-email" value="<?php echo esc_attr($s['email']);?>" placeholder="dispatch@yourcab.com" class="oct-input">
        </div>
        <div class="oct-form-row">
            <div class="oct-form-group">
                <label>Hours Open</label>
                <input type="time" id="w-hours-open" value="<?php echo esc_attr($s['hours_open']);?>" class="oct-input">
            </div>
            <div class="oct-form-group">
                <label>Hours Close</label>
                <input type="time" id="w-hours-close" value="<?php echo esc_attr($s['hours_close']);?>" class="oct-input">
            </div>
        </div>
        <div class="oct-form-row">
            <div class="oct-form-group">
                <label>Per Mile Rate ($)</label>
                <input type="number" id="w-per-mile" value="<?php echo esc_attr($s['per_mile']);?>" step="0.01" class="oct-input">
            </div>
            <div class="oct-form-group">
                <label>Base Rate ($)</label>
                <input type="number" id="w-base-rate" value="<?php echo esc_attr($s['base_rate']);?>" step="0.01" class="oct-input">
            </div>
        </div>
        <div id="oct-step2-status" class="oct-status"></div>
        <div class="oct-btn-row">
            <button class="oct-btn-secondary oct-prev-step" data-prev="1">← Back</button>
            <button class="oct-btn-primary" id="oct-save-business">Save & Continue →</button>
        </div>
    </div>
    <script>
    document.getElementById('oct-save-business').addEventListener('click', function(){
        var data = new FormData();
        data.append('action','oct_wizard_save_business');
        data.append('nonce', octAdmin.nonce);
        data.append('business_name', document.getElementById('w-business-name').value);
        data.append('phone', document.getElementById('w-phone').value);
        data.append('email', document.getElementById('w-email').value);
        data.append('hours_open', document.getElementById('w-hours-open').value);
        data.append('hours_close', document.getElementById('w-hours-close').value);
        data.append('per_mile', document.getElementById('w-per-mile').value);
        data.append('base_rate', document.getElementById('w-base-rate').value);
        fetch(octAdmin.ajax, {method:'POST',body:data})
        .then(r=>r.json()).then(d=>{
            var el = document.getElementById('oct-step2-status');
            el.className = 'oct-status ' + (d.success ? 'success' : 'error');
            el.textContent = d.data;
            if(d.success) setTimeout(()=>{ window.location = octAdmin.wizardUrl + '&oct_step=3'; }, 800);
        });
    });
    </script>
<?php }

function oct_wizard_step_3() {
    $s = oct_get_settings(); ?>
    <div class="oct-step-content">
        <div class="oct-step-icon">🗺️</div>
        <h2>Google Maps API Key</h2>
        <div class="oct-how-to">
            <h3>How to get your Google Maps API Key:</h3>
            <ol>
                <li>Go to <a href="https://console.cloud.google.com" target="_blank">console.cloud.google.com</a></li>
                <li>Create a new project (or select existing)</li>
                <li>Click <strong>APIs & Services → Library</strong></li>
                <li>Enable: <strong>Maps JavaScript API</strong>, <strong>Places API</strong>, <strong>Geocoding API</strong>, <strong>Directions API</strong></li>
                <li>Click <strong>APIs & Services → Credentials → Create Credentials → API Key</strong></li>
                <li>Copy the key and paste it below</li>
            </ol>
        </div>
        <div class="oct-form-group">
            <label>Google Maps API Key *</label>
            <input type="text" id="w-maps-key" value="<?php echo esc_attr($s['google_maps_key']);?>" placeholder="AIzaSy..." class="oct-input oct-monospace">
        </div>
        <div id="oct-step3-status" class="oct-status"></div>
        <div class="oct-btn-row">
            <button class="oct-btn-secondary oct-prev-step" data-prev="2">← Back</button>
            <button class="oct-btn-primary" id="oct-verify-maps">Verify & Continue →</button>
        </div>
    </div>
    <script>
    document.getElementById('oct-verify-maps').addEventListener('click', function(){
        var key = document.getElementById('w-maps-key').value.trim();
        var el  = document.getElementById('oct-step3-status');
        el.className = 'oct-status'; el.textContent = 'Verifying...';
        var fd = new FormData();
        fd.append('action','oct_verify_maps'); fd.append('nonce',octAdmin.nonce); fd.append('key',key);
        fetch(octAdmin.ajax,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
            el.className = 'oct-status '+(d.success?'success':'error');
            el.textContent = d.data;
            if(d.success) setTimeout(()=>{ window.location = octAdmin.wizardUrl + '&oct_step=4'; }, 1000);
        });
    });
    </script>
<?php }

function oct_wizard_step_4() { ?>
    <div class="oct-step-content">
        <div class="oct-step-icon">📅</div>
        <h2>Google Calendar</h2>
        <p>Connect your Google Calendar so every booking automatically appears as a scheduled event.</p>
        <div class="oct-how-to">
            <h3>How to set up Google Calendar:</h3>
            <ol>
                <li>Go to <a href="https://console.cloud.google.com" target="_blank">console.cloud.google.com</a></li>
                <li>Select your project → <strong>APIs & Services → Library</strong></li>
                <li>Enable: <strong>Google Calendar API</strong></li>
                <li>Go to <strong>APIs & Services → Credentials → Create OAuth 2.0 Client ID</strong></li>
                <li>Application type: <strong>Web application</strong></li>
                <li>Add your site URL to Authorized redirect URIs:<br>
                    <code><?php echo admin_url('admin.php?page=oct-calendar'); ?></code></li>
                <li>Copy the Client ID and Client Secret below</li>
            </ol>
        </div>
        <?php
        $client_id = get_option('oct_gcal_client_id','');
        $client_secret = get_option('oct_gcal_client_secret','');
        ?>
        <div class="oct-form-group">
            <label>OAuth Client ID</label>
            <input type="text" id="w-gcal-id" value="<?php echo esc_attr($client_id);?>" placeholder="123456789-abc.apps.googleusercontent.com" class="oct-input oct-monospace">
        </div>
        <div class="oct-form-group">
            <label>OAuth Client Secret</label>
            <input type="text" id="w-gcal-secret" value="<?php echo esc_attr($client_secret);?>" placeholder="GOCSPX-..." class="oct-input oct-monospace">
        </div>
        <div id="oct-step4-status" class="oct-status"></div>
        <div class="oct-btn-row">
            <button class="oct-btn-secondary oct-prev-step" data-prev="3">← Back</button>
            <button class="oct-btn-primary" id="oct-save-gcal">Save & Authorize →</button>
            <button class="oct-btn-secondary oct-next-step" data-next="5">Skip for now →</button>
        </div>
    </div>
    <script>
    document.getElementById('oct-save-gcal').addEventListener('click', function(){
        var fd = new FormData();
        fd.append('action','oct_wizard_save_gcal'); fd.append('nonce',octAdmin.nonce);
        fd.append('client_id', document.getElementById('w-gcal-id').value);
        fd.append('client_secret', document.getElementById('w-gcal-secret').value);
        fetch(octAdmin.ajax,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
            var el = document.getElementById('oct-step4-status');
            el.className='oct-status '+(d.success?'success':'error');
            el.textContent=d.data;
            if(d.success) setTimeout(()=>{ window.location = d.auth_url || (octAdmin.wizardUrl+'&oct_step=5'); },800);
        });
    });
    </script>
<?php }

function oct_wizard_step_5() {
    $s = oct_get_settings(); ?>
    <div class="oct-step-content">
        <div class="oct-step-icon">💳</div>
        <h2>Square Payments</h2>
        <p>Accept credit and debit cards at booking. Square is free to sign up — you only pay per transaction.</p>
        <div class="oct-how-to">
            <h3>How to get your Square credentials:</h3>
            <ol>
                <li>Go to <a href="https://developer.squareup.com" target="_blank">developer.squareup.com</a></li>
                <li>Sign in or create a free Square account</li>
                <li>Create a new application</li>
                <li>Go to <strong>Credentials</strong> tab — copy your <strong>Application ID</strong></li>
                <li>Go to <strong>OAuth</strong> — generate an <strong>Access Token</strong></li>
                <li>Go to your <a href="https://squareup.com/dashboard/locations" target="_blank">Square Dashboard → Locations</a> — copy your <strong>Location ID</strong></li>
            </ol>
        </div>
        <div class="oct-form-group">
            <label>Square Application ID</label>
            <input type="text" id="w-sq-appid" value="<?php echo esc_attr($s['square_app_id']);?>" placeholder="sq0idp-..." class="oct-input oct-monospace">
        </div>
        <div class="oct-form-group">
            <label>Square Access Token</label>
            <input type="text" id="w-sq-token" value="<?php echo esc_attr($s['square_token']);?>" placeholder="EAAAl..." class="oct-input oct-monospace">
        </div>
        <div class="oct-form-group">
            <label>Square Location ID</label>
            <input type="text" id="w-sq-location" value="<?php echo esc_attr($s['square_location']);?>" placeholder="L..." class="oct-input oct-monospace">
        </div>
        <div id="oct-step5-status" class="oct-status"></div>
        <div class="oct-btn-row">
            <button class="oct-btn-secondary oct-prev-step" data-prev="4">← Back</button>
            <button class="oct-btn-primary" id="oct-verify-square">Verify & Continue →</button>
            <button class="oct-btn-secondary oct-next-step" data-next="6">Skip for now →</button>
        </div>
    </div>
    <script>
    document.getElementById('oct-verify-square').addEventListener('click', function(){
        var el = document.getElementById('oct-step5-status');
        el.className='oct-status'; el.textContent='Verifying with Square...';
        var fd = new FormData();
        fd.append('action','oct_verify_square'); fd.append('nonce',octAdmin.nonce);
        fd.append('app_id', document.getElementById('w-sq-appid').value);
        fd.append('token', document.getElementById('w-sq-token').value);
        fd.append('location', document.getElementById('w-sq-location').value);
        fetch(octAdmin.ajax,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
            el.className='oct-status '+(d.success?'success':'error');
            el.textContent=d.data;
            if(d.success) setTimeout(()=>{ window.location=octAdmin.wizardUrl+'&oct_step=6'; },1000);
        });
    });
    </script>
<?php }

function oct_wizard_step_6() {
    $s = oct_get_settings(); ?>
    <div class="oct-step-content">
        <div class="oct-step-icon">📲</div>
        <h2>Pushover Push Notifications</h2>
        <p>Get an instant notification on your phone every time a booking is placed — no SMS fees required.</p>
        <div class="oct-how-to">
            <h3>How to set up Pushover:</h3>
            <ol>
                <li>Go to <a href="https://pushover.net" target="_blank">pushover.net</a> and create a free account</li>
                <li>Install the <strong>Pushover app</strong> on your iPhone or Android</li>
                <li>Log into pushover.net — your <strong>Pushover Email Alias</strong> is shown on your dashboard (looks like <code>abc123@pomail.net</code>)</li>
                <li>Paste that email address below and click Send Test</li>
            </ol>
        </div>
        <div class="oct-form-group">
            <label>Pushover Email Alias</label>
            <input type="email" id="w-pushover" value="<?php echo esc_attr($s['pushover_email']);?>" placeholder="yourkey@pomail.net" class="oct-input oct-monospace">
        </div>
        <div id="oct-step6-status" class="oct-status"></div>
        <div class="oct-btn-row">
            <button class="oct-btn-secondary oct-prev-step" data-prev="5">← Back</button>
            <button class="oct-btn-primary" id="oct-verify-pushover">Send Test & Continue →</button>
            <button class="oct-btn-secondary oct-next-step" data-next="7">Skip for now →</button>
        </div>
    </div>
    <script>
    document.getElementById('oct-verify-pushover').addEventListener('click', function(){
        var el = document.getElementById('oct-step6-status');
        el.className='oct-status'; el.textContent='Sending test notification...';
        var fd = new FormData();
        fd.append('action','oct_verify_pushover'); fd.append('nonce',octAdmin.nonce);
        fd.append('email', document.getElementById('w-pushover').value);
        fetch(octAdmin.ajax,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
            el.className='oct-status '+(d.success?'success':'error');
            el.textContent=d.data;
            if(d.success) setTimeout(()=>{ window.location=octAdmin.wizardUrl+'&oct_step=7'; },1500);
        });
    });
    </script>
<?php }

function oct_wizard_step_7() {
    $c = oct_get_colors(); ?>
    <div class="oct-step-content">
        <div class="oct-step-icon">🎨</div>
        <h2>Brand Colors</h2>
        <p>Choose 4 colors to match your taxi company's brand. These apply instantly to your booking page.</p>
        <div class="oct-color-grid">
            <div class="oct-color-item">
                <label>Primary <small>Buttons & highlights</small></label>
                <input type="text" id="w-color-primary" value="<?php echo esc_attr($c['primary']);?>" class="oct-color-picker-field">
                <div class="oct-color-swatch" id="sw-primary" style="background:<?php echo esc_attr($c['primary']);?>"></div>
            </div>
            <div class="oct-color-item">
                <label>Secondary <small>Header & nav</small></label>
                <input type="text" id="w-color-secondary" value="<?php echo esc_attr($c['secondary']);?>" class="oct-color-picker-field">
                <div class="oct-color-swatch" id="sw-secondary" style="background:<?php echo esc_attr($c['secondary']);?>"></div>
            </div>
            <div class="oct-color-item">
                <label>Accent <small>Prices & totals</small></label>
                <input type="text" id="w-color-accent" value="<?php echo esc_attr($c['accent']);?>" class="oct-color-picker-field">
                <div class="oct-color-swatch" id="sw-accent" style="background:<?php echo esc_attr($c['accent']);?>"></div>
            </div>
            <div class="oct-color-item">
                <label>Background <small>Page background</small></label>
                <input type="text" id="w-color-bg" value="<?php echo esc_attr($c['background']);?>" class="oct-color-picker-field">
                <div class="oct-color-swatch" id="sw-bg" style="background:<?php echo esc_attr($c['background']);?>"></div>
            </div>
        </div>
        <div class="oct-color-preview" id="oct-preview-bar">
            <div style="background:var(--prev-bg,<?php echo $c['background'];?>);padding:16px;border-radius:8px;display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                <div style="background:var(--prev-primary,<?php echo $c['primary'];?>);padding:10px 20px;border-radius:4px;font-weight:700;color:#000">Book Now</div>
                <div style="color:var(--prev-accent,<?php echo $c['accent'];?>);font-size:1.2rem;font-weight:700">$25.00</div>
                <div style="color:rgba(255,255,255,.7);font-size:.9rem">Live preview of your booking button</div>
            </div>
        </div>
        <div id="oct-step7-status" class="oct-status"></div>
        <div class="oct-btn-row">
            <button class="oct-btn-secondary oct-prev-step" data-prev="6">← Back</button>
            <button class="oct-btn-primary" id="oct-save-colors">Save Colors & Continue →</button>
        </div>
    </div>
    <script>
    document.getElementById('oct-save-colors').addEventListener('click', function(){
        var fd = new FormData();
        fd.append('action','oct_wizard_save_colors'); fd.append('nonce',octAdmin.nonce);
        fd.append('primary',    document.getElementById('w-color-primary').value);
        fd.append('secondary',  document.getElementById('w-color-secondary').value);
        fd.append('accent',     document.getElementById('w-color-accent').value);
        fd.append('background', document.getElementById('w-color-bg').value);
        fetch(octAdmin.ajax,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
            var el=document.getElementById('oct-step7-status');
            el.className='oct-status '+(d.success?'success':'error'); el.textContent=d.data;
            if(d.success) setTimeout(()=>{ window.location=octAdmin.wizardUrl+'&oct_step=8'; },800);
        });
    });
    </script>
<?php }

function oct_wizard_step_8() { ?>
    <div class="oct-step-content">
        <div class="oct-step-icon">🖼️</div>
        <h2>Logo & Business Name</h2>
        <p>Upload your company logo. It will appear at the top of your booking page.</p>
        <div class="oct-form-group">
            <label>Upload Logo</label>
            <div class="oct-upload-area" id="oct-logo-drop">
                <?php $logo = get_option('oct_logo_url',''); ?>
                <?php if($logo): ?>
                <img src="<?php echo esc_url($logo); ?>" style="max-width:200px;max-height:100px">
                <?php else: ?>
                <div class="oct-upload-placeholder">🖼️<br>Click or drag your logo here<br><small>PNG, JPG, SVG — recommended 400×200px</small></div>
                <?php endif; ?>
                <input type="file" id="oct-logo-file" accept="image/*" style="display:none">
            </div>
            <button class="oct-btn-secondary" id="oct-choose-logo">Choose File</button>
        </div>
        <div id="oct-step8-status" class="oct-status"></div>
        <div class="oct-btn-row">
            <button class="oct-btn-secondary oct-prev-step" data-prev="7">← Back</button>
            <button class="oct-btn-primary oct-next-step" data-next="9">Continue →</button>
        </div>
    </div>
    <script>
    document.getElementById('oct-choose-logo').addEventListener('click',function(){
        document.getElementById('oct-logo-file').click();
    });
    document.getElementById('oct-logo-file').addEventListener('change',function(){
        var fd = new FormData();
        fd.append('action','oct_upload_logo'); fd.append('nonce',octAdmin.nonce);
        fd.append('logo', this.files[0]);
        var el=document.getElementById('oct-step8-status');
        el.className='oct-status'; el.textContent='Uploading...';
        fetch(octAdmin.ajax,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
            el.className='oct-status '+(d.success?'success':'error'); el.textContent=d.data;
        });
    });
    </script>
<?php }

function oct_wizard_step_9() { ?>
    <div class="oct-step-content">
        <div class="oct-step-icon">🗺️</div>
        <h2>Flat Rate Destinations</h2>
        <p>Add fixed-price destinations. Customers select from these instead of seeing a metered rate. <strong>Optional — you can add these later.</strong></p>
        <p>Go to <strong>OneClick Taxi → 🗺️ Flat Rates</strong> in your admin menu to add destinations.</p>
        <div class="oct-info-box">
            💡 <strong>Tip:</strong> Add your most popular destinations like airports, hotels, and tourist spots. Group them by direction (North, South, East, West).
        </div>
        <div class="oct-btn-row">
            <button class="oct-btn-secondary oct-prev-step" data-prev="8">← Back</button>
            <a href="<?php echo admin_url('admin.php?page=oct-flatrates'); ?>" class="oct-btn-secondary">Add Flat Rates Now</a>
            <button class="oct-btn-primary oct-next-step" data-next="10">Continue →</button>
        </div>
    </div>
<?php }

function oct_wizard_step_10() {
    update_option('oct_setup_complete', 1);
    $s = oct_get_settings();
    $booking_url = home_url('/book-a-ride/');
    ?>
    <div class="oct-step-welcome">
        <div class="oct-step-icon" style="font-size:4rem">🚀</div>
        <h2>Your Taxi App is Live!</h2>
        <p>Congratulations! Your OneClick Taxi Ordering App is ready to take bookings.</p>
        <div class="oct-launch-box">
            <div class="oct-launch-item">
                <strong>📱 Booking Page:</strong>
                <a href="<?php echo esc_url($booking_url); ?>" target="_blank"><?php echo esc_url($booking_url); ?></a>
            </div>
            <div class="oct-launch-item">
                <strong>📞 Phone:</strong> <?php echo esc_html($s['phone'] ?: 'Not set — add in General Settings'); ?>
            </div>
        </div>
        <div class="oct-checklist">
            <div class="oct-check-item">✅ Google Maps routing — active</div>
            <div class="oct-check-item">✅ Booking form — live at <?php echo esc_url($booking_url); ?></div>
            <div class="oct-check-item"><?php echo $s['square_app_id'] ? '✅' : '⚠️'; ?> Square Payments — <?php echo $s['square_app_id'] ? 'connected' : 'not connected (set up in General Settings)'; ?></div>
            <div class="oct-check-item"><?php echo get_option('oct_gcal_token') ? '✅' : '⚠️'; ?> Google Calendar — <?php echo get_option('oct_gcal_token') ? 'connected' : 'not connected (set up in Calendar Auth)'; ?></div>
            <div class="oct-check-item"><?php echo $s['pushover_email'] ? '✅' : '⚠️'; ?> Push Notifications — <?php echo $s['pushover_email'] ? 'active' : 'not set (add in General Settings)'; ?></div>
        </div>
        <div class="oct-btn-row" style="justify-content:center">
            <a href="<?php echo esc_url($booking_url); ?>" target="_blank" class="oct-btn-primary">View Your Booking Page 🚕</a>
            <a href="<?php echo admin_url('admin.php?page=oct-settings'); ?>" class="oct-btn-secondary">General Settings</a>
        </div>
    </div>
<?php }

/* AJAX handlers for wizard saves */
add_action('wp_ajax_oct_wizard_save_business', function(){
    check_ajax_referer('oct_nonce','nonce');
    $s = oct_get_settings();
    foreach(['business_name','phone','email','hours_open','hours_close','per_mile','base_rate'] as $f)
        $s[$f] = sanitize_text_field($_POST[$f] ?? $s[$f]);
    update_option('oct_settings', $s);
    update_option('oct_setup_step', 3);
    wp_send_json_success('✅ Business info saved!');
});

add_action('wp_ajax_oct_wizard_save_gcal', function(){
    check_ajax_referer('oct_nonce','nonce');
    update_option('oct_gcal_client_id',     sanitize_text_field($_POST['client_id'] ?? ''));
    update_option('oct_gcal_client_secret', sanitize_text_field($_POST['client_secret'] ?? ''));
    update_option('oct_setup_step', 4);
    $redirect = admin_url('admin.php?page=oct-calendar');
    wp_send_json_success(['message'=>'✅ Credentials saved. Redirecting to authorize...', 'auth_url'=>$redirect]);
});

add_action('wp_ajax_oct_wizard_save_colors', function(){
    check_ajax_referer('oct_nonce','nonce');
    update_option('oct_colors', [
        'primary'    => sanitize_hex_color($_POST['primary']    ?? '#c8a84b'),
        'secondary'  => sanitize_hex_color($_POST['secondary']  ?? '#1a3a1a'),
        'accent'     => sanitize_hex_color($_POST['accent']     ?? '#f5c518'),
        'background' => sanitize_hex_color($_POST['background'] ?? '#0f2a0f'),
    ]);
    update_option('oct_setup_step', 8);
    wp_send_json_success('✅ Colors saved!');
});

add_action('wp_ajax_oct_upload_logo', function(){
    check_ajax_referer('oct_nonce','nonce');
    if(empty($_FILES['logo'])) wp_send_json_error('No file received.');
    require_once ABSPATH.'wp-admin/includes/image.php';
    require_once ABSPATH.'wp-admin/includes/file.php';
    require_once ABSPATH.'wp-admin/includes/media.php';
    $attachment_id = media_handle_upload('logo', 0);
    if(is_wp_error($attachment_id)) wp_send_json_error($attachment_id->get_error_message());
    $url = wp_get_attachment_url($attachment_id);
    update_option('oct_logo_url', $url);
    wp_send_json_success('✅ Logo uploaded!');
});
