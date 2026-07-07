<?php
defined( 'ABSPATH' ) or die();

use BookneticApp\Models\Appointment;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use BookneticAddon\Customerpanel\CustomerPanelHelper;
use function BookneticAddon\Customerpanel\bkntc__;

$now = Date::epoch();
$upcoming = [];
$past = [];

foreach ( $parameters['appointments'] AS $appointment ) {
    Permission::setTenantId( $appointment->tenant_id );
    $clientTimeZoneIsOpen = Helper::getOption('client_timezone_enable', 'off') == 'on';

    $duration   = (int)$appointment->ends_at - (int)$appointment->starts_at;
    $dateFormat = Helper::isSaaSVersion() ? 'Y-m-d' : Helper::getOption( 'date_format', 'Y-m-d' );

    $clientDate = Helper::isSaaSVersion() ? Date::dateSQL( $appointment->starts_at ,false,$clientTimeZoneIsOpen ) : Date::datee( $appointment->starts_at,false,$clientTimeZoneIsOpen );
    $clientTime = $duration >= 24 * 60 * 60 ? '' : ( Helper::isSaaSVersion() ? Date::timeSQL( $appointment->starts_at ,false,$clientTimeZoneIsOpen ) : Date::time( $appointment->starts_at,false,$clientTimeZoneIsOpen ) );

    $originalDate = Helper::isSaaSVersion() ? Date::dateSQL( $appointment->starts_at ) : Date::datee( $appointment->starts_at );
    $originalTime = $duration >= 24 * 60 * 60 ? '' : ( Helper::isSaaSVersion() ? Date::timeSQL( $appointment->starts_at ) : Date::time( $appointment->starts_at ) );

    $appointment->client_date = $clientDate;
    $appointment->client_time = $clientTime;
    $appointment->original_date = $originalDate;
    $appointment->original_time = $originalTime;
    $appointment->duration_formatted = Helper::secFormat( $duration );
    $appointment->dateFormat = $dateFormat;
    $appointment->duration_sec = $duration;

    // Check if upcoming
    if ( $appointment->starts_at > $now && !in_array($appointment->status, ['canceled', 'rejected']) ) {
        $upcoming[] = $appointment;
    } else {
        $past[] = $appointment;
    }
}

// Calculate stats
$upcoming_count = count($upcoming);
$total_visits = count($parameters['appointments']);
$total_spent = 0;
foreach ($parameters['appointments'] as $app) {
    if (!in_array($app->status, ['canceled', 'rejected'])) {
        $total_spent += $app->total_price;
    }
}
?>

<style>
/* Embedded style to force circle avatar shape */
.cp2-staff-avatar, 
.cp2-appt-meta img,
.cp2-appt-meta span img {
    width: 18px !important;
    height: 18px !important;
    min-width: 18px !important;
    min-height: 18px !important;
    max-width: 18px !important;
    max-height: 18px !important;
    border-radius: 50% !important;
    object-fit: cover !important;
    display: inline-block !important;
}
</style>

<!-- Quick Stats Grid -->
<div class="cp2-stats">
  <div class="cp2-stat-card">
    <div class="stat-label"><?php echo bkntc__('Upcoming'); ?></div>
    <div class="stat-value"><?php echo $upcoming_count; ?></div>
    <div class="stat-sub">
      <?php 
      if ($upcoming_count > 0) {
          $next_app = reset($upcoming); // first element in array
          echo bkntc__('Next: ') . $next_app->client_date; 
      } else {
          echo bkntc__('No upcoming visits'); 
      }
      ?>
    </div>
  </div>
  <div class="cp2-stat-card">
    <div class="stat-label"><?php echo bkntc__('Total Bookings'); ?></div>
    <div class="stat-value"><?php echo $total_visits; ?></div>
    <div class="stat-sub"><?php echo bkntc__('All-time visits'); ?></div>
  </div>
  <div class="cp2-stat-card">
    <div class="stat-label"><?php echo bkntc__('Total Spent'); ?></div>
    <div class="stat-value"><?php echo Helper::price($total_spent); ?></div>
    <div class="stat-sub"><?php echo bkntc__('Completed & approved'); ?></div>
  </div>
</div>

<!-- Dynamic Hero Subtitle Updater script -->
<script>
  jQuery(document).ready(function($) {
    $('#cp_hero_sub_text').text('<?php echo sprintf(bkntc__("%d upcoming appointment(s) this week"), $upcoming_count); ?>');
  });
</script>

<!-- Upcoming Appointments Section -->
<div class="cp2-section-title"><?php echo bkntc__('Upcoming Appointments'); ?></div>
<?php if ( empty($upcoming) ): ?>
  <div class="empty-state">
    <div class="e-icon"><i class="far fa-calendar-times"></i></div>
    <h3><?php echo bkntc__('No upcoming appointments'); ?></h3>
    <p><?php echo bkntc__('You do not have any upcoming visits booked.'); ?></p>
  </div>
