<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Core\Permission;
use BookneticSaaS\Providers\UI\MenuUI;
use BookneticSaaS\Providers\Core\Route;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Helper as RegularHelper;
use BookneticApp\Providers\UI\Abstracts\AbstractMenuUI;

$localization = [
    // Appearance
    'are_you_sure'					=> bkntcsaas__('Are you sure?'),
    'deleted'					    => bkntcsaas__('Deleted!'),

    // Appointments
    'select'						=> bkntcsaas__('Select...'),
    'firstly_select_service'		=> bkntcsaas__('Please firstly choose a service!'),
    'fill_all_required'				=> bkntcsaas__('Please fill in all required fields correctly!'),
    'timeslot_is_not_available'		=> bkntcsaas__('This time slot is not available!'),

    // Base
    'are_you_sure_want_to_delete'	=> bkntcsaas__('Are you sure you want to delete?'),
    'rows_deleted'					=> bkntcsaas__('Rows deleted!'),
    'delete'                        => bkntcsaas__('DELETE'),
    'cancel'                        => bkntcsaas__('CANCEL'),
    'dear_user'                     => bkntcsaas__('Dear user'),

    // calendar
    'group_appointment'				=> bkntcsaas__('Group appointment'),

    // Customforms
    'select_services'				=> bkntcsaas__('Select services...'),
    'changes_saved'					=> bkntcsaas__('Changes has been saved!'),

    // Dashboard
    'loading'					    => bkntcsaas__('Loading...'),

    // Notifications
    'fill_form_correctly'			=> bkntcsaas__('Fill the form correctly!'),
    'saved_successfully'			=> bkntcsaas__('Saved succesfully!'),
    'type_email'   					=> bkntcsaas__('Please type email!'),
    'type_phone_number'   			=> bkntcsaas__('Please type phone number!'),

    // Services
    'delete_service_extra'			=> bkntcsaas__('Are you sure that you want to delete this service extra?'),
    'no_more_staff_exist'			=> bkntcsaas__('No more Staff exists for select!'),
    'delete_special_day'			=> bkntcsaas__('Are you sure to delete this special day?'),
    'times_per_month'				=> bkntcsaas__('time(s) per month'),
    'times_per_week'				=> bkntcsaas__('time(s) per week'),
    'every_n_day'					=> bkntcsaas__('Every n day(s)'),
    'delete_service'				=> bkntcsaas__('Are you sure you want to delete this service?'),
    'delete_category'				=> bkntcsaas__('Are you sure you want to delete this category?'),
    'category_name'					=> bkntcsaas__('Category name'),

    // months
    'January'               		=> bkntcsaas__('January'),
    'February'              		=> bkntcsaas__('February'),
    'March'                 		=> bkntcsaas__('March'),
    'April'                 		=> bkntcsaas__('April'),
    'May'                   		=> bkntcsaas__('May'),
    'June'                  		=> bkntcsaas__('June'),
    'July'                  		=> bkntcsaas__('July'),
    'August'                		=> bkntcsaas__('August'),
    'September'             		=> bkntcsaas__('September'),
    'October'               		=> bkntcsaas__('October'),
    'November'              		=> bkntcsaas__('November'),
    'December'              		=> bkntcsaas__('December'),

    //days of week
    'Mon'                   		=> bkntcsaas__('Mon'),
    'Tue'                   		=> bkntcsaas__('Tue'),
    'Wed'                   		=> bkntcsaas__('Wed'),
    'Thu'                   		=> bkntcsaas__('Thu'),
    'Fri'                   		=> bkntcsaas__('Fri'),
    'Sat'                   		=> bkntcsaas__('Sat'),
    'Sun'                   		=> bkntcsaas__('Sun'),

    'session_has_expired'           => bkntcsaas__('Your session has expired. Please refresh the page and try again.'),
    'join_beta' => bkntcsaas__('Congratulations, you are a beta user!')
];

$localization = apply_filters('bkntc_localization', $localization);

