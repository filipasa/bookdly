<?php

defined('ABSPATH') or die();

use BookneticSaaS\Providers\Helpers\Helper;

$payment_success = (isset($_GET['payment_status']) && $_GET['payment_status'] === 'success');
$payment_cancel = (isset($_GET['payment_status']) && $_GET['payment_status'] === 'cancel');
?>

<div class="bookneticsaas_signup">
    <?php if ($payment_cancel): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px; border-radius: 8px; font-size: 14px; padding: 12px 16px; background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;">
            <?php echo bkntcsaas__('Payment checkout was canceled. Please try again to complete registration.') ?>
        </div>
    <?php endif; ?>

	<div class="bookneticsaas_step_1" style="<?php echo $payment_success ? 'display: none;' : ''; ?>">
		<div class="bookneticsaas_header"><?php echo bkntcsaas__('Sign Up')?></div>
		<form method="post" class="bookneticsaas_form">
			<input type="hidden" name="plan_id" value="<?php echo (int) (isset($_GET['plan_id']) ? $_GET['plan_id'] : 0); ?>">
			<input type="hidden" name="billing_cycle" value="<?php echo htmlspecialchars(isset($_GET['billing_cycle']) ? $_GET['billing_cycle'] : 'monthly'); ?>">
			<div class="bookneticsaas_form_element">
				<label for="bookneticsaas_full_name"><?php echo bkntcsaas__('Full name')?></label>
				<input type="text" id="bookneticsaas_full_name" maxlength="100" name="name" value="<?php echo htmlspecialchars(Helper::_get('name', '', 'string')); ?>">
			</div>
			<div class="bookneticsaas_form_element">
				<label for="bookneticsaas_email"><?php echo bkntcsaas__('Email')?></label>
				<input type="text" id="bookneticsaas_email" maxlength="100" name="email" value="<?php echo htmlspecialchars(Helper::_get('email', '', 'email')); ?>">
			</div>
			<div class="bookneticsaas_form_element">
				<label for="bookneticsaas_password"><?php echo bkntcsaas__('Password')?></label>
                <div class="bkntc-password-input">
                    <input type="password" id="bookneticsaas_password" name="password">
                    <button type="button" class="bkntc-toggle-password-visibility">
                        <span class="bkntc-eye-icon bkntc-eye-open">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M320 96C239.2 96 174.5 132.8 127.4 176.6C80.6 220.1 49.3 272 34.4 307.7C31.1 315.6 31.1 324.4 34.4 332.3C49.3 368 80.6 420 127.4 463.4C174.5 507.1 239.2 544 320 544C400.8 544 465.5 507.2 512.6 463.4C559.4 419.9 590.7 368 605.6 332.3C608.9 324.4 608.9 315.6 605.6 307.7C590.7 272 559.4 220 512.6 176.6C465.5 132.9 400.8 96 320 96zM176 320C176 240.5 240.5 176 320 176C399.5 176 464 240.5 464 320C464 399.5 399.5 464 320 464C240.5 464 176 399.5 176 320zM320 256C320 291.3 291.3 320 256 320C244.5 320 233.7 317 224.3 311.6C223.3 322.5 224.2 333.7 227.2 344.8C240.9 396 293.6 426.4 344.8 412.7C396 399 426.4 346.3 412.7 295.1C400.5 249.4 357.2 220.3 311.6 224.3C316.9 233.6 320 244.4 320 256z"/></svg>
                        </span>
                        <span class="bkntc-eye-icon bkntc-eye-closed" style="display: none">
                           <svg width="20"  height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M73 39.1C63.6 29.7 48.4 29.7 39.1 39.1C29.8 48.5 29.7 63.7 39 73.1L567 601.1C576.4 610.5 591.6 610.5 600.9 601.1C610.2 591.7 610.3 576.5 600.9 567.2L504.5 470.8C507.2 468.4 509.9 466 512.5 463.6C559.3 420.1 590.6 368.2 605.5 332.5C608.8 324.6 608.8 315.8 605.5 307.9C590.6 272.2 559.3 220.2 512.5 176.8C465.4 133.1 400.7 96.2 319.9 96.2C263.1 96.2 214.3 114.4 173.9 140.4L73 39.1zM236.5 202.7C260 185.9 288.9 176 320 176C399.5 176 464 240.5 464 320C464 351.1 454.1 379.9 437.3 403.5L402.6 368.8C415.3 347.4 419.6 321.1 412.7 295.1C399 243.9 346.3 213.5 295.1 227.2C286.5 229.5 278.4 232.9 271.1 237.2L236.4 202.5zM357.3 459.1C345.4 462.3 332.9 464 320 464C240.5 464 176 399.5 176 320C176 307.1 177.7 294.6 180.9 282.7L101.4 203.2C68.8 240 46.4 279 34.5 307.7C31.2 315.6 31.2 324.4 34.5 332.3C49.4 368 80.7 420 127.5 463.4C174.6 507.1 239.3 544 320.1 544C357.4 544 391.3 536.1 421.6 523.4L357.4 459.2z"/></svg>
                        </span>
                    </button>
                </div>
			</div>
			<div>
				<button type="submit" class="bookneticsaas_btn_primary bookneticsaas_signup_btn"><?php echo bkntcsaas__('CONTINUE')?></button>
			</div>
		</form>

		<?php if (\BookneticApp\Providers\Helpers\Helper::getOption('google_login_enable', 'off') == 'on'): ?>
			<div class="bkntc-saas-social-login-separator" style="text-align: center; margin: 20px 0; color: #a1a1aa; font-size: 13px; position: relative;">
				<span style="background: #fff; padding: 0 10px; position: relative; z-index: 1;"><?php echo bkntcsaas__('OR') ?></span>
				<div style="position: absolute; top: 50%; left: 0; right: 0; border-top: 1px solid #e4e4e7; z-index: 0;"></div>
			</div>
			<div class="bkntc-saas-social-login-buttons" style="margin-bottom: 20px;">
				<button type="button" class="bkntc-saas-google-signup-btn" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 12px; height: 40px; padding: 0 16px; background: #ffffff; border: 1px solid #dadce0; border-radius: 20px; font-family: 'Google Sans', Roboto, Arial, sans-serif; font-weight: 500; font-size: 14px; color: #1f1f1f; cursor: pointer; transition: background-color 0.2s, box-shadow 0.2s, border-color 0.2s; outline: none; box-shadow: none;" onmouseover="this.style.backgroundColor='#f8f9fa'; this.style.borderColor='#d2d4d7';" onmouseout="this.style.backgroundColor='#ffffff'; this.style.borderColor='#dadce0';">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
						<path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
						<path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
						<path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
					</svg>
					<?php echo bkntcsaas__('Continue with Google') ?>
				</button>
			</div>

			<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('.bkntc-saas-google-signup-btn').on('click', function() {
					var signupUrl = '<?php echo site_url() . "/?" . \BookneticApp\Providers\Helpers\Helper::getSlugName() . "_action=google_login"; ?>';
					var win = window.open(signupUrl, 'Google Signup', 'width=500,height=600');
					
					var timer = setInterval(function() {
						if (!win || win.closed) {
							clearInterval(timer);
							return;
						}
						try {
							if (win.booknetic_login_status) {
								var status = win.booknetic_login_status;
								var redir = win.booknetic_redirect_url;
								var data = win.booknetic_user_data;
								
								clearInterval(timer);
								win.close();
								
								if (status === 'success') {
									window.location.href = redir || '/wp-admin/admin.php?page=booknetic';
								} else if (data) {
									var fullName = ((data.first_name || '') + ' ' + (data.last_name || '')).trim();
									$('#bookneticsaas_full_name').val(fullName);
									$('#bookneticsaas_email').val(data.email || '');
									if (typeof bookneticSaaS !== 'undefined' && bookneticSaaS.toast) {
										bookneticSaaS.toast('Google account authenticated. Please complete details.', 'success');
									}
								}
							}
						} catch(e) {}
					}, 500);
				});
			});
			</script>
		<?php endif; ?>

		<div class="bookneticsaas_footer">
			<span><?php echo bkntcsaas__('Already have an account?')?></span>
			<a href="<?php echo get_permalink(Helper::getOption('sign_in_page'))?>"><?php echo bkntcsaas__('Sign in')?></a>
		</div>
	</div>
	<div class="bookneticsaas_step_2" style="<?php echo $payment_success ? 'display: block;' : ''; ?>">
		<div class="bookneticsaas_header"><?php echo bkntcsaas__('Congratulations!')?></div>
		<div class="bookneticsaas_check_your_email">
			<?php echo bkntcsaas__('We need to verify your email.')?><br/>
			<?php echo bkntcsaas__('Please, check your inbox for a confirmation link.')?>
		</div>
		<div class="bookneticsaas_email_success">
			<img src="<?php echo Helper::assets('images/signup-success.svg', 'front-end')?>" alt="">
		</div>
		<div class="bookneticsaas_footer bookneticsaas_resend_activation">
			<span><?php echo bkntcsaas__('Didn\'t receive the email?')?></span>
			<a href="javascript:;" class="bookneticsaas_resend_activation_link"><?php echo bkntcsaas__('Resend again')?></a>
		</div>
	</div>
</div>