<?php

defined( 'ABSPATH' ) or die();

use \BookneticAddon\Tax\TaxAddon;

echo $parameters['table_html'];
?>
<script type="text/javascript" src="<?php echo TaxAddon::loadAsset( 'assets/backend/js/taxes.js' )?>"></script>
