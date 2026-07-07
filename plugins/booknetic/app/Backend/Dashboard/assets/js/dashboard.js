(function ($)
{
	"use strict";

	const doc = $(document);

	doc.ready(function ()
	{

		var currentDateType = 'today';
		var customDateStart = '';
		var customDateEnd = '';

		var apptTitleMap = {
			'today':      booknetic.__("TODAY'S APPOINTMENTS"),
			'yesterday':  booknetic.__("YESTERDAY'S APPOINTMENTS"),
			'tomorrow':   booknetic.__("TOMORROW'S APPOINTMENTS"),
			'this_week':  booknetic.__("THIS WEEK'S APPOINTMENTS"),
			'last_week':  booknetic.__("LAST WEEK'S APPOINTMENTS"),
			'this_month': booknetic.__("THIS MONTH'S APPOINTMENTS"),
			'this_year':  booknetic.__("THIS YEAR'S APPOINTMENTS"),
			'custom':     booknetic.__('APPOINTMENTS')
		};

		var apptEmptyMap = {
			'today':      booknetic.__('No appointments scheduled for today'),
			'yesterday':  booknetic.__('No appointments found for yesterday'),
			'tomorrow':   booknetic.__('No appointments scheduled for tomorrow'),
			'this_week':  booknetic.__('No appointments found for this week'),
			'last_week':  booknetic.__('No appointments found for last week'),
			'this_month': booknetic.__('No appointments found for this month'),
			'this_year':  booknetic.__('No appointments found for this year'),
			'custom':     booknetic.__('No appointments found for this period')
		};

		doc.on('click', '#date_buttons .date_button', function ()
		{
			if( $(this).hasClass('active_btn') )
				return;


			$("#date_buttons .date_button.active_btn").removeClass('active_btn');
			$(this).addClass('active_btn');


			var type = $(this).data('type');

			if( type == 'custom' )
			{
				$(".custom_date_range").parent().fadeIn(200);
			    $(this).css("display", "none");

				return;
			}
			else
			{
				$(".custom_date_range").parent().fadeOut(200);
			    $(".bkntc-custom-date").css("display", "block");

			}

			currentDateType = type;
			customDateStart = '';
			customDateEnd = '';

			loadStatisticData( type );
			updateApptWidgetTitle();
			loadTodayAppointments( false );
		}).on('click', '.boostore-chip', function (e) {
			if ($(e.target).hasClass('boostore-chip-close')) {
				return;
			}

			const slug = $(this).data('slug');
			const url = new URL(window.location.href);

			url.searchParams.set('module', 'boostore');
			url.searchParams.set('action', 'details');
			url.searchParams.set('slug', slug);

			window.location.href = url.href;

		}).on('click', '.boostore-chip-close', function (e) {
			e.stopPropagation();

			const chip = $(this).closest('.boostore-chip');
			const slug = chip.data('slug');

			booknetic.ajax('Base.dismiss_notification', {slug}, () => {
				chip.fadeOut(200, function () {
					$(this).remove();

					if ($('.boostore-announcements-chips .boostore-chip').length === 0) {
						$('.boostore-announcements').fadeOut(250, function () {
							$(this).remove();
						});
					}
				});
			});

		}).on('click', '.boostore-dismiss-all', function () {
			booknetic.ajax('Base.dismiss_all_notifications', {}, () => {
				$('.boostore-announcements').fadeOut(250, function () {
					$(this).remove();
				});
			});
		});

		dateFormat = dateFormat.replace('Y', 'YYYY')
			.replace('m', 'MM')
			.replace('d', 'DD');

		$(".custom_date_range").daterangepicker({
			opens: 'left',
			locale: {
				format: dateFormat, // "YYYY-MM-DD",
				separator: " - ",
				applyLabel: booknetic.__('Apply'),
				cancelLabel: booknetic.__('Cancel'),
				fromLabel: booknetic.__('From'),
				toLabel: booknetic.__('To'),
				customRangeLabel: "Custom",
				daysOfWeek: [
					booknetic.__("Sun"),
					booknetic.__("Mon"),
					booknetic.__("Tue"),
					booknetic.__("Wed"),
					booknetic.__("Thu"),
					booknetic.__("Fri"),
					booknetic.__("Sat")
				],
				monthNames: [
					booknetic.__("January"),
					booknetic.__("February"),
					booknetic.__("March"),
					booknetic.__("April"),
					booknetic.__("May"),
					booknetic.__("June"),
					booknetic.__("July"),
					booknetic.__("August"),
					booknetic.__("September"),
					booknetic.__("October"),
					booknetic.__("November"),
					booknetic.__("December")
				],
				firstDay: 1
			},
			startDate: new Date(),
			endDate: new Date(),
			cancelClass: "btn-outline-secondary"
		}, function(start, end, label)
		{
			currentDateType = 'custom';
			customDateStart = start.format(dateFormat);
			customDateEnd = end.format(dateFormat);

			loadStatisticData( 'custom', customDateStart, customDateEnd );
			updateApptWidgetTitle();
			loadTodayAppointments( false );
		});

		function loadStatisticData( type, startDate, endDate )
		{

			booknetic.ajax('Dashboard.get_stat', {type: type, start: startDate, end: endDate}, function( result )
			{
				$("#statistic-boxes-area .box-number-div[data-stat='appointments']").text( result['appointments'] );
				$("#statistic-boxes-area .box-number-div[data-stat='duration']").text( result['duration'] );
				$("#statistic-boxes-area .box-number-div[data-stat='revenue']").text( result['revenue'] );
				$("#statistic-boxes-area .box-number-div[data-stat='customers']").text( result['customers'] );

				$('.dashboard-appointments').each(function () {
					let element = $(this).find('.appointment-stats');
					let status = element.attr('data-stat').replace('status-','');
					element.text( result['count_by_status'][status] !== undefined ? result['count_by_status'][status]['count'] : 0);
				})
			});

		}

		loadStatisticData('today');


		// Today's Appointments widget
		var apptOffset = 0;
		var apptShowAll = false;
		var apptLoading = false;

		function renderApptRows( appointments )
		{
			var tbody = $('#today-appt-tbody');

			appointments.forEach(function( appt )
			{
				var row = $('<tr>')
					.addClass('today-appt-row')
					.attr('data-id', appt.id)
					.append(
						$('<td>').addClass('appt-time-cell').html(
							appt.is_day_based
								? '<span class="appt-day-based">' + booknetic.__('Day-based') + '</span>'
								: '<span class="appt-time-start">' + appt.time_start + '</span>' +
								  '<span class="appt-time-separator"> - </span>' +
								  '<span class="appt-time-end">' + appt.time_end + '</span>'
						),
						$('<td>').addClass('appt-customer-cell').html(
							'<div class="appt-customer-info">' +
								'<img class="appt-avatar" src="' + appt.customer_profile + '" alt="">' +
								'<span>' + appt.customer_name + '</span>' +
							'</div>'
						),
						$('<td>').addClass('appt-service-cell').text(appt.service_name),
						$('<td>').addClass('appt-staff-cell').html(
							'<div class="appt-staff-info">' +
								'<img class="appt-avatar" src="' + appt.staff_profile + '" alt="">' +
								'<span>' + appt.staff_name + '</span>' +
							'</div>'
						),
						$('<td>').addClass('appt-status-cell').html(
							'<div class="appt-status-badge" style="background-color:' + appt.status_color + '2b">' +
								'<i class="' + appt.status_icon + '" style="color:' + appt.status_color + '"></i> ' +
								'<span style="color:' + appt.status_color + '">' + appt.status_title + '</span>' +
							'</div>'
						),
						$('<td>').addClass('appt-duration-cell').text(appt.duration)
					);
				tbody.append(row);
			});
		}

		function updateApptWidgetTitle()
		{
			$('.today-appt-title-text').text(apptTitleMap[currentDateType] || apptTitleMap['today']);
			$('.today-appt-empty').text(apptEmptyMap[currentDateType] || apptEmptyMap['today']);
		}

		function loadTodayAppointments( append )
		{
			if( apptLoading )
				return;

			apptLoading = true;
			$('.today-appt-show-more-btn').prop('disabled', true);

			var params = {
				show_all: apptShowAll ? 1 : 0,
				offset: append ? apptOffset : 0,
				type: currentDateType,
				start: customDateStart,
				end: customDateEnd
			};

			booknetic.ajax('Dashboard.get_upcoming_appointments', params, function( result )
			{
				apptLoading = false;
				$('.today-appt-show-more-btn').prop('disabled', false);

				var appointments = result.appointments;
				var table = $('.today-appt-table');
				var empty = $('.today-appt-empty');
				var loading = $('.today-appt-loading');
				var showMore = $('.today-appt-show-more');

				loading.hide();

				if( !append )
				{
					$('#today-appt-tbody').empty();
					apptOffset = 0;
				}

				if( apptOffset === 0 && appointments.length === 0 )
				{
					empty.show();
					table.hide();
					showMore.hide();
					$('.today-appt-count').text('');
					$('.today-appt-show-all').hide();
					return;
				}

				empty.hide();
				table.show();
				$('.today-appt-show-all').show();

				apptOffset += appointments.length;

				$('.today-appt-count').text(result.count_text);
				renderApptRows(appointments);

				if( result.has_more )
				{
					showMore.show();
				}
				else
				{
					showMore.hide();
				}
			});
		}

		loadTodayAppointments(false);

		doc.on('click', '.today-appt-show-more-btn', function()
		{
			loadTodayAppointments(true);
		});

		doc.on('click', '.today-appt-row', function()
		{
			booknetic.loadModal('appointments.info', { id: $(this).data('id') });
		});

		doc.on('click', '.today-appt-show-all', function()
		{
			var btn = $(this);
			apptShowAll = !apptShowAll;
			btn.text(apptShowAll ? booknetic.__('Show Active') : booknetic.__('Show All'));
			loadTodayAppointments(false);
		});


		function loadGraphData( year )
		{

			booknetic.ajax('Dashboard.get_graph_data', { year }, function( result )
			{
				document.querySelector('#graph').innerHTML = booknetic.htmlspecialchars_decode(result.html);
			});

		}

		$('body').on('mouseover' , 'svg rect' ,function (e) {
			$(".graph_info_popup").html($(this).attr('data-count') + " " + booknetic.__("bookings_on") + " " + $(this).attr('data-date') );
			let jsNode = document.querySelector('.graph_info_popup');
			$(".graph_info_popup").show();
			$(".graph_info_popup").css({
				"left" : e.pageX - jsNode.offsetWidth / 2,
				"top"  : e.pageY - jsNode.offsetHeight - 10
			});
		});

		$('body').on('mouseout' , 'svg rect' ,function () {
			$(".graph_info_popup").hide();
		});

		$(".graph-btns .date_buttons_span .date_button").on("click" , function () {
			$(".graph-btns .date_buttons_span .date_button.active").removeClass('active');
			$(this).addClass('active');
			let year  = $(this).attr('data-type');
			loadGraphData( year );
		});

	});

})(jQuery);