<?php

defined('ABSPATH') or die();

use BookneticApp\Models\Appointment;
use BookneticApp\Providers\Helpers\NotificationHelper;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Providers\Helpers\Helper;

?>
<link rel="stylesheet" type="text/css" href="<?php echo Helper::assets('css/dashboard.css', 'Dashboard')?>" />

<script type="application/javascript" src="<?php echo Helper::assets('js/dashboard.js', 'Dashboard')?>"></script>

<?php $notifications = NotificationHelper::getVisible(); ?>
<?php if (!empty($notifications)): ?>
	<div class="boostore-announcements">
		<div class="boostore-announcements-header">
			<div class="boostore-announcements-title"><?php echo bkntc__("What's New"); ?></div>
			<a href="javascript:void(0)" class="boostore-dismiss-all"><?php echo bkntc__('Dismiss all'); ?></a>
		</div>
		<div class="boostore-announcements-chips">
			<?php foreach ($notifications as $notification): ?>
				<div class="boostore-chip" data-slug="<?php echo htmlspecialchars($notification['slug']); ?>">
					<span class="boostore-chip-badge"><?php echo bkntc__('NEW'); ?></span>
					<span class="boostore-chip-name"><?php echo htmlspecialchars($notification['name']); ?></span>
					<span class="boostore-chip-close">&times;</span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>

<div class="m_header clearfix">
	<div class="m_head_title float-left"><?php echo bkntcsaas__('Dashboard')?></div>
</div>

<div id="statistic-boxes-area">
	<div class="row m-0">
		<div class="col-xl-3 col-lg-6 p-0 pr-lg-3 mb-4 mb-xl-0">
			<div class="statistic-boxes">
				<div class="box-icon-div"><img src="<?php echo Helper::icon('1.svg', 'Dashboard')?>"></div>
				<div class="box-number-div" data-stat="appointments"><?php echo Tenant::count()?></div>
				<div class="box-title-div"><?php echo bkntcsaas__('Tenants')?></div>
			</div>
		</div>
		<div class="col-xl-3 col-lg-6 p-0 pr-xl-3 mb-4 mb-xl-0">
			<div class="statistic-boxes">
				<div class="box-icon-div"><img src="<?php echo Helper::icon('2.svg', 'Dashboard')?>"></div>
				<div class="box-number-div" data-stat="duration"><?php echo Appointment::noTenant()->count()?></div>
				<div class="box-title-div"><?php echo bkntcsaas__('Appointments')?></div>
			</div>
		</div>
		<div class="col-xl-3 col-lg-6 p-0 pr-lg-3 mb-4 mb-lg-0">
			<div class="statistic-boxes">
				<div class="box-icon-div"><img src="<?php echo Helper::icon('3.svg', 'Dashboard')?>"></div>
				<div class="box-number-div" data-stat="revenue"><?php echo $parameters['this_month_earning'];?></div>
				<div class="box-title-div"><?php echo bkntcsaas__('Income of the month')?></div>
			</div>
		</div>
		<div class="col-xl-3 col-lg-6 p-0">
			<div class="statistic-boxes">
				<div class="box-icon-div"><img src="<?php echo Helper::icon('4.svg', 'Dashboard')?>"></div>
				<div class="box-number-div" data-stat="pending"><?php echo $parameters['last_month_earning'];?></div>
				<div class="box-title-div"><?php echo bkntcsaas__('Income of the previous month')?></div>
			</div>
		</div>
	</div>
</div>