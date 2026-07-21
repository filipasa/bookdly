<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 */
echo $parameters['table'];
?>

<style>
  /* Hide ID column (2nd) and Staff column (5th) visually on Payments page */
  .fs_data_table_wrapper th:nth-child(2),
  .fs_data_table_wrapper td:nth-child(2),
  .fs_data_table_wrapper th:nth-child(5),
  .fs_data_table_wrapper td:nth-child(5) {
    display: none !important;
  }
</style>

<script type="application/javascript" src="<?php echo Helper::assets('js/payments.js', 'Payments')?>?v=<?php echo time(); ?>"></script>

<div id="booknetic_payment_fullpage_container" style="display: none;"></div>



