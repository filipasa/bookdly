(function ($) {
    "use strict";

    $(document).ready(function () {

        booknetic.initMultilangInput($('input#new_category_name'), 'location_categories', 'name');

        $('#save_new_category').on('click', function () {

            const id = $("#add_new_category_JS").data('category-id');
            let name = $(".fs-modal #new_category_name").val();

            let formData = new FormData();
            formData.append("id", id);
            formData.append("name", name);
            formData.append("translations", booknetic.getTranslationData($('.fs-modal').first()));

            let url;

            if (id > 0) {
                url = 'location_categories.update';
            } else {
                url = 'location_categories.create';
            }

            booknetic.ajax(url, formData, function () {

                booknetic.toast(booknetic.__('saved_successfully'), 'success');
                booknetic.modalHide($(".fs-modal"));

                const fsTableDiv = $("#fs_data_table_div");

                if (fsTableDiv.length) {
                    booknetic.dataTable.reload(fsTableDiv);
                }

            });
        });

    });
})(jQuery);
