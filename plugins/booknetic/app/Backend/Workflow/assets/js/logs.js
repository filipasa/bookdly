$(document).ready(function () {
    booknetic.addAction('ajax_after_retry_unsuccess', function () {
        booknetic.dataTable.reload($('#fs_data_table_div'));
    });

    booknetic.dataTable.actionCallbacks['details'] = function (ids) {
        booknetic.loadModal('details', {'id': ids[0]});
    }

    booknetic.dataTable.actionCallbacks['retry'] = function (ids) {
        booknetic.confirm(
            booknetic.__('Are you sure you want to retry this workflow?'),
            'warning',
            'updates',
            function () {
                booknetic.ajax('retry', {id: ids[0]}, function () {
                    booknetic.dataTable.reload($('#fs_data_table_div'));
                    booknetic.toast(booknetic.__('Success'), 'success');
                });
            },
            booknetic.__('Retry')
        );
    }

    $(document).on('click', '#detailsRetryBtn', function () {
        const logId = $(this).data('log-id');

        booknetic.confirm(
            booknetic.__('Are you sure you want to retry this workflow?'),
            'warning',
            'updates',
            function () {
                booknetic.ajax('retry', {id: logId}, function () {
                    booknetic.dataTable.reload($('#fs_data_table_div'));
                    booknetic.toast(booknetic.__('Success'), 'success');
                    booknetic.modalHide($('.fs-modal'));
                });
            },
            booknetic.__('Retry')
        );
    });
});