?>
<!DOCTYPE html>
<html <?php echo is_rtl() ? 'dir="rtl"' : ''?>>
<head>
    <title>Booknetic</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css?family=Poppins:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css?ver=5.0.2" type="text/css">

    <link rel="stylesheet" href="<?php echo Helper::assets('css/bootstrap.min.css')?>" type="text/css">

    <link rel="stylesheet" href="<?php echo Helper::assets('css/main.css')?>" type="text/css">
    <link rel="stylesheet" href="<?php echo Helper::assets('css/animate.css')?>" type="text/css">
    <link rel="stylesheet" href="<?php echo Helper::assets('css/select2.min.css')?>" type="text/css">
    <link rel="stylesheet" href="<?php echo Helper::assets('css/select2-bootstrap.css')?>" type="text/css">
    <link rel="stylesheet" href="<?php echo Helper::assets('css/bootstrap-datepicker.css')?>" type="text/css">
    <link rel="shortcut icon" href="<?php echo \BookneticApp\Providers\Helpers\Helper::profileImage(\BookneticApp\Providers\Helpers\Helper::getOption('whitelabel_logo_sm', 'logo-sm', false), 'Base')?>">


    <script type="application/javascript" src="<?php echo Helper::assets('js/jquery-3.3.1.min.js')?>"></script>
    <script type="application/javascript" src="<?php echo Helper::assets('js/popper.min.js')?>"></script>
    <script type="application/javascript" src="<?php echo Helper::assets('js/bootstrap.min.js')?>"></script>
    <script type="application/javascript" src="<?php echo Helper::assets('js/select2.min.js')?>"></script>
    <script type="application/javascript" src="<?php echo Helper::assets('js/jquery-ui.js')?>"></script>
    <script type="application/javascript" src="<?php echo Helper::assets('js/jquery.ui.touch-punch.min.js')?>"></script>
    <script type="application/javascript" src="<?php echo Helper::assets('js/bootstrap-datepicker.min.js')?>"></script>
    <script type="application/javascript" src="<?php echo Helper::assets('js/jquery.nicescroll.min.js')?>"></script>
    <script type="application/javascript" src="<?php echo \BookneticApp\Providers\Helpers\Helper::assets('js/generic-table.js') ?>"></script>

    <script>
        const BACKEND_SLUG = 'booknetic-saas';
    </script>

    <script src="<?php echo RegularHelper::assets('js/common.js')?>"></script>
    <script src="<?php echo Helper::assets('js/booknetic.js')?>"></script>

    <script>
        var ajaxurl			    =	'?page=<?php echo \BookneticSaaS\Providers\Core\Backend::MENU_SLUG?>&ajax=1',
            currentModule	    =	"<?php echo htmlspecialchars(Route::getCurrentModule())?>",
            assetsUrl		    =	"<?php echo Helper::assets('')?>",
            frontendAssetsUrl	=	"<?php echo Helper::assets('', 'front-end')?>",
            weekStartsOn	    =	"<?php echo Helper::getOption('week_starts_on', 'sunday') == 'monday' ? 'monday' : 'sunday'?>",
            dateFormat  	    =	"<?php echo htmlspecialchars(Helper::getOption('date_format', 'Y-m-d'))?>",
            localization	    =   <?php echo json_encode($localization)?>;
    </script>

</head>
<body class="<?php echo is_rtl() ? 'rtl ' : ''?>minimized_left_menu-">
<?php $changeLogsUrl = Helper::showChangelogs();
if (!empty($changeLogsUrl)): ?>
    <!-- Changlogs popup after plugin updated -->
    <link rel="stylesheet" href="<?php echo Helper::assets('css/changelogs_popup.css')?>">
    <script type="application/javascript" src="<?php echo Helper::assets('js/changelogs_popup.js'); ?>"></script>
    <div id="changelogsPopup" class="changelogs-popup-container">
        <div class="changelogs-popup">
            <div id="changelogsPopupClose" class="changelogs-popup-close">
                <i class="fas fa-times"></i>
            </div>
            <iframe src="<?php echo $changeLogsUrl; ?>"></iframe>
        </div>
    </div>
<?php endif; ?>

<div id="booknetic_progress" class="booknetic_progress_waiting booknetic_progress_done"><dt></dt><dd></dd></div>

