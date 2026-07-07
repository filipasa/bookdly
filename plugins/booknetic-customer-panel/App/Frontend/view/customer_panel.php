<?php
defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Customerpanel\bkntc__;

/**
 * @var $parameters
 */
$is_valid_customer = $parameters["is_valid_customer"];
$uniqId = uniqid();
?>

<div id="booknetic_progress" class="booknetic_progress_waiting booknetic_progress_done"><dt></dt><dd></dd></div>

<div class="booknetic-body cp2-shell">
  <!-- ============ TOP BAR ============ -->
  <div class="cp2-topbar">
    <div class="cp2-logo">
      <img src="https://bookdly.co.uk/wp-content/uploads/booknetic/base/bookdly_logo.png" alt="Bookdly Logo" style="height: 28px; width: auto; display: block;">
    </div>
    <div class="cp2-toptools">
      <div class="cp2-user-chip">
        <div class="cp2-avatar"><?php echo strtoupper(substr($parameters['customer']->first_name, 0, 1) . substr($parameters['customer']->last_name, 0, 1)) ?></div>
        <span class="cp2-user-name" style="font-size: 13px; font-weight: 600; color: var(--ink); margin-left: 6px;"><?php echo htmlspecialchars($parameters['customer']->first_name . ' ' . $parameters['customer']->last_name) ?></span>
      </div>
      <button type="button" class="btn btn-ghost btn-sm booknetic_cp_header_logout_btn" data-href="<?php echo wp_logout_url(site_url()) ?>" style="margin-left: 10px; display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 12px; border-radius: 4px; font-size: 12px; font-weight: 600;">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span>Logout</span>
      </button>
    </div>
  </div>

  <div class="cp2-body">
    <!-- ============ LEFT RAIL (DESKTOP NAV) ============ -->
    <div class="cp2-rail">
      <div class="cp2-rail-section">Menu</div>
      <button class="cp2-rail-item active booknetic-cp-tab-item" data-target="#booknetic-tab-appointments" type="button">
        <span class="cp2-rail-icon"><i class="fa-solid fa-clock"></i></span>My Appointments
      </button>
      <a href="<?php echo \BookneticAddon\Customerpanel\CustomerPanelHelper::getCompanyLink() ?>" target="_blank" class="cp2-rail-item" style="text-decoration: none;">
        <span class="cp2-rail-icon"><i class="fa-solid fa-calendar-plus"></i></span>New Appointment
      </a>
      <button class="cp2-rail-item booknetic-cp-tab-item" data-target="#booknetic-tab-payments" type="button">
        <span class="cp2-rail-icon"><i class="fa-solid fa-credit-card"></i></span>Payments
      </button>
      <button class="cp2-rail-item booknetic-cp-tab-item" data-target="#booknetic-tab-profile" type="button">
        <span class="cp2-rail-icon"><i class="fa-solid fa-user"></i></span>Profile &amp; Settings
      </button>
    </div>

    <!-- ============ MAIN CONTENT AREA ============ -->
    <div class="cp2-main">
      <!-- HERO HEADER -->
      <div class="cp2-hero">
        <div>
          <div class="cp2-hero-greeting">Hi <?php echo htmlspecialchars($parameters['customer']->first_name) ?> 👋</div>
          <div class="cp2-hero-sub"><span id="cp_hero_sub_text">Loading bookings...</span></div>
        </div>
        <a href="<?php echo \BookneticAddon\Customerpanel\CustomerPanelHelper::getCompanyLink() ?>" target="_blank" class="cp2-hero-cta" style="text-decoration: none;">+ Book new appointment</a>
      </div>

      <div class="cp2-content">
        <!-- 1. Appointments Tab -->
        <div id="booknetic-tab-appointments" class="booknetic-cp-tab show">
          <div id="booknetic_appointments_container" data-load-appointments="<?php echo $is_valid_customer ?>">
            <!-- AJAX will load appointments_list.php here -->
          </div>
        </div>

        <!-- 2. Payments Tab -->
        <div id="booknetic-tab-payments" class="booknetic-cp-tab">
          <div id="booknetic_payments_container">
            <!-- AJAX will load payments_list.php here -->
          </div>
        </div>

        <!-- 3. Profile Tab -->
        <div id="booknetic-tab-profile" class="booknetic-cp-tab">
          <div class="cp2-pp-grid">
            <!-- Column 1: Personal Details -->
            <div>
              <form action="" id="bookentic-cp-user-form">
                <div class="cp2-section-title" style="font-size:14px;">Personal details</div>
                
                <div class="field" style="margin-bottom:14px;">
                  <label for="booknetic_input_name"><?php echo bkntc__('Name')?></label>
                  <input type="text" class="form-control" id="booknetic_input_name" name="name" value="<?php echo htmlspecialchars($parameters['customer']->first_name)?>">
                </div>

                <div class="field" style="margin-bottom:14px;">
                  <label for="booknetic_input_surname"><?php echo bkntc__('Surname')?></label>
                  <input type="text" class="form-control" id="booknetic_input_surname" name="surname" value="<?php echo htmlspecialchars($parameters['customer']->last_name)?>">
                </div>

                <div class="field" style="margin-bottom:14px;">
                  <label for="booknetic_input_email"><?php echo bkntc__('Email')?></label>
                  <input type="email" class="form-control" id="booknetic_input_email" name="email" value="<?php echo htmlspecialchars($parameters['customer']->email)?>">
                </div>

                <div class="field" style="margin-bottom:14px;">
                  <label for="booknetic_input_phone"><?php echo bkntc__('Phone')?></label>
                  <input type="tel" class="form-control" id="booknetic_input_phone" name="phone" value="<?php echo htmlspecialchars($parameters['customer']->phone_number)?>" data-country-code="<?php echo Helper::getOption('default_phone_country_code', '', $parameters['customer']->tenant_id)?>">
                </div>

                <div class="field" style="margin-bottom:14px;">
                  <label for="booknetic_input_birthdate"><?php echo bkntc__('Date of birth')?></label>
                  <input type="text" class="form-control flatpickr-input" id="booknetic_input_birthdate" name="birthdate" value="<?php echo htmlspecialchars($parameters['customer']->birthdate)?>">
                </div>

                <div class="field" style="margin-bottom:14px;">
                  <label for="booknetic_input_gender"><?php echo bkntc__('Gender')?></label>
                  <select id="booknetic_input_gender" class="form-control" name="gender">
                    <option value="male"<?php echo $parameters['customer']->gender == 'male' ? ' selected' : ''?>><?php echo bkntc__('Male')?></option>
                    <option value="female"<?php echo $parameters['customer']->gender == 'female' ? ' selected' : ''?>><?php echo bkntc__('Female')?></option>
                  </select>
                </div>

                <div style="margin-top: 20px; display: flex; gap: 10px;">
                  <button type="button" class="btn btn-primary btn-sm" <?php echo (!$is_valid_customer ? 'disabled' : '') ?> id="booknetic_profile_save"><?php echo bkntc__('SAVE PROFILE')?></button>
                  <?php if( Helper::getOption('customer_panel_allow_delete_account', 'on', false ) == 'on' ): ?>
                    <button type="button" <?php echo (!$is_valid_customer ? 'disabled' : '') ?> class="btn btn-danger btn-sm" id="booknetic_profile_delete"><?php echo bkntc__('DELETE MY PROFILE')?></button>
                  <?php endif; ?>
                </div>
              </form>
            </div>

            <!-- Column 2: Password Change -->
            <div>
              <form action="" id="booknetic_tab_change_password">
                <div class="cp2-section-title" style="font-size:14px;">Change password</div>
                
                <div class="field" style="margin-bottom:14px;">
                  <label for="booknetic_input_old_password"><?php echo bkntc__('Current password')?></label>
                  <input type="password" class="form-control" id="booknetic_input_old_password" name="old_password" placeholder="*****">
                </div>

                <div class="field" style="margin-bottom:14px;">
                  <label for="booknetic_input_new_password"><?php echo bkntc__('New password')?></label>
                  <input type="password" class="form-control" id="booknetic_input_new_password" name="new_password" placeholder="*****">
                </div>

                <div class="field" style="margin-bottom:14px;">
                  <label for="booknetic_input_repeat_new_password"><?php echo bkntc__('Repeat new password')?></label>
                  <input type="password" class="form-control" id="booknetic_input_repeat_new_password" name="repeat_new_password" placeholder="*****">
                </div>

                <div style="margin-top: 20px;">
                  <button type="button" <?php echo (!$is_valid_customer ? 'disabled' : '') ?> class="btn btn-primary btn-sm" id="booknetic_change_password_save"><?php echo bkntc__('CHANGE PASSWORD')?></button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ============ BOTTOM BAR (MOBILE NAV) ============ -->
  <div class="cp2-bottombar">
    <button class="cp2-bb-item active booknetic-cp-tab-item" data-target="#booknetic-tab-appointments" type="button">
      <span class="cp2-bb-icon"><i class="fa-solid fa-clock"></i></span>
      <span class="cp2-bb-label">Bookings</span>
    </button>
    <a href="<?php echo \BookneticAddon\Customerpanel\CustomerPanelHelper::getCompanyLink() ?>" target="_blank" class="cp2-bb-item">
      <span class="cp2-bb-icon"><i class="fa-solid fa-calendar-plus"></i></span>
      <span class="cp2-bb-label">Book New</span>
    </a>
    <button class="cp2-bb-item booknetic-cp-tab-item" data-target="#booknetic-tab-payments" type="button">
      <span class="cp2-bb-icon"><i class="fa-solid fa-credit-card"></i></span>
      <span class="cp2-bb-label">Payments</span>
    </button>
    <button class="cp2-bb-item booknetic-cp-tab-item" data-target="#booknetic-tab-profile" type="button">
      <span class="cp2-bb-icon"><i class="fa-solid fa-user"></i></span>
      <span class="cp2-bb-label">Profile</span>
    </button>
  </div>

  <!-- ============ CONFIRM / RESCHEDULE POPUPS ============ -->
  <div id="booknetic_cp_delete_profile_popup" class="booknetic_popup booknetic_hidden">
      <div class="booknetic_popup_body">
          <div class="booknetic_cp_cancel_icon">
              <div><img src="<?php echo Helper::assets( 'icons/trash.svg' )?>"></div>
          </div>
          <div class="booknetic_cancel_popup_body">
              <?php echo bkntc__('Are you sure you want to delete your profile?')?>
          </div>
          <div class="booknetic_reschedule_popup_footer">
              <button class="booknetic_btn_secondary booknetic_cancel_popup_no" type="button" data-dismiss="modal"><?php echo bkntc__('NO')?></button>
              <button class="booknetic_btn_danger booknetic_delete_profile_popup_yes" type="button"><?php echo bkntc__('YES')?></button>
          </div>
      </div>
  </div>

  <div id="booknetic_cp_cancel_popup" class="booknetic_popup booknetic_hidden">
      <div class="booknetic_popup_body">
          <div class="booknetic_cp_cancel_icon">
              <div><img src="<?php echo Helper::assets( 'icons/trash.svg' )?>"></div>
          </div>
          <div class="booknetic_cancel_popup_body">
              <?php echo bkntc__('Are you sure you want to cancel your appointment?')?>
          </div>
          <div class="booknetic_reschedule_popup_footer">
              <button class="booknetic_btn_secondary booknetic_cancel_popup_no" type="button" data-dismiss="modal"><?php echo bkntc__('NO')?></button>
              <button class="booknetic_btn_danger booknetic_cancel_popup_yes" type="button"><?php echo bkntc__('YES')?></button>
          </div>
      </div>
  </div>

  <div id="booknetic_cp_reschedule_popup" class="booknetic_popup booknetic_hidden">
      <div class="booknetic_popup_body">
          <div class="booknetic_cp_reschedule_icon">
              <img src="<?php echo Helper::assets('icons/reschedule.svg', 'front-end')?>">
          </div>
          <div class="booknetic_reschedule_popup_body">
              <div class="form-row">
                  <div class="form-group col-md-6">
                      <label for="booknetic_reschedule_popup_date"><?php echo bkntc__('Date')?></label>
                      <input id="booknetic_reschedule_popup_date" type="text" class="form-control">
                  </div>
                  <div class="form-group col-md-6" id="booknetic_reschedule_popup_time_area">
                      <label for="<?php echo $uniqId . '_1' ?>"><?php echo bkntc__('Time')?></label>
                      <select id="<?php echo $uniqId . '_1' ?>" class="form-control booknetic_reschedule_popup_time"></select>
                  </div>
              </div>
          </div>
          <div class="booknetic_reschedule_popup_footer">
              <button class="booknetic_btn_secondary booknetic_reschedule_popup_cancel" type="button" data-dismiss="modal"><?php echo bkntc__('CANCEL')?></button>
              <button class="booknetic_btn_danger booknetic_reschedule_popup_confirm" type="button"><?php echo bkntc__('RESCHEDULE')?></button>
          </div>
      </div>
  </div>

  <div id="booknetic_cp_change_status_popup" class="booknetic_popup booknetic_hidden">
      <div class="booknetic_popup_body">
          <div class="booknetic_cp_reschedule_icon">
              <img src="<?php echo Helper::assets('icons/reschedule.svg', 'front-end')?>">
          </div>
          <div class="booknetic_change_status_popup_body">
              <div class="form-row">
                  <div class="form-group col-md-12">
                      <label for="<?php echo $uniqId . '_2' ?>"><?php echo bkntc__('Select Status')?></label>
                      <select id="<?php echo $uniqId . '_2' ?>" class="form-control booknetic_change_status_popup_select"></select>
                  </div>
              </div>
          </div>
          <div class="booknetic_reschedule_popup_footer">
              <button class="booknetic_btn_secondary booknetic_reschedule_popup_cancel" type="button" data-dismiss="modal"><?php echo bkntc__('CANCEL')?></button>
              <button class="booknetic_btn_danger booknetic_change_status_popup_confirm" type="button"><?php echo bkntc__('SAVE')?></button>
          </div>
      </div>
  </div>

  <div id="booknetic_cp_pay_now_popup" class="booknetic_popup booknetic_hidden">
      <div class="booknetic_popup_body">
         <div class="booknetic_pay_now_popup_body">
              <div class="form-row">
                  <div class="form-group col-md-12">
                      <label for="<?php echo $uniqId . '_3' ?>"><?php echo bkntc__('Select Payment Gateway')?></label>
                      <select id="<?php echo $uniqId . '_3' ?>" class="booknetic_pay_now_popup_select form-control"></select>
                  </div>
              </div>
          </div>
          <div class="booknetic_reschedule_popup_footer">
              <button class="booknetic_btn_secondary booknetic_pay_now_popup_cancel" type="button" data-dismiss="modal"><?php echo bkntc__('CANCEL')?></button>
              <button class="booknetic_btn_danger booknetic_pay_now_popup_confirm" type="button"><?php echo bkntc__('Pay')?></button>
          </div>
      </div>
  </div>
</div>

<style>
  .rtl .iti__country-list {
      left:0;
  }
  .iti__flag {
      background-image: url("<?php echo \BookneticAddon\Customerpanel\CustomerPanelAddon::loadAsset('assets/frontend/img/flags.png')?>");
  }
  @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
      .iti__flag {
          background-image: url("<?php echo \BookneticAddon\Customerpanel\CustomerPanelAddon::loadAsset('assets/frontend/img/flags@2x.png')?>");
      }
  }
</style>
