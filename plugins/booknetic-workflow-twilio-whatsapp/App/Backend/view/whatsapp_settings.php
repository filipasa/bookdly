<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\TwilioWhatsapp\TwilioWhatsappAddon;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\TwilioWhatsapp\bkntc__;

?>
<div id="booknetic_settings_area">
	<script type="application/javascript" src="<?php echo TwilioWhatsappAddon::loadAsset('assets/backend/js/settings.js')?>"></script>

	<div class="actions_panel clearfix">
		<button type="button" class="btn btn-lg btn-success settings-save-btn float-right"><i class="fa fa-check pr-2"></i> <?php echo bkntc__('SAVE CHANGES')?></button>
	</div>

	<div class="settings-light-portlet">
		<div class="ms-title">
			<?php echo bkntc__('WhatsApp Twilio settings')?>
		</div>
		<div class="ms-content">

			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_sms_account_sid"><?php echo bkntc__('Account SID')?>:</label>
					<input class="form-control" id="input_sms_account_sid" value="<?php echo htmlspecialchars( Helper::getOption('whatsapp_account_sid', '') )?>">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_sms_auth_token"><?php echo bkntc__('Auth Token')?>:</label>
					<input class="form-control" id="input_sms_auth_token" value="<?php echo htmlspecialchars( Helper::getOption('whatsapp_auth_token', '') )?>">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-md-6">
					<label for="input_sender_phone_number_whatsapp"><?php echo bkntc__('Sender phone number for WhatsApp')?>:</label>
					<input class="form-control" id="input_sender_phone_number_whatsapp" value="<?php echo htmlspecialchars( Helper::getOption('sender_phone_number_whatsapp', '') )?>" placeholder="+15123456789">
				</div>
			</div>

		</div>
	</div>
</div>