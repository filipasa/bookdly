<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 */
echo $parameters['table'];
?>

<link rel="stylesheet" type="text/css" href="<?php echo Helper::assets('css/appointments.css', 'Appointments')?>" />
<link rel="stylesheet" href="<?php echo Helper::assets('css/info.css', 'Customers')?>">
<script src='<?php echo Helper::assets('js/appointment.js', 'Appointments')?>?v=<?php echo time(); ?>'></script>

<div id="booknetic_appointment_fullpage_container" style="display: none;"></div>

<?php if (isset($_GET['open_appointment'])): ?>
<style id="wf-hide-appointments-style">
  .data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap {
      display: none !important;
  }
</style>
<?php endif; ?>

<div class="fs-popover fs-popover-customers" id="customers-list-popover">
	<div class="fs-popover-title">
		<span><?php echo bkntc__('Customers')?></span>
		<img src="<?php echo Helper::icon('cross.svg')?>" class="close-popover-btn">
	</div>
	<div class="fs-popover-content">

	</div>
</div>
