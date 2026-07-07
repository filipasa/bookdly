(function ($)
{
    "use strict";

    $(document).ready(function()
    {
        booknetic.initMultilangInput( $( '#input_name' ), 'taxes', 'name' );
        $('.fs-modal').on('click', '#addTaxSave', function ()
        {
            var name				= $("#input_name").val(),
                type	        	= $("#input_type").val(),
                value				= $("#input_value").val(),
                locations			= $("#input_locations").val(),
                services			= $("#input_services").val(),
                is_active			= $("#input_is_active").prop('checked');

            var data = new FormData();

            data.append('id', $("#add_new_JS").data('tax-id'));
            data.append('name', name);
            data.append('type', type);
            data.append('value', value);
            data.append('locations', JSON.stringify( locations ));
            data.append('services', JSON.stringify( services ));
            data.append('is_active', is_active);
            data.append('translations', booknetic.getTranslationData( $( '.fs-modal' ).first() ) )

            booknetic.ajax( 'save_tax', data, function()
            {
                booknetic.modalHide($(".fs-modal"));

                booknetic.dataTable.reload( $("#fs_data_table_div") );
            });
        });

        booknetic.select2Ajax( $(".fs-modal #input_locations"), 'get_locations');
        booknetic.select2Ajax( $(".fs-modal #input_services"), 'get_services');

    });

})(jQuery);