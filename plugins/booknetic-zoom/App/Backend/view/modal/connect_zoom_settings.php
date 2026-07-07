<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\Zoom\ZoomAddon;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Zoom\bkntc__;

$zoomData = Helper::getOption('zoom_user_data', []);
?>
<div id="booknetic_settings_area">
	<script type="application/javascript" src="<?php echo ZoomAddon::loadAsset('assets/backend/js/connect_zoom_settings.js' )?>"></script>

	<div class="actions_panel clearfix">&nbsp;</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntc__('Integration with Zoom')?>
		</div>
		<div class="ms-content">

			<form class="position-relative">

				<div class="form-row">
					<div class="form-group col-md-12">

						<button type="button" id="connect_zoom" class="btn btn-primary btn-lg<?php echo (!empty( $zoomData )? ' hidden' : '')?>"><?php echo bkntc__('CLICK TO CONNECT ZOOM ACCOUNT')?></button>

						<?php if( !empty( $zoomData ) ):?>
							<div id="disconnect_zoom_area">
								<div class="alert alert-success"><?php echo bkntc__('Zoom account ( %s ) has been connected successfully.', [ $zoomData['user_email'] ])?></div>
								<button type="button" class="btn btn-danger btn-lg" id="disconnect_zoom"><?php echo bkntc__('DISCONNECT')?></button>
							</div>
						<?php endif;?>

					</div>
				</div>

			</form>

		</div>
	</div>
</div>