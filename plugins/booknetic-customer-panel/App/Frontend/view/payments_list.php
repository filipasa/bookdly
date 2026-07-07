<?php
defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use function BookneticAddon\Customerpanel\bkntc__;

$payments = [];
foreach ($parameters['appointments'] as $appointment) {
    if ($appointment->total_price > 0) {
        $payments[] = $appointment;
    }
}
?>

<div class="cp2-section-title"><?php echo bkntc__('All Payments'); ?></div>
<?php if ( empty($payments) ): ?>
  <div class="empty-state">
    <div class="e-icon"><i class="fa-solid fa-credit-card"></i></div>
    <h3><?php echo bkntc__('No payments recorded'); ?></h3>
  </div>
<?php else: ?>
  <!-- Mobile Cards View -->
  <div class="cp2-history-cards">
    <?php foreach ($payments as $payment): ?>
      <?php
      $clientTimeZoneIsOpen = Helper::getOption('client_timezone_enable', 'off') == 'on';
      $payment_date = Helper::isSaaSVersion() ? Date::dateSQL( $payment->starts_at ,false,$clientTimeZoneIsOpen ) : Date::datee( $payment->starts_at,false,$clientTimeZoneIsOpen );
      
      $is_fully_paid = $payment->paid_amount >= $payment->total_price;
      $is_partially_paid = $payment->paid_amount > 0 && $payment->paid_amount < $payment->total_price;
      
      if ($is_fully_paid) {
          $status_label = bkntc__('Paid');
          $status_class = 'badge-paid';
      } elseif ($is_partially_paid) {
          $status_label = bkntc__('Partial');
          $status_class = 'badge-partial';
      } else {
          $status_label = bkntc__('Unpaid');
          $status_class = 'badge-unpaid';
      }
      ?>
      <div class="cp2-history-card">
        <span class="badge <?php echo $status_class; ?>" style="position: absolute; top: 13px; right: 14px;">
          <?php echo $status_label; ?>
        </span>
        <div class="svc"><?php echo htmlspecialchars($payment->service_name); ?></div>
        <div class="meta"><?php echo $payment_date; ?> · <?php echo htmlspecialchars(ucfirst($payment->payment_method ?: 'Local')); ?></div>
        <div class="amt"><?php echo Helper::price($payment->paid_amount); ?> / <?php echo Helper::price($payment->total_price); ?></div>
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
          <th class="col-staff"><?php echo bkntc__('Method'); ?></th>
          <th><?php echo bkntc__('Status'); ?></th>
          <th><?php echo bkntc__('Amount Paid'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($payments as $payment): ?>
          <?php
          $clientTimeZoneIsOpen = Helper::getOption('client_timezone_enable', 'off') == 'on';
          $payment_date = Helper::isSaaSVersion() ? Date::dateSQL( $payment->starts_at ,false,$clientTimeZoneIsOpen ) : Date::datee( $payment->starts_at,false,$clientTimeZoneIsOpen );
          
          $is_fully_paid = $payment->paid_amount >= $payment->total_price;
          $is_partially_paid = $payment->paid_amount > 0 && $payment->paid_amount < $payment->total_price;
          
          if ($is_fully_paid) {
              $status_label = bkntc__('Paid');
              $status_class = 'badge-paid';
          } elseif ($is_partially_paid) {
              $status_label = bkntc__('Partial');
              $status_class = 'badge-partial';
          } else {
              $status_label = bkntc__('Unpaid');
              $status_class = 'badge-unpaid';
          }
          ?>
          <tr>
            <td><?php echo $payment_date; ?></td>
            <td style="font-weight: 600;"><?php echo htmlspecialchars($payment->service_name); ?></td>
            <td class="col-staff"><?php echo htmlspecialchars(ucfirst($payment->payment_method ?: 'Local')); ?></td>
            <td>
              <span class="badge <?php echo $status_class; ?>">
                <?php echo $status_label; ?>
              </span>
            </td>
            <td style="font-weight: 600;"><?php echo Helper::price($payment->paid_amount); ?> <?php if ($is_partially_paid) echo ' / ' . Helper::price($payment->total_price); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