<?php else: ?>
  <?php foreach ($upcoming as $appointment): ?>
    <?php
    $app_month = date('M', $appointment->starts_at);
    $app_day = date('d', $appointment->starts_at);
    ?>
    <div class="cp2-appt-card" data-id="<?php echo $appointment->id;?>" data-date="<?php echo $appointment->client_date; ?>" data-date-original="<?php echo $appointment->original_date; ?>" data-time-original="<?php echo $appointment->original_time; ?>" data-time="<?php echo $appointment->client_time; ?>" data-date-format="<?php echo $appointment->dateFormat; ?>" data-datebased="<?php echo ( int ) ($appointment->duration_sec >= 24 * 60); ?>">
      <div class="cp2-appt-date">
        <div class="m"><?php echo $app_month; ?></div>
        <div class="d"><?php echo $app_day; ?></div>
      </div>
      <div class="cp2-appt-info">
        <div class="cp2-appt-service"><?php echo htmlspecialchars($appointment->service_name); ?></div>
        <div class="cp2-appt-meta">
          <span>🕐 <?php echo $appointment->client_time; ?> (<?php echo $appointment->duration_formatted; ?>)</span>
          <span style="display: inline-flex; align-items: center; gap: 6px;">
            <img class="cp2-staff-avatar" src="<?php echo htmlspecialchars(Helper::profileImage($appointment->staff_profile_image, 'staff')); ?>" style="width: 18px !important; height: 18px !important; border-radius: 50% !important; object-fit: cover !important;" alt="<?php echo htmlspecialchars($appointment->staff_name); ?>">
            <?php echo htmlspecialchars($appointment->staff_name); ?>
          </span>
          <?php if( !empty($appointment->location_address) ): ?>
            <span>📍 <?php echo htmlspecialchars( $appointment->location_address )?></span>
          <?php endif;?>
        </div>
      </div>
      <div class="cp2-appt-right">
        <span class="badge" style="background: <?php echo htmlspecialchars( $appointment->status_color ) ?>1A; color: <?php echo htmlspecialchars( $appointment->status_color ) ?>;">
          <?php echo htmlspecialchars( $appointment->status_text ) ?>
        </span>
        <div class="cp2-appt-actions">
          <?php do_action( 'bkntc_customer_panel_appointment_actions', $appointment->id ); ?>
          <?php if( CustomerPanelHelper::canRescheduleAppointment( $appointment ) ): ?>
            <button class="btn btn-ghost btn-sm booknetic_reschedule_btn" type="button"><i class="fa-regular fa-clock"></i> Reschedule</button>
          <?php endif; ?>
          <?php if( CustomerPanelHelper::canCancelAppointment( $appointment ) ): ?>
            <button class="btn btn-ghost btn-sm booknetic_cancel_btn" type="button" style="color: var(--danger);"><i class="fa-solid fa-xmark"></i> Cancel</button>
          <?php endif; ?>
          <?php if ( CustomerPanelHelper::canChangeAppointmentStatus( $appointment ) ): ?>
            <button class="btn btn-ghost btn-sm booknetic_change_status_btn" type="button"><i class="fa-solid fa-arrows-rotate"></i> Status</button>
          <?php endif; ?>
          <?php if ( Helper::getOption('hide_pay_now_btn_customer_panel', 'off')=='off' && $appointment->total_price != $appointment->paid_amount): ?>
            <button class="btn btn-primary btn-sm booknetic_pay_now_btn" type="button"><i class="fa-solid fa-credit-card"></i> Pay Now</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<!-- Booking History Section -->
<div class="separator" style="margin: 28px 0;"></div>
<div class="cp2-section-title"><?php echo bkntc__('Booking History'); ?></div>
<?php if ( empty($past) ): ?>
  <div class="empty-state">
    <div class="e-icon"><i class="fa-solid fa-history"></i></div>
    <h3><?php echo bkntc__('No past bookings'); ?></h3>
  </div>
<?php else: ?>
  <!-- Mobile Cards View -->
  <div class="cp2-history-cards">
    <?php foreach ($past as $appointment): ?>
      <div class="cp2-history-card">
        <span class="badge" style="position: absolute; top: 13px; right: 14px; background: <?php echo htmlspecialchars( $appointment->status_color ) ?>1A; color: <?php echo htmlspecialchars( $appointment->status_color ) ?>;">
          <?php echo htmlspecialchars( $appointment->status_text ) ?>
        </span>
        <div class="svc"><?php echo htmlspecialchars($appointment->service_name); ?></div>
        <div class="meta"><?php echo $appointment->client_date; ?> · <?php echo htmlspecialchars($appointment->staff_name); ?></div>
        <div class="amt"><?php echo Helper::price($appointment->total_price); ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Tablet / Desktop Table View -->
  <div class="cp2-table-card">
    <table class="cp2-history-table">
      <thead>
        <tr>
          <th><?php echo bkntc__('Date'); ?></th>
          <th><?php echo bkntc__('Service'); ?></th>
          <th class="col-staff"><?php echo bkntc__('Staff'); ?></th>
          <th><?php echo bkntc__('Status'); ?></th>
          <th><?php echo bkntc__('Amount'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($past as $appointment): ?>
          <tr>
            <td><?php echo $appointment->client_date; ?></td>
            <td style="font-weight: 600;"><?php echo htmlspecialchars($appointment->service_name); ?></td>
            <td class="col-staff"><?php echo htmlspecialchars($appointment->staff_name); ?></td>
            <td>
              <span class="badge" style="background: <?php echo htmlspecialchars( $appointment->status_color ) ?>1A; color: <?php echo htmlspecialchars( $appointment->status_color ) ?>;">
                <?php echo htmlspecialchars( $appointment->status_text ) ?>
              </span>
            </td>
            <td style="font-weight: 600;"><?php echo Helper::price($appointment->total_price); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
