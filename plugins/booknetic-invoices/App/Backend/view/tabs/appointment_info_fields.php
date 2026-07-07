<?php

defined( 'ABSPATH' ) or die();

use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Invoices\bkntc__;

if ( empty( $parameters[ 'invoices' ] ) )
{
    echo '<div class="text-secondary font-size-14 text-center">' . bkntc__( 'No invoices found' ) . '</div>';
}
else
{
    ?>
    <div class="customer-fields-area dashed-border">

        <div class="d-flex justify-content-center user_visit_card">
            <?php echo bkntc__( 'Invoices' ) ?>
        </div>

            <div class="form-row">
            <?php foreach ( $parameters[ 'invoices' ] AS $invoice ): ?>
                <div class="form-group col-md-12">
                    <a target="_blank" class="text-decoration-none text-break <?php echo ! $invoice[ 'exists' ] ? 'text-secondary' : '' ?>" href="<?php echo $invoice[ 'exists' ] ? $invoice[ 'href' ] : '#' ?>" >
                        <?php echo $invoice[ 'name' ] . ( $invoice[ 'exists' ] ? '' : ' ( Not found )' ) ?>
                    </a>
                </div>
            <?php endforeach; ?>
            </div>

        </div>

        <?php
}

?>