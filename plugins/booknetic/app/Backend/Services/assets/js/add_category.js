(function ($) {
    "use strict";

    $(document).ready(function () {

        $(document).on('click', '#addBtn', function () {
            var options = {};
            if (new URLSearchParams(window.location.search).get('module') === 'service_categories') {
                options = {type: 'center'};
            }
            booknetic.loadModal('add_new', {id: 0}, options);
        });

        booknetic.dataTable.actionCallbacks['edit'] = function (ids) {
            var options = {};
            if (new URLSearchParams(window.location.search).get('module') === 'service_categories') {
                options = {type: 'center'};
            }
            booknetic.loadModal('add_new', {id: ids[0]}, options);
        };

    });

})(jQuery);
