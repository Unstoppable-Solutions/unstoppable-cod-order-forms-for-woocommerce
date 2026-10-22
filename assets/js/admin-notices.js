/**
 * Admin Notices Dismissal
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle notice dismissal
        $(document).on('click', '.ucof-notice .notice-dismiss', function() {
            var $notice = $(this).closest('.ucof-notice');
            var noticeType = $notice.data('notice');
            
            if (noticeType) {
                $.ajax({
                    url: ucof_notices.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ucof_dismiss_notice',
                        notice: noticeType,
                        nonce: ucof_notices.nonce
                    }
                });
            }
        });
    });

})(jQuery);
