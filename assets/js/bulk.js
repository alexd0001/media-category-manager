(function ($, window) {
    'use strict';

    function getSelectedAttachmentIds() {
        var ids = [];

        $('tbody th.check-column input[type="checkbox"]:checked').each(function () {
            ids.push($(this).val());
        });

        $('.attachments .attachment.selected').each(function () {
            var id = $(this).data('id');

            if (id) {
                ids.push(id);
            }
        });

        return ids.filter(function (value, index, self) {
            return self.indexOf(value) === index;
        });
    }

    $(function () {
        $('#mcm-bulk-apply').on('click', function () {
            var ids = getSelectedAttachmentIds();
            var addIds = $('#mcm-bulk-add').val() || [];
            var removeIds = $('#mcm-bulk-remove').val() || [];
            var status = $('#mcm-bulk-status');

            if (!ids.length) {
                status.text(window.mcmAdmin.strings.selectionRequired);
                return;
            }

            status.text('');

            $.post(window.mcmAdmin.ajaxUrl, {
                action: window.mcmAdmin.action,
                nonce: window.mcmAdmin.nonce,
                attachment_ids: ids,
                add_term_ids: addIds,
                remove_term_ids: removeIds
            }).done(function (response) {
                if (response && response.success) {
                    status.text(response.data.message || window.mcmAdmin.strings.bulkSuccess);
                    window.location.reload();
                    return;
                }

                status.text((response && response.data && response.data.message) || window.mcmAdmin.strings.bulkError);
            }).fail(function () {
                status.text(window.mcmAdmin.strings.bulkError);
            });
        });
    });
})(jQuery, window);
