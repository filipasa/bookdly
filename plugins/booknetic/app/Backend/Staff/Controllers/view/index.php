<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Helper;

/**
 * @var array $parameters
 * $parameters['staff'] – array of staff rows
 * $parameters['edit']  – int, staff id to open on load (0 = none)
 */

$staffList  = $parameters['staff'] ?? [];
$canAdd     = Permission::isAdministrator() || Capabilities::userCan('staff_add');
$canDelete  = Permission::isAdministrator() || Capabilities::userCan('staff_delete');

$svgMail    = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>';
$svgPhone   = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 0 1 2-2h3.28a1 1 0 0 1 .948.684l1.498 4.493a1 1 0 0 1-.502 1.21l-2.257 1.13a11.042 11.042 0 0 0 5.516 5.516l1.13-2.257a1 1 0 0 1 1.21-.502l4.493 1.498a1 1 0 0 1 .684.949V19a2 2 0 0 1-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>';
$svgUser    = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
$svgEdit    = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
$svgDupe    = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="13" height="13" x="9" y="9" rx="2" ry="2"/><path stroke-linecap="round" d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
$svgShare   = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path stroke-linecap="round" d="m8.59 13.51 6.83 3.98M15.41 6.51l-6.82 3.98"/></svg>';
$svgTrash   = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path stroke-linecap="round" d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>';
$svgSearch  = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>';
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/bkc-cards.css') ?>">
<link rel="stylesheet" href="<?php echo Helper::assets('css/intlTelInput.min.css', 'front-end')?>">
<link rel="stylesheet" href="<?php echo Helper::assets('css/add_new.css', 'Staff')?>">
<link rel="stylesheet" href="<?php echo Helper::assets('css/bootstrap-year-calendar.min.css')?>">
<script type="application/javascript" src="<?php echo Helper::assets('js/bootstrap-year-calendar.min.js')?>"></script>
<script type="application/javascript" src="<?php echo Helper::assets('js/intlTelInput.min.js', 'front-end')?>"></script>
<script>
    var telInputAssetUrl = "<?php echo Helper::assets('js/utilsIntlTelInput.js', 'front-end')?>";
    booknetic.can_delete_associated_account = <?php echo (Permission::isAdministrator() || Capabilities::userCan('staff_delete_wordpress_account')) ? 1 : 0 ?>;
</script>
<script type="application/javascript" src="<?php echo Helper::assets('js/staff.js', 'Staff')?>" id="staff-js12394610" data-edit="<?php echo $parameters['edit']?>"></script>

<div class="m_header">
    <div class="m_head_title"><?php echo bkntc__('Staff') ?> <span class="badge badge-secondary"><?php echo count($staffList) ?></span></div>
    <div class="m_head_actions">
        <select class="bkc-filter-select" id="bkc_staff_status_filter">
            <option value=""><?php echo bkntc__('All Statuses') ?></option>
            <option value="1"><?php echo bkntc__('Active') ?></option>
            <option value="0"><?php echo bkntc__('Hidden') ?></option>
        </select>
        <?php if ($canAdd): ?>
            <button type="button" class="btn btn-primary" id="addBtn">+ <?php echo bkntc__('ADD STAFF') ?></button>
        <?php endif; ?>
    </div>
</div>

