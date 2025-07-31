(function($) {
    'use strict';

    $(document).ready(function() {
        $('#pattern-list').on('click', '.acd-download-button', function(e) {
            e.preventDefault();

            var $button = $(this);
            var patternId = $button.data('pattern-id');

            $button.text('Downloading...');

            $.ajax({
                url: acdAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'acd_download_pattern',
                    nonce: acdAdmin.nonce,
                    pattern_id: patternId
                },
                success: function(response) {
                    if (response.success) {
                        // The download is being handled by the Cubbit Authenticated Downloader plugin.
                        // We just need to let the user know that the download has started.
                        $button.text('Download Started');
                    } else {
                        $button.text('Download Failed');
                        alert(response.data.message);
                    }
                },
                error: function() {
                    $button.text('Download Failed');
                    alert('An error occurred while trying to download the pattern.');
                }
            });
        });
    });
})(jQuery);
