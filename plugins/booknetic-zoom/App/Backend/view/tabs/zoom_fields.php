<?php

defined( 'ABSPATH' ) or die();

use function BookneticAddon\Zoom\bkntc__;

if(isset($parameters['staff'])):

?>

<div class="form-row">
    <div class="form-group col-md-12">
        <label for="zoom_user_select"><?php echo bkntc__('Zoom user')?></label>
        <select class="form-control" id="zoom_user_select">
            <?php
            if( !empty( $parameters['staff']->getData( 'zoom_user' ) ) )
            {
                $zoomUser = json_decode( $parameters['staff']->getData( 'zoom_user' ), true );
                if( isset( $zoomUser['id'] ) && is_string( $zoomUser['id'] ) && isset( $zoomUser['name'] ) && is_string( $zoomUser['name'] ) )
                {
                    ?>
                    <option value="<?php echo htmlspecialchars($zoomUser['id'])?>"><?php echo htmlspecialchars($zoomUser['name'])?></option>
                    <?php
                }
            }
            ?>
        </select>
    </div>

</div>

<?php
    endif;
?>