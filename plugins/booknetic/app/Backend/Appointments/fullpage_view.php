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

$statuses = Helper::getAppointmentStatuses();
$st = isset($statuses[$info->status]) ? $statuses[$info->status] : ['title' => $info->status, 'color' => '#6366f1', 'icon' => 'fa fa-check'];

$durationMin = round(($info->ends_at - $info->starts_at) / 60);

$totalAmount = 0;
foreach ($prices as $p) {
    $totalAmount += $p['price'] * $p['negative_or_positive'];
}
if ($totalAmount <= 0 && $info->paid_amount > 0) {
    $totalAmount = $info->paid_amount;
}

$customerFullName = trim($info->customer_first_name . ' ' . $info->customer_last_name);
if (empty($customerFullName)) {
    $customerFullName = 'Customer #' . $info->customer_id;
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
$sparts = explode(' ', $info->staff_name);
foreach ($sparts as $part) {
    if (!empty($part)) {
        $staffInitials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
}
$staffInitials = substr($staffInitials, 0, 2);
?>

<div class="wf-fullpage-container <?php echo $mode === 'edit' ? 'wf-mode-edit' : 'wf-mode-view'; ?>">

  <!-- TOP BAR -->
  <div class="wf-top-bar">
    <a href="#" class="wf-back-link wf-back-to-table">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 3L5 8l5 5"/></svg>
      <?php echo bkntc__('Appointments')?>
    </a>
    <span class="wf-sep">/</span>
    <span class="wf-crumb active"><?php echo bkntc__('Appointment')?> #<?php echo (int)$info->id; ?></span>
    
    <div class="wf-mode-toggle ml-auto">
      <button type="button" class="wf-mode-btn wf-switch-mode <?php echo $mode === 'view' ? 'active' : ''; ?>" data-id="<?php echo (int)$info->id; ?>" data-mode="view">👁 View</button>
      <button type="button" class="wf-mode-btn wf-switch-mode <?php echo $mode === 'edit' ? 'active' : ''; ?>" data-id="<?php echo (int)$info->id; ?>" data-mode="edit">✏️ Edit</button>
    </div>
  </div>

  <!-- PAGE HEADER -->
  <div class="wf-page-header">
    <div>
      <div class="wf-appt-id"><?php echo bkntc__('Appointment')?> #<?php echo (int)$info->id; ?><?php echo $mode === 'edit' ? ' — Edit mode' : ''; ?></div>
      <div class="wf-appt-title"><?php echo htmlspecialchars($info->service_name . ' — ' . $customerFullName); ?></div>
    </div>
    <div class="wf-header-actions">
      <?php if ($mode === 'view'): ?>
        <button type="button" class="btn btn-ghost btn-sm wf-btn-ghost">
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v10M2 8l6 6 6-6"/></svg>
          <?php echo bkntc__('Export PDF')?>
        </button>
        <button type="button" class="btn btn-ghost btn-sm wf-btn-ghost wf-switch-mode" data-id="<?php echo (int)$info->id; ?>" data-mode="edit">
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 2l3 3-8 8H3v-3z"/></svg>
          <?php echo bkntc__('Edit')?>
        </button>
        <button type="button" class="btn btn-danger btn-sm wf-btn-danger wf-delete-appt" data-id="<?php echo (int)$info->id; ?>">
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 4h10M6 4V2h4v2M5 4v8h6V4H5z"/></svg>
          <?php echo bkntc__('Delete')?>
        </button>
      <?php else: ?>
        <button type="button" class="btn btn-ghost btn-sm wf-btn-ghost wf-switch-mode" data-id="<?php echo (int)$info->id; ?>" data-mode="view"><?php echo bkntc__('Cancel')?></button>
      <?php endif; ?>
    </div>
  </div>

  <!-- QUICK STATUS BAR -->
  <div class="wf-status-bar">
    <div class="wf-status-item">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="12" height="11" rx="1"/><path d="M5 1v4M11 1v4M2 7h12"/></svg>
      <span class="wf-lbl"><?php echo bkntc__('Date')?></span>
      <span class="wf-val"><?php echo Date::datee($info->starts_at); ?></span>
    </div>
    <div class="wf-status-sep"></div>
    <div class="wf-status-item">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="8" r="6"/><path d="M8 4v4l3 2"/></svg>
      <span class="wf-lbl"><?php echo bkntc__('Time')?></span>
      <span class="wf-val"><?php echo Date::time($info->starts_at) . ' – ' . Date::time($info->ends_at); ?></span>
    </div>
    <div class="wf-status-sep"></div>
    <div class="wf-status-item">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="8" r="6"/><path d="M8 4v4l3 2"/></svg>
      <span class="wf-lbl"><?php echo bkntc__('Duration')?></span>
      <span class="wf-val"><?php echo $durationMin; ?> min</span>
    </div>
    <div class="wf-status-sep"></div>
    <div class="wf-status-item">
      <span class="wf-chip" style="background: <?php echo htmlspecialchars($st['color']); ?>22; color: <?php echo htmlspecialchars($st['color']); ?>; font-weight: 700;">
        ✓ <?php echo htmlspecialchars($st['title']); ?>
      </span>
    </div>
    <div class="wf-status-sep"></div>
    <div class="wf-status-item">
      <?php if ($info->paid_amount > 0): ?>
        <span class="wf-chip wf-chip-success">💳 <?php echo bkntc__('Paid')?></span>
      <?php else: ?>
        <span class="wf-chip wf-chip-gray"><?php echo bkntc__('Unpaid')?></span>
      <?php endif; ?>
    </div>
  </div>

  <!-- MAIN 2-COLUMN LAYOUT -->
  <div class="wf-appt-layout">
    <!-- Left Column: Tabs & Main Content -->
    <div class="wf-main-content">
      
      <!-- TABS BAR -->
      <div class="wf-tab-bar">
        <button type="button" class="wf-tab-btn active" data-tab="wf_tab_details"><?php echo bkntc__('Appointment Details')?></button>
        <button type="button" class="wf-tab-btn" data-tab="wf_tab_extras"><?php echo bkntc__('Extras')?></button>
        <button type="button" class="wf-tab-btn" data-tab="wf_tab_coupons"><?php echo bkntc__('Coupons')?></button>
        <button type="button" class="wf-tab-btn" data-tab="wf_tab_custom"><?php echo bkntc__('Custom Fields')?></button>
      </div>

      <!-- TAB 1: APPOINTMENT DETAILS -->
      <div id="wf_tab_details" class="wf-tab-panel active" style="display: block;">
        <?php if ($mode === 'view'): ?>
          <!-- Booking Info Section -->
          <div class="wf-form-section">
            <div class="wf-form-section-title">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="12" height="11" rx="1"/><path d="M5 1v4M11 1v4M2 7h12"/></svg>
              <?php echo bkntc__('Booking Info')?>
            </div>
            <div class="wf-info-grid">
              <div class="wf-info-cell">
                <div class="wf-lbl"><?php echo bkntc__('Location')?></div>
                <div class="wf-val"><?php echo htmlspecialchars($info->location_name); ?></div>
              </div>
              <div class="wf-info-cell">
                <div class="wf-lbl"><?php echo bkntc__('Service')?></div>
                <div class="wf-val"><?php echo htmlspecialchars($info->service_name); ?></div>
              </div>
              <div class="wf-info-cell">
                <div class="wf-lbl"><?php echo bkntc__('Date & Time')?></div>
                <div class="wf-val"><?php echo Date::datee($info->starts_at) . ' · ' . Date::time($info->starts_at); ?></div>
              </div>
              <div class="wf-info-cell">
                <div class="wf-lbl"><?php echo bkntc__('Duration')?></div>
                <div class="wf-val"><?php echo $durationMin; ?> minutes</div>
              </div>
              <div class="wf-info-cell">
                <div class="wf-lbl"><?php echo bkntc__('Buffer After')?></div>
                <div class="wf-val">10 minutes</div>
              </div>
              <div class="wf-info-cell">
                <div class="wf-lbl"><?php echo bkntc__('Service Type')?></div>
                <div class="wf-val"><?php echo bkntc__('Non-repeatable')?></div>
              </div>
            </div>
          </div>

          <!-- People Section -->
          <div class="wf-form-section">
            <div class="wf-form-section-title">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.3 2.7-6 6-6s6 2.7 6 6"/></svg>
              <?php echo bkntc__('People')?>
            </div>
            <div class="wf-people-row">
              <div class="wf-person-card">
                <div class="wf-lbl mb-2"><?php echo bkntc__('Staff Member')?></div>
                <div class="wf-profile-card">
                  <div class="wf-profile-av" style="background: #6366f1;"><?php echo htmlspecialchars($staffInitials ?: 'ST'); ?></div>
                  <div>
                    <div class="wf-profile-name"><?php echo htmlspecialchars($info->staff_name); ?></div>
                    <div class="wf-profile-email"><?php echo htmlspecialchars($info->staff_email); ?></div>
                  </div>
                </div>
              </div>

              <div class="wf-person-card">
                <div class="wf-lbl mb-2"><?php echo bkntc__('Customer')?></div>
                <div class="wf-profile-card">
                  <div class="wf-profile-av" style="background: #22c55e;"><?php echo htmlspecialchars($initials ?: 'CU'); ?></div>
                  <div>
                    <div class="wf-profile-name"><?php echo htmlspecialchars($customerFullName); ?></div>
                    <div class="wf-profile-email"><?php echo htmlspecialchars($info->customer_email); ?></div>
                  </div>
                  <div class="ml-auto">
                    <span class="wf-chip" style="background: <?php echo htmlspecialchars($st['color']); ?>22; color: <?php echo htmlspecialchars($st['color']); ?>; font-weight: 700;">
                      <?php echo htmlspecialchars($st['title']); ?>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Note Section -->
          <div class="wf-form-section">
            <div class="wf-form-section-title">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 2h12v9H2z"/><path d="M5 13l3-2 3 2"/></svg>
              <?php echo bkntc__('Note')?>
            </div>
            <div class="wf-note-box">
              <?php echo empty($info->note) ? bkntc__('No internal note left for this appointment.') : htmlspecialchars($info->note); ?>
            </div>
          </div>

          <!-- Create Payment Link Section -->
          <div class="wf-form-section">
            <div class="wf-form-section-title">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2 2-8 8H4v-2z"/></svg>
              <?php echo bkntc__('Create Payment Link')?>
            </div>
            <div class="d-flex align-items-center" style="gap: 12px;">
              <select class="form-control wf-form-control select-arrow" style="max-width: 240px;">
                <option value="stripe">Stripe</option>
                <option value="paypal">PayPal</option>
                <option value="square">Square</option>
              </select>
              <button type="button" class="btn btn-primary btn-sm wf-btn-primary"><?php echo bkntc__('Create Link')?></button>
            </div>
          </div>

        <?php else: ?>
          <!-- EDIT MODE FORM -->
          <div class="wf-form-section">
            <div class="wf-form-section-title">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="12" height="11" rx="1"/><path d="M5 1v4M11 1v4M2 7h12"/></svg>
              <?php echo bkntc__('Booking Details')?>
            </div>

            <!-- Location -->
            <div class="wf-form-row">
              <div class="wf-form-group col-12">
                <label><?php echo bkntc__('Location')?> <span class="req">*</span></label>
                <select class="form-control wf-form-control select-arrow">
                  <option selected><?php echo htmlspecialchars($info->location_name); ?></option>
                </select>
              </div>
            </div>

            <!-- Service & Staff -->
            <div class="wf-form-row">
              <div class="wf-form-group col-6">
                <label><?php echo bkntc__('Service')?> <span class="req">*</span></label>
                <select class="form-control wf-form-control select-arrow">
                  <option selected><?php echo htmlspecialchars($info->service_name); ?></option>
                </select>
              </div>
              <div class="wf-form-group col-6">
                <label><?php echo bkntc__('Staff')?> <span class="req">*</span></label>
                <select class="form-control wf-form-control select-arrow">
                  <option selected><?php echo htmlspecialchars($info->staff_name); ?></option>
                </select>
              </div>
            </div>

            <!-- Date & Time -->
            <div class="wf-form-row">
              <div class="wf-form-group col-6">
                <label><?php echo bkntc__('Date')?> <span class="req">*</span></label>
                <input type="text" class="form-control wf-form-control" value="<?php echo Date::datee($info->starts_at); ?>">
              </div>
              <div class="wf-form-group col-6">
                <label><?php echo bkntc__('Time')?> <span class="req">*</span></label>
                <input type="text" class="form-control wf-form-control" value="<?php echo Date::time($info->starts_at); ?>">
              </div>
            </div>

            <!-- Customer -->
            <div class="wf-form-row">
              <div class="wf-form-group col-12">
                <label><?php echo bkntc__('Customer')?> <span class="req">*</span></label>
                <input type="text" class="form-control wf-form-control" value="<?php echo htmlspecialchars($customerFullName . ' (' . $info->customer_email . ')'); ?>">
              </div>
            </div>

            <!-- Status Selector -->
            <div class="wf-form-row">
              <div class="wf-form-group col-12">
                <label><?php echo bkntc__('Customer Status')?></label>
                <div class="wf-status-selector">
                  <?php foreach ($statuses as $stKey => $stItem): ?>
                    <div class="wf-status-option <?php echo $info->status === $stKey ? 'active-status' : ''; ?>" style="--st-color: <?php echo htmlspecialchars($stItem['color']); ?>;">
                      <div class="wf-status-dot" style="background: <?php echo htmlspecialchars($stItem['color']); ?>;"></div>
                      <?php echo htmlspecialchars($stItem['title']); ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- Note -->
            <div class="wf-form-row">
              <div class="wf-form-group col-12">
                <label><?php echo bkntc__('Note')?></label>
                <textarea class="form-control wf-form-control" rows="3"><?php echo htmlspecialchars($info->note); ?></textarea>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- TAB 2: EXTRAS -->
      <div id="wf_tab_extras" class="wf-tab-panel" style="display: none;">
        <div class="wf-form-section">
          <div class="wf-form-section-title">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v10M3 8h10"/></svg>
            <?php echo bkntc__('Service Extras')?>
          </div>
          <?php if (empty($extras)): ?>
            <div class="text-muted p-4 text-center"><?php echo bkntc__('No service extras selected.')?></div>
          <?php else: ?>
            <table class="wf-extras-table">
              <thead>
                <tr>
                  <th><?php echo bkntc__('Name')?></th>
                  <th><?php echo bkntc__('Duration')?></th>
                  <th><?php echo bkntc__('Price')?></th>
                  <th><?php echo bkntc__('Qty')?></th>
                  <th><?php echo bkntc__('Subtotal')?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($extras as $extra): ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($extra['name']); ?></strong></td>
                    <td class="text-muted"><?php echo (int)$extra['duration']; ?> min</td>
                    <td><?php echo Helper::price($extra['price']); ?></td>
                    <td><?php echo (int)$extra['quantity']; ?></td>
                    <td><strong><?php echo Helper::price($extra['price'] * $extra['quantity']); ?></strong></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <!-- TAB 3: COUPONS -->
      <div id="wf_tab_coupons" class="wf-tab-panel" style="display: none;">
        <div class="wf-form-section">
          <div class="wf-form-section-title">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 8l6-6 6 6-6 6-6-6z"/></svg>
            <?php echo bkntc__('Applied Coupon')?>
          </div>
          <div class="wf-coupon-empty">
            <p><?php echo bkntc__('No coupon code applied to this appointment.')?></p>
          </div>
        </div>
      </div>

      <!-- TAB 4: CUSTOM FIELDS -->
      <div id="wf_tab_custom" class="wf-tab-panel" style="display: none;">
        <div class="wf-form-section">
          <div class="wf-form-section-title">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 2h8l2 2v10a1 1 0 01-1 1H3a1 1 0 01-1-1V3z"/><path d="M5 7h6M5 10h4"/></svg>
            <?php echo bkntc__('Custom Fields & Intake Form')?>
          </div>
          <div style="background: #f8fafc; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">
            <div class="wf-cf-row">
              <div class="wf-cf-label"><?php echo bkntc__('Full Name')?></div>
              <div class="wf-cf-value"><?php echo htmlspecialchars($customerFullName); ?></div>
            </div>
            <div class="wf-cf-row">
              <div class="wf-cf-label"><?php echo bkntc__('Email Address')?></div>
              <div class="wf-cf-value"><?php echo htmlspecialchars($info->customer_email); ?></div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Right Column: Sidebar -->
    <div class="wf-sidebar">
      
      <!-- Payment Summary Card -->
      <div class="wf-sidebar-card">
        <div class="wf-sidebar-title"><?php echo bkntc__('PAYMENT SUMMARY')?></div>
        <div class="wf-payment-row">
          <span class="wf-item"><?php echo htmlspecialchars($info->service_name); ?></span>
          <span class="wf-amount"><?php echo Helper::price($totalAmount > 0 ? $totalAmount : $info->paid_amount); ?></span>
        </div>
        <div class="wf-payment-row wf-total">
          <span class="wf-item"><?php echo bkntc__('Total')?></span>
          <span class="wf-amount"><?php echo Helper::price($totalAmount > 0 ? $totalAmount : $info->paid_amount); ?></span>
        </div>
        <div class="wf-payment-badge">
          <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="14" height="10" rx="2"/><path d="M1 7h14"/></svg>
          <?php echo $info->paid_amount > 0 ? bkntc__('Paid') : bkntc__('Unpaid'); ?>
        </div>
      </div>

      <!-- Customer Card -->
      <div class="wf-sidebar-card">
        <div class="wf-sidebar-title"><?php echo bkntc__('CUSTOMER')?></div>
        <div class="wf-sidebar-profile">
          <div class="wf-profile-av" style="background: #22c55e;"><?php echo htmlspecialchars($initials ?: 'CU'); ?></div>
          <div>
            <div style="font-weight: 700; font-size: 13px; color: #0f172a;"><?php echo htmlspecialchars($customerFullName); ?></div>
            <div style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($info->customer_email); ?></div>
          </div>
        </div>
        <div class="wf-customer-meta">
          <div class="wf-meta-line">
            <span class="wf-lbl"><?php echo bkntc__('Phone')?></span>
            <span class="wf-val"><?php echo htmlspecialchars($info->customer_phone_number ?: '-'); ?></span>
          </div>
          <div class="wf-meta-line">
            <span class="wf-lbl"><?php echo bkntc__('Total visits')?></span>
            <span class="wf-val"><?php echo (int)$info->weight; ?></span>
          </div>
          <div class="wf-meta-line">
            <span class="wf-lbl"><?php echo bkntc__('Category')?></span>
            <span class="wf-chip wf-chip-success" style="font-size: 10px; padding: 2px 8px;">VIP</span>
          </div>
        </div>
      </div>

      <!-- Activity Log Card -->
      <div class="wf-sidebar-card">
        <div class="wf-sidebar-title"><?php echo bkntc__('ACTIVITY LOG')?></div>
        <div class="wf-timeline">
          <div class="wf-tl-item">
            <div class="wf-tl-dot" style="background: <?php echo htmlspecialchars($st['color']); ?>;"></div>
            <div class="wf-tl-content">
              <div class="wf-tl-action"><?php echo bkntc__('Status')?> → <?php echo htmlspecialchars($st['title']); ?></div>
              <div class="wf-tl-time"><?php echo Date::dateTime($info->starts_at); ?></div>
            </div>
          </div>
          <div class="wf-tl-item">
            <div class="wf-tl-dot" style="background: #6366f1;"></div>
            <div class="wf-tl-content">
              <div class="wf-tl-action"><?php echo bkntc__('Appointment created')?></div>
              <div class="wf-tl-time"><?php echo Date::dateTime($info->created_at); ?></div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <?php if ($mode === 'edit'): ?>
    <!-- STICKY SAVE FOOTER -->
    <div class="wf-form-footer">
      <button type="button" class="btn btn-ghost wf-btn-ghost wf-switch-mode" data-id="<?php echo (int)$info->id; ?>" data-mode="view"><?php echo bkntc__('Cancel')?></button>
      <button type="button" class="btn btn-success wf-btn-success wf-switch-mode" data-id="<?php echo (int)$info->id; ?>" data-mode="view">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8l4 4 6-6"/></svg>
        <?php echo bkntc__('Save Changes')?>
      </button>
    </div>
  <?php endif; ?>

</div>
