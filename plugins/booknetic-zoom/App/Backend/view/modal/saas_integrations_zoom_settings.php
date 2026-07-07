<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\Zoom\Integration\ZoomService;
use BookneticAddon\Zoom\ZoomAddon;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Zoom\bkntc__;

?>
<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php echo ZoomAddon::loadAsset('assets/backend/css/integrations_zoom_settings_saas.css')?>">
	<script type="application/javascript" src="<?php echo ZoomAddon::loadAsset('assets/backend/js/integrations_zoom_settings_saas.js')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntc__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntc__('Integration with Zoom')?>
		</div>
		<div class="ms-content">

			<form class="position-relative">

				<div class="form-row enable_disable_row">

					<div class="form-group col-md-2">
						<input id="input_zoom_enable" type="radio" name="input_zoom_enable" value="off"<?php echo Helper::getOption('zoom_enable', 'off')=='off'?' checked':''?>>
						<label for="input_zoom_enable"><?php echo bkntc__('Disabled')?></label>
					</div>
					<div class="form-group col-md-2">
						<input id="input_zoom_disable" type="radio" name="input_zoom_enable" value="on"<?php echo Helper::getOption('zoom_enable', 'off')=='on'?' checked':''?>>
						<label for="input_zoom_disable"><?php echo bkntc__('Enabled')?></label>
					</div>

				</div>

				<div id="integrations_zoom_settings_area">

					<div class="form-row">

						<div class="form-group col-md-6">
							<label for="input_zoom_integration_method"><?php echo bkntc__('Integration method')?>: <i class="far fa-question-circle do_tooltip" data-content="<?php echo bkntc__('OAuth method - in this method you need to build an OAuth Application on the Zoom App Marketplace. After building the App you need to submit it for review. Once you submit your app, Zoom will conduct a functional and security review of your app. On a successful review, your app will be published to the Zoom App Marketplace and the Tenants can connect their Zoom accounts.<br/><br/>JWT method - in this method each tenant need to create their personal JWT app and connect the Zoom account via the JWT app.<br/><br/>Server to server Oauth - in this method each tenant need to create their personal Server-To-Server Oauth app and connect the Zoom account via the Server-To-Server Oauth app.')?>"></i> </label>
							<select class="form-control" id="input_zoom_integration_method">
								<option value="oauth"><?php echo bkntc__('OAuth method')?></option>
								<option value="server_to_server"<?php echo Helper::getOption( 'zoom_integration_method', 'oauth' ) == 'server_to_server' ? 'selected' : '' ?>> <?php echo bkntc__( 'Server to server oauth' ) ?> </option>
							</select>
						</div>
					</div>

					<div class="form-row" data-method="oauth">

						<div class="form-group col-md-12">
							<label for="input_zoom_calback_uri"><?php echo bkntc__('Callback URI')?>:</label>
							<input class="form-control" id="input_zoom_calback_uri" readonly value="<?php echo ZoomService::redirectUri()?>">
						</div>

						<div class="form-group col-md-6">
							<label for="input_zoom_api_key"><?php echo bkntc__('Client ID')?>: <span class="required-star">*</span></label>
							<input class="form-control" id="input_zoom_api_key" value="<?php echo htmlspecialchars( Helper::getOption('zoom_api_key', '') )?>">
						</div>

						<div class="form-group col-md-6">
							<label for="input_zoom_api_secret"><?php echo bkntc__('Client Secret')?>: <span class="required-star">*</span></label>
							<input class="form-control" id="input_zoom_api_secret" value="<?php echo htmlspecialchars( Helper::getOption('zoom_api_secret', '') )?>">
						</div>

					</div>

				</div>

			</form>

		</div>
	</div>
</div>