<?php
defined('ABSPATH') or die();

use BookneticApp\Backend\Appointments\Helpers\AppointmentSmartObject;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Math;

/**
 * @var $parameters [ 'payment' => AppointmentSmartObject ]
 */
$payment = $parameters['payment'];
$txnId = (int)$payment->getId();
$startsAt = $payment->getAppointmentInfo()->starts_at;
$formattedDate = Date::dateTime($startsAt);

$customerName = htmlspecialchars($payment->getCustomerInf()->full_name);
$customerEmail = htmlspecialchars($payment->getCustomerInf()->email);
$customerId = (int)$payment->getInfo()->customer_id;

$status = htmlspecialchars($payment->getInfo()->payment_status);
$paymentMethod = htmlspecialchars($payment->getInfo()->payment_method);
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
	cursor: pointer;
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

/* MODE TOGGLE */
.wf-mode-toggle {
	margin-left: auto;
	display: flex;
	background: var(--wf-surface-2);
	border-radius: 8px;
	padding: 3px;
	gap: 2px;
}

.wf-mode-btn {
	padding: 6px 14px;
	border: none;
	background: none;
	border-radius: 6px;
	cursor: pointer;
	font-size: 12px;
	font-weight: 600;
	color: var(--wf-text-3);
	transition: all 0.18s ease;
	outline: none !important;
}

.wf-mode-btn.active {
	background: var(--wf-surface);
	color: var(--wf-text);
	box-shadow: var(--wf-shadow);
}

/* LAYOUT */
.wf-details-layout {
	max-width: 1150px;
	margin: 32px auto;
	padding: 0 24px 100px;
}

.wf-header-row {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	margin-bottom: 24px;
}

.wf-header-title {
	font-size: 22px;
	font-weight: 800;
	color: var(--wf-text);
}

.wf-header-subtitle {
	font-size: 11px;
	font-weight: 700;
	color: var(--wf-text-3);
	text-transform: uppercase;
	letter-spacing: .06em;
	margin-bottom: 4px;
}

.wf-header-actions {
	display: flex;
	align-items: center;
	gap: 10px;
}

.wf-btn {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	padding: 8px 16px;
	border-radius: var(--wf-radius-sm);
	font-size: 13px;
	font-weight: 600;
	cursor: pointer;
	border: none;
	transition: all 0.18s ease;
}

.wf-btn-primary {
	background: #16a34a;
	color: #fff;
}

.wf-btn-primary:hover {
	background: #15803d;
	box-shadow: 0 4px 12px rgba(22,163,74,0.25);
}

.wf-btn-ghost {
	background: none;
	color: var(--wf-text-2);
	border: 1.5px solid var(--wf-border);
}

.wf-btn-ghost:hover {
	background: var(--wf-surface-2);
}

.wf-grid-layout {
	display: grid;
	grid-template-columns: 1fr 320px;
	gap: 32px;
	align-items: flex-start;
}

