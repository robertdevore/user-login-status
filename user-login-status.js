jQuery(document).ready(function($) {
    // Function to check user statuses periodically via a single AJAX call.
    function checkAllUserStatuses() {
        let userIds = [];

        // Collect all user IDs.
        $('.user-status-circle').each(function() {
            let userId = $(this).attr('id').replace('user-status-', '');
            userIds.push(userId);
        });

        // Make a single AJAX request with all user IDs.
        $.ajax({
            url: ajax_object.ajax_url,
            method: 'POST',
            data: {
                action: 'check_user_status',
                user_ids: userIds,
                nonce: ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Loop through the response and update each user's status.
                    $.each(response.data, function(userId, status) {
                        let userStatusElement = $('span#user-status-' + userId);
                        if (status === 'online') {
                            userStatusElement.removeClass('user-status-offline').addClass('user-status-online');
                        } else {
                            userStatusElement.removeClass('user-status-online').addClass('user-status-offline');
                        }
                    });
                }
            }
        });
    }

    // Poll every 30 seconds to check all user statuses in a single call.
    setInterval(checkAllUserStatuses, 30000);
});
