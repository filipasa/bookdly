<?php
defined('ABSPATH') or die();
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

$ids = $parameters['ids'] ?? [];
$statuses = $parameters['statuses'] ?? [];
$selectedStatus = $parameters['selectedStatus'] ?? '';
$appointmentsList = $parameters['appointmentsList'] ?? [];
?>

<div class="wf-fullpage-container wf-mode-edit">

  <!-- TOP BAR -->
  <div class="wf-top-bar">
    <a href="#" class="wf-back-link wf-back-to-table">
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 3L5 8l5 5"/></svg>
      <?php echo bkntc__('Appointments')?>
    </a>
    <span class="wf-sep">/</span>
    <span class="wf-crumb active"><?php echo bkntc__('Change Status')?></span>
  </div>

  <div style="max-width: 780px; margin: 32px auto; padding: 0 24px 120px;">

    <!-- Context Banner -->
    <div class="cs-context-banner">
      <div class="cs-context-icon">
        <svg width="20" height="20" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="1" y="3" width="14" height="11" rx="2"/>
          <path d="M5 1v4M11 1v4M1 7h14"/>
        </svg>
      </div>
      <div class="cs-context-body">
        <div class="cs-context-title">
          <?php echo bkntc__('Changing status for')?> 
          <strong><?php echo count($ids); ?> <?php echo count($ids) === 1 ? bkntc__('appointment') : bkntc__('appointments'); ?></strong>
        </div>
        <div class="cs-context-list">
          <?php foreach ($appointmentsList as $appt): ?>
            <?php 
              $apptSt = isset($statuses[$appt['status']]) ? $statuses[$appt['status']] : ['color' => '#94a3b8']; 
            ?>
            <span class="cs-appt-chip">
              <span class="cs-chip-dot" style="background: <?php echo htmlspecialchars($apptSt['color']); ?>;"></span>
              #<?php echo (int)$appt['id']; ?> &middot; <?php echo htmlspecialchars($appt['customer_name']); ?> &middot; <?php echo htmlspecialchars($appt['service_name']); ?> &middot; <?php echo Date::datee($appt['starts_at']); ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Main Status Selection Card -->
    <div class="cs-card">
      <div class="cs-card-header">
        <div class="cs-card-icon">
          <svg width="18" height="18" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M11 2l3 3-8 8H3v-3z"/>
          </svg>
        </div>
        <div>
          <div class="cs-card-title"><?php echo bkntc__('New Status')?></div>
          <div class="cs-card-sub"><?php echo bkntc__('Select the status to apply to all selected appointments')?></div>
        </div>
      </div>

      <!-- Status Grid -->
      <div class="cs-status-grid">
        <?php foreach ($statuses as $stSlug => $stItem): ?>
          <?php 
            $isActive = ($selectedStatus === $stSlug);
            $bg = $stItem['color'] . '22';
            $fg = $stItem['color'];
          ?>
          <button type="button" class="cs-status-card <?php echo $isActive ? 'active' : ''; ?>" data-status="<?php echo htmlspecialchars($stSlug); ?>" data-color="<?php echo htmlspecialchars($stItem['color']); ?>" data-title="<?php echo htmlspecialchars($stItem['title']); ?>">
            <div class="cs-status-icon" style="background: <?php echo htmlspecialchars($bg); ?>; color: <?php echo htmlspecialchars($fg); ?>;">
              <i class="<?php echo htmlspecialchars($stItem['icon']); ?>" style="font-size: 20px;"></i>
            </div>
            <div class="cs-status-label"><?php echo htmlspecialchars($stItem['title']); ?></div>
            <div class="cs-status-desc"><?php echo bkntc__('Change state to')?> <?php echo htmlspecialchars(strtolower($stItem['title'])); ?></div>
            <div class="cs-status-check" style="background: <?php echo htmlspecialchars($fg); ?>;">
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 8l4 4 6-6"/></svg>
            </div>
          </button>
        <?php endforeach; ?>
      </div>

      <!-- Live Preview Badge -->
      <div class="cs-preview">
        <span class="cs-preview-label"><?php echo bkntc__('Selected status')?></span>
        <?php 
          $prvSt = isset($statuses[$selectedStatus]) ? $statuses[$selectedStatus] : ['title' => bkntc__('Select status...'), 'color' => '#94a3b8']; 
        ?>
        <div class="cs-preview-badge <?php echo htmlspecialchars($selectedStatus); ?>" id="cs-preview-badge" style="background: <?php echo htmlspecialchars($prvSt['color']); ?>22; color: <?php echo htmlspecialchars($prvSt['color']); ?>;">
          <div class="cs-preview-dot" style="background: <?php echo htmlspecialchars($prvSt['color']); ?>;"></div>
          <span id="cs-preview-text"><?php echo htmlspecialchars($prvSt['title']); ?></span>
        </div>
        <div class="cs-preview-arrow"><?php echo bkntc__('will be applied to')?> <strong><?php echo count($ids); ?></strong> <?php echo bkntc__('appointment(s)')?></div>
      </div>

      <!-- Divider -->
      <div style="border-top: 1.5px solid #e2e8f0; margin: 24px 0;"></div>

      <!-- Workflow Checkbox -->
      <label class="cs-workflow-row">
        <input type="checkbox" id="input_run_workflows_cs" checked>
        <div class="cs-workflow-text">
          <span class="cs-workflow-title"><?php echo bkntc__('Run workflows on save')?></span>
          <span class="cs-workflow-sub"><?php echo bkntc__('Triggers automations like email confirmations and notifications')?></span>
        </div>
      </label>

    </div>

  </div>

  <!-- STICKY FOOTER -->
  <div class="wf-form-footer" style="display: flex;">
    <div style="margin-right: auto; font-size: 13px; color: #475569; font-weight: 500;">
      <?php echo bkntc__('Applying to:')?> <strong><?php echo count($ids); ?> <?php echo count($ids) === 1 ? bkntc__('appointment') : bkntc__('appointments'); ?></strong>
    </div>
    <button class="btn btn-ghost wf-btn-ghost wf-back-to-table"><?php echo bkntc__('Cancel')?></button>
    <button class="btn btn-success wf-btn-success wf-btn-apply-status" data-ids="<?php echo implode(',', $ids); ?>">
      <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8l4 4 6-6"/></svg>
      <?php echo bkntc__('Apply Status')?>
    </button>
  </div>

</div>
