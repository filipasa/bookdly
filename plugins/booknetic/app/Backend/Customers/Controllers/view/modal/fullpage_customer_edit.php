<?php
defined('ABSPATH') or die();
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

/**
 * @var \BookneticApp\Backend\Customers\DTOs\Response\CustomerViewResponse $parameters
 */
$customer = $parameters->getCustomer();
$customerId = (int)$customer->getId();

$isEmailRequired = $parameters->isEmailRequired();
$isPhoneRequired = $parameters->isPhoneRequired();
$users = $parameters->getUsers();
$hasWpUser = $parameters->hasWpUser();
$categories = $parameters->getCategories();
$isFullNameEnabled = $parameters->isFullNameEnabled();

$customerFullName = trim($customer->getFirstName() . ' ' . $customer->getLastName());
$initials = '';
if (!empty($customerFullName)) {
    $words = explode(' ', $customerFullName);
    foreach ($words as $w) {
        $initials .= mb_substr($w, 0, 1, 'UTF-8');
    }
    $initials = mb_substr($initials, 0, 2, 'UTF-8');
}
$colors = ['#ef4444', '#f97316', '#f59e0b', '#10b981', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899', '#14b8a6'];
$colorIndex = abs(crc32($customer->getEmail() ?: $customerFullName)) % count($colors);
$customerColor = $colors[$colorIndex];
?>

<style>
:root {
	--wf-primary: #6366f1;
	--wf-primary-light: #818cf8;
	--wf-primary-dim: #eef2ff;
	--wf-success: #22c55e;
	--wf-success-dim: #dcfce7;
	--wf-warning: #f59e0b;
	--wf-warning-dim: #fef3c7;
	--wf-danger: #ef4444;
	--wf-danger-dim: #fee2e2;
	--wf-bg: #f8fafc;
	--wf-surface: #ffffff;
	--wf-surface-2: #f1f5f9;
	--wf-border: #e2e8f0;
	--wf-text: #0f172a;
	--wf-text-2: #475569;
	--wf-text-3: #94a3b8;
	--wf-radius: 12px;
	--wf-radius-sm: 8px;
	--wf-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 12px rgba(0,0,0,.04);
}

#booknetic_customer_fullpage_container {
	width: 100%;
	background: var(--wf-bg);
	font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
	color: var(--wf-text);
	min-height: calc(100vh - 120px);
}

.wf-fullpage-container {
	width: 100%;
	display: block;
}

/* TOP BAR */
.wf-top-bar {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 16px 32px;
	background: var(--wf-surface);
	border-bottom: 2px solid var(--wf-border);
	position: sticky;
	top: 0;
	z-index: 50;
}

.wf-top-bar .wf-back-link {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	font-size: 13px;
	font-weight: 500;
	color: var(--wf-text-3);
	text-decoration: none !important;
	transition: color 0.18s ease;
}

.wf-top-bar .wf-back-link:hover {
	color: var(--wf-primary);
}

.wf-top-bar .wf-back-link svg {
	width: 15px;
	height: 15px;
}

.wf-top-bar .wf-sep {
	color: var(--wf-text-3);
	font-size: 14px;
}

.wf-top-bar .wf-crumb {
	font-size: 13px;
	font-weight: 600;
	color: var(--wf-text-2);
}

.wf-top-bar .wf-crumb.active {
	color: var(--wf-text);
}

/* PAGE HEADER */
.wf-page-header {
	display: flex;
	align-items: flex-start;
	justify-content: space-between;
	padding: 28px 32px 0;
	gap: 20px;
}

.wf-appt-id {
	font-size: 11px;
	font-weight: 700;
	color: var(--wf-text-3);
	text-transform: uppercase;
	letter-spacing: 0.06em;
	margin-bottom: 4px;
}

.wf-appt-title {
	font-size: 24px;
	font-weight: 800;
	color: var(--wf-text);
	line-height: 1.25;
}

.wf-header-actions {
	display: flex;
	align-items: center;
	gap: 12px;
}

/* CUSTOMER LAYOUT */
.wf-customer-layout {
	max-width: 1150px;
	margin: 32px auto;
	padding: 0 24px 100px;
}

.wf-appt-layout {
	display: grid;
	grid-template-columns: 1fr 320px;
	gap: 32px;
	align-items: flex-start;
}

