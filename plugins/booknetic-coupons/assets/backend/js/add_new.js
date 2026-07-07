(function ($)
{
	"use strict";

	$(document).ready(function()
	{
		var fadeSpeed = 0;

		$('.fs-modal').on('click', '#addCouponSave', function ()
		{
			var code				= $("#input_code").val(),
				discount			= $("#input_discount").val(),
				discount_type		= $("#input_discount_type").val(),
				start_date			= $("#input_start_date").val(),
				end_date			= $("#input_end_date").val(),
				usage_limit			= $("#input_usage_limit").val(),
				once_per_customer	= $("#input_once_per_select").val().includes('once_per_customer'),
				once_per_booking	= $("#input_once_per_select").val().includes('once_per_booking'),
				services			= $("#input_services").val(),
				staff				= $("#input_staff").val();

			console.log(once_per_customer, once_per_booking);

			var data = new FormData();

			data.append('id', $("#add_new_JS").data('coupon-id'));
			data.append('code', code);
			data.append('discount', discount);
			data.append('discount_type', discount_type);
			data.append('start_date', start_date);
			data.append('end_date', end_date);
			data.append('usage_limit', usage_limit);
			data.append('once_per_customer', once_per_customer);
			data.append('once_per_booking', once_per_booking);
			data.append('services', JSON.stringify( services ));
			data.append('staff', JSON.stringify( staff ));

			booknetic.ajax( 'save_coupon', data, function()
			{
				booknetic.modalHide($(".fs-modal"));

				booknetic.dataTable.reload( $("#fs_data_table_div") );
			});
		});

		booknetic.select2Ajax( $(".fs-modal #input_services"), 'get_services');
		booknetic.select2Ajax( $(".fs-modal #input_staff"), 'get_staff');
		$(".fs-modal #input_once_per_select").select2({
			theme:			'bootstrap',
			placeholder:	booknetic.__('select'),
			allowClear:		true
		});

	});

})(jQuery);