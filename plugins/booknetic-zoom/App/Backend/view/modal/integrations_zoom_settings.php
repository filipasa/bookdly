<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\Zoom\ZoomAddon;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Zoom\bkntc__;

?>

<script>
    var all_shortcodes = <?php echo json_encode($parameters['all_shortcode']) ?>;
    var all_shortcodes_obj = {};
    all_shortcodes.forEach((value,index) => {
        all_shortcodes_obj[value.code] = value.name;
    });
</script>

<script src="<?php echo Helper::assets('plugins/summernote/summernote-lite.min.js')?>"></script>
<link rel="stylesheet" href="<?php echo Helper::assets('plugins/summernote/summernote-lite.min.css')?>">
<script src="<?php echo Helper::assets('js/summernote.js')?>"></script>
<link rel="stylesheet" href="<?php echo Helper::assets('css/summernote.css')?>" type="text/css">

<div id="booknetic_settings_area">
	<link rel="stylesheet" href="<?php echo ZoomAddon::loadAsset('assets/backend/css/integrations_zoom_settings.css' )?>">
	<script type="application/javascript" src="<?php echo ZoomAddon::loadAsset('assets/backend/js/integrations_zoom_settings.js' )?>"></script>

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
					<?php if( ! Helper::isSaaSVersion() || Helper::getOption('zoom_integration_method', 'oauth', false) == 'jwt' || Helper::getOption('zoom_integration_method', 'oauth', false) == 'server_to_server' ):?>

                    <div class="form-row zoom-integration-method-details" id="zoom_integration_method_server_to_server">
                        <div class="form-group col-md-4">
                            <label for="input_zoom_account_id"><?php echo bkntc__('Account ID')?>: <span class="required-star">*</span></label>
                            <input class="form-control" id="input_zoom_account_id" value="<?php echo htmlspecialchars( Helper::getOption('zoom_account_id', '') )?>">
                        </div>

                        <div class="form-group col-md-4">
                            <label for="input_zoom_client_id"><?php echo bkntc__('Client ID')?>: <span class="required-star">*</span></label>
                            <input class="form-control" id="input_zoom_client_id" value="<?php echo htmlspecialchars( Helper::getOption('zoom_client_id', '') )?>">
                        </div>

                        <div class="form-group col-md-4">
                            <label for="input_zoom_client_secret"><?php echo bkntc__('Client secret')?>: <span class="required-star">*</span></label>
                            <input class="form-control" id="input_zoom_client_secret" value="<?php echo htmlspecialchars( Helper::getOption('zoom_client_secret', '') )?>">
                        </div>
                    </div>

					<?php else:?>
					<div class="form-row">
						<div class="form-group col-md-12">
						<button type="button" id="connect_zoom" class="btn btn-primary btn-lg<?php echo (!empty( $parameters['zoom_data'] )? ' hidden' : '')?>"><?php echo bkntc__('CLICK TO CONNECT ZOOM ACCOUNT')?></button>

						<?php if( !empty( $parameters['zoom_data'] ) ):?>
							<div id="disconnect_zoom_area">
								<div class="alert alert-success"><?php echo bkntc__('Zoom account ( %s ) has been connected successfully.', [ $parameters['zoom_data']['user_email'] ])?></div>
								<button type="button" class="btn btn-danger btn-lg" id="disconnect_zoom"><?php echo bkntc__('DISCONNECT')?></button>
							</div>
						<?php endif;?>
						</div>
					</div>
					<?php endif;?>

					<div class="form-row">

						<div class="form-group col-md-12">
							<label for="input_zoom_meeting_title"><?php echo bkntc__('Meeting topic')?>: <span class="required-star">*</span></label>
							<input class="form-control" id="input_zoom_meeting_title" value="<?php echo htmlspecialchars( Helper::getOption('zoom_meeting_title', '') )?>">
						</div>

						<div class="form-group col-md-12">
							<label for="input_zoom_meeting_agenda"><?php echo bkntc__('Meeting description')?>: <span class="required-star">*</span></label>
							<textarea class="form-control" id="input_zoom_meeting_agenda"><?php echo htmlspecialchars( Helper::getOption('zoom_meeting_agenda', '') )?></textarea>
						</div>

						<div class="form-group col-md-6">
							<div class="form-control-checkbox">
								<label for="input_zoom_set_random_password"><?php echo bkntc__('Set random password for meetings')?>:</label>
								<div class="fs_onoffswitch">
									<input type="checkbox" class="fs_onoffswitch-checkbox" id="input_zoom_set_random_password"<?php echo Helper::getOption('zoom_set_random_password', 'on')=='on'?' checked':''?>>
									<label class="fs_onoffswitch-label" for="input_zoom_set_random_password"></label>
								</div>
							</div>
						</div>
					</div>
				</div>

			</form>

		</div>
	</div>
</div>