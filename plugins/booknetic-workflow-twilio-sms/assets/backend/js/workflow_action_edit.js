(function ($)
{
	"use strict";

	$(document).ready(function()
	{

		$(".fs-modal").on('click', '#saveWorkflowActionBtn', function ()
		{
			saveTwilioSMS();
		}).on('click', '#saveAndTestWorkflowActionBtn', function ()
		{
			saveTwilioSMS(function ()
			{
				booknetic.modal('<div class="p-3 pt-5 pb-5">' +
					'<div class="mb-2">' +
					'<input class="form-control" id="send_test_sms_to" placeholder="'+booknetic.__('To')+'">' +
					'</div>' +
					'<div class="d-flex justify-content-center">' +
					'<button type="button" class="btn btn-lg btn-default mr-1" data-dismiss="modal">'+booknetic.__('CLOSE')+'</button>' +
					'<button type="button" class="btn btn-lg btn-success" id="send_test_btn">'+booknetic.__('SEND')+'</button>' +
					'</div>' +
					'</div>', {type: 'center'});

				$('#send_test_btn').click(function ()
				{
					let modal = $(this).closest( '.modal' );

					booknetic.ajax( 'twilio_sms_workflow.workflow_action_send_test_data', { id: workflow_action_id, to: $('#send_test_sms_to').val()}, function ()
					{
						booknetic.modalHide( modal );
					} );
				});
			});
		});

		function saveTwilioSMS( callback )
		{
			var to	    = $("#input_to").val(),
				body    = $("#input_body").val(),
				is_active = $("#input_is_active").is(':checked') ? 1 : 0;

			var data = new FormData();
			data.append('id', workflow_action_id);
			data.append('to', to);
			data.append('body', body);
			data.append('is_active', is_active);

			booknetic.ajax('twilio_sms_workflow.workflow_action_save_data', data, function()
			{
				if( typeof callback !== 'undefined' )
				{
					callback();
				}
				else
				{
					booknetic.modalHide($(".fs-modal"));
					booknetic.reloadActionList();
				}
			});
		}

		$( '#input_to' ).select2( {
			tokenSeparators: [ ',' ],
			theme: 'bootstrap',
			tags: true,
		});

		booknetic.initKeywordsInput(
			$('#input_body'),
			workflow_twilio_sms_action_all_shortcodes_obj
		);

	});

})(jQuery);