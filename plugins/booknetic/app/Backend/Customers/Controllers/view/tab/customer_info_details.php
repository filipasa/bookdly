<?php

defined('ABSPATH') or die();

use BookneticApp\Backend\Customers\DTOs\Response\CustomerResponse;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

/**
 * @var array $parameters
 * @var CustomerResponse $customer
 */
$customer = $parameters['customer'];
$customerId = $customer->getId();

// Load the raw customer db row
$customerDb = \BookneticApp\Models\Customer::get($customerId);

// Fetch all appointments for calculations and listing
$appointments = \BookneticApp\Models\Appointment::where('customer_id', $customerId)
    ->orderBy('starts_at', 'DESC')
    ->fetchAll();

$bookingsCount = count($appointments);

// Calculate Customer Since
$customerSince = '';

// 1. Try WordPress registration date if user_id exists
$user_id = $customer->getUserId();
if ($user_id > 0) {
    $userdata = get_userdata($user_id);
    if ($userdata && !empty($userdata->user_registered)) {
        $customerSince = date('M Y', strtotime($userdata->user_registered));
    }
}

// 2. Try first appointment date if we still don't have it
if (empty($customerSince)) {
    $firstAppointment = \BookneticApp\Models\Appointment::where('customer_id', $customerId)
        ->orderBy('starts_at', 'ASC')
        ->fetch();
    if ($firstAppointment) {
        $startsAtVal = $firstAppointment->starts_at;
        if (is_numeric($startsAtVal)) {
            $customerSince = date('M Y', (int)$startsAtVal);
        } else {
            $customerSince = date('M Y', strtotime($startsAtVal));
        }
    }
}

// 3. Try created_at if it's a valid timestamp (greater than 100000000)
if (empty($customerSince)) {
    $createdAt = $customerDb ? $customerDb->created_at : '';
    if (!empty($createdAt)) {
        if (is_numeric($createdAt) && (int)$createdAt > 100000000) {
            $customerSince = date('M Y', (int)$createdAt);
        } else if (!is_numeric($createdAt)) {
            $customerSince = date('M Y', strtotime($createdAt));
        }
    }
}

// 4. Default fallback to current month/year
if (empty($customerSince)) {
    $customerSince = date('M Y');
}

// Calculate Total Spent
$totalSpent = 0;
foreach ($appointments as $appointment) {
    $totalSpent += $appointment->paid_amount;
}

// Calculate Favourite Service
$serviceCounts = [];
foreach ($appointments as $appointment) {
    $serviceId = $appointment->service_id;
    if ($serviceId > 0) {
        $serviceCounts[$serviceId] = ($serviceCounts[$serviceId] ?? 0) + 1;
    }
}

