(function( $ ) {
    'use strict';

    $(function() {
        var mediaUploader;
        var mediaIds = [];
        var verificationInterval;

        function resetUploader() {
            mediaIds = [];
            $('.media-preview-wrapper').empty();
            $('#golfvista-media-ids').val('');
            $('input[name="golfvista_media_submission"]').prop('disabled', true);
        }

        $('input[name="media_type"]').on('change', function() {
            var mediaType = $(this).val();
            $('#golfvista-media-type').val(mediaType);
            resetUploader();
        });

        $('#upload-media-button').on('click', function(e) {
            e.preventDefault();

            var mediaType = $('#golfvista-media-type').val();
            var limit = (mediaType === 'image') ? 5 : 1;

            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Choose ' + (mediaType === 'image' ? 'Images' : 'Video'),
                button: {
                    text: 'Choose Media'
                },
                multiple: true,
                library: {
                    type: mediaType
                }
            });

            mediaUploader.on('select', function() {
                var attachments = mediaUploader.state().get('selection').toJSON();
                var previewWrapper = $('.media-preview-wrapper');
                var submitButton = $('input[name="golfvista_media_submission"]');

                if (attachments.length !== limit) {
                    alert('You must select exactly ' + limit + ' ' + (limit > 1 ? 'files' : 'file') + '.');
                    submitButton.prop('disabled', true);
                    return;
                }

                resetUploader();

                $.each(attachments, function(index, attachment) {
                    mediaIds.push(attachment.id);
                    if (attachment.type === 'image') {
                        previewWrapper.append('<img src="' + attachment.url + '" style="max-width:100px; height:auto; margin:5px;" />');
                    } else {
                        previewWrapper.append('<div class="attachment-preview" style="width:100px; height:100px; margin:5px; background:#f0f0f0; display:inline-block;"><div style="padding:10px;">' + attachment.filename + '</div></div>');
                    }
                });
                
                $('#golfvista-media-ids').val(mediaIds.join(','));
                
                if(mediaIds.length === limit){
                    submitButton.prop('disabled', false);
                } else {
                    submitButton.prop('disabled', true);
                }
            });

            mediaUploader.open();
        });

        $('#media-submission-form').on('submit', function(e) {
            var mediaType = $('#golfvista-media-type').val();
            var limit = (mediaType === 'image') ? 5 : 1;
            var mediaIdsInput = $('#golfvista-media-ids');

            if (!mediaIdsInput.val() || mediaIds.length !== limit) {
                e.preventDefault();
                alert('Please upload exactly ' + limit + ' ' + (mediaType === 'image' ? 'images' : 'a video') + '.');
            }
        });

        // Polling logic for media verification status
        function checkMediaVerificationStatus() {
            if ($('#media-verification-status').length) {
                if (!verificationInterval) {
                    verificationInterval = setInterval(function() {
                        $.ajax({
                            url: golfvista_challenge_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'golfvista_check_media_verification',
                                nonce: golfvista_challenge_ajax.nonce
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('#dynamic-verification-message').text(response.data.message);
                                    if (response.data.status === 'media_approved') {
                                        clearInterval(verificationInterval);
                                        window.location.href = response.data.product_url;
                                    } else if (response.data.status === 'media_failed') {
                                        clearInterval(verificationInterval);
                                        // The page will reload on 'try again', so no need to clear interval manually
                                        window.location.reload();
                                    }
                                } else {
                                    $('#dynamic-verification-message').text('Error: ' + response.data.message);
                                    clearInterval(verificationInterval);
                                }
                            },
                            error: function() {
                                $('#dynamic-verification-message').text('Communication error.');
                                clearInterval(verificationInterval);
                            }
                        });
                    }, 5000);
                }
            }
        }

        if ($('#media-verification-status').length > 0) {
            checkMediaVerificationStatus();
        }
    });

})( jQuery );
