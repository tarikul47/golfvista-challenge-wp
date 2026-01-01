(function( $ ) {
    'use strict';

    $(function() {
        var mediaUploader;
        var mediaIds = [];

        $('#upload-media-button').on('click', function(e) {
            e.preventDefault();
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Choose Media',
                button: {
                    text: 'Choose Media'
                },
                multiple: true
            });

            mediaUploader.on('select', function() {
                var attachments = mediaUploader.state().get('selection').toJSON();
                var previewWrapper = $('.media-preview-wrapper');
                var submitButton = $('input[name="golfvista_media_submission"]');

                if (attachments.length !== 5) {
                    alert('You must select exactly 5 files.');
                    return;
                }

                previewWrapper.empty();
                mediaIds = [];

                $.each(attachments, function(index, attachment) {
                    mediaIds.push(attachment.id);
                    if (attachment.type === 'image') {
                        previewWrapper.append('<img src="' + attachment.url + '" style="max-width:100px; height:auto; margin:5px;" />');
                    } else {
                        previewWrapper.append('<div class="attachment-preview" style="width:100px; height:100px; margin:5px; background:#f0f0f0; display:inline-block;"><div style="padding:10px;">' + attachment.filename + '</div></div>');
                    }
                });
                
                $('#golfvista-media-ids').val(mediaIds.join(','));
                
                if(mediaIds.length === 5){
                    submitButton.prop('disabled', false);
                } else {
                    submitButton.prop('disabled', true);
                }
            });

            mediaUploader.open();
        });
    });

})( jQuery );