<div class="left_side_menu">

    <div class="l_m_head">
        <img src="<?php echo Helper::assets('images/logo-white.svg')?>" class="head_logo_xl">
        <img src="<?php echo Helper::assets('images/logo-sm.svg')?>" class="head_logo_sm">
    </div>

    <?php if (MenuUI::isset('boostore', AbstractMenuUI::MENU_TYPE_BOOSTORE)): ?>
        <?php $boo = MenuUI::get('boostore', AbstractMenuUI::MENU_TYPE_BOOSTORE); ?>
        <div class="boostore-button-container">
            <a href="<?php echo $boo->getLink(); ?>" class="boostore-button-body">
                <div class="boostore-button-text">
                    <?php echo $boo->getTitle() ?>
                </div>
                <div class="boostore-button-icon">
                    <img src="<?php echo $boo->getIcon() ?>" alt="">
                </div>
            </a>
        </div>
    <?php endif; ?>

    <ul class="l_m_nav">
        <?php foreach (MenuUI::getItems(AbstractMenuUI::MENU_TYPE_LEFT) as $menu) { ?>
            <li class="l_m_nav_item
                <?php echo $menu->isActive() ? 'active_menu' : ''; ?>
                <?php echo $hasActiveChild = array_reduce($menu->getSubItems(), fn ($carry, $sub) => $carry || $sub->isActive()) ? 'has_active_child' : ''?>
                <?php echo(! empty($menu->getSubItems()) ? ' is_parent" data-id="' . $menu->getSlug() : ''); ?>"
            >
                <a href="<?php echo $menu->getLink(); ?>" class="l_m_nav_item_link">
                    <i class="l_m_nav_item_icon <?php echo $menu->getIcon(); ?>"></i>
                    <span class="l_m_nav_item_text"><?php echo $menu->getTitle(); ?></span>
                    <?php if (! empty($menu->getSubItems())): ?>
                        <i class="l_m_nav_item_icon is_collapse_icon fa fa-chevron-<?php echo $hasActiveChild ? 'down' : 'up' ?>"></i>
                    <?php endif; ?>
                </a>
            </li>
            <?php if (! empty($menu->getSubItems())): ?>
                <?php foreach ($menu->getSubItems() as $submenu): ?>
                    <li class="l_m_nav_item <?php echo $submenu->isActive() ? 'active_menu' : ''; ?> is_sub" data-parent-id="<?php echo $menu->getSlug(); ?>">
                        <a href="<?php echo $submenu->getLink(); ?>" class="l_m_nav_item_link">
                            <i class="l_m_nav_item_icon <?php echo $submenu->getIcon(); ?>"></i>
                            <span class="l_m_nav_item_text"><?php echo $submenu->getTitle(); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php } ?>



        <li class="l_m_nav_item d-md-none">
            <a href="index.php" class="l_m_nav_item_link">
                <i class="l_m_nav_item_icon fab fa-wordpress"></i>
                <span class="l_m_nav_item_text"><?php echo bkntcsaas__('Back to WordPress')?></span>
            </a>
        </li>

    </ul>

</div>