<div class="bkc-page-container">
    <div class="bkc-search-container">
        <div class="bkc-search-wrap-full">
            <?php echo $svgSearch ?>
            <input class="bkc-search-input-full" type="text" id="bkc_staff_search" placeholder="<?php echo bkntc__('Quick search') ?>">
        </div>
    </div>

    <div class="bkc-divider"></div>

    <div class="bkc-card-grid" id="bkc_staff_grid">
    <?php if (empty($staffList)): ?>
        <div class="bkc-empty-state">
            <?php echo $svgUser ?>
            <h3><?php echo bkntc__('No staff members yet') ?></h3>
            <p><?php echo bkntc__('Add your first staff member to get started.') ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($staffList as $s):
            $isActive  = (int)($s['is_active'] ?? 1);
            $id        = (int)$s['id'];
            $name      = htmlspecialchars($s['name'] ?? '');
            $email     = htmlspecialchars($s['email'] ?? '');
            $phone     = htmlspecialchars($s['phone_number'] ?? '');
            $initials  = mb_strtoupper(mb_substr($name, 0, 2));
            $pillClass = $isActive ? 'bkc-pill--active' : 'bkc-pill--hidden';
            $pillLabel = $isActive ? bkntc__('Active') : bkntc__('Hidden');
            $cardClass = $isActive ? '' : ' bkc-card--hidden';
            $imgSrc    = Helper::profileImage($s['profile_image'] ?? '', 'Staff');
        ?>
        <div class="bkc-card<?php echo $cardClass ?>"
             data-id="<?php echo $id ?>"
             data-name="<?php echo $name ?>"
             data-email="<?php echo strtolower($email) ?>"
             data-phone="<?php echo $phone ?>"
             data-active="<?php echo $isActive ?>">
            <div class="bkc-card__body">
                <div class="bkc-card__identity">
                    <?php if ($imgSrc): ?>
                        <img src="<?php echo $imgSrc ?>" class="bkc-avatar" alt="<?php echo $name ?>">
                    <?php else: ?>
                        <div class="bkc-avatar-placeholder<?php echo $isActive ? '' : ' bkc-avatar-placeholder--muted' ?>"><?php echo $initials ?></div>
                    <?php endif; ?>
                    <div class="bkc-card__name-wrap">
                        <div class="bkc-card__name"><?php echo $name ?></div>
                        <?php if ($email): ?>
                            <div class="bkc-card__sub"><?php echo $email ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="bkc-pill <?php echo $pillClass ?>"><span class="bkc-pill__dot"></span><?php echo $pillLabel ?></span>
                </div>
                <div class="bkc-card__meta">
                    <?php if ($email): ?>
                        <div class="bkc-meta-row"><?php echo $svgMail ?><span><?php echo $email ?></span></div>
                    <?php endif; ?>
                    <?php if ($phone): ?>
                        <div class="bkc-meta-row"><?php echo $svgPhone ?><span><?php echo $phone ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bkc-card__footer">
                <button class="bkc-action-btn edit_staff_btn" data-id="<?php echo $id ?>" title="<?php echo bkntc__('Edit') ?>"><?php echo $svgEdit ?></button>
                <button class="bkc-action-btn duplicate_staff_btn" data-id="<?php echo $id ?>" title="<?php echo bkntc__('Duplicate') ?>"><?php echo $svgDupe ?></button>
                <button class="bkc-action-btn share_staff_btn" data-id="<?php echo $id ?>" title="<?php echo bkntc__('Share') ?>"><?php echo $svgShare ?></button>
                <span class="bkc-action-spacer"></span>
                <?php if ($canDelete): ?>
                    <button class="bkc-action-btn bkc-action-btn--danger delete_staff_btn" data-id="<?php echo $id ?>" title="<?php echo bkntc__('Delete') ?>"><?php echo $svgTrash ?></button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>

<script>
(function($){
    $(document).ready(function(){
        // Mock dataTable reload for refresh
        booknetic.dataTable.reload = function() {
            location.reload();
        };

        // Initialize select2 on dropdowns
        $('.bkc-filter-select').select2({
            theme: 'bootstrap',
            placeholder: booknetic.__('Select'),
            allowClear: true
        });

        // Click actions
        $(document).on('click', '.edit_staff_btn', function() {
            var id = $(this).data('id');
            booknetic.dataTable.actionCallbacks['edit']([id]);
        });

        $(document).on('click', '.duplicate_staff_btn', function() {
            var id = $(this).data('id');
            booknetic.dataTable.doAction('duplicate', [id], {}, function() {
                booknetic.toast(booknetic.__('Duplicated'), 'success', 2000);
            });
        });

        $(document).on('click', '.share_staff_btn', function() {
            var id = $(this).data('id');
            booknetic.dataTable.actionCallbacks['share']([id]);
        });

        $(document).on('click', '.delete_staff_btn', function() {
            var id = $(this).data('id');
            booknetic.dataTable.actionCallbacks['delete']([id]);
        });

        // Filtering
        var searchEl  = $('#bkc_staff_search');
        var statusEl  = $('#bkc_staff_status_filter');

        function filter(){
            var q = searchEl.val().toLowerCase();
            var status = statusEl.val();
            $('#bkc_staff_grid .bkc-card').each(function(){
                var card   = $(this);
                var name   = (card.data('name') || '').toLowerCase();
                var email  = (card.data('email') || '').toLowerCase();
                var phone  = (card.data('phone') || '').toLowerCase();
                var active = card.data('active');
                var show   = (!q || name.indexOf(q) > -1 || email.indexOf(q) > -1 || phone.indexOf(q) > -1)
                          && (status === '' || String(active) === String(status));
                if (show) {
                    card.show();
                } else {
                    card.hide();
                }
            });
        }

        searchEl.on('input', filter);
        statusEl.on('change', filter);
    });
})(jQuery);
</script>
