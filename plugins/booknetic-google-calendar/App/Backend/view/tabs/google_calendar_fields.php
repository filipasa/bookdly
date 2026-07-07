<?php

defined( 'ABSPATH' ) or die();

use function BookneticAddon\Googlecalendar\bkntc__;

/**
 * @var mixed $parameters
 */

if(isset($parameters['staff'])):

?>

<div class="form-row">
    <div class="form-group col-md-12">
        <label for="google_calendar_select"><?php echo bkntc__('Google calendar')?></label>

        <div class="input-group">

            <select class="form-control" name="google_calendar_id" id="google_calendar_select" <?php echo (empty( $parameters['staff']->getData( 'google_access_token' ) ) ? 'disabled' : '') ?>>
                <?php
                if( !empty( $parameters['staff']->getData( 'google_calendar_id' ) ) )
                {
                    ?>
                    <option value="<?php echo htmlspecialchars($parameters['staff']->getData( 'google_calendar_id' ))?>"><?php echo htmlspecialchars($parameters['staff']->getData( 'google_calendar_id' ))?></option>
                    <?php
                }
                ?>
            </select>

            <div class="input-group-append">
                <button type="button" class="btn btn-lg btn-primary <?php echo (empty( $parameters['staff']->getData( 'google_access_token' ) ) ? '' : 'hidden') ?>" id="login_google_account">
                    <div class="login_google_account_container">
                        <img src="<?php echo \BookneticAddon\Googlecalendar\GoogleCalendarAddon::loadAsset('assets/icons/icons8-google-48.png') ?>" alt="">
                        <span><?php echo __('GOOGLE SIGN IN')?></span>
                    </div>
                </button>
                <button type="button" class="btn btn-lg btn-danger <?php echo (!empty( $parameters['staff']->getData( 'google_access_token' ) ) ? '' : 'hidden') ?>" id="logout_google_account"><?php echo __('GOOGLE SIGN OUT')?></button>
            </div>

        </div>

    </div>

</div>

<?php endif; ?>