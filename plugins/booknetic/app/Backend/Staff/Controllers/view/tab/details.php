<?php

defined('ABSPATH') or die();

use BookneticApp\Backend\Staff\DTOs\Response\StaffGetResponse;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;

/**
 * @var StaffGetResponse $parameters
 */
?>

<div class="wf-staff-appt-layout" style="padding-top:0;">
    <div class="wf-staff-left-column">
        <!-- Section: Personal Details -->
        <div class="form-section">
            <div class="form-section-title"><?php echo bkntc__('Personal Details')?></div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="input_name"><?php echo bkntc__('Full Name')?> <span class="required-star">*</span></label>
                    <input type="text" data-multilang="true" data-multilang-fk="<?php echo $parameters->getId() ?>" class="form-control" id="input_name" value="<?php echo htmlspecialchars($parameters->getStaff()->getName())?>">
                </div>
                <div class="form-group col-md-6">
                    <label for="input_profession"><?php echo bkntc__('Profession')?></label>
                    <input type="text" data-multilang="true" data-multilang-fk="<?php echo $parameters->getId() ?>" class="form-control" id="input_profession" value="<?php echo htmlspecialchars($parameters->getStaff()->getProfession())?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="input_email"><?php echo bkntc__('Email')?> <span class="required-star">*</span></label>
                    <input type="text" class="form-control" id="input_email" placeholder="example@gmail.com" value="<?php echo htmlspecialchars($parameters->getStaff()->getEmail())?>" <?php echo ($parameters->getId() > 0 && $parameters->getStaff()->getWpUserId() > 0 && !Permission::isAdministrator() ? ' disabled' : '')?>>
                </div>
                <div class="form-group col-md-6">
                    <label for="input_phone"><?php echo bkntc__('Phone')?></label>
                    <input type="text" class="form-control" id="input_phone" data-country-code="<?php echo $parameters->getDefaultCountryCode() ?>" value="<?php echo htmlspecialchars($parameters->getStaff()->getPhone())?>">
                </div>
            </div>
        </div>

        <!-- Section: WordPress Login Access -->
        <?php if (Permission::isAdministrator() || Capabilities::userCan('staff_allow_to_login')) : ?>
            <div class="form-section">
                <div class="form-section-title"><?php echo bkntc__('WordPress Login Access')?></div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <?php if (Helper::isSaaSVersion()) : ?>
                            <label>&nbsp;</label>
                        <?php endif; ?>
                        <div class="form-control-checkbox">
                            <label for="input_allow_staff_to_login"><?php echo bkntc__('Allow to log in')?></label>
                            <div class="fs_onoffswitch">
                                <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_allow_staff_to_login" <?php echo ($parameters->getStaff()->getWpUserId() > 0 ? ' checked' : '')?>>
                                <label class="fs_onoffswitch-label" for="input_allow_staff_to_login"></label>
                            </div>
                        </div>
                    </div>
                    <?php if (!Helper::isSaaSVersion()): ?>
                        <div class="form-group col-md-6" data-hide="allow_staff_to_login">
                            <select class="form-control" id="input_wp_user_use_existing">
                                <option value="yes" <?php echo ($parameters->getStaff()->getWpUserId() > 0 ? ' selected' : '')?>><?php echo bkntc__('Use existing WordPress user')?></option>
                                <option value="no"><?php echo bkntc__('Create new WordPress user')?></option>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" id="input_wp_user_use_existing" value="no">
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <?php if (!Helper::isSaaSVersion()): ?>
                        <div class="form-group col-md-6" data-hide="existing_user">
                            <label for="input_wp_user"><?php echo bkntc__('WordPress user')?></label>
                            <select class="form-control" id="input_wp_user">
                                <?php foreach ($parameters->getUsers() as $user) { ?>
                                    <option value="<?php echo $user->getId()?>" <?php echo ($user->getId() === $parameters->getStaff()->getWpUserId() ? ' selected' : '')?> data-email="<?php echo htmlspecialchars($user->getEmail())?>"><?php echo htmlspecialchars($user->getName())?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <?php if ($parameters->getId() > 0): ?>
                            <div class="form-group col-md-6" data-hide="existing_user">
                                <label>&nbsp;</label>
                                <div class="form-control-checkbox">
                                    <label for="input_update_wp_user"><?php echo bkntc__('Update Wordpress User')?></label>
                                    <div class="fs_onoffswitch">
                                        <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_update_wp_user">
                                        <label class="fs_onoffswitch-label" for="input_update_wp_user"></label>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="form-group col-md-6" data-hide="create_password">
                        <label for="input_wp_user_password"><?php echo bkntc__('User password')?></label>
                        <input type="text" class="form-control" id="input_wp_user_password" placeholder="*****">
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Section: Profile Image -->
        <div class="form-section">
            <div class="form-section-title"><?php echo bkntc__('Profile Image')?></div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="input_image"><?php echo bkntc__('Image')?></label>
                    <input type="file" class="form-control" id="input_image" style="display:none;">
                    <div class="form-control" style="display:flex; justify-content:space-between; align-items:center; cursor:pointer;" onclick="document.getElementById('input_image').click()">
                        <span style="color:#94a3b8"><?php echo bkntc__('(PNG, JPG, max 800x800 to 5mb)')?></span>
                        <span style="background:#e8e7fe; color:#6366f1; font-weight:700; padding:4px 8px; border-radius:4px; font-size:11px;"><?php echo bkntc__('BROWSE')?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: Locations & Services -->
        <div class="form-section">
            <div class="form-section-title"><?php echo bkntc__('Locations & Services')?></div>
            <?php if (Capabilities::tenantCan('locations')) : ?>
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label for="input_locations"><?php echo bkntc__('Locations')?> <span class="required-star">*</span></label>
                        <select class="form-control" id="input_locations" multiple>
                            <?php foreach ($parameters->getLocations() as $location) {
                                echo '<option value="' . $location->getId() . '"' . (in_array($location->getId(), $parameters->getStaff()->getLocations()) ? ' selected' : '') .'>' . htmlspecialchars($location->getName()) . '</option>';
                            } ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="input_services"><?php echo bkntc__('Services')?></label>
                    <select class="form-control" id="input_services" multiple>
                        <?php foreach ($parameters->getServices() as $service) {
                            echo '<option value="' . $service->getId() . '"' . (in_array($service->getId(), $parameters->getSelectedServices()) ? ' selected' : '') .'>' . htmlspecialchars($service->getName()) . '</option>';
                        } ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="input_note"><?php echo bkntc__('Note')?></label>
                    <textarea id="input_note" class="form-control" rows="3" placeholder="<?php echo bkntc__('About the staff member...')?>"><?php echo htmlspecialchars($parameters->getStaff()->getAbout())?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Sidebar column: Profile Preview -->
    <div class="wf-staff-sidebar">
        <div class="wf-staff-sidebar-card" style="text-align:center;">
            <div class="wf-staff-sidebar-card-title"><?php echo bkntc__('Profile Preview')?></div>
            <div class="wf-profile-avatar-wrap">
                <?php if($parameters->getId() > 0 && !empty($parameters->getStaff()->getProfileImage())): ?>
                    <img src="<?php echo Helper::profileImage($parameters->getStaff()->getProfileImage())?>" class="wf-profile-avatar">
                <?php else: ?>
                    <span class="wf-profile-avatar-placeholder">&#128100;</span>
                <?php endif; ?>
            </div>
            <div class="wf-profile-name" style="font-weight:600; font-size:14px; margin-top:8px;"><?php echo htmlspecialchars($parameters->getStaff()->getName() ? $parameters->getStaff()->getName() : bkntc__('Staff Name'))?></div>
            <div class="wf-profile-profession" style="font-size:12px; color:#94a3b8; margin-top:2px;"><?php echo htmlspecialchars($parameters->getStaff()->getProfession() ? $parameters->getStaff()->getProfession() : bkntc__('Profession'))?></div>
        </div>
    </div>
</div>