.wf-card {
	background: var(--wf-surface);
	border: 1.5px solid var(--wf-border);
	border-radius: var(--wf-radius);
	padding: 24px;
	margin-bottom: 20px;
	box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

.wf-card-title {
	font-size: 13px;
	font-weight: 700;
	color: var(--wf-text-2);
	text-transform: uppercase;
	letter-spacing: .05em;
	padding-bottom: 16px;
	border-bottom: 1.5px solid var(--wf-border);
	margin-bottom: 20px;
	display: flex;
	align-items: center;
	gap: 8px;
}

.wf-card-title svg {
	width: 15px;
	height: 15px;
	color: var(--wf-primary);
}

/* INFO GRID */
.wf-info-grid {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: 0;
}

.wf-info-cell {
	padding: 16px 20px;
	border-right: 1px solid var(--wf-border);
	border-bottom: 1px solid var(--wf-border);
}

.wf-info-grid .wf-info-cell:nth-child(2n) {
	border-right: none;
}

.wf-info-grid .wf-info-cell:nth-last-child(-n+2) {
	border-bottom: none;
}

.wf-info-cell .wf-lbl {
	font-size: 11px;
	font-weight: 700;
	color: var(--wf-text-3);
	text-transform: uppercase;
	letter-spacing: .04em;
	margin-bottom: 6px;
}

.wf-info-cell .wf-val {
	font-size: 14px;
	font-weight: 600;
	color: var(--wf-text);
}

/* FORM FIELDS */
.wf-form-row {
	display: flex;
	gap: 16px;
	margin-bottom: 16px;
}

.wf-form-row:last-child {
	margin-bottom: 0;
}

.wf-form-group {
	display: flex;
	flex-direction: column;
	gap: 6px;
	flex: 1;
}

.wf-label {
	font-size: 12px;
	font-weight: 600;
	color: var(--wf-text-2);
}

.wf-label .wf-req {
	color: var(--wf-danger);
	margin-left: 2px;
}

.wf-input-addon-container {
	display: flex;
}

.wf-input-prefix {
	background: var(--wf-surface-2);
	border: 1.5px solid var(--wf-border);
	border-right: none;
	padding: 0 14px;
	border-radius: var(--wf-radius-sm) 0 0 var(--wf-radius-sm);
	font-size: 13px;
	color: var(--wf-text-3);
	display: flex;
	align-items: center;
}

.wf-form-control {
	height: 40px;
	padding: 0 12px;
	border: 1.5px solid var(--wf-border);
	border-radius: var(--wf-radius-sm);
	font-size: 13px;
	color: var(--wf-text);
	background: var(--wf-surface);
	outline: none;
	transition: all 0.18s ease;
	width: 100%;
}

.wf-input-addon-container .wf-form-control {
	border-radius: 0 var(--wf-radius-sm) var(--wf-radius-sm) 0;
}

.wf-form-control:focus {
	border-color: var(--wf-primary);
	box-shadow: 0 0 0 3px rgba(99,102,241,.12);
}

.wf-select-arrow {
	appearance: none;
	-webkit-appearance: none;
	background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%2394a3b8' stroke-width='2' viewBox='0 0 16 16'%3E%3Cpath d='M4 6l4 4 4-4'/%3E%3C/svg%3E");
	background-repeat: no-repeat;
	background-position: right 12px center;
	padding-right: 32px;
}
</style>

<div class="wf-fullpage-container wf-mode-edit" data-payment-id="<?php echo $txnId; ?>">
  <!-- TOP BAR -->
  <div class="wf-top-bar">
    <a class="wf-back-link">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 3L5 8l5 5"/></svg>
      <?php echo bkntc__('Back to Payments')?>
    </a>
    <span class="wf-sep">/</span>
    <span class="wf-crumb active"><?php echo bkntc__('Edit Payment')?></span>

    <div class="wf-mode-toggle">
      <button class="wf-mode-btn" id="payModeViewBtn" data-id="<?php echo $txnId; ?>"><?php echo bkntc__('View')?></button>
      <button class="wf-mode-btn active" id="payModeEditBtn"><?php echo bkntc__('Edit')?></button>
    </div>
  </div>

  <div class="wf-details-layout">
    <div class="wf-header-row">
      <div>
        <div class="wf-header-subtitle"><?php echo bkntc__('Edit Payment Transaction')?></div>
        <div class="wf-header-title">#TXN-<?php echo sprintf('%05d', $txnId); ?> · <?php echo $formattedDate; ?></div>
      </div>
      <div class="wf-header-actions">
        <button class="wf-btn wf-btn-ghost" id="wf-edit-payment-cancel" data-id="<?php echo $txnId; ?>"><?php echo bkntc__('Cancel')?></button>
        <button class="wf-btn wf-btn-primary" id="wf-edit-payment-save" data-id="<?php echo $txnId; ?>">
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8l4 4 6-6"/></svg>
          <?php echo bkntc__('Save Changes')?>
        </button>
      </div>
    </div>

    <div class="wf-grid-layout">
      <!-- Main Content (Left) -->
      <div>
        <!-- Transaction Info -->
        <div class="wf-card">
          <div class="wf-card-title">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="14" height="10" rx="2"/><path d="M1 8h14M5 11h.01M8 11h.01M11 11h.01"/></svg>
            <?php echo bkntc__('Transaction Info')?>
          </div>

          <div class="wf-info-grid">
            <div class="wf-info-cell">
              <div class="wf-lbl"><?php echo bkntc__('Customer')?></div>
              <div class="wf-val" style="display: flex; align-items: center; gap: 10px; margin-top: 4px;">
                <?php
                // Generate color and initials
                $initials = '';
                if (!empty($customerName)) {
                    $words = explode(' ', $customerName);
                    foreach ($words as $w) {
                        $initials .= mb_substr($w, 0, 1, 'UTF-8');
                    }
                    $initials = mb_substr($initials, 0, 2, 'UTF-8');
                }
                $colors = ['#ef4444', '#f97316', '#f59e0b', '#10b981', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899', '#14b8a6'];
                $colorIndex = abs(crc32($customerEmail ?: $customerName)) % count($colors);
                $customerColor = $colors[$colorIndex];
                
                $customerAvatar = $payment->getCustomerInf()->profile_image;
                $hasCustomImage = !empty($customerAvatar) && strpos($customerAvatar, 'no-photo') === false;
                ?>
                <div style="width: 36px; height: 36px; border-radius: 50%; background: <?php echo htmlspecialchars($customerColor); ?>; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; overflow: hidden; flex-shrink: 0;">
                  <?php if ($hasCustomImage): ?>
                    <img src="<?php echo htmlspecialchars(Helper::profileImage($customerAvatar, 'Customers')); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                  <?php else: ?>
                    <?php echo htmlspecialchars($initials ?: '?'); ?>
                  <?php endif; ?>
                </div>
                <div>
                  <div style="font-weight:600; color:var(--wf-text);"><?php echo $customerName; ?></div>
                  <div style="font-size: 11px; color:var(--wf-text-3); font-weight:400;"><?php echo $customerEmail; ?></div>
                </div>
              </div>
            </div>
            <div class="wf-info-cell">
              <div class="wf-lbl"><?php echo bkntc__('Appointment')?></div>
              <div class="wf-val">#<?php echo $txnId; ?> (<?php echo htmlspecialchars($payment->getServiceInf()->name); ?>)</div>
            </div>
            <div class="wf-info-cell">
              <div class="wf-lbl"><?php echo bkntc__('Payment Method')?></div>
              <div class="wf-val"><?php echo Helper::paymentMethod($paymentMethod); ?></div>
            </div>
            <div class="wf-info-cell">
              <div class="wf-lbl"><?php echo bkntc__('Date')?></div>
              <div class="wf-val"><?php echo $formattedDate; ?></div>
            </div>
          </div>
        </div>

        <!-- Price Items Form -->
        <div class="wf-card">
          <div class="wf-card-title">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2H4a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2zM8 5v6M5 8h6"/></svg>
            <?php echo bkntc__('Price Items')?>
          </div>

          <div class="prices-section">
            <?php foreach ($payment->getPrices() as $price): ?>
              <div class="wf-form-row">
                <div class="wf-form-group">
                  <label class="wf-label" for="input_price_<?php echo (int)$price->id; ?>">
                    <?php echo htmlspecialchars($price->name); ?> <span class="wf-req">*</span>
                  </label>
                  <div class="wf-input-addon-container">
                    <span class="wf-input-prefix">$</span>
                    <input class="wf-form-control" id="input_price_<?php echo (int)$price->id; ?>" data-price-id="<?php echo htmlspecialchars($price->unique_key); ?>" value="<?php echo Math::floor($price->price); ?>" placeholder="0">
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Sidebar (Right) -->
      <div class="wf-sidebar">
        <!-- Billing Details -->
        <div class="wf-card">
          <div class="wf-card-title" style="border-bottom:none; margin-bottom:12px; padding-bottom:0;">
            <?php echo bkntc__('Billing Details')?>
          </div>

          <div class="wf-form-group" style="margin-bottom:14px;">
            <label class="wf-label" for="input_payment_status"><?php echo bkntc__('Payment Status')?> <span class="wf-req">*</span></label>
            <select class="wf-form-control wf-select-arrow" id="input_payment_status">
              <option value="pending" <?php echo ($status == 'pending' ? 'selected' : ''); ?>><?php echo bkntc__('Pending')?></option>
              <option value="paid" <?php echo ($status == 'paid' ? 'selected' : ''); ?>><?php echo bkntc__('Paid')?></option>
              <option value="canceled" <?php echo ($status == 'canceled' ? 'selected' : ''); ?>><?php echo bkntc__('Canceled')?></option>
              <option value="not_paid" <?php echo ($status == 'not_paid' ? 'selected' : ''); ?>><?php echo bkntc__('Not Paid')?></option>
            </select>
          </div>

          <div class="wf-form-group">
            <label class="wf-label" for="input_paid_amount"><?php echo bkntc__('Paid Amount')?> <span class="wf-req">*</span></label>
            <div class="wf-input-addon-container">
              <span class="wf-input-prefix">$</span>
              <input class="wf-form-control" id="input_paid_amount" value="<?php echo Math::floor($payment->getPaidAmount()); ?>" placeholder="0">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
