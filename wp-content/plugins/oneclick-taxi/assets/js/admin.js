/* OneClick Taxi — Admin JS */
jQuery(function($){

    /* Color pickers */
    $('.oct-color-picker, .oct-color-picker-field').wpColorPicker({
        change: function(event, ui){
            var id    = $(this).attr('id');
            var color = ui.color.toString();
            var swatchMap = {
                'w-color-primary':   'sw-primary',
                'w-color-secondary': 'sw-secondary',
                'w-color-accent':    'sw-accent',
                'w-color-bg':        'sw-bg',
            };
            if(swatchMap[id]) $('#'+swatchMap[id]).css('background', color);
        }
    });

    /* Step navigation */
    $(document).on('click', '.oct-next-step', function(){
        var next = $(this).data('next');
        var fd   = new FormData();
        fd.append('action', 'oct_wizard_next');
        fd.append('nonce',  octAdmin.nonce);
        fd.append('next',   next);
        fetch(octAdmin.ajax, {method:'POST', body:fd})
        .then(r=>r.json()).then(d=>{
            if(d.success) window.location = octAdmin.wizardUrl + '&oct_step=' + next;
        });
    });

    $(document).on('click', '.oct-prev-step', function(){
        var prev = $(this).data('prev');
        var fd   = new FormData();
        fd.append('action', 'oct_wizard_next');
        fd.append('nonce',  octAdmin.nonce);
        fd.append('next',   prev);
        fetch(octAdmin.ajax, {method:'POST', body:fd})
        .then(r=>r.json()).then(d=>{
            if(d.success) window.location = octAdmin.wizardUrl + '&oct_step=' + prev;
        });
    });

    /* Logo drag/drop */
    $('#oct-logo-drop').on('dragover', function(e){
        e.preventDefault();
        $(this).css('border-color','#2e7d32');
    }).on('dragleave', function(){
        $(this).css('border-color','#c8a84b');
    }).on('drop', function(e){
        e.preventDefault();
        var file = e.originalEvent.dataTransfer.files[0];
        uploadLogo(file);
    }).on('click', function(){
        $('#oct-logo-file').click();
    });

    $('#oct-logo-file').on('change', function(){
        uploadLogo(this.files[0]);
    });

    function uploadLogo(file) {
        var fd = new FormData();
        fd.append('action', 'oct_upload_logo');
        fd.append('nonce', octAdmin.nonce);
        fd.append('logo', file);
        $('#oct-step8-status').removeClass('success error').text('Uploading...');
        fetch(octAdmin.ajax, {method:'POST', body:fd})
        .then(r=>r.json()).then(d=>{
            $('#oct-step8-status').addClass(d.success ? 'success' : 'error').text(d.data);
            if(d.success && d.url) {
                $('#oct-logo-drop').html('<img src="'+d.url+'" style="max-width:200px;max-height:100px">');
            }
        });
    }
});
