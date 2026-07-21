<?php
defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

/**
 * @var array $parameters
 */
$info = $parameters['info'];
$mode = $parameters['mode'];
$extras = $parameters['extras'] ?? [];
$prices = $parameters['prices'] ?? [];
$customForms = $parameters['customForms'] ?? [];
$couponCode = $parameters['couponCode'] ?? '';
$couponAmount = $parameters['couponAmount'] ?? 0;
$couponDiscount = $parameters['couponDiscount'] ?? 0;
$couponDiscountType = $parameters['couponDiscountType'] ?? '';
$statusHistory = $parameters['statusHistory'] ?? [];

$statuses = Helper::getAppointmentStatuses();
$st = isset($statuses[$info['status']]) ? $statuses[$info['status']] : ['title' => $info['status'], 'color' => '#6366f1', 'icon' => 'fa fa-check'];

$colorsList = ['#ef4444', '#f97316', '#f59e0b', '#10b981', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899', '#14b8a6'];
$customerColor = $colorsList[(int)$info['customer_id'] % count($colorsList)];
$staffColor = $colorsList[(int)$info['staff_id'] % count($colorsList)];

$durationMin = round(($info['ends_at'] - $info['starts_at']) / 60);

$totalAmount = 0;
$servicePrice = 0;
foreach ($prices as $p) {
    $totalAmount += $p['price'] * $p['negative_or_positive'];
    if ($p['unique_key'] === 'service_price') {
        $servicePrice = (float)$p['price'];
    }
}
if ($servicePrice <= 0) {
    $servicePrice = (float)($info['service_price'] ?? 0);
}
if ($totalAmount <= 0 && $info['paid_amount'] > 0) {
    $totalAmount = $info['paid_amount'];
}

$customerFullName = trim(($info['customer_first_name'] ?? '') . ' ' . ($info['customer_last_name'] ?? ''));
if (empty($customerFullName)) {
    $customerFullName = ($info['customer_id'] > 0) ? 'Customer #' . $info['customer_id'] : '';
}

$initials = '';
$parts = explode(' ', $customerFullName);
foreach ($parts as $part) {
    if (!empty($part)) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
}
$initials = substr($initials, 0, 2);

$staffInitials = '';
$sparts = explode(' ', $info['staff_name']);
foreach ($sparts as $part) {
    if (!empty($part)) {
        $staffInitials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
}
$staffInitials = substr($staffInitials, 0, 2);
?>

<div class="wf-fullpage-container <?php echo ($mode === 'edit' || $mode === 'add') ? 'wf-mode-edit' : 'wf-mode-view'; ?>">

  <!-- TOP BAR -->
  <div class="wf-top-bar">
    <a href="#" class="wf-back-link wf-back-to-table">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 3L5 8l5 5"/></svg>
      <?php echo bkntc__('Appointments')?>
    </a>
    <span class="wf-sep">/</span>
    <?php if ($mode === 'add'): ?>
      <span class="wf-crumb active"><?php echo bkntc__('New Appointment')?></span>
    <?php else: ?>
      <span class="wf-crumb active"><?php echo bkntc__('Appointment')?> #<?php echo (int)$info['id']; ?></span>
    <?php endif; ?>
  </div>

  <!-- PAGE HEADER -->
  <div class="wf-page-header">
    <div>
      <?php if ($mode === 'add'): ?>
        <div class="wf-appt-id"><?php echo bkntc__('New Booking')?></div>
        <div class="wf-appt-title"><?php echo bkntc__('Create a New Appointment')?></div>
      <?php else: ?>
        <div class="wf-appt-id"><?php echo bkntc__('Appointment')?> #<?php echo (int)$info['id']; ?><?php echo $mode === 'edit' ? ' — Edit mode' : ''; ?></div>
        <div class="wf-appt-title"><?php echo htmlspecialchars($info['service_name'] . ' — ' . $customerFullName); ?></div>
      <?php endif; ?>
    </div>
    <div class="wf-header-actions">
      <?php if ($mode === 'view'): ?>
        <button type="button" class="btn btn-ghost btn-sm wf-btn-ghost">
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v10M2 8l6 6 6-6"/></svg>
          <?php echo bkntc__('Export PDF')?>
        </button>
        <button type="button" class="btn btn-ghost btn-sm wf-btn-ghost wf-switch-mode" data-id="<?php echo (int)$info['id']; ?>" data-mode="edit">
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 2l3 3-8 8H3v-3z"/></svg>
          <?php echo bkntc__('Edit')?>
        </button>
        <button type="button" class="btn btn-danger btn-sm wf-btn-danger wf-delete-appt" data-id="<?php echo (int)$info['id']; ?>">
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 4h10M6 4V2h4v2M5 4v8h6V4H5z"/></svg>
          <?php echo bkntc__('Delete')?>
        </button>
      <?php elseif ($mode === 'edit'): ?>
        <button type="button" class="btn btn-ghost btn-sm wf-btn-ghost wf-switch-mode" data-id="<?php echo (int)$info['id']; ?>" data-mode="view"><?php echo bkntc__('Cancel')?></button>
      <?php else: ?>
        <button type="button" class="btn btn-ghost btn-sm wf-btn-ghost wf-back-to-table"><?php echo bkntc__('Cancel')?></button>
      <?php endif; ?>
    </div>
  </div>

  <!-- QUICK STATUS BAR -->
  <div class="wf-status-bar" <?php echo $mode === 'add' ? 'style="display:none;"' : ''; ?>>
    <div class="wf-status-item">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="12" height="11" rx="1"/><path d="M5 1v4M11 1v4M2 7h12"/></svg>
      <span class="wf-lbl"><?php echo bkntc__('Date')?></span>
      <span class="wf-val"><?php echo Date::datee($info['starts_at']); ?></span>
    </div>
    <div class="wf-status-sep"></div>
    <div class="wf-status-item">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="8" r="7"/><path d="M8 3v5h3"/></svg>
      <span class="wf-lbl"><?php echo bkntc__('Time')?></span>
      <span class="wf-val"><?php echo Date::time($info['starts_at']) . ' – ' . Date::time($info['ends_at']); ?></span>
    </div>
    <div class="wf-status-sep"></div>
    <div class="wf-status-item">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="8" r="7"/><path d="M8 4v4l3 3"/></svg>
      <span class="wf-lbl"><?php echo bkntc__('Duration')?></span>
      <span class="wf-val"><?php echo (int)$durationMin . ' min'; ?></span>
    </div>
    <div class="wf-status-sep"></div>
    <div class="wf-status-item">
      <span class="wf-lbl"><?php echo bkntc__('Status')?></span>
      <span class="wf-chip" style="background: <?php echo htmlspecialchars($st['color']); ?>22; color: <?php echo htmlspecialchars($st['color']); ?>; font-weight: 700;">
        <i class="<?php echo htmlspecialchars($st['icon']); ?>" style="font-size: 9px; margin-right: 4px;"></i>
        <?php echo htmlspecialchars($st['title']); ?>
      </span>
    </div>
    <div class="wf-status-sep"></div>
    <div class="wf-status-item">
      <span class="wf-lbl"><?php echo bkntc__('Payment')?></span>
      <?php if ($info['paid_amount'] > 0): ?>
        <span class="wf-chip wf-chip-success"><?php echo bkntc__('Paid')?></span>
      <?php else: ?>
        <span class="wf-chip" style="background: #fee2e2; color: #ef4444; font-weight: 700;"><?php echo bkntc__('Unpaid')?></span>
      <?php endif; ?>
    </div>
  </div>

  <!-- MAIN LAYOUT -->
  <div class="wf-appt-layout <?php echo $mode === 'add' ? 'wf-no-sidebar' : ''; ?>">
    
    <!-- Left Column (Main Info) -->
    <div class="wf-main-content">
      
      <!-- Tabs -->
      <div class="wf-tab-bar">
        <button type="button" class="wf-tab-btn active" data-tab="wf_tab_details"><?php echo bkntc__('Appointment Details')?></button>
        <button type="button" class="wf-tab-btn" data-tab="wf_tab_extras"><?php echo bkntc__('Extras')?></button>
        <button type="button" class="wf-tab-btn" data-tab="wf_tab_coupons"><?php echo bkntc__('Coupons')?></button>
        <button type="button" class="wf-tab-btn" data-tab="wf_tab_custom"><?php echo bkntc__('Custom Fields')?></button>
      </div>

      <!-- Tab 1: Details -->
      <div class="wf-tab-panel active" id="wf_tab_details">
        
        <?php if ($mode === 'view'): ?>
          <!-- View Mode -->
          <div class="wf-form-section">
            <div class="wf-form-section-title">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 3h14M1 8h14M1 13h14"/></svg>
              <?php echo bkntc__('Booking Info')?>
            </div>
            <div class="wf-info-grid">
              <div class="wf-info-cell">
                <div class="wf-lbl"><?php echo bkntc__('Location')?></div>
                <div class="wf-val"><?php echo htmlspecialchars($info['location_name']); ?></div>
              </div>
              <div class="wf-info-cell">
                <div class="wf-lbl"><?php echo bkntc__('Service')?></div>
                <div class="wf-val"><?php echo htmlspecialchars($info['service_name']); ?></div>
              </div>
              <div class="wf-info-cell">
                <div class="wf-lbl"><?php echo bkntc__('Date & Time')?></div>
                <div class="wf-val"><?php echo Date::datee($info['starts_at']) . ' · ' . Date::time($info['starts_at']); ?></div>
              </div>
            </div>
          </div>

          <div class="wf-form-section">
            <div class="wf-form-section-title">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 14c0-2.209-1.791-4-4-4s-4 1.791-4 4M8 7a3 3 0 100-6 3 3 0 000 6z"/></svg>
              <?php echo bkntc__('Participants')?>
            </div>
            <div class="wf-people-row">
              <div>
                <div class="wf-info-cell" style="margin-bottom: 8px;">
                  <div class="wf-lbl"><?php echo bkntc__('Staff')?></div>
                </div>
                <div class="wf-profile-card">
                  <div class="wf-profile-av" style="background: <?php echo $staffColor; ?>; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                    <?php if (!empty($info['staff_profile_image'])): ?>
                      <img src="<?php echo htmlspecialchars(Helper::profileImage($info['staff_profile_image'], 'Staff')); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                      <?php echo htmlspecialchars($staffInitials ?: 'ST'); ?>
                    <?php endif; ?>
                  </div>
                  <div>
                    <div class="wf-profile-name"><?php echo htmlspecialchars($info['staff_name']); ?></div>
                    <div class="wf-profile-email"><?php echo htmlspecialchars($info['staff_email']); ?></div>
                  </div>
                </div>
              </div>
              <div>
                <div class="wf-info-cell" style="margin-bottom: 8px;">
                  <div class="wf-lbl"><?php echo bkntc__('Customer')?></div>
                </div>
                <div class="wf-profile-card">
                  <div class="wf-profile-av" style="background: <?php echo $customerColor; ?>; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                    <?php if (!empty($info['customer_profile_image'])): ?>
                      <img src="<?php echo htmlspecialchars(Helper::profileImage($info['customer_profile_image'], 'Customers')); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                      <?php echo htmlspecialchars($initials ?: 'CU'); ?>
                    <?php endif; ?>
                  </div>
                  <div>
                    <div class="wf-profile-name"><?php echo htmlspecialchars($customerFullName); ?></div>
                    <div class="wf-profile-email"><?php echo htmlspecialchars($info['customer_email']); ?></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="wf-form-section">
            <div class="wf-form-section-title">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 13h12V3H2v10zM5 7h6M5 10h4"/></svg>
              <?php echo bkntc__('Note')?>
            </div>
            <div class="wf-note-box">
              <?php echo empty($info['note']) ? bkntc__('No internal note left for this appointment.') : htmlspecialchars($info['note']); ?>
            </div>
          </div>

        <?php else: ?>
          <!-- Edit Mode Form -->
          <div class="wf-form-section">
            <div class="wf-form-section-title">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 3h14M1 8h14M1 13h14"/></svg>
              <?php echo bkntc__('Edit Booking Info')?>
            </div>
            
            <div class="wf-form-row">
              <div class="wf-form-group col-6">
                <label><?php echo bkntc__('Location')?></label>
                <select class="form-control wf-form-control" id="input_location">
                  <?php if ($info['location_id'] > 0): ?>
                    <option value="<?php echo (int)$info['location_id']; ?>" selected><?php echo htmlspecialchars($info['location_name']); ?></option>
                  <?php else: ?>
                    <option value="" selected><?php echo bkntc__('- Select location -'); ?></option>
                  <?php endif; ?>
                </select>
              </div>
              <div class="wf-form-group col-6">
                <label><?php echo bkntc__('Category')?></label>
                <select class="form-control wf-form-control input_category">
                  <?php if (isset($serviceCategoryId) && $serviceCategoryId > 0 && !empty($categoryName)): ?>
                    <option value="<?php echo $serviceCategoryId; ?>" selected><?php echo htmlspecialchars($categoryName); ?></option>
                  <?php else: ?>
                    <option value="0" selected><?php echo bkntc__('Default Category'); ?></option>
                  <?php endif; ?>
                </select>
              </div>
            </div>

            <div class="wf-form-row">
              <div class="wf-form-group col-6">
                <label><?php echo bkntc__('Service')?></label>
                <select class="form-control wf-form-control" id="input_service">
                  <?php if ($info['service_id'] > 0): ?>
                    <option value="<?php echo (int)$info['service_id']; ?>" selected><?php echo htmlspecialchars($info['service_name']); ?></option>
                  <?php else: ?>
                    <option value="" selected><?php echo bkntc__('- Select service -'); ?></option>
                  <?php endif; ?>
                </select>
              </div>
              <div class="wf-form-group col-6">
                <label><?php echo bkntc__('Staff')?></label>
                <select class="form-control wf-form-control" id="input_staff">
                  <?php if ($info['staff_id'] > 0): ?>
                    <option value="<?php echo (int)$info['staff_id']; ?>" selected><?php echo htmlspecialchars($info['staff_name']); ?></option>
                  <?php else: ?>
                    <option value="" selected><?php echo bkntc__('- Select staff -'); ?></option>
                  <?php endif; ?>
                </select>
              </div>
            </div>

            <div class="wf-form-row">
              <div class="wf-form-group col-6">
                <label><?php echo bkntc__('Date')?></label>
                <input type="text" class="form-control wf-form-control" id="input_date" value="<?php echo $mode === 'add' ? '' : Date::datee($info['starts_at']); ?>" placeholder="<?php echo bkntc__('Select date...'); ?>">
              </div>
              <div class="wf-form-group col-6">
                <label><?php echo bkntc__('Time')?></label>
                <select class="form-control wf-form-control" id="input_time">
                  <?php if ($mode !== 'add'): ?>
                    <option selected value="<?php echo Date::time($info['starts_at']); ?>"><?php echo Date::time($info['starts_at']); ?></option>
                  <?php endif; ?>
                </select>
              </div>
            </div>

            <div class="wf-form-row">
              <div class="wf-form-group col-12">
                <label><?php echo bkntc__('Customer')?></label>
                <select class="form-control wf-form-control input_customer">
                  <?php if ($info['customer_id'] > 0): ?>
                    <option value="<?php echo (int)$info['customer_id']; ?>" selected><?php echo htmlspecialchars($customerFullName); ?></option>
                  <?php endif; ?>
                </select>
              </div>
            </div>

            <div class="wf-form-row">
              <div class="wf-form-group col-12">
                <label style="margin-bottom: 10px;"><?php echo bkntc__('Status')?></label>
                <div class="wf-status-selector">
                  <?php foreach ($statuses as $stKey => $stItem): ?>
                    <div class="wf-status-option <?php echo $info['status'] === $stKey ? 'active-status' : ''; ?>" style="--st-color: <?php echo htmlspecialchars($stItem['color']); ?>;" data-status="<?php echo $stKey; ?>">
                      <span class="wf-status-dot" style="background: <?php echo htmlspecialchars($stItem['color']); ?>;"></span>
                      <?php echo htmlspecialchars($stItem['title']); ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <div class="wf-form-row">
              <div class="wf-form-group col-12">
                <label><?php echo bkntc__('Note')?></label>
                <textarea class="form-control wf-form-control" id="input_note" rows="3"><?php echo htmlspecialchars($info['note']); ?></textarea>
              </div>
            </div>

          </div>
        <?php endif; ?>

      </div>

      <!-- Tab 2: Extras -->
      <div class="wf-tab-panel" id="wf_tab_extras" style="display: none;">
        <div class="wf-form-section">
          <div class="wf-form-section-title">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3 3-8 8H3v-3z"/></svg>
            <?php echo bkntc__('Appointment Extras')?>
          </div>
          <?php if ($mode === 'view'): ?>
            <?php if (empty($extras)): ?>
              <div style="font-size: 13px; color: var(--wf-text-2); text-align: center; padding: 20px 0;">
                <?php echo bkntc__('No extra services chosen for this appointment.')?>
              </div>
            <?php else: ?>
              <table class="wf-extras-table">
                <thead>
                  <tr>
                    <th><?php echo bkntc__('Extra Name')?></th>
                    <th style="text-align: right;"><?php echo bkntc__('Price')?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($extras as $extra): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($extra['service_extras_name'] ?? $extra['name'] ?? ''); ?></td>
                      <td style="text-align: right; font-weight: 600;"><?php echo Helper::price($extra['price'] * $extra['quantity']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          <?php else: ?>
            <div id="tab_extras"></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Tab 3: Coupons -->
      <div class="wf-tab-panel" id="wf_tab_coupons" style="display: none;">
        <div class="wf-form-section">
          <?php if ($mode === 'view'): ?>
            <?php if (!empty($couponCode)): ?>
              <div style="font-size: 11px; font-weight: 700; color: var(--wf-text-3); text-transform: uppercase; margin-bottom: 16px; display: flex; align-items: center; gap: 6px;">
                <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" style="transform: rotate(45deg);"><rect x="4" y="4" width="8" height="8" /></svg>
                <?php echo bkntc__('APPLIED COUPON')?>
              </div>
              <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <div style="width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #16a34a;">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 8.5l3.5 3.5L13 4.5"/></svg>
                  </div>
                  <div>
                    <div style="font-weight: 800; font-size: 14px; color: #16a34a; margin-bottom: 2px; font-family: monospace; letter-spacing: 0.05em;"><?php echo htmlspecialchars($couponCode); ?></div>
                    <div style="font-size: 12px; color: #15803d; font-weight: 500;">
                      <?php 
                        if ($couponDiscountType === 'percent') {
                            echo sprintf(bkntc__('%d%% discount applied — saved %s'), $couponDiscount, Helper::price($couponAmount));
                        } else {
                            echo sprintf(bkntc__('Discount applied — saved %s'), Helper::price($couponAmount));
                        }
                      ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php else: ?>
              <div class="wf-form-section-title">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h12v10H2V3zm0 4h12"/></svg>
                <?php echo bkntc__('Coupons & Discounts')?>
              </div>
              <div style="font-size: 13px; color: var(--wf-text-2); text-align: center; padding: 20px 0;">
                <?php echo bkntc__('No coupon codes were applied to this appointment.')?>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="wf-form-section-title">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h12v10H2V3zm0 4h12"/></svg>
              <?php echo bkntc__('Coupons & Discounts')?>
            </div>
            <?php if (!empty($couponCode)): ?>
              <div style="font-size: 11px; font-weight: 700; color: var(--wf-text-3); text-transform: uppercase; margin-bottom: 16px; display: flex; align-items: center; gap: 6px;">
                <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" style="transform: rotate(45deg);"><rect x="4" y="4" width="8" height="8" /></svg>
                <?php echo bkntc__('APPLIED COUPON')?>
              </div>
              <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <div style="width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #16a34a;">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 8.5l3.5 3.5L13 4.5"/></svg>
                  </div>
                  <div>
                    <div style="font-weight: 800; font-size: 14px; color: #16a34a; margin-bottom: 2px; font-family: monospace; letter-spacing: 0.05em;"><?php echo htmlspecialchars($couponCode); ?></div>
                    <div style="font-size: 12px; color: #15803d; font-weight: 500;">
                      <?php 
                        if ($couponDiscountType === 'percent') {
                            echo sprintf(bkntc__('%d%% discount applied — saved %s'), $couponDiscount, Helper::price($couponAmount));
                        } else {
                            echo sprintf(bkntc__('Discount applied — saved %s'), Helper::price($couponAmount));
                        }
                      ?>
                    </div>
                  </div>
                </div>
                <button type="button" class="btn btn-sm wf-remove-coupon-btn" style="border: 1px solid #bbf7d0; background: #ffffff; color: #16a34a; font-weight: 600; font-size: 11px; padding: 5px 12px; border-radius: 6px; cursor: pointer; transition: all 0.2s;">
                  <?php echo bkntc__('Remove'); ?>
                </button>
              </div>
            <?php endif; ?>
            <div id="coupons-edit-tab"></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Tab 4: Custom Fields -->
      <div class="wf-tab-panel" id="wf_tab_custom" style="display: none;">
        <div class="wf-form-section">
          <div class="wf-form-section-title">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h10v10H3V3z"/></svg>
            <?php echo bkntc__('Custom Fields Data')?>
          </div>
          <?php if ($mode === 'view'): ?>
            <?php if (empty($customForms)): ?>
              <div style="font-size: 13px; color: var(--wf-text-2); text-align: center; padding: 20px 0;">
                <?php echo bkntc__('No custom fields data available for this appointment.')?>
              </div>
            <?php else: ?>
              <div class="wf-cf-list">
                <?php foreach ($customForms as $formId => $form): ?>
                  <div style="font-weight: 700; font-size: 12px; color: var(--wf-text-3); text-transform: uppercase; margin-bottom: 12px; border-bottom: 2px solid var(--wf-border); padding-bottom: 4px;"><?php echo htmlspecialchars($form['name']); ?></div>
                  <?php foreach ($form['inputs'] as $field): ?>
                    <div class="wf-cf-item" style="border-bottom: 1px solid var(--wf-surface-2); padding: 10px 0; display: flex; justify-content: space-between; align-items: center;">
                      <div class="wf-cf-label" style="font-size: 12px; font-weight: 600; color: var(--wf-text-2);"><?php echo htmlspecialchars($field['form_input_label']); ?></div>
                      <div class="wf-cf-value" style="font-size: 13px; font-weight: 600; color: var(--wf-text);">
                        <?php
                        if ($field['form_input_type'] == 'file' || $field['form_input_type'] == 'file_multiple') {
                            $files = json_decode($field['input_value'], true);
                            if (is_array($files)) {
                                $links = [];
                                foreach ($files as $file) {
                                    $links[] = '<div><a href="' . Helper::uploadedFileURL(htmlspecialchars($file['path']), 'Customforms') . '" target="_blank" style="color: var(--wf-primary); font-weight:700;">' . htmlspecialchars($file['name']) . '</a></div>';
                                }
                                echo implode('', $links);
                            } else {
                                echo '<a href="' . Helper::uploadedFileURL(htmlspecialchars($field['input_value']), 'Customforms') . '" target="_blank" style="color: var(--wf-primary); font-weight:700;">' . htmlspecialchars($field['input_file_name']) . '</a>';
                            }
                        } else if (in_array($field['form_input_type'], ['select', 'checkbox', 'radio'])) {
                            try {
                                $realValues = \BookneticAddon\Customforms\Model\FormInputChoice::whereFindInSet('id', explode(',', $field['input_value']))->select('group_concat(title separator \', \') as titles', true)->fetch();
                                echo htmlspecialchars($realValues['titles']);
                            } catch (Exception $e) {
                                echo htmlspecialchars($field['input_value']);
                            }
                        } else if ($field['form_input_type'] == 'date') {
                            echo Date::datee(Date::reformatDateFromCustomFormat(htmlspecialchars($field['input_value'])));
                        } else if ($field['form_input_type'] == 'time') {
                            echo Date::time(htmlspecialchars($field['input_value']));
                        } else {
                            echo htmlspecialchars($field['input_value']);
                        }
                        ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div id="tab_custom_fields_edit">
              <div id="custom_fields"></div>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- Right Column (Sidebar) -->
    <div class="wf-sidebar">
      
      <?php if ($mode !== 'add'): ?>
        <!-- Payment Summary -->
        <div class="wf-sidebar-card">
          <div class="wf-sidebar-title"><?php echo bkntc__('PAYMENT SUMMARY')?></div>
          <div class="wf-payment-row">
            <span><?php echo htmlspecialchars($info['service_name']); ?></span>
            <span style="font-weight: 600;"><?php echo Helper::price($servicePrice); ?></span>
          </div>
          <?php foreach ($extras as $extra): ?>
            <div class="wf-payment-row">
              <span><?php echo htmlspecialchars($extra['service_extras_name'] ?? $extra['name'] ?? ''); ?></span>
              <span style="font-weight: 600;"><?php echo Helper::price($extra['price'] * $extra['quantity']); ?></span>
            </div>
          <?php endforeach; ?>
          <?php if (!empty($couponCode)): ?>
            <div class="wf-payment-row" style="color: var(--wf-success);">
              <span>
                <?php 
                  $discLabel = bkntc__('Coupon') . ' ' . $couponCode;
                  if ($couponDiscountType === 'percent') {
                      $discLabel .= ' (' . (int)$couponDiscount . '%)';
                  }
                  echo htmlspecialchars($discLabel); 
                ?>
              </span>
              <span style="font-weight: 600;">-<?php echo Helper::price($couponAmount); ?></span>
            </div>
          <?php endif; ?>
          <div class="wf-payment-row wf-total">
            <span class="wf-item"><?php echo bkntc__('Total')?></span>
            <span class="wf-amount" style="color: #6366f1;"><?php echo Helper::price($totalAmount); ?></span>
          </div>
          <div class="wf-payment-badge">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="14" height="10" rx="2"/><path d="M1 7h14"/></svg>
            <?php echo $info['paid_amount'] > 0 ? bkntc__('Paid') : bkntc__('Unpaid'); ?>
          </div>
        </div>

        <!-- Customer Card -->
        <div class="wf-sidebar-card">
          <div class="wf-sidebar-title"><?php echo bkntc__('CUSTOMER')?></div>
          <div class="wf-sidebar-profile">
            <div class="wf-profile-av" style="background: <?php echo $customerColor; ?>; overflow: hidden; display: flex; align-items: center; justify-content: center;">
              <?php if (!empty($info['customer_profile_image'])): ?>
                <img src="<?php echo htmlspecialchars(Helper::profileImage($info['customer_profile_image'], 'Customers')); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
              <?php else: ?>
                <?php echo htmlspecialchars($initials ?: 'CU'); ?>
              <?php endif; ?>
            </div>
            <div>
              <div style="font-weight: 700; font-size: 13px; color: #0f172a;"><?php echo htmlspecialchars($customerFullName); ?></div>
              <div style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($info['customer_email']); ?></div>
            </div>
          </div>
          <div class="wf-customer-meta">
            <div class="wf-meta-line">
              <span class="wf-lbl"><?php echo bkntc__('Phone')?></span>
              <span class="wf-val"><?php echo htmlspecialchars($info['customer_phone_number'] ?: '-'); ?></span>
            </div>
            <div class="wf-meta-line">
              <span class="wf-lbl"><?php echo bkntc__('Total visits')?></span>
              <span class="wf-val"><?php echo (int)$info['weight']; ?></span>
            </div>
            <div class="wf-meta-line">
              <span class="wf-lbl"><?php echo bkntc__('Category')?></span>
              <span class="wf-chip wf-chip-success" style="font-size: 10px; padding: 2px 8px;">VIP</span>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($mode !== 'add'): ?>
        <!-- Activity Log Card -->
        <div class="wf-sidebar-card">
          <div class="wf-sidebar-title"><?php echo bkntc__('ACTIVITY LOG')?></div>
          <div class="wf-timeline">
            <?php foreach (array_reverse($statusHistory) as $log): ?>
              <?php 
                $logSt = isset($statuses[$log['status']]) ? $statuses[$log['status']] : ['title' => $log['status'], 'color' => '#6366f1', 'icon' => 'fa fa-check'];
              ?>
              <div class="wf-tl-item">
                <div class="wf-tl-dot" style="background: <?php echo htmlspecialchars($logSt['color']); ?>;"></div>
                <div class="wf-tl-content">
                  <div class="wf-tl-action"><?php echo bkntc__('Status')?> → <?php echo htmlspecialchars($logSt['title']); ?></div>
                  <div class="wf-tl-time"><?php echo Date::dateTime($log['time']); ?></div>
                </div>
              </div>
            <?php endforeach; ?>
            <div class="wf-tl-item">
              <div class="wf-tl-dot" style="background: #6366f1;"></div>
              <div class="wf-tl-content">
                <div class="wf-tl-action"><?php echo bkntc__('Appointment created')?></div>
                <div class="wf-tl-time"><?php echo Date::dateTime($info['created_at']); ?></div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <?php if ($mode === 'edit' || $mode === 'add'): ?>
    <!-- STICKY SAVE FOOTER -->
    <div class="wf-form-footer">
      <div style="margin-right: auto; display: flex; align-items: center; gap: 8px;">
        <input type="checkbox" id="input_run_workflows" checked style="width: 16px; height: 16px; accent-color: #6366f1; cursor: pointer;">
        <label for="input_run_workflows" style="font-size: 13px; font-weight: 500; color: #475569; cursor: pointer; user-select: none; margin-bottom: 0;"><?php echo bkntc__('Run workflows on save')?></label>
      </div>
      <?php if ($mode === 'add'): ?>
        <button type="button" class="btn btn-ghost wf-btn-ghost wf-back-to-table"><?php echo bkntc__('Cancel')?></button>
        <button type="button" class="btn btn-success wf-btn-success wf-btn-save-changes" data-id="0">
          <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8l4 4 6-6"/></svg>
          <?php echo bkntc__('Save Appointment')?>
        </button>
      <?php else: ?>
        <button type="button" class="btn btn-ghost wf-btn-ghost wf-switch-mode" data-id="<?php echo (int)$info['id']; ?>" data-mode="view"><?php echo bkntc__('Cancel')?></button>
        <button type="button" class="btn btn-success wf-btn-success wf-btn-save-changes" data-id="<?php echo (int)$info['id']; ?>">
          <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8l4 4 6-6"/></svg>
          <?php echo bkntc__('Save Changes')?>
        </button>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>
