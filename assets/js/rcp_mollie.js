/**
 * Created by sanderdewijs on 21-07-16.
 */

/**
 * Set cookie when user dismisses the update notification
 */
jQuery(document).ready(function($) {
    $(".rcp-mollie-notice .notice-dismiss").on('click', function(e){
        e.preventDefault();
        data = {
            action: 'set_rcp_mollie_notice_cookie'
        };
        $.post(ajaxurl, data, function(response) {

        });
    });
});