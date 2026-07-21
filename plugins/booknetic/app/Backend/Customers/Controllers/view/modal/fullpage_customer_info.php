<?php
defined('ABSPATH') or die();
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

$customer = $parameters['customer'];
$appointments = $parameters['appointments'] ?? [];
$totalBookings = $parameters['totalBookings'] ?? 0;
$totalSpent = $parameters['totalSpent'] ?? 0.0;
$noShows = $parameters['noShows'] ?? 0;
$statusHistory = $parameters['statusHistory'] ?? [];
$statuses = $parameters['statuses'] ?? [];

$customerFullName = trim($customer['first_name'] . ' ' . $customer['last_name']);
$initials = '';
if (!empty($customerFullName)) {
    $words = explode(' ', $customerFullName);
    foreach ($words as $w) {
        $initials .= mb_substr($w, 0, 1, 'UTF-8');
    }
    $initials = mb_substr($initials, 0, 2, 'UTF-8');
}
$colors = ['#ef4444', '#f97316', '#f59e0b', '#10b981', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899', '#14b8a6'];
$colorIndex = abs(crc32($customer['email'] ?: $customerFullName)) % count($colors);
$customerColor = $colors[$colorIndex];

$joinedDate = !empty($customer['created_at']) ? Date::datee($customer['created_at']) : '-';
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

/* CUSTOMER LAYOUT */
.wf-customer-layout {
	max-width: 1150px;
	margin: 32px auto;
	padding: 0 24px 100px;
}

.wf-appt-layout {
	display: grid;
	grid-template-columns: 320px 1fr;
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

/* CHIPS */
.wf-chip {
	display: inline-flex;
	align-items: center;
	padding: 4px 10px;
	border-radius: 20px;
	font-size: 11px;
	font-weight: 700;
}

.wf-chip-success {
	background: var(--wf-success-dim);
	color: var(--wf-success);
}

/* TABS */
.wf-tab-bar {
	display: flex;
	gap: 8px;
	border-bottom: 2px solid var(--wf-border);
	padding-bottom: 2px;
}

.wf-tab-btn {
	background: none;
	border: none;
	border-bottom: 2px solid transparent;
	padding: 10px 18px;
	font-weight: 700;
	font-size: 14px;
	color: var(--wf-text-3);
	cursor: pointer;
	transition: all 0.18s ease;
	outline: none !important;
}

.wf-tab-btn:hover {
	color: var(--wf-text-2);
}

.wf-tab-btn.active {
	color: var(--wf-primary);
	border-bottom-color: var(--wf-primary);
}

.wf-tab-pane {
	display: none;
}

.wf-tab-pane.active {
	display: block;
}

/* TIMELINE (ACTIVITY LOG) */
.wf-timeline {
	position: relative;
	padding-left: 20px;
	margin-top: 10px;
}

.wf-timeline::before {
	content: '';
	position: absolute;
	left: 4px;
	top: 8px;
	bottom: 8px;
	width: 2px;
	background: var(--wf-border);
}

.wf-tl-item {
	position: relative;
	margin-bottom: 24px;
}

.wf-tl-item:last-child {
	margin-bottom: 0;
}

.wf-tl-dot {
	position: absolute;
	left: -20px;
	top: 6px;
	width: 10px;
	height: 10px;
	border-radius: 50%;
	border: 2px solid var(--wf-surface);
	box-shadow: 0 0 0 2px var(--wf-border);
}

.wf-tl-content {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.wf-tl-action {
	font-size: 13px;
	color: var(--wf-text-2);
	font-weight: 500;
}

.wf-tl-time {
	font-size: 11px;
	color: var(--wf-text-3);
	font-weight: 600;
}

/* BUTTONS */
.wf-btn-success {
	background: #10b981 !important;
	color: #fff !important;
	border-color: #10b981 !important;
	font-weight: 700;
	display: inline-flex;
	align-items: center;
	gap: 6px;
	border-radius: 8px;
	padding: 8px 16px;
	font-size: 13px;
	cursor: pointer;
	border: none;
}

.wf-btn-success:hover {
	background: #059669 !important;
}

.wf-btn-success svg {
	width: 16px;
	height: 16px;
}
</style>

<div class="wf-fullpage-container wf-mode-view" data-appointments-css="<?php echo Helper::assets('css/appointments.css', 'Appointments'); ?>">

  <!-- TOP BAR / BREADCRUMBS -->
  <div class="wf-top-bar">
    <a href="#" class="wf-back-link wf-back-to-table">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 3L5 8l5 5"/></svg>
      <?php echo bkntc__('Customers')?>
    </a>
    <span class="wf-sep">/</span>
    <span class="wf-crumb active"><?php echo htmlspecialchars($customerFullName); ?></span>
  </div>

  <div class="wf-customer-layout" style="max-width: 1100px; margin: 32px auto; padding: 0 24px 100px;">
    
    <div class="wf-appt-layout">
      <!-- Left Column (Sidebar) -->
      <div class="wf-sidebar" style="display: flex; flex-direction: column; gap: 20px;">
        
        <!-- Profile Details Card -->
        <div class="wf-sidebar-card">
          <div class="wf-sidebar-profile" style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 12px; margin-bottom: 20px;">
            <div class="wf-profile-av" style="width: 80px; height: 80px; font-size: 28px; background: <?php echo $customerColor; ?>; overflow: hidden; display: flex; align-items: center; justify-content: center; border-radius: 50%; color: #fff; font-weight: 700; margin: 0 auto;">
              <?php if (!empty($customer['profile_image'])): ?>
                <img src="<?php echo htmlspecialchars(Helper::profileImage($customer['profile_image'], 'Customers')); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
              <?php else: ?>
                <?php echo htmlspecialchars($initials ?: 'CU'); ?>
              <?php endif; ?>
            </div>
            <div>
              <div style="font-weight: 800; font-size: 18px; color: #0f172a;"><?php echo htmlspecialchars($customerFullName); ?></div>
              <div style="font-size: 13px; color: #64748b; margin-top: 4px;"><?php echo htmlspecialchars($customer['email']); ?></div>
            </div>
          </div>

          <div class="wf-customer-meta" style="border-top: 1px solid #e2e8f0; padding-top: 16px;">
            <div class="wf-meta-line" style="margin-bottom: 12px;">
              <span class="wf-lbl"><?php echo bkntc__('Phone')?></span>
              <span class="wf-val" style="font-weight: 600; color: #334155;"><?php echo htmlspecialchars($customer['phone_number'] ?: '-'); ?></span>
            </div>
            <div class="wf-meta-line" style="margin-bottom: 12px;">
              <span class="wf-lbl"><?php echo bkntc__('Joined')?></span>
              <span class="wf-val" style="font-weight: 600; color: #334155;"><?php echo htmlspecialchars($joinedDate); ?></span>
            </div>
            <?php if (!empty($customer['category'])): ?>
              <div class="wf-meta-line" style="margin-bottom: 12px;">
                <span class="wf-lbl"><?php echo bkntc__('Category')?></span>
                <span class="wf-chip wf-chip-success"><?php echo htmlspecialchars($customer['category']); ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Stats Summary Card -->
        <div class="wf-sidebar-card" style="padding: 16px;">
          <div class="wf-sidebar-title" style="margin-bottom: 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em;"><?php echo bkntc__('Summary')?></div>
          <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; background: #f8fafc; border-radius: 10px; overflow: hidden; border: 1.5px solid #e2e8f0;">
            <div style="text-align: center; padding: 14px 8px; border-right: 1.5px solid #e2e8f0;">
              <div style="font-size: 20px; font-weight: 800; color: #0f172a;"><?php echo (int)$totalBookings; ?></div>
              <div style="font-size: 9px; color: #64748b; font-weight: 600; text-transform: uppercase; margin-top: 4px;"><?php echo bkntc__('Bookings')?></div>
            </div>
            <div style="text-align: center; padding: 14px 8px; border-right: 1.5px solid #e2e8f0;">
              <div style="font-size: 20px; font-weight: 800; color: #0f172a;"><?php echo Helper::price($totalSpent); ?></div>
              <div style="font-size: 9px; color: #64748b; font-weight: 600; text-transform: uppercase; margin-top: 4px;"><?php echo bkntc__('Spent')?></div>
            </div>
            <div style="text-align: center; padding: 14px 8px;">
              <div style="font-size: 20px; font-weight: 800; color: #ef4444;"><?php echo (int)$noShows; ?></div>
              <div style="font-size: 9px; color: #64748b; font-weight: 600; text-transform: uppercase; margin-top: 4px;"><?php echo bkntc__('No-shows')?></div>
            </div>
          </div>
        </div>

      </div>

      <!-- Right Column (Main Info) -->
      <div class="wf-main-content">
        
        <!-- Tab Headers -->
        <div class="wf-tab-bar">
          <button type="button" class="wf-tab-btn active" data-tab="cust_tab_appointments"><?php echo bkntc__('Appointments')?></button>
          <button type="button" class="wf-tab-btn" data-tab="cust_tab_notes"><?php echo bkntc__('Notes')?></button>
          <button type="button" class="wf-tab-btn" data-tab="cust_tab_activity"><?php echo bkntc__('Activity Log')?></button>
        </div>

        <div class="wf-tab-content" style="margin-top: 20px;">
          
          <!-- Appointments Tab -->
          <div class="wf-tab-pane active" id="cust_tab_appointments">
            <?php if (empty($appointments)): ?>
              <div class="text-muted text-center" style="padding: 40px; background: #fff; border-radius: 12px; border: 1.5px solid #e2e8f0; font-weight: 500;">
                <?php echo bkntc__('No appointments found for this customer.')?>
              </div>
            <?php else: ?>
              <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($appointments as $appt): ?>
                  <?php 
                    $apptSt = isset($statuses[$appt['status']]) ? $statuses[$appt['status']] : ['title' => $appt['status'], 'color' => '#94a3b8'];
                    
                    $day = date('d', $appt['starts_at']);
                    $mon = date('M', $appt['starts_at']);
                    
                    // Determine light color variables for the date badge background
                    $badgeBg = $apptSt['color'] . '15';
                    $badgeFg = $apptSt['color'];
                  ?>
                  <div class="wf-payment-row" style="background: #fff; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; display: flex; align-items: center; gap: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); transition: all 0.2s ease;">
                    
                    <!-- Date badge -->
                    <div style="width: 52px; height: 52px; border-radius: 10px; background: <?php echo $badgeBg; ?>; display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0;">
                      <div style="font-size: 18px; font-weight: 800; color: <?php echo $badgeFg; ?>; line-height: 1;"><?php echo $day; ?></div>
                      <div style="font-size: 10px; font-weight: 700; color: <?php echo $badgeFg; ?>; text-transform: uppercase; margin-top: 2px;"><?php echo $mon; ?></div>
                    </div>

                    <!-- Details -->
                    <div style="flex: 1;">
                      <div style="font-weight: 700; font-size: 14px; color: #0f172a;"><?php echo htmlspecialchars($appt['service_name']); ?></div>
                      <div class="text-muted" style="font-size: 12px; color: #64748b; margin-top: 4px; font-weight: 500;">
                        <?php echo Date::time($appt['starts_at']); ?> &middot; <?php echo bkntc__('Staff:')?> <?php echo htmlspecialchars($appt['staff_name']); ?> &middot; <?php echo bkntc__('Location:')?> <?php echo htmlspecialchars($appt['location_name']); ?>
                      </div>
                    </div>

                    <!-- Status -->
                    <span class="wf-chip" style="background: <?php echo $badgeBg; ?>; color: <?php echo $badgeFg; ?>; font-weight: 700; padding: 6px 12px; border-radius: 20px; font-size: 11px;">
                      <?php echo htmlspecialchars($apptSt['title']); ?>
                    </span>

                    <!-- Row Action -->
                    <button type="button" class="btn btn-default btn-xs btn-outline-secondary wf-view-appt-btn" data-appt-id="<?php echo (int)$appt['id']; ?>" style="border-radius: 8px; font-weight: 600; padding: 5px 12px; font-size: 11px;">
                      <?php echo bkntc__('View')?>
                    </button>

                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Notes Tab -->
          <div class="wf-tab-pane" id="cust_tab_notes">
            <div class="wf-sidebar-card" style="padding: 24px; background: #fff; border-radius: 12px; border: 1.5px solid #e2e8f0;">
              <h4 style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 12px;"><?php echo bkntc__('Customer Notes')?></h4>
              <textarea id="cust_notes_textarea" class="form-control" style="min-height: 180px; border-radius: 10px; border: 1.5px solid #cbd5e1; padding: 12px; font-size: 13px;" placeholder="<?php echo bkntc__('Add private notes about this customer…'); ?>"><?php echo htmlspecialchars($customer['notes'] ?? ''); ?></textarea>
              <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
                <button type="button" class="btn btn-success wf-btn-success wf-btn-save-cust-notes" data-cust-id="<?php echo (int)$customer['id']; ?>">
                  <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8l4 4 6-6"/></svg>
                  <?php echo bkntc__('Save Notes')?>
                </button>
              </div>
            </div>
          </div>

          <!-- Activity Log Tab -->
          <div class="wf-tab-pane" id="cust_tab_activity">
            <?php if (empty($statusHistory)): ?>
              <div class="text-muted text-center" style="padding: 40px; background: #fff; border-radius: 12px; border: 1.5px solid #e2e8f0; font-weight: 500;">
                <?php echo bkntc__('No status logs found for this customer.')?>
              </div>
            <?php else: ?>
              <div class="wf-sidebar-card" style="padding: 24px; background: #fff; border-radius: 12px; border: 1.5px solid #e2e8f0;">
                <div class="wf-timeline" style="margin-top: 0;">
                  <?php foreach ($statusHistory as $log): ?>
                    <?php 
                      $logSt = isset($statuses[$log['status']]) ? $statuses[$log['status']] : ['title' => $log['status'], 'color' => '#94a3b8'];
                    ?>
                    <div class="wf-tl-item">
                      <div class="wf-tl-dot" style="background: <?php echo htmlspecialchars($logSt['color']); ?>;"></div>
                      <div class="wf-tl-content">
                        <div class="wf-tl-action">
                          <?php echo bkntc__('Appointment')?> <strong>#<?php echo (int)$log['appointment_id']; ?></strong> 
                          <?php echo bkntc__('Status')?> &rarr; 
                          <span style="font-weight: 700; color: <?php echo htmlspecialchars($logSt['color']); ?>;"><?php echo htmlspecialchars($logSt['title']); ?></span>
                        </div>
                        <div class="wf-tl-time"><?php echo Date::dateTime($log['time']); ?></div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>

        </div>

      </div>
    </div>

  </div>

</div>
