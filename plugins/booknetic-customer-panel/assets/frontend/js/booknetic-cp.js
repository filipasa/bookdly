var bookneticPaymentStatus;
var bookneticHooks = {
	hooks: {
		'ajax': [],
		'steps' : []
	},

	addFilter: function ( key, fn ) {
		key = key.toLowerCase();

		if ( ! this.hooks.hasOwnProperty( key ) )
		{
			this.hooks[ key ] = [];
		}

		this.hooks[ key ].push( fn );
	},

	doFilter: function ( key, params, ...extra )
	{
		key = key.toLowerCase();

		if ( this.hooks.hasOwnProperty( key ) )
		{
			for (let fn_id in this.hooks[key])
			{
				let fn = this.hooks[key][fn_id];
				if ( typeof params === 'undefined' )
				{
					params = fn( ...extra );
				}
				else
				{
					params = fn( params, ...extra );
				}
			};
		}

		return params;
	},

	addAction: function ( key, fn ) {
		this.addFilter( key, fn );
	},

	doAction: function ( key, ...params ) {
		this.doFilter( key, undefined, ...params );
	}
};

(function($)
{
	"use strict";

	function __( key )
	{
		return key in BookneticData.localization ? BookneticData.localization[ key ] : key;
	}

	$(document).ready( function()
	{
		let initCustomerPanelPage = function( value )
		{
			var customer_panel_js = $( value );

			var booknetic = {

				options: {
					'templates': {
						'loader': '<div class="booknetic_loading_layout"></div>',
						'toast': '<div id="booknetic-toastr"><div class="booknetic-toast-img"><img></div><div class="booknetic-toast-details"><span class="booknetic-toast-description"></span></div><div class="booknetic-toast-remove"><i class="fa fa-times"></i></div></div>'
					}
				},

				localization: {
					month_names: [ __('January'), __('February'), __('March'), __('April'), __('May'), __('June'), __('July'), __('August'), __('September'), __('October'), __('November'), __('December') ],
					day_of_week: [ __('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat'), __('Sun') ] ,
				},

				panel_js: customer_panel_js,

				loadAppointmentsList: function()
				{
					let container = customer_panel_js.find( "#booknetic_appointments_container" );
					if ( ! container.length || ! container.attr( "data-load-appointments" ) ) {
						return;
					}
					let data  = new FormData();
					data.append('client_time_zone' ,booknetic.timeZoneOffset());
					booknetic.ajax( 'get_appointments_list', data, function ( result )
					{
						container.html( result['list_html'] );
						customer_panel_js.find( "#booknetic_payments_container" ).html( result['payments_html'] );
					});
				},

				loadAvailableDate: function(instance , appointmentId )
				{
					instance.set('enable',[]);

					let data  = new FormData();
					data.append('appointment_id' , appointmentId);
					data.append('client_time_zone' ,booknetic.timeZoneOffset());
					data.append('current_month' , (instance.currentMonth + 1).toString().padStart(2,'0') ) ;
					data.append('current_year' , instance.currentYear ) ;
					booknetic.ajax( 'get_available_dates', data, function ( result )
					{
						instance.set('enable',result['available_dates']);
					});
				},


				parseHTML: function ( html )
				{
					var range = document.createRange();
					var documentFragment = range.createContextualFragment( html );
					return documentFragment;
				},

				loading: function ( onOff )
				{
					if( typeof onOff === 'undefined' || onOff )
					{
						$('#booknetic_progress').removeClass('booknetic_progress_done').show();
						$({property: 0}).animate({property: 100}, {
							duration: 1000,
							step: function()
							{
								var _percent = Math.round(this.property);
								if( !$('#booknetic_progress').hasClass('booknetic_progress_done') )
								{
									$('#booknetic_progress').css('width',  _percent+"%");
								}
							}
						});

						$('body').append( this.options.templates.loader );
					}
					else if( ! $('#booknetic_progress').hasClass('booknetic_progress_done') )
					{
						$('#booknetic_progress').addClass('booknetic_progress_done').css('width', 0);

						// IOS bug...
						setTimeout(function ()
						{
							$('.booknetic_loading_layout').remove();
						}, 0);
					}
				},

				htmlspecialchars_decode: function (string, quote_style)
				{
					var optTemp = 0,
						i = 0,
						noquotes = false;
					if(typeof quote_style==='undefined')
					{
						quote_style = 2;
					}
					string = string.toString().replace(/&lt;/g, '<').replace(/&gt;/g, '>');
					var OPTS ={
						'ENT_NOQUOTES': 0,
						'ENT_HTML_QUOTE_SINGLE': 1,
						'ENT_HTML_QUOTE_DOUBLE': 2,
						'ENT_COMPAT': 2,
						'ENT_QUOTES': 3,
						'ENT_IGNORE': 4
					};
					if(quote_style===0)
					{
						noquotes = true;
					}
					if(typeof quote_style !== 'number')
					{
						quote_style = [].concat(quote_style);
						for (i = 0; i < quote_style.length; i++){
							if(OPTS[quote_style[i]]===0){
								noquotes = true;
							} else if(OPTS[quote_style[i]]){
								optTemp = optTemp | OPTS[quote_style[i]];
							}
						}
						quote_style = optTemp;
					}
					if(quote_style & OPTS.ENT_HTML_QUOTE_SINGLE)
					{
						string = string.replace(/&#0*39;/g, "'");
					}
					if(!noquotes){
						string = string.replace(/&quot;/g, '"');
					}
					string = string.replace(/&amp;/g, '&');
					return string;
				},

				htmlspecialchars: function ( string, quote_style, charset, double_encode )
				{
					var optTemp = 0,
						i = 0,
						noquotes = false;
					if(typeof quote_style==='undefined' || quote_style===null)
					{
						quote_style = 2;
					}
					string = typeof string != 'string' ? '' : string;

					string = string.toString();
					if(double_encode !== false){
						string = string.replace(/&/g, '&amp;');
					}
					string = string.replace(/</g, '&lt;').replace(/>/g, '&gt;');
					var OPTS = {
						'ENT_NOQUOTES': 0,
						'ENT_HTML_QUOTE_SINGLE': 1,
						'ENT_HTML_QUOTE_DOUBLE': 2,
						'ENT_COMPAT': 2,
						'ENT_QUOTES': 3,
						'ENT_IGNORE': 4
					};
					if(quote_style===0)
					{
						noquotes = true;
					}
					if(typeof quote_style !== 'number')
					{
						quote_style = [].concat(quote_style);
						for (i = 0; i < quote_style.length; i++)
						{
							if(OPTS[quote_style[i]]===0)
							{
								noquotes = true;
							}
							else if(OPTS[quote_style[i]])
							{
								optTemp = optTemp | OPTS[quote_style[i]];
							}
						}
						quote_style = optTemp;
					}
					if(quote_style & OPTS.ENT_HTML_QUOTE_SINGLE)
					{
						string = string.replace(/'/g, '&#039;');
					}
					if(!noquotes)
					{
						string = string.replace(/"/g, '&quot;');
					}
					return string;
				},

				ajaxResultCheck: function ( res )
				{

					if( typeof res != 'object' )
					{
						try
						{
							res = JSON.parse(res);
						}
						catch(e)
						{
							this.toast( 'Error!', 'unsuccess' );
							return false;
						}
					}

					if( typeof res['status'] == 'undefined' )
					{
						this.toast( 'Error!', 'unsuccess' );
						return false;
					}

					if( res['status'] == 'error' )
					{
						this.toast( typeof res['error_msg'] == 'undefined' ? 'Error!' : res['error_msg'], 'unsuccess' );
						return false;
					}

					if( res['status'] == 'ok' )
						return true;

					// else

					this.toast( 'Error!', 'unsuccess' );
					return false;
				},

				ajax: function ( action , params , func , loading, fnOnError )
				{
					loading = loading === false ? false : true;

					if( loading )
					{
						booknetic.loading(true);
					}

					if( params instanceof FormData)
					{
						params.append('action', 'bkntc_' + action);
					}
					else
					{
						params['action'] = 'bkntc_' + action;
					}

					params = bookneticHooks.doFilter( 'ajax_' + action, params );

					var ajaxObject =
						{
							url: BookneticData.ajax_url,
							method: 'POST',
							data: params,
							success: function ( result )
							{
								if( loading )
								{
									booknetic.loading( 0 );
								}

								if( booknetic.ajaxResultCheck( result, fnOnError ) )
								{
									try
									{
										result = JSON.parse(result);
									}
									catch(e)
									{

									}
									if( typeof func == 'function' )
										func( result );
								}
								else if( typeof fnOnError == 'function' )
								{
									try
									{
										result = typeof result === 'string' ? JSON.parse(result) : result;
									}
									catch(e) {}
									fnOnError( result );
								}
							},
							error: function (jqXHR, exception)
							{
								if( loading )
								{
									booknetic.loading( 0 );
								}

								booknetic.toast( jqXHR.status + ' error!' );

								if( typeof fnOnError == 'function' )
								{
									fnOnError( { status: 'error', error_msg: jqXHR.status + ' error!' } );
								}
							}
						};

					if( params instanceof FormData)
					{
						ajaxObject['processData'] = false;
						ajaxObject['contentType'] = false;
					}

					$.ajax( ajaxObject );

				},

				select2Ajax: function ( select, action, parameters )
				{
					var params = {};
					params['action'] = 'bkntc_' + action;

					select.select2({
						theme: 'bootstrap',
						placeholder: __('select'),
						language: {
							searching: function() {
								return __('searching');
							}
						},
						allowClear: true,
						ajax: {
							url: BookneticData.ajax_url,
							dataType: 'json',
							type: "POST",
							data: function ( q )
							{
								var sendParams = params;
								sendParams['q'] = q['term'];

								if( typeof parameters == 'function' )
								{
									var additionalParameters = parameters( $(this) );

									for (var key in additionalParameters)
									{
										sendParams[key] = additionalParameters[key];
									}
								}
								else if( typeof parameters == 'object' )
								{
									for (var key in parameters)
									{
										sendParams[key] = parameters[key];
									}
								}

								return sendParams;
							},
							processResults: function ( result )
							{
								if( booknetic.ajaxResultCheck( result ) )
								{
									try
									{
										result = JSON.parse(result);
									}
									catch(e)
									{

									}

									return result;
								}
							}
						}
					});
				},

				zeroPad: function(n, p)
				{
					p = p > 0 ? p : 2;
					n = String(n);
					return n.padStart(p, '0');
				},

				toastTimer: 0,

				toast: function(title , type , duration )
				{
					$("#booknetic-toastr").remove();

					if( this.toastTimer )
						clearTimeout(this.toastTimer);

					$("body").append(this.options.templates.toast);

					$("#booknetic-toastr").hide().fadeIn(300);

					type = type === 'unsuccess' ? 'unsuccess' : 'success';

					$("#booknetic-toastr .booknetic-toast-img > img").attr('src', BookneticData.assets_url + 'icons/' + type + '.svg');

					$("#booknetic-toastr .booknetic-toast-description").text(title);

					duration = typeof duration != 'undefined' ? duration : 1000 * ( title.length > 48 ? parseInt(title.length / 12) : 4 );

					this.toastTimer = setTimeout(function()
					{
						$("#booknetic-toastr").fadeOut(200 , function()
						{
							$(this).remove();
						});
					} , typeof duration != 'undefined' ? duration : 4000);
				},

				timeZoneOffset: function()
				{
					if( BookneticData.client_time_zone == 'off' )
						return  '-';

					if ( window.Intl && typeof window.Intl === 'object' )
					{
						return Intl.DateTimeFormat().resolvedOptions().timeZone;
					}
					else
					{
						return new Date().getTimezoneOffset();
					}
				},

				reformatTimeFromCustomFormat: function ( time )
				{
					let parts = time.match( /^([0-9]{1,2}):([0-9]{1,2})\s(am|pm)$/i );

					if ( parts )
					{
						let hours = parseInt( parts[ 1 ] );
						let minutes = parseInt( parts[ 2 ] );
						let ampm = parts[ 3 ].toLowerCase();

						if ( ampm === 'pm' && hours < 12 ) hours += 12;
						if ( ampm === 'am' && hours === 12 ) hours = 0;

						if ( hours < 10 ) hours = '0' + hours.toString();
						if ( minutes < 10 ) minutes = '0' + minutes.toString();

						return hours + ':' + minutes;
					}

					return time;
				},

				waitPaymentFinish: function()
				{
					if( booknetic.paymentWindow.closed )
					{
						return;
					}

					setTimeout( booknetic.waitPaymentFinish, 1000 );
				},

				paymentFinished: function ( status )
				{
					booknetic.paymentStatus = status;
					if( booknetic.paymentWindow && !booknetic.paymentWindow.closed )
					{
						booknetic.paymentWindow.close();
					}
				},
			};

			if( BookneticData.client_timezone != 'off' && BookneticData.tz_offset_param === '-' && typeof bkntc_preview !== 'undefined' && !bkntc_preview )
			{
				location.href = location.href + (location.href.indexOf('?') === -1 ? '?' : '&') + 'client_time_zone=' + booknetic.timeZoneOffset();
			}

			if( 'datepicker' in $.fn && $.fn.datepicker?.dates )
			{
				$.fn.datepicker.dates['en']['months'] = [__('January'), __('February'), __('March'), __('April'), __('May'), __('June'), __('July'), __('August'), __('September'), __('October'), __('November'), __('December')];
				$.fn.datepicker.dates['en']['days'] = [__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')];
				$.fn.datepicker.dates['en']['daysShort'] = $.fn.datepicker.dates['en']['days'];
				$.fn.datepicker.dates['en']['daysMin'] = $.fn.datepicker.dates['en']['days'];
			}

			customer_panel_js.on('click', '#booknetic-toaster .booknetic-toast-remove', function ()
			{
				$(this).closest('#booknetic-toaster').fadeOut(200, function()
				{
					$(this).remove();
					this.toastTimer = 0;
				});
			}).on('click', '.booknetic_cp_header_menu_item', function()
			{
				if( $(this).hasClass('booknetic_cp_header_menu_active') )
				{
					return;
				}

				var tabid = $(this).data('tabid');

				customer_panel_js.find('.booknetic_cp_header_menu_active').removeClass('booknetic_cp_header_menu_active');
				$(this).addClass('booknetic_cp_header_menu_active');

				customer_panel_js.find('.booknetic_cp_tab').hide();
				customer_panel_js.find('#booknetic_tab_' + tabid).show();
			}).on('click', '#booknetic_profile_save', function ()
			{
				var name		= customer_panel_js.find('#booknetic_input_name').val().trim(),
					surname 	= customer_panel_js.find('#booknetic_input_surname').val().trim(),
					email		= customer_panel_js.find('#booknetic_input_email').val().trim(),
					phone_el	= customer_panel_js.find('#booknetic_input_phone'),
					phone		= phone_el.val();
				
				if (phone_el.data('iti') && typeof phone_el.data('iti').getNumber === 'function') {
					try {
						if (typeof intlTelInputUtils !== 'undefined' && intlTelInputUtils.numberFormat) {
							phone = phone_el.data('iti').getNumber(intlTelInputUtils.numberFormat.E164);
						} else {
							phone = phone_el.data('iti').getNumber();
						}
					} catch(e) {
						console.error('[Save Profile Phone Resolve Error]', e);
					}
				}

				var birthdate	= customer_panel_js.find('#booknetic_input_birthdate').val(),
					gender		= customer_panel_js.find('#booknetic_input_gender').val();

				// Frontend Validation
				if (name === '' || email === '') {
					customer_panel_js.find('.cp2-alert-msg').remove();
					var alertMsg = $('<div class="cp2-alert-msg error" style="margin-bottom:10px; padding:10px; border-radius:8px; font-size:13px; font-weight:600; background-color: #fef2f2; color: #b91c1c; border: 1px solid #ef4444;">Name and Email fields are required!</div>');
					$('#booknetic_profile_save').before(alertMsg);
					return;
				}

				booknetic.ajax('save_profile', {
					name: name,
					surname: surname,
					email: email,
					phone: phone,
					birthdate: birthdate,
					gender: gender
				}, function ( result )
				{
					booknetic.toast( result['message'] );
					
					// Render dynamic inline alert box next to button for user feedback
					var statusClass = (result['status'] === 'ok' || result['status'] === true) ? 'success' : 'error';
					var alertMsg = $('<div class="cp2-alert-msg ' + statusClass + '" style="margin-bottom:10px; padding:10px; border-radius:8px; font-size:13px; font-weight:600;">' + result['message'] + '</div>');
					if (statusClass === 'success') {
						alertMsg.css({'background-color': '#ecfdf5', 'color': '#047857', 'border': '1px solid #10b981'});
					} else {
						alertMsg.css({'background-color': '#fef2f2', 'color': '#b91c1c', 'border': '1px solid #ef4444'});
					}
					
					customer_panel_js.find('.cp2-alert-msg').remove();
					$('#booknetic_profile_save').before(alertMsg);
					
					setTimeout(function() {
						alertMsg.fadeOut(300, function() { $(this).remove(); });
					}, 5000);
				}, true, function( result )
				{
					// Handle AJAX error
					var alertMsg = $('<div class="cp2-alert-msg error" style="margin-bottom:10px; padding:10px; border-radius:8px; font-size:13px; font-weight:600; background-color: #fef2f2; color: #b91c1c; border: 1px solid #ef4444;">' + result['error_msg'] + '</div>');
					customer_panel_js.find('.cp2-alert-msg').remove();
					$('#booknetic_profile_save').before(alertMsg);
					return false;
				});
			}).on('click', '#booknetic_change_password_save', function ()
			{
				var old_password		= customer_panel_js.find('#booknetic_input_old_password').val().trim(),
					new_password		= customer_panel_js.find('#booknetic_input_new_password').val().trim(),
					repeat_new_password	= customer_panel_js.find('#booknetic_input_repeat_new_password').val().trim();

				customer_panel_js.find('.cp2-alert-msg').remove();

				// Frontend Validation
				if (old_password === '' || new_password === '' || repeat_new_password === '') {
					var alertMsg = $('<div class="cp2-alert-msg error" style="margin-bottom:10px; padding:10px; border-radius:8px; font-size:13px; font-weight:600; background-color: #fef2f2; color: #b91c1c; border: 1px solid #ef4444;">Please fill in all password fields!</div>');
					$('#booknetic_change_password_save').before(alertMsg);
					return;
				}

				if (new_password !== repeat_new_password) {
					var alertMsg = $('<div class="cp2-alert-msg error" style="margin-bottom:10px; padding:10px; border-radius:8px; font-size:13px; font-weight:600; background-color: #fef2f2; color: #b91c1c; border: 1px solid #ef4444;">New passwords do not match!</div>');
					$('#booknetic_change_password_save').before(alertMsg);
					return;
				}

				booknetic.ajax('change_password', {
					old_password: old_password,
					new_password: new_password,
					repeat_new_password: repeat_new_password
				}, function ( result )
				{
					booknetic.toast( result['message'] );
					
					var statusClass = (result['status'] === 'ok' || result['status'] === true) ? 'success' : 'error';
					var alertMsg = $('<div class="cp2-alert-msg ' + statusClass + '" style="margin-bottom:10px; padding:10px; border-radius:8px; font-size:13px; font-weight:600;">' + result['message'] + '</div>');
					if (statusClass === 'success') {
						alertMsg.css({'background-color': '#ecfdf5', 'color': '#047857', 'border': '1px solid #10b981'});
					} else {
						alertMsg.css({'background-color': '#fef2f2', 'color': '#b91c1c', 'border': '1px solid #ef4444'});
					}
					
					customer_panel_js.find('.cp2-alert-msg').remove();
					$('#booknetic_change_password_save').before(alertMsg);
					
					setTimeout(function() {
						alertMsg.fadeOut(300, function() { $(this).remove(); });
					}, 5000);
				}, true, function( result )
				{
					// Handle AJAX error
					var alertMsg = $('<div class="cp2-alert-msg error" style="margin-bottom:10px; padding:10px; border-radius:8px; font-size:13px; font-weight:600; background-color: #fef2f2; color: #b91c1c; border: 1px solid #ef4444;">' + result['error_msg'] + '</div>');
					customer_panel_js.find('.cp2-alert-msg').remove();
					$('#booknetic_change_password_save').before(alertMsg);
					return false;
				});
			}).on('click', '.booknetic_cp_header_logout_btn', function ()
			{
				location.href = $(this).data('href');
			}).on('click', '.booknetic_reschedule_popup_cancel,.booknetic_pay_now_popup_cancel, .booknetic_cancel_popup_no', function ()
			{
				$(this).closest('.booknetic_popup').fadeOut(200);
				$("html, body").css({ overflow: "auto" });
			}).on('click', '.booknetic_reschedule_btn', function ()
			{
				var tr				= $( this ).closest( '[data-id]' ),
					id				= tr.attr( 'data-id' ),
					date			= tr.attr( 'data-date' ),
					time			= tr.attr( 'data-time' ),
					date_format		= tr.attr( 'data-date-format' ),
					datebased		= tr.data( 'datebased' );

				if ( datebased == 1 )
				{
					customer_panel_js.find( '#booknetic_reschedule_popup_time_area' ).hide();
				}
				else
				{
					customer_panel_js.find( '#booknetic_reschedule_popup_time_area' ).show();
				}

				customer_panel_js.find( '#booknetic_reschedule_popup_date' ).attr( 'o_date', date );

				customer_panel_js.find( '#booknetic_reschedule_popup_date' ).flatpickr(
					{
						altInput: true,
						altFormat: date_format,
						dateFormat: date_format,
						monthSelectorType: 'dropdown',
						locale: {
							firstDayOfWeek: BookneticData.week_starts_on === 'sunday' ? 0 : 1
						},
						defaultDate: date,
						onMonthChange :  (selectedDates, dateStr, instance)=>{
							booknetic.loadAvailableDate(instance , id );
						},
						onOpen : (selectedDates, dateStr, instance)=>{
							booknetic.loadAvailableDate(instance , id );
						},
					} );

				customer_panel_js.find( '.booknetic_reschedule_popup_time' ).html( '<option value="' + time + '">' + time + '</option>' );
				$("html, body").css({ overflow: "hidden" });
				customer_panel_js.find( '#booknetic_cp_reschedule_popup' ).attr( 'data-appointment-id', id ).removeClass('booknetic_hidden').hide().fadeIn( 200 );
			}).on('click', '.booknetic_pay_now_btn', function ()
			{
				let tr = $( this ).closest( '[data-id]' ),
					id = tr.attr( 'data-id' );

				customer_panel_js.find( '#booknetic_cp_pay_now_popup' ).attr( 'data-appointment-id', id ).removeClass('booknetic_hidden').hide().fadeIn( 200 );

			}).on('click', '.booknetic_cancel_btn', function ()
			{
				let tr = $( this ).closest( '[data-id]' ),
					id = tr.attr( 'data-id' );

				customer_panel_js.find( '#booknetic_cp_cancel_popup' ).attr( 'data-appointment-id', id ).removeClass('booknetic_hidden').hide().fadeIn( 200 );

			}).on('click', '.booknetic_cancel_popup_yes', function ()
			{
				let appointment_id = customer_panel_js.find( '#booknetic_cp_cancel_popup' ).attr( 'data-appointment-id' );
				let data = new FormData();
				data.append('id', appointment_id);

				booknetic.ajax( 'cancel_appointment', data, function ( result )
				{
					customer_panel_js.find( '#booknetic_cp_cancel_popup' ).fadeOut( 200 );
					booknetic.toast( result['message'] );
					booknetic.loadAppointmentsList();
				});

			}).on('click', '.booknetic_change_status_btn', function ()
			{
				var tr				= $( this ).closest( '[data-id]' ),
					id				= tr.attr( 'data-id' );

				customer_panel_js.find( '#booknetic_cp_change_status_popup' ).attr( 'data-appointment-id', id ).removeClass('booknetic_hidden').hide().fadeIn( 200 );

			}).on('click', '.booknetic_reschedule_popup_confirm', function ()
			{
				var dataid	= customer_panel_js.find('#booknetic_cp_reschedule_popup').attr( 'data-appointment-id'),
					date	= customer_panel_js.find('#booknetic_reschedule_popup_date').val(),
					time	= customer_panel_js.find('.booknetic_reschedule_popup_time').val();

				booknetic.ajax('reschedule_appointment', {
					id: dataid,
					date: date,
					time: booknetic.reformatTimeFromCustomFormat( time ),
					client_time_zone: booknetic.timeZoneOffset(),
				}, function ( result )
				{
					booknetic.loadAppointmentsList();

					booknetic.toast( result['message'] );
					$("html, body").css({ overflow: "auto" });
					customer_panel_js.find('#booknetic_cp_reschedule_popup').fadeOut(200);
				});
			}).on('click', '.booknetic_change_status_popup_confirm', function ()
			{
				var appointment_id	= customer_panel_js.find('#booknetic_cp_change_status_popup').attr( 'data-appointment-id'),
					status_key	= customer_panel_js.find('.booknetic_change_status_popup_select').val();

				booknetic.ajax('change_appointment_status', {
					id: appointment_id,
					status: status_key
				}, function ( result )
				{
					booknetic.loadAppointmentsList();

					booknetic.toast( result['message'] );

					customer_panel_js.find('#booknetic_cp_change_status_popup').fadeOut(200);
				});
			}).on('click', '.booknetic_pay_now_popup_confirm', function ()
			{
				var appointment_id	= customer_panel_js.find('#booknetic_cp_pay_now_popup').attr( 'data-appointment-id'),
					payment_method	= customer_panel_js.find('.booknetic_pay_now_popup_select').val();

				let data = new FormData()
				data.append('id',appointment_id)
				data.append('payment_method',payment_method)

				bookneticHooks.doAction('ajax_before_confirm' , data , booknetic);
				booknetic.ajax('create_payment_link', data , function ( result )
				{
					bookneticHooks.doAction('ajax_after_confirm_success' , booknetic,data,result)
				},true,function ()
				{
					bookneticHooks.doAction('ajax_after_confirm_error' , booknetic,data,null)
				});
			}).on('click', '#booknetic_profile_delete', function ()
			{
				customer_panel_js.find('#booknetic_cp_delete_profile_popup').removeClass('booknetic_hidden').hide().fadeIn(200);
			}).on('click', '.booknetic_delete_profile_popup_yes', function ()
			{
				booknetic.ajax('delete_profile', {}, function ( result )
				{
					booknetic.loading(1);
					location.href = result['redirect_url'];
				});
			}).on('change', '#booknetic_reschedule_popup_date', function ()
			{
				customer_panel_js.find("#booknetic_reschedule_popup_date").attr('o_date', $("#booknetic_reschedule_popup_date").val());
				customer_panel_js.find('.booknetic_reschedule_popup_time').val('').trigger('change');
			});

			booknetic.select2Ajax( customer_panel_js.find(".booknetic_change_status_popup_select"), 'get_allowed_statuses', function()
			{
				return {
					id: customer_panel_js.find('#booknetic_cp_change_status_popup').attr( 'data-appointment-id'),
				}
			});

			booknetic.select2Ajax( customer_panel_js.find(".booknetic_pay_now_popup_select"), 'get_allowed_payment_gateways', function()
			{
				return {
					id: customer_panel_js.find('#booknetic_cp_pay_now_popup').attr( 'data-appointment-id'),
				}
			});

			booknetic.select2Ajax( customer_panel_js.find(".booknetic_reschedule_popup_time"), 'get_available_times_of_appointment',function()
			{
				return {
					id: customer_panel_js.find('#booknetic_cp_reschedule_popup').attr( 'data-appointment-id'),
					date:  customer_panel_js.find("#booknetic_reschedule_popup_date").attr('o_date'),
					client_time_zone:booknetic.timeZoneOffset()
				}
			});

			var phone_input = customer_panel_js.find('#booknetic_input_phone');
			phone_input.data('iti', window.intlTelInput( phone_input[0], {
				utilsScript: BookneticData.assets_url + "js/utilsIntlTelInput.js",
				initialCountry: phone_input.data('country-code')
			}));

			customer_panel_js.find('.td_datetime').each(function (){
				let tenant_timezone = $( this ).data('appointment-timezone');
				let tenant_offset = 0;
				let offset_diff;

				if( /^[a-zA-Z_-]+\/[a-zA-Z_-]+\/*[a-zA-Z_-]*$/.test( tenant_timezone.trim() ) )
				{
					const str = new Date().toLocaleString('en-us', { timeZone: tenant_timezone.trim(), hour12: false });
					offset_diff = ( new Date( str ).getTime() - new Date().getTime() );
				}
				else if( !isNaN(parseFloat( tenant_timezone.replace("UTC", '') )) )
				{
					tenant_offset = parseFloat(tenant_timezone.replace("UTC", ''))*60*60*1000;
				}

				let client_offset = new Date().getTimezoneOffset()*60*1000*(-1);
				let datetime = $( this ).parent('tr').data('date');
				datetime += ' ';
				datetime += $( this ).parent('tr').data('time');

				datetime = new Date( datetime ) ;

				if ( typeof offset_diff != "undefined" && offset_diff != null )
				{
					datetime = new Date( ( datetime.getTime() - offset_diff ) );
				}
				else
				{
					datetime = new Date( ( datetime.getTime() - tenant_offset + client_offset ) );
				}


				let dateYear = datetime.getFullYear();
				let dateMonth = ("0" + (datetime.getMonth()+1)).slice(-2);
				let dateDay = ("0" + datetime.getDate()).slice(-2);
				let dateString = '';
				let date_format = $( this ).data('date-format');
				switch(date_format) {
					case 'Y-m-d':
						dateString = dateYear + '-' + dateMonth + '-' + dateDay;
						break;
					case 'm/d/Y':
						dateString = dateMonth + '/' + dateDay + '/' + dateYear;
						break;
					case 'd-m-Y':
						dateString = dateDay + '-' + dateMonth + '-' + dateYear;
						break;
					case 'd/m/Y':
						dateString = dateDay + '/' + dateMonth + '/' + dateYear;
						break;
					case 'd.m.Y':
						dateString = dateDay + '.' + dateMonth + '.' + dateYear;
						break;
					default:
						dateString = dateYear + '-' + dateMonth + '-' + dateDay;
				}

				let timeHour = ("0" + datetime.getHours()).slice(-2);
				let timeMinute = ("0" + datetime.getMinutes()).slice(-2);
				let timeString = "";
				let is12Hour = $( this ).data('time-format') == 'g:i A';
				if( parseInt(timeHour) >= 12 && is12Hour )
				{
					timeHour = parseInt( timeHour - 12 );
					if( timeHour === 0 )
					{
						timeHour = 12;
					}
					timeString = timeHour + ":" + timeMinute + " PM";
				}
				else if( parseInt(timeHour) < 12 && is12Hour)
				{
					timeString = timeHour + ":" + timeMinute + " AM";
				}

				else
				{
					timeString = timeHour + ":" + timeMinute;
				}

				$( this ).text( dateString + " " + timeString);
				$( this ).parent('tr').attr('data-date', dateString);
				$( this ).parent('tr').data('time-show', timeString);
			});

			booknetic.loadAppointmentsList();

			bookneticHooks.doAction( 'customer_panel_loaded', booknetic )
		}


		$('.booknetic-body').each( ( i, v ) =>
		{
			initCustomerPanelPage( v )
		})

	});

})(jQuery);

