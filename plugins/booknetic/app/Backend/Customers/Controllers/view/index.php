<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 */
echo $parameters['table'];
?>

<style>
  /* Hide ID column (2nd) visually on Customers page */
  .fs_data_table_wrapper th:nth-child(2),
  .fs_data_table_wrapper td:nth-child(2) {
    display: none !important;
  }
</style>

<script>
    booknetic.can_delete_associated_account = <?php echo (Permission::isAdministrator() || Capabilities::userCan('customers_delete_wordpress_account')) ? 1 : 0 ?>
</script>
<script type="application/javascript" src="<?php echo Helper::assets('js/customers.js', 'Customers')?>?v=<?php echo time(); ?>"></script>