.wf-sidebar-card {
	background: var(--wf-surface);
	border: 1.5px solid var(--wf-border);
	border-radius: var(--wf-radius);
	padding: 24px;
	box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

.wf-meta-line {
	display: flex;
	justify-content: space-between;
	align-items: center;
	font-size: 13px;
	margin-bottom: 12px;
}

.wf-meta-line:last-child {
	margin-bottom: 0;
}

.wf-meta-line .wf-lbl {
	color: var(--wf-text-3);
	font-weight: 500;
}

.wf-meta-line .wf-val {
	color: var(--wf-text);
	font-weight: 600;
}
.wf-btn-save-customer:hover {
	background: #16a34a !important;
	border-color: #16a34a !important;
}
</style>

<link rel="stylesheet" href="<?php echo Helper::assets('css/intlTelInput.min.css', 'front-end')?>">
<script type="application/javascript" src="<?php echo Helper::assets('js/intlTelInput.min.js', 'front-end')?>"></script>

<div class="wf-fullpage-container wf-mode-edit" data-customer-id="<?php echo $customerId; ?>" data-tel-input-asset-url="<?php echo htmlspecialchars(Helper::assets('js/utilsIntlTelInput.js', 'front-end'), ENT_QUOTES); ?>">

  <!-- TOP BAR / BREADCRUMBS -->
  <div class="wf-top-bar">
    <a href="#" class="wf-back-link wf-back-to-table">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 3L5 8l5 5"/></svg>
      <?php echo bkntc__('Customers')?>
    </a>
    <span class="wf-sep">/</span>
    <span class="wf-crumb active"><?php echo $customerId > 0 ? bkntc__('Edit Customer') : bkntc__('Add Customer'); ?></span>
  </div>

  <!-- PAGE HEADER -->
  <div class="wf-page-header" style="max-width: 1100px; margin: 28px auto 0; padding: 0 24px;">
    <div>
      <div class="wf-appt-id"><?php echo $customerId > 0 ? bkntc__('Modify Customer') : bkntc__('Add New Customer'); ?></div>
      <div class="wf-appt-title"><?php echo $customerId > 0 ? bkntc__('Update customer profile information.') : bkntc__('Create a new customer profile.'); ?></div>
    </div>
    <div class="wf-header-actions" style="display: flex; gap: 12px;">
      <button type="button" class="btn btn-default btn-outline-secondary wf-back-to-table" style="font-weight: 600; border-radius: 8px; height: 38px; padding: 0 16px;"><?php echo bkntc__('Cancel')?></button>
      <button type="button" class="btn btn-primary wf-btn-save-customer" style="font-weight: 700; border-radius: 8px; height: 38px; padding: 0 16px; display: inline-flex; align-items: center; gap: 6px; background: #22c55e !important; border-color: #22c55e !important; color: #fff !important;">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8l4 4 6-6"/></svg>
        <?php echo $customerId > 0 ? bkntc__('Save Changes') : bkntc__('Add Customer'); ?>
      </button>
    </div>
  </div>

  <div class="wf-customer-layout" style="max-width: 1100px; margin: 24px auto; padding: 0 24px 100px;">
    
    <div class="wf-appt-layout">
      <!-- Main Content (Left Column) -->
      <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- Profile Information Section -->
        <div class="wf-sidebar-card" style="padding: 28px;">
          <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="8" r="6"/><path d="M8 8a3 3 0 100-6 3 3 0 000 6z"/><path d="M2 14c0-3.3 2.7-6 6-6s6 2.7 6 6"/></svg>
            <?php echo bkntc__('Profile Information')?>
          </div>

          <!-- Avatar Selection -->
          <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 28px;">
            <?php 
              $profileImg = $customer->getProfileImage();
              $hasCustomImage = !empty($profileImg) && strpos($profileImg, 'no-photo') === false;
            ?>
            <div id="wf-avatar-preview" style="width: 64px; height: 64px; border-radius: 50%; background: <?php echo htmlspecialchars($customerColor); ?>; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; overflow: hidden;">
              <?php if ($hasCustomImage): ?>
                <img src="<?php echo htmlspecialchars(Helper::profileImage($profileImg, 'Customers')); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
              <?php else: ?>
                <?php echo htmlspecialchars($initials ?: '?'); ?>
              <?php endif; ?>
            </div>
            <div>
              <input type="file" id="input_image" style="display: none;" accept="image/*">
              <label for="input_image" class="btn btn-default btn-sm btn-outline-secondary" style="margin-bottom: 4px; padding: 6px 14px; height: auto; font-weight: 600; border-radius: 8px; cursor: pointer; display: inline-block; border: 1px solid #cbd5e1;">
                <?php echo bkntc__('Upload Image')?>
              </label>
              <div style="font-size: 11px; color: #94a3b8; font-weight: 500;"><?php echo bkntc__('PNG, JPG, max 800x800 up to 5mb')?></div>
            </div>
          </div>

          <!-- Names Row -->
          <div class="row">
            <div class="form-group col-md-6" style="margin-bottom: 20px;">
              <label style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; display: block;"><?php echo bkntc__('First Name')?> <span style="color: #ef4444;">*</span></label>
              <input type="text" id="input_first_name" class="form-control" style="border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 8px 12px; height: 40px; font-size: 13px;" placeholder="John" value="<?php echo htmlspecialchars($customer->getFirstName() ?? ''); ?>">
            </div>
            <div class="form-group col-md-6" style="margin-bottom: 20px;">
              <label style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; display: block;"><?php echo bkntc__('Last Name')?> <span style="color: #ef4444;">*</span></label>
              <input type="text" id="input_last_name" class="form-control" style="border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 8px 12px; height: 40px; font-size: 13px;" placeholder="Doe" value="<?php echo htmlspecialchars($customer->getLastName() ?? ''); ?>">
            </div>
          </div>

          <!-- Category and Gender Row -->
          <div class="row">
            <div class="form-group col-md-6" style="margin-bottom: 20px;">
              <label style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; display: block;"><?php echo bkntc__('Category')?></label>
              <div style="display: flex; gap: 8px; align-items: center;">
                <select id="input_category_id" class="form-control" style="flex: 1; border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 6px 12px; height: 40px; font-size: 13px;">
                  <option value=""><?php echo bkntc__('Select category')?></option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo $customer->getCategoryId() == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="button" id="wf-add-category-btn" class="btn" style="border: 1.5px solid #cbd5e1; background: #fff; color: #6366f1; border-radius: 8px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold; cursor: pointer; padding: 0;">+</button>
              </div>
            </div>
            <div class="form-group col-md-6" style="margin-bottom: 20px;">
              <label style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; display: block;"><?php echo bkntc__('Gender')?></label>
              <select id="input_gender" class="form-control" style="width: 100%; border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 6px 12px; height: 40px; font-size: 13px;">
                <option value="" <?php echo empty($customer->getGender()) ? 'selected' : ''; ?>><?php echo bkntc__('Select gender')?></option>
                <option value="female" <?php echo $customer->getGender() === 'female' ? 'selected' : ''; ?>><?php echo bkntc__('Female')?></option>
                <option value="male" <?php echo $customer->getGender() === 'male' ? 'selected' : ''; ?>><?php echo bkntc__('Male')?></option>
                <option value="other" <?php echo $customer->getGender() === 'other' ? 'selected' : ''; ?>><?php echo bkntc__('Other')?></option>
              </select>
            </div>
          </div>

          <!-- Date of Birth Row -->
          <div class="row">
            <div class="form-group col-md-6" style="margin-bottom: 0;">
              <label style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; display: block;"><?php echo bkntc__('Date of birth')?></label>
              <input type="date" id="input_birthday" class="form-control" style="border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 8px 12px; height: 40px; font-size: 13px;" value="<?php echo htmlspecialchars($customer->getBirthdate() ?? ''); ?>">
            </div>
          </div>

        </div>

        <!-- Contact Details Section -->
        <div class="wf-sidebar-card" style="padding: 28px;">
          <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 4h12v8H2z"/><path d="M2 4l6 4 6-4"/></svg>
            <?php echo bkntc__('Contact Details')?>
          </div>

          <div class="row">
            <div class="form-group col-md-6" style="margin-bottom: 0;">
              <label style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; display: block;"><?php echo bkntc__('Email Address')?></label>
              <input type="email" id="input_email" class="form-control" style="border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 8px 12px; height: 40px; font-size: 13px;" placeholder="example@gmail.com" value="<?php echo htmlspecialchars($customer->getEmail() ?? ''); ?>">
            </div>
            <div class="form-group col-md-6" style="margin-bottom: 0;">
              <label style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; display: block;"><?php echo bkntc__('Phone Number')?></label>
              <input type="text" id="input_phone" class="form-control" style="border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 8px 12px; height: 40px; font-size: 13px;" placeholder="" value="<?php echo htmlspecialchars($customer->getPhoneNumber() ?? ''); ?>" data-country-code="<?php echo Helper::getOption('default_phone_country_code', ''); ?>">
            </div>
          </div>
        </div>

      </div>

      <!-- Right Column (Sidebar) -->
      <div style="display: flex; flex-direction: column; gap: 20px;">
        
        <!-- Account Access Card -->
        <div class="wf-sidebar-card" style="padding: 20px;">
          <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em; margin-bottom: 16px;"><?php echo bkntc__('Account Access')?></div>
          
          <label style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; cursor: pointer;">
            <span style="font-size: 13px; font-weight: 600; color: #475569;"><?php echo bkntc__('Allow to log in')?></span>
            <input type="checkbox" id="input_allow_customer_to_login" style="accent-color: #6366f1; width: 16px; height: 16px;" <?php echo !empty($customer->getUserId()) ? 'checked' : ''; ?>>
          </label>

          <div data-hide="allow_customer_to_login" style="display: none; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 12px;">
            
            <?php if (empty($customer->getUserId()) || $hasWpUser): ?>
              <div class="form-group" style="margin-bottom: 14px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 6px; display: block;"><?php echo bkntc__('WordPress User')?></label>
                <select id="input_wp_user_use_existing" class="form-control" style="width: 100%; border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 6px 12px; height: 40px; font-size: 13px;">
                  <option value="no" <?php echo empty($customer->getUserId()) ? 'selected' : ''; ?>><?php echo bkntc__('Create new user')?></option>
                  <option value="yes" <?php echo !empty($customer->getUserId()) ? 'selected' : ''; ?>><?php echo bkntc__('Use existing user')?></option>
                </select>
              </div>

              <div class="form-group" data-hide="existing_user" style="display: none; margin-bottom: 14px;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 6px; display: block;"><?php echo bkntc__('WordPress User')?></label>
                <select id="input_wp_user" class="form-control" style="width: 100%;">
                  <option value=""><?php echo bkntc__('Select user')?></option>
                  <?php foreach ($users as $user): ?>
                    <option value="<?php echo (int)$user->getId(); ?>" <?php echo $customer->getUserId() == $user->getId() ? 'selected' : ''; ?>><?php echo htmlspecialchars($user->getName()); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group" data-hide="create_password" style="display: none; margin-bottom: 0;">
                <label style="font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 6px; display: block;"><?php echo bkntc__('Password')?></label>
                <input type="password" id="input_wp_user_password" class="form-control" style="border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 8px 12px; height: 40px; font-size: 13px;" placeholder="••••••••">
              </div>
            <?php endif; ?>

          </div>
        </div>

        <!-- Workflows Card -->
        <div class="wf-sidebar-card" style="padding: 20px;">
          <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em; margin-bottom: 14px;"><?php echo bkntc__('Workflows')?></div>
          
          <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; margin-bottom: 0;">
            <input type="checkbox" id="input_run_workflows" checked style="margin-top: 2px; accent-color: #6366f1; width: 16px; height: 16px;">
            <div style="font-size: 12px; color: #475569; line-height: 1.4;">
              <strong style="color: #0f172a; display: block; margin-bottom: 2px; font-weight: 700;"><?php echo bkntc__('Run workflows on save')?></strong>
              <?php echo bkntc__('Triggers automation like email notifications.')?>
            </div>
          </label>
        </div>

        <!-- Private Note Card -->
        <div class="wf-sidebar-card" style="padding: 20px;">
          <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em; margin-bottom: 12px;"><?php echo bkntc__('Private Note')?></div>
          <textarea id="input_note" class="form-control" rows="5" style="border-radius: 8px; border: 1.5px solid #cbd5e1; padding: 10px 12px; font-size: 12px;" placeholder="<?php echo bkntc__('Add specific preferences or admin notes here...'); ?>"><?php echo htmlspecialchars($customer->getNotes() ?? ''); ?></textarea>
        </div>

  </div>
</div>
