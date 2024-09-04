jQuery(document).ready(function($) {
    // Function to check user status periodically via AJAX.
    function checkUserStatus(userId) {
        $.ajax({
            url: ajax_object.ajax_url,
            method: 'POST',
            data: {
                action: 'check_user_status',
                user_id: userId,
                nonce: ajax_object.nonce // Include nonce in the request
            },
            success: function(response) {
                if (response.success) {
                    // Update the user's status circle based on the response.
                    if (response.data.is_logged_in) {
                        $('span#user-status-' + userId).removeClass('user-status-offline').addClass('user-status-online');
                    } else {
                        $('span#user-status-' + userId).removeClass('user-status-online').addClass('user-status-offline');
                    }
                }
            }
        });
    }

    // Polling every 5 seconds to check the status.
    setInterval(function() {
        $('.user-status-circle').each(function() {
            let userId = $(this).attr('id').replace('user-status-', '');
            checkUserStatus(userId);
        });
    }, 30000);
});
