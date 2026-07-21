<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

/**
 * @var array $parameters
 */
?>

<div class="wf-info-card mb-4">
    <div class="wf-card-title">
        <i class="fa fa-calendar-alt text-primary mr-2"></i>
        <?php echo bkntc__('Booking Info')?>
    </div>
    <div class="wf-info-grid">
        <div class="wf-info-cell">
            <div class="wf-lbl"><?php echo bkntc__('Location')?></div>
            <div class="wf-val"><?php echo htmlspecialchars($parameters['info']['location_name'])?></div>
        </div>
        <div class="wf-info-cell">
            <div class="wf-lbl"><?php echo bkntc__('Service')?></div>
            <div class="wf-val"><?php echo htmlspecialchars($parameters['info']['service_name'])?></div>
        </div>
        <div class="wf-info-cell">
            <div class="wf-lbl"><?php echo bkntc__('Date, time')?></div>
            <div class="wf-val"><?php echo ($parameters['info']['ends_at'] - $parameters['info']['starts_at']) >= 24 * 60 * 60 ? Date::datee($parameters['info']['starts_at']) : (Date::dateTime($parameters['info']['starts_at']) . ' - ' . Date::time($parameters['info']['ends_at']))?></div>
        </div>
    </div>
    <?php if (!empty($parameters['info']->note)): ?>
    <div class="wf-info-note pt-3 mt-3 border-top">
        <div class="wf-lbl"><?php echo bkntc__('Note')?></div>
        <div class="wf-val text-muted"><pre id="pre-note-text" class="m-0"><?php echo htmlspecialchars($parameters['info']->note)?></pre></div>
    </div>
    <?php endif; ?>
</div>

<div class="wf-info-card mb-4">
    <div class="wf-card-title">
        <i class="fa fa-users text-primary mr-2"></i>
        <?php echo bkntc__('Participants')?>
    </div>
    <div class="row m-0">
        <div class="col-md-6 p-3 wf-participant-cell border-right">
            <div class="wf-lbl mb-2"><?php echo bkntc__('Staff')?></div>
            <div><?php echo Helper::profileCard($parameters['info']['staff_name'], $parameters['info']['staff_profile_image'], $parameters['info']['staff_email'], 'Staff')?></div>
        </div>
        <div class="col-md-6 p-3 wf-participant-cell">
            <div class="wf-lbl mb-2"><?php echo bkntc__('Customer')?></div>
            <div class="fs_data_table_wrapper">
                <?php
                $statuses = Helper::getAppointmentStatuses();
                $info = $parameters['info'];
                $status = $statuses[$info['status']];
                echo '<div class="per-customer-div cursor-pointer d-flex align-items-center justify-content-between" data-load-modal="customers.info" data-parameter-id="'.(int)$info['customer_id'].'">';
                echo Helper::profileCard($info['customer_first_name'] . ' ' . $info['customer_last_name'], $info['customer_profile_image'], $info['customer_email'], 'Customers');
                echo '<div class="d-flex align-items-center">';
                echo '<div class="appointment-status-badge ml-3 px-3 py-1 rounded-pill font-size-12 font-weight-600" style="background-color: ' . htmlspecialchars($status[ 'color' ]) . '2b; color: ' . htmlspecialchars($status[ 'color' ]) . ';">
                        <i class="' . htmlspecialchars($status[ 'icon' ]) .  ' mr-1"></i> ' . htmlspecialchars($status['title']) . '
                      </div>';
                echo '<span class="num_of_customers_span ml-3 badge badge-light border py-1 px-2"><i class="fa fa-user"></i> ' . (int)$info['weight'] . '</span>';
                echo '</div>';
                echo '</div>';
                ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($parameters['paymentGateways'])): ?>
<div class="form-row">
    <div class="form-group col-md-12">
        <label><?php echo bkntc__('Create Payment Link')?> </label>

        <div class="">
            <div class="form-row ">
                <div class="col-md-6">
                    <div class="input-group">
                        <select class="form-control" id="appointment_info_payment_gateway">
                            <?php foreach ($parameters['paymentGateways'] as $paymentGateway): ?>
                                <?php
                    $title = PaymentGatewayService::find($paymentGateway)->getTitle();

                                if (strstr(PaymentGatewayService::find($paymentGateway)->getSlug(), 'split') !== false) {
                                    $title .= ' ' . \bkntc__('(with Commission)');
                                }
                                ?>
                                <option value="<?php echo $paymentGateway ?>"><?php echo $title ?></option>
                            <?php endforeach; ?>
                        </select>

                    </div>
                </div>
                <div class="col-md-6 d-flex">
                <span>
                    <button data-appointment-id="<?php echo $parameters['info']['id'] ?>" id="bkntc_create_payment_link" class="btn btn-lg btn-primary"  type="button" >
                        <?php echo bkntc__('Create Link') ?>
                    </button>
                </span>
                </div>
            </div>

        </div>
    </div>
</div>
<div style="width: 100%; display: none" class="bkntc_payment_link_container" >
    <div class="payment_link" style="padding:10px;overflow-wrap: anywhere;background-color: #f3f3f3">
    </div>
    <button class="btn btn-primary copy_url_payment_link" type="button" style=""><?php echo bkntc__('COPY URL') ?></button>
</div>
<?php endif; ?>