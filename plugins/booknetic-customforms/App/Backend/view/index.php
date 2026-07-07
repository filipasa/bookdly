<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\Customforms\CustomFormsAddon;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;
use function BookneticAddon\Customforms\bkntc__;

/**
 * @var mixed $parameters
 */

echo $parameters['table'];
?>

<script type="application/javascript" src="<?php echo CustomFormsAddon::loadAsset('assets/backend/js/customforms.js')?>"></script>