<div class="top_side_menu">
    <div class="t_m_left">
        <?php foreach (MenuUI::getItems(AbstractMenuUI::MENU_TYPE_TOP_LEFT) as $menu) { ?>
            <a class="btn btn-default btn-lg d-md-inline-block d-none" href="<?php echo $menu->getLink(); ?>"><i class="<?php echo $menu->getIcon(); ?> pr-2"></i>
                <span><?php echo $menu->getTitle(); ?></span>
            </a>
        <?php } ?>

        <button class="btn btn-default btn-lg d-md-none" type="button" id="open_menu_bar"><i class="fa fa-bars"></i></button>
    </div>
    <div class="t_m_right">

        <?php if (Permission::isSuperAdministrator()): ?>
            <div class="booknetic_join_beta_modal" style="display: none">
                <div class="booknetic_join_beta_modal_container">
                    <div class="booknetic_join_beta_modal_top">
                        <div class="booknetic_join_beta_modal_top_left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 16 16" fill="none">
                                <g clip-path="url(#clip0_2320_15084)">
                                    <path d="M2.6666 14.5449C3.06832 14.6666 3.61091 14.6666 4.53325 14.6666H11.4666C12.3889 14.6666 12.9315 14.6666 13.3332 14.5449M2.6666 14.5449C2.58047 14.5188 2.50081 14.4871 2.42527 14.4487C2.04895 14.2569 1.74299 13.951 1.55124 13.5746C1.33325 13.1468 1.33325 12.5868 1.33325 11.4666V4.53331C1.33325 3.41321 1.33325 2.85316 1.55124 2.42533C1.74299 2.04901 2.04895 1.74305 2.42527 1.5513C2.85309 1.33331 3.41315 1.33331 4.53325 1.33331H11.4666C12.5867 1.33331 13.1467 1.33331 13.5746 1.5513C13.9509 1.74305 14.2569 2.04901 14.4486 2.42533C14.6666 2.85316 14.6666 3.41321 14.6666 4.53331V11.4666C14.6666 12.5868 14.6666 13.1468 14.4486 13.5746C14.2569 13.951 13.9509 14.2569 13.5746 14.4487C13.499 14.4871 13.4194 14.5188 13.3332 14.5449M2.6666 14.5449C2.66682 14.0054 2.67006 13.7199 2.71782 13.4797C2.92824 12.4219 3.75517 11.595 4.81301 11.3846C5.07061 11.3333 5.38038 11.3333 5.99992 11.3333H9.99992C10.6195 11.3333 10.9292 11.3333 11.1868 11.3846C12.2447 11.595 13.0716 12.4219 13.282 13.4797C13.3298 13.7199 13.333 14.0054 13.3332 14.5449M10.6666 6.33331C10.6666 7.80607 9.47268 8.99998 7.99992 8.99998C6.52716 8.99998 5.33325 7.80607 5.33325 6.33331C5.33325 4.86055 6.52716 3.66665 7.99992 3.66665C9.47268 3.66665 10.6666 4.86055 10.6666 6.33331Z" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
                                </g>
                                <defs>
                                    <clipPath id="clip0_2320_15084">
                                        <rect width="16" height="16" fill="white"/>
                                    </clipPath>
                                </defs>
                            </svg>
                            <p class="booknetic_join_beta_modal_title"><?php echo bkntcsaas__("Beta user request confirmation") ?></p>
                        </div>
                        <div class="booknetic_join_beta_modal_top_right">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M18 6L6 18M6 6L18 18" stroke="#14151A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="booknetic_join_beta_modal_body">
                        <div class="booknetic_join_beta_modal_body_content">
                            <p><?php echo bkntcsaas__('Thank you for joining our Beta program! Your participation is invaluable to us and helps improve our product. 🚀') ?></p>
                            <p><span class="booknetic_bold"><?php echo bkntcsaas__('Why use the Beta?') ?></span></p>
                            <ul>
                                <li><?php echo bkntcsaas__('For You: Get early access to new features and enhancements.') ?></li>
                                <li><?php echo bkntcsaas__('For Us: Your feedback helps us refine and enhance the plugin.') ?></li>
                            </ul>
                            <p><?php echo bkntcsaas__('As a valued member of our Beta Program, you have the unique opportunity to utilize Booknetic Beta in a second domain exclusively for staging or testing purposes. This benefit is currently available only to our Beta users, empowering you to:') ?></p>
                            <ol>
                                <li><?php echo bkntcsaas__('Safely Experiment: Test new features and configurations in a controlled staging environment without affecting your main website.') ?></li>
                                <li><?php echo bkntcsaas__('Provide Feedback: Your insights are crucial. Directly influence the development of Booknetic by sharing your experiences and suggestions.') ?></li>
                            </ol>
                            <p>
                                <span class="booknetic_bold"><?php echo bkntcsaas__('Important Note:') ?></span>
                                <?php echo bkntcsaas__(' We recommend using the Beta in a staging environment (subdomain) to avoid any oversights. If needed, you can request direct support. We’ll allow Beta usage on your staging subdomain.') ?>
                            </p>
                            <p><?php echo bkntcsaas__('Remember, the staging environment is a mirror of your production site, allowing you to assess the impact of updates in real-time without any risk to your live operations.') ?></p>
                        </div>
                    </div>
                    <div class="booknetic_join_beta_modal_bottom">
                        <div class="booknetic_join_beta_modal_bottom_left">
                            <label class="d-flex align-items-center m-0">
                                <input type="checkbox" class="accept_terms">
                                <p class="m-0 ml-2"><?php echo bkntcsaas__("Accept outlined") ?></>
                            </label>
                        </div>
                        <div class="booknetic_join_beta_modal_bottom_right">
                            <button class="booknetic_cancel"><?php echo bkntcsaas__('Cancel') ?></button>
                            <button class="booknetic_request"><?php echo bkntcsaas__('Request') ?></button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="user_visit_card">
            <div class="circle_image">
                <img src="<?php echo get_avatar_url(get_current_user_id())?>">
            </div>
            <div class="user_visit_details" data-toggle="dropdown" aria-haspopup="false" aria-expanded="false" onclick="document.getElementsByClassName( 'booknetic_help_center_dropdown' )[ 0 ].style.display = 'none'">
                <span><?php echo bkntcsaas__('Hello') ?>, <?php echo htmlspecialchars(wp_get_current_user()->display_name)?> <i class="fa fa-angle-down"></i></span>
            </div>
            <div class="dropdown-menu dropdown-menu-right row-actions-area">
                <?php foreach (MenuUI::get(AbstractMenuUI::MENU_TYPE_TOP_RIGHT) as $menu) { ?>
                    <a href="<?php echo $menu->getLink()?>" class="dropdown-item info_action_btn"><i class="<?php echo $menu->getIcon()?>"></i> <?php echo $menu->getName()?></a>
                <?php } ?>

                <a href="<?php echo wp_logout_url(home_url()); ?>" class="dropdown-item "><i class="fa fa-sign-out-alt"></i> <?php echo bkntcsaas__('Log out')?></a>
            </div>
        </div>
    </div>
</div>

<div class="main_wrapper">
    <?php echo RegularHelper::renderView('Base.view.addon_warnings'); ?>

    <?php

    if (isset($childViewFile) && file_exists($childViewFile)) {
        require_once $childViewFile;
    }

?>

</div>

</body>
</html>