(function( $ ) {
    'use strict';

    $(function() {
        var mediaUploader;
        var mediaIds = [];
        var verificationInterval; // To store the interval for polling

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
                    submitButton.prop('disabled', true); // Disable submit if count is wrong
                    return;
                }

                previewWrapper.empty();
                mediaIds = [];

                $.each(attachments, function(index, attachment) {
                    mediaIds.push(attachment.id);
                    if (attachment.type === 'image') {
                        previewWrapper.append('<img src="' + attachment.url + '" style="max-width:100px; height:auto; margin:5px;" />');
                    } else {
                        // For videos or other types, just show a placeholder
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

        // Handle media submission form
        $('#media-submission-form').on('submit', function(e) {
            var submitButton = $('input[name="golfvista_media_submission"]');
            var mediaIdsInput = $('#golfvista-media-ids');

            // Only proceed if media IDs are set and the button is not disabled
            if (mediaIdsInput.val() && !submitButton.prop('disabled')) {
                // Allow the form to submit normally, as the server-side will handle setting
                // the 'media_pending_verification' status and then the page will reload.
                // The polling logic will then kick in if the status is 'media_pending_verification'.
            } else {
                e.preventDefault(); // Prevent submission if no media or incorrect count
                alert('Please upload exactly 5 media files.');
            }
        });

        // Polling logic for media verification status
        function checkMediaVerificationStatus() {
            // Check if the verification status wrapper is present on the page
            if ($('#media-verification-status').length && $('#dynamic-verification-message').length) {
                // Start the polling only if it's not already running
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
                                console.log('AJAX Response:', response); // For debugging
                                if (response.success) {
                                    $('#dynamic-verification-message').text(response.data.message);
                                    if (response.data.status === 'media_approved') {
                                        clearInterval(verificationInterval);
                                        verificationInterval = null; // Clear the interval
                                        window.location.href = response.data.product_url;
                                    } else if (response.data.status === 'media_failed') {
                                        clearInterval(verificationInterval);
                                        verificationInterval = null; // Clear the interval
                                        // No reload here, let the user click 'try again'
                                        // The message should guide them to click the link
                                        $('#dynamic-verification-message').html('Unfortunately, your media did not pass verification. Please <a href="' + $('#golfvista-try-again-link').attr('href') + '">try again</a>.');
                                    }
                                } else {
                                    $('#dynamic-verification-message').text('Error checking status: ' + response.data.message);
                                    clearInterval(verificationInterval);
                                    verificationInterval = null; // Clear the interval
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                console.error('AJAX Error:', textStatus, errorThrown);
                                $('#dynamic-verification-message').text('Communication error with the server.');
                                clearInterval(verificationInterval);
                                verificationInterval = null; // Clear the interval
                            }
                        });
                    }, 5000); // Poll every 5 seconds
                }
            } else if ($('#golfvista-try-again-link').length > 0 && verificationInterval) {
                // If media_failed status is displayed (and try again link is present)
                // and polling is somehow still running, clear it.
                clearInterval(verificationInterval);
                verificationInterval = null;
            }
        }

        // Initialize polling when the document is ready, if the user is in the verification stage
        // We can simply check for the presence of the '#media-verification-status' div,
        // which will only be rendered if the status is 'media_pending_verification' or 'media_uploaded'.
        if ($('#media-verification-status').length > 0) {
            checkMediaVerificationStatus();
        }

    });

})( jQuery );