$favouriteService = bkntc__('N/A');
if (!empty($serviceCounts)) {
    arsort($serviceCounts);
    $favouriteServiceId = key($serviceCounts);
    $serviceObj = \BookneticApp\Models\Service::get($favouriteServiceId);
    if ($serviceObj) {
        $favouriteService = $serviceObj->name;
    }
}
?>
<div class="modal_payment">
    <!-- Header: Name, Edit & New Appointment buttons, Subtitle -->
    <div class="modal_payment-header d-flex flex-column pb-4 border-bottom">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="modal_payment-profile d-flex align-items-center mb-2">
                <?php 
                $profileImg = $customerDb && !empty($customerDb->profile_image) 
                    ? Helper::profileImage($customerDb->profile_image, 'Customers') 
                    : 'https://bookdly.co.uk/wp-content/plugins/booknetic/app/Backend/Customers/assets/images/no-photo.png';
                ?>
                <img src="<?php echo $profileImg; ?>" alt="" style="width: 54px; height: 54px; border-radius: 50%; margin-right: 12px; object-fit: cover;">
                <span class="h5 mb-0 font-weight-bold" style="color: #2D3748;"><?php echo htmlspecialchars($customer->getFirstName() . ' ' . $customer->getLastName()); ?></span>
            </div>
            <div class="mb-2 d-flex align-items-center">
                <button type="button" class="btn btn-outline-secondary btn-md font-weight-bold" id="bkntc_cust_info_edit_btn" data-customer-id="<?php echo $customerId; ?>" style="border-radius: 6px; padding: 6px 14px;">
                    <i class="fa fa-edit mr-1"></i> <?php echo bkntc__('Edit') ?>
                </button>
                <button type="button" class="btn btn-primary btn-md font-weight-bold ml-2" id="bkntc_cust_info_new_app_btn" data-customer-id="<?php echo $customerId; ?>" style="border-radius: 6px; padding: 6px 14px; background-color: #6C5CE7; border-color: #6C5CE7;">
                    <i class="fa fa-plus mr-1"></i> <?php echo bkntc__('New Appointment') ?>
                </button>
            </div>
        </div>
        <div class="text-muted mt-1 font-size-14">
            <?php echo sprintf(bkntc__('Customer since %s · %d bookings'), $customerSince, $bookingsCount); ?>
        </div>
    </div>

    <!-- Quick info / Stats grids -->
    <div class="row mt-4">
        <div class="col-sm-6 mb-3">
            <div class="p-3" style="background: #F8FAFC; border-radius: 8px; border: 1px solid #E2E8F0; height: 100%;">
                <div class="mb-2">
                    <strong style="color: #4A5568;"><?php echo bkntc__('Email') ?>:</strong>
                    <span class="text-break d-block mt-1" style="color: #1A202C;"><?php echo $customer->getEmail() ?: bkntc__('N/A'); ?></span>
                </div>
                <div>
                    <strong style="color: #4A5568;"><?php echo bkntc__('Phone') ?>:</strong>
                    <span class="d-block mt-1" style="color: #1A202C;"><?php echo $customer->getPhoneNumber() ?: bkntc__('N/A'); ?></span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 mb-3">
            <div class="p-3" style="background: #F8FAFC; border-radius: 8px; border: 1px solid #E2E8F0; height: 100%;">
                <div class="mb-2">
                    <strong style="color: #4A5568;"><?php echo bkntc__('Total Spent') ?>:</strong>
                    <span class="d-block mt-1 font-weight-bold" style="color: #2D3748; font-size: 16px;"><?php echo Helper::price($totalSpent); ?></span>
                </div>
                <div>
                    <strong style="color: #4A5568;"><?php echo bkntc__('Favourite Service') ?>:</strong>
                    <span class="d-block mt-1" style="color: #1A202C;"><?php echo htmlspecialchars($favouriteService); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Note field if exists -->
    <?php if (!empty($customer->getNotes())): ?>
        <div class="row mt-2">
            <div class="col-12 mb-3">
                <div class="p-3" style="background: #FFFBEB; border-radius: 8px; border: 1px solid #FDE68A;">
                    <strong style="color: #92400E;"><?php echo bkntc__('Note') ?>:</strong>
                    <p class="mb-0 mt-1" style="color: #78350F; white-space: pre-wrap; font-size: 13px;"><?php echo htmlspecialchars($customer->getNotes()); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Previous Appointments Table -->
    <div class="mt-4">
        <h6 class="font-weight-bold mb-3" style="color: #2D3748;"><?php echo bkntc__('Previous Appointments') ?></h6>
        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
            <table class="table table-hover table-striped mb-0" style="border: 1px solid #E2E8F0;">
                <thead style="background: #EDF2F7; position: sticky; top: 0; z-index: 1;">
                    <tr>
                        <th style="font-size: 12px; color: #4A5568; font-weight: 700; border-bottom: 2px solid #CBD5E0;"><?php echo bkntc__('Date') ?></th>
                        <th style="font-size: 12px; color: #4A5568; font-weight: 700; border-bottom: 2px solid #CBD5E0;"><?php echo bkntc__('Service') ?></th>
                        <th style="font-size: 12px; color: #4A5568; font-weight: 700; border-bottom: 2px solid #CBD5E0;"><?php echo bkntc__('Staff') ?></th>
                        <th style="font-size: 12px; color: #4A5568; font-weight: 700; border-bottom: 2px solid #CBD5E0;"><?php echo bkntc__('Status') ?></th>
                        <th style="font-size: 12px; color: #4A5568; font-weight: 700; border-bottom: 2px solid #CBD5E0;"><?php echo bkntc__('Paid') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3" style="font-size: 13px;"><?php echo bkntc__('No appointments found.') ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($appointments as $appointment):
                            $service = \BookneticApp\Models\Service::get($appointment->service_id);
                            $staff = \BookneticApp\Models\Staff::get($appointment->staff_id);
                            ?>
                            <tr>
                                <td style="font-size: 13px; color: #2D3748; white-space: nowrap; vertical-align: middle;">
                                    <?php echo Date::dateTime($appointment->starts_at); ?>
                                </td>
                                <td style="font-size: 13px; color: #2D3748; vertical-align: middle;">
                                    <?php echo $service ? htmlspecialchars($service->name) : bkntc__('N/A'); ?>
                                </td>
                                <td style="font-size: 13px; color: #2D3748; vertical-align: middle;">
                                    <?php echo $staff ? htmlspecialchars($staff->name) : bkntc__('N/A'); ?>
                                </td>
                                <td style="font-size: 13px; vertical-align: middle;">
                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($appointment->status_color); ?>; color: #ffffff; padding: 4px 8px; border-radius: 4px; font-weight: 600;">
                                        <?php echo htmlspecialchars($appointment->status_name); ?>
                                    </span>
                                </td>
                                <td style="font-size: 13px; color: #2D3748; font-weight: 600; vertical-align: middle; white-space: nowrap;">
                                    <?php echo Helper::price($appointment->paid_amount); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
(function($) {
    "use strict";

    $(document).ready(function() {
        // Edit Customer Modal Trigger
        $('#bkntc_cust_info_edit_btn').off('click').on('click', function() {
            var customerId = $(this).attr('data-customer-id');
            // Hide the parent modal (Customer Info)
            $(this).closest('.modal').modal('hide');
            // Load Edit Customer modal
            setTimeout(function() {
                booknetic.loadModal('customers.add_new', {'id': customerId});
            }, 300);
        });

        // New Appointment Modal Trigger
        $('#bkntc_cust_info_new_app_btn').off('click').on('click', function() {
            var customerId = $(this).attr('data-customer-id');
            // Hide the parent modal (Customer Info)
            $(this).closest('.modal').modal('hide');
            // Load New Appointment modal with pre-selected customer
            setTimeout(function() {
                booknetic.loadModal('appointments.add_new', {'customer_id': customerId});
            }, 300);
        });
    });
})(jQuery);
</script>
