<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\Invoices\InvoicesAddon;

echo $parameters['table'];
?>
<script type="text/javascript" src="<?php echo InvoicesAddon::loadAsset('assets/backend/js/invoices.js')?>"></script>