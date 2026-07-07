<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\Googlecalendar\GoogleCalendarAddon;
use BookneticAddon\Googlecalendar\Integration\GoogleCalendarService;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;
use function BookneticAddon\Googlecalendar\bkntc__;

/***
 * @var mixed $parameters
 */

?>

<script>
    var google_all_shortcodes = <?php echo json_encode($parameters['all_shortcode']) ?>;
    var google_all_shortcodes_obj = {};
    google_all_shortcodes.forEach((value,index) => {
        google_all_shortcodes_obj[value.code] = value.name;
    });
</script>

<script src="<?php echo Helper::assets('plugins/summernote/summernote-lite.min.js')?>"></script>
<link rel="stylesheet" href="<?php echo Helper::assets('plugins/summernote/summernote-lite.min.css')?>">
<script src="<?php echo Helper::assets('js/summernote.js')?>"></script>
<link rel="stylesheet" href="<?php echo Helper::assets('css/summernote.css')?>" type="text/css">

<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php echo GoogleCalendarAddon::loadAsset('assets/css/google_calendar_settings.css')?>">
	<script type="application/javascript" src="<?php echo GoogleCalendarAddon::loadAsset('assets/js/google_calendar_settings.js')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntc__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntc__('Google calendar')?>
		</div>
		<div class="ms-content">

			<form class="position-relative">

				<div class="form-row enable_disable_row">

					<div class="form-group col-md-2">
						<input id="input_google_calendar_enable" type="radio" name="input_google_calendar_enable" value="off"<?php echo Helper::getOption('google_calendar_enable', 'off')=='off'?' checked':''?>>
						<label for="input_google_calendar_enable"><?php echo bkntc__('Disabled')?></label>
					</div>
					<div class="form-group col-md-2">
						<input id="input_google_calendar_disable" type="radio" name="input_google_calendar_enable" value="on"<?php echo Helper::getOption('google_calendar_enable', 'off')=='on'?' checked':''?>>
						<label for="input_google_calendar_disable"><?php echo bkntc__('Enabled')?></label>
					</div>

				</div>

				<div id="google_calendar_settings_area">

					<div class="form-row">
						<div class="form-group col-md-12">
							<label for="input_google_calendar_redirect_uri"><?php echo bkntc__('Redirect URI')?>:</label>
							<input class="form-control" id="input_google_calendar_redirect_uri" value="<?php echo GoogleCalendarService::redirectURI() ?>" readonly>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group col-md-6">
							<label for="input_google_calendar_client_id"><?php echo bkntc__('Client ID')?>:</label>
							<input class="form-control" id="input_google_calendar_client_id" value="<?php echo htmlspecialchars( Helper::getOption('google_calendar_client_id', '') )?>">
						</div>
						<div class="form-group col-md-6">
							<label for="input_google_calendar_client_secret"><?php echo bkntc__('Client Secret')?>:</label>
							<input class="form-control" id="input_google_calendar_client_secret" value="<?php echo htmlspecialchars( Helper::getOption('google_calendar_client_secret', '') )?>">
						</div>
					</div>

					<div class="form-row">
						<div class="form-group col-md-12">
							<label for="input_google_calendar_event_title"><?php echo bkntc__('Event title')?>:</label>
							<input class="form-control" id="input_google_calendar_event_title" value="<?php echo htmlspecialchars( Helper::getOption('google_calendar_event_title', '') )?>">
						</div>
					</div>

					<div class="form-row">
						<div class="form-group col-md-12">
							<label for="input_google_calendar_event_description"><?php echo bkntc__('Event description')?>:</label>
							<textarea class="form-control" id="input_google_calendar_event_description"><?php echo htmlspecialchars( Helper::getOption('google_calendar_event_description', '') )?></textarea>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group col-md-6">
							<label for="input_google_calendar_2way_sync"><?php echo bkntc__('Sync method for busy slots from Google Calendar')?>: <i class="far fa-question-circle do_tooltip" data-content="<?php echo bkntc__("1. Live sync;<br/>If you have a few staff, this method would be more convenient for you. When your customers are booking, the plugin will connect to the google calendar and sync busy slots in real-time.<br/>2. Background sync;<br/>For this method, first, you must configure the Cron jobs ( <a href='https://www.booknetic.com/documentation/cron-job' target='_blank'>How to?</a> ). The shorter you set the Cron jobs interval, the more accuracy you will get. This method is usually designed for businesses with a large number of employees and using the \"Any Staff\" option. Because in this case, when your customer selects Any staff option, it might take more than 30-60 seconds to sync all Staff busy slots with Google calendar. By choosing this method, the plugin Cron Jobs will connect to the Google Calendars in the background at the interval you set up and will store the busy slots of all your employees in your local databases. During booking, it will read the information directly from your database. Errors in this method are inevitable. For example, if you configure your cron jobs to run every 15 minutes, the busy slot you add to your Google calendar will be stored in the plugin's local database every 15 minutes. That is, within these 15 minutes, someone can book an appointment in that time slot. Therefore, the shorter you configure the Cron jobs, the less likely there will be errors.")?>"></i></label>
							<select class="form-control" id="input_google_calendar_2way_sync">
								<option value="on"<?php echo Helper::getOption('google_calendar_2way_sync', 'off') == 'on' ? ' selected' : ''?>><?php echo bkntc__('Live sync')?></option>
								<option value="on_background"<?php echo Helper::getOption('google_calendar_2way_sync', 'off') == 'on_background' ? ' selected' : ''?>><?php echo bkntc__('Background sync')?></option>
								<option value="off"<?php echo Helper::getOption('google_calendar_2way_sync', 'off') == 'off' ? ' selected' : ''?>><?php echo bkntc__('Don\'t sync busy slots')?></option>
							</select>
						</div>
						<div class="form-group col-md-6">
							<label for="input_google_calendar_sync_interval"><?php echo bkntc__('Since what date do events in Google calendar sync?')?>:</label>
							<select class="form-control" id="input_google_calendar_sync_interval">
								<option value="1"<?php echo Helper::getOption('google_calendar_sync_interval', '1') == '1' ? ' selected' : ''?>><?php echo bkntc__('Events up to 1 month')?></option>
								<option value="2"<?php echo Helper::getOption('google_calendar_sync_interval', '1') == '2' ? ' selected' : ''?>><?php echo bkntc__('Events up to 2 month')?></option>
								<option value="3"<?php echo Helper::getOption('google_calendar_sync_interval', '1') == '3' ? ' selected' : ''?>><?php echo bkntc__('Events up to 3 month')?></option>
							</select>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group col-md-6">
							<div class="form-control-checkbox">
								<label for="input_google_calendar_add_attendees"><?php echo bkntc__('Add customers as attendees in your calendar events')?>:</label>
								<div class="fs_onoffswitch">
									<input type="checkbox" class="fs_onoffswitch-checkbox" id="input_google_calendar_add_attendees"<?php echo Helper::getOption('google_calendar_add_attendees', 'off')=='on'?' checked':''?>>
									<label class="fs_onoffswitch-label" for="input_google_calendar_add_attendees"></label>
								</div>
							</div>
						</div>
						<div class="form-group col-md-6">
							<div class="form-control-checkbox">
								<label for="input_google_calendar_send_notification"><?php echo bkntc__('Send email invitations to attendees by Google')?>:</label>
								<div class="fs_onoffswitch">
									<input type="checkbox" class="fs_onoffswitch-checkbox" id="input_google_calendar_send_notification"<?php echo Helper::getOption('google_calendar_send_notification', 'off')=='on'?' checked':''?>>
									<label class="fs_onoffswitch-label" for="input_google_calendar_send_notification"></label>
								</div>
							</div>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group col-md-6">
							<div class="form-control-checkbox">
								<label for="input_google_calendar_can_see_attendees"><?php echo bkntc__('Customers can see other attendees')?>:</label>
								<div class="fs_onoffswitch">
									<input type="checkbox" class="fs_onoffswitch-checkbox" id="input_google_calendar_can_see_attendees"<?php echo Helper::getOption('google_calendar_can_see_attendees', 'off')=='on'?' checked':''?>>
									<label class="fs_onoffswitch-label" for="input_google_calendar_can_see_attendees"></label>
								</div>
							</div>
						</div>
					</div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="input_google_calendar_appointment_status"><?php echo bkntc__('Which appointments should be appear on Google Calendar?')?></label>
                            <select class="form-control" id="input_google_calendar_appointment_status" multiple>
                                <?php
                                $busyStatuses = Helper::getBusyAppointmentStatuses();
                                foreach ( Helper::getAppointmentStatuses() AS $statusKey => $status ):
                                    if ( in_array( $statusKey, $busyStatuses ) ) echo '<option value="'.$statusKey.'" ' . (in_array($statusKey, $parameters['google_calendar_appointment_status']) ? "selected" : "") . ' > '.htmlspecialchars($status['title']).' </option>';
                                 endforeach;
                                ?>
                            </select>
                        </div>
                    </div>


				</div>

			</form>

		</div>
	</div>
</div>