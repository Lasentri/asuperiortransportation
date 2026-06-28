/* OneClick Taxi License Store JS */
jQuery(function($){

    var selectedTier  = null;
    var squareCard    = null;
    var squarePayment = null;

    /* Init Square */
    async function initSquare() {
        if ( ! window.Square ) return;
        try {
            squarePayment = Square.payments(OCT_LS.sqAppId, OCT_LS.sqLocationId);
            squareCard    = await squarePayment.card({
                style: {
                    input: { color:'#ffffff', backgroundColor:'transparent', fontSize:'14px' },
                    '.input-container': { borderColor:'rgba(255,255,255,0.2)', borderRadius:'6px' },
                    '.input-container.is-focus': { borderColor:'#c8a84b' },
                }
            });
            await squareCard.attach('#oct-card-container');
        } catch(e) { console.warn('Square init:', e); }
    }

    /* Select tier */
    $(document).on('click', '.oct-select-tier', function(){
        selectedTier = {
            slug:  $(this).data('tier'),
            label: $(this).data('label'),
            price: $(this).data('price'),
        };
        /* Scroll to disclaimer first */
        $('html,body').animate({ scrollTop: $('#oct-disclaimer').offset().top - 20 }, 400);
        /* Update purchase form */
        $('#oct-selected-tier-display').text(selectedTier.label + ' — ' + selectedTier.price);
        /* Show purchase form after disclaimer */
        $('#oct-purchase-form').slideDown(300);
        setTimeout(function(){
            $('html,body').animate({ scrollTop: $('#oct-purchase-form').offset().top - 20 }, 400);
        }, 200);
        if ( ! squareCard ) initSquare();
    });

    /* Cancel purchase */
    $('#oct-cancel-purchase').on('click', function(){
        $('#oct-purchase-form').slideUp(200);
        selectedTier = null;
        $('html,body').animate({ scrollTop: $('#pricing').offset().top - 20 }, 300);
    });

    /* Enable/disable purchase button based on disclaimer checkbox */
    $('#oct-agree-disclaimer').on('change', function(){
        $('#oct-purchase-btn').prop('disabled', ! this.checked);
    });

    /* Purchase button */
    $('#oct-purchase-btn').on('click', async function(){
        var $btn  = $(this);
        var name  = $('#oct-buyer-name').val().trim();
        var email = $('#oct-buyer-email').val().trim();
        var agreed= $('#oct-agree-disclaimer').is(':checked');
        var errEl = $('#oct-purchase-error');

        errEl.hide().text('');

        if ( ! name || ! email ) { errEl.text('Please enter your name and email.').show(); return; }
        if ( ! /\S+@\S+\.\S+/.test(email) ) { errEl.text('Please enter a valid email address.').show(); return; }
        if ( ! agreed ) { errEl.text('You must agree to the No-Refund Policy.').show(); return; }
        if ( ! selectedTier ) { errEl.text('Please select a license plan.').show(); return; }

        $btn.prop('disabled', true).text('Processing payment...');

        try {
            /* Tokenize card */
            var result = await squareCard.tokenize();
            if ( result.status !== 'OK' ) {
                var msg = result.errors ? result.errors.map(e=>e.message).join(', ') : 'Card tokenization failed.';
                errEl.text(msg).show();
                $btn.prop('disabled', false).text('🔒 Complete Purchase');
                return;
            }

            /* Send to server */
            var fd = new FormData();
            fd.append('action',             'oct_ls_purchase');
            fd.append('nonce',              OCT_LS.nonce);
            fd.append('tier',               selectedTier.slug);
            fd.append('name',               name);
            fd.append('email',              email);
            fd.append('square_token',       result.token);
            fd.append('agreed_disclaimer',  '1');

            var resp = await fetch(OCT_LS.ajax, { method:'POST', body:fd });
            var data = await resp.json();

            if ( data.success ) {
                /* Show success */
                $('#oct-purchase-form').slideUp(200);
                $('#oct-success-email').text(email);
                $('#oct-success-key').text(data.data.key);
                $('#oct-success-tier').text(data.data.tier);
                $('#oct-success-expires').text(data.data.expires);
                $('#oct-success-wrap').slideDown(300);
                $('html,body').animate({ scrollTop: $('#oct-success-wrap').offset().top - 20 }, 400);
            } else {
                errEl.text(data.data.message || 'Payment failed. Please try again.').show();
                $btn.prop('disabled', false).text('🔒 Complete Purchase');
            }

        } catch(e) {
            errEl.text('An error occurred. Please try again or contact stalcollc@gmail.com').show();
            $btn.prop('disabled', false).text('🔒 Complete Purchase');
        }
    });

});
