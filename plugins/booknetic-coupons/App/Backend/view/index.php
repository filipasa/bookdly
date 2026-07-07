<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\Coupons\CouponsAddon;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;
use function BookneticAddon\Coupons\bkntc__;

echo $parameters['table'];
?>
<link rel="stylesheet" href="<?php echo CouponsAddon::loadAsset('assets/backend/css/info.css' )?>">
<script type="text/javascript" src="<?php echo CouponsAddon::loadAsset('assets/backend/js/coupons.js')?>"></script>
<script type="text/javascript" src="<?php echo CouponsAddon::loadAsset('assets/backend/js/info.js' )?>"></script>