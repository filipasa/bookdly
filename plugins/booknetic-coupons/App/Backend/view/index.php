<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\Coupons\CouponsAddon;
use BookneticAddon\Coupons\Model\Coupon;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Math;
use function BookneticAddon\Coupons\bkntc__;

/**
 * @var array $parameters
 * $parameters['coupons'] – array of Coupon model objects
 */

$coupons   = $parameters['coupons'] ?? [];
$canDelete = Capabilities::userCan('coupons_delete');

$svgCal    = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>';
$svgUsers  = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
$svgTag    = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" x2="7.01" y1="7" y2="7"/></svg>';
$svgEdit   = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
$svgDupe   = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="13" height="13" x="9" y="9" rx="2" ry="2"/><path stroke-linecap="round" d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
$svgTrash  = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path stroke-linecap="round" d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>';
$svgSearch = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>';
$svgHist   = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/bkc-cards.css') ?>">
<link rel="stylesheet" href="<?php echo CouponsAddon::loadAsset('assets/backend/css/info.css') ?>">
<script type="text/javascript" src="<?php echo CouponsAddon::loadAsset('assets/backend/js/coupons.js') ?>"></script>
<script type="text/javascript" src="<?php echo CouponsAddon::loadAsset('assets/backend/js/info.js') ?>"></script>

<div class="m_header">
    <div class="m_head_title"><?php echo bkntc__('Coupons') ?> <span class="badge badge-secondary"><?php echo count($coupons) ?></span></div>
    <div class="m_head_actions">
        <button type="button" class="btn btn-primary" id="addBtn">+ <?php echo bkntc__('ADD COUPON') ?></button>
    </div>
</div>

<div class="bkc-toolbar-container">
    <div class="bkc-toolbar">
        <div class="bkc-search-wrap">
            <?php echo $svgSearch ?>
            <input class="bkc-search-input" type="text" id="bkc_coupon_search" placeholder="<?php echo bkntc__('Search by code…') ?>">
        </div>
        <select class="bkc-filter-select" id="bkc_coupon_status_filter">
            <option value=""><?php echo bkntc__('All Statuses') ?></option>
            <option value="active"><?php echo bkntc__('Active') ?></option>
            <option value="inactive"><?php echo bkntc__('Inactive') ?></option>
            <option value="expired"><?php echo bkntc__('Expired') ?></option>
        </select>
        <select class="bkc-filter-select" id="bkc_coupon_type_filter">
            <option value=""><?php echo bkntc__('All Types') ?></option>
            <option value="percent"><?php echo bkntc__('Percentage') ?></option>
            <option value="fixed"><?php echo bkntc__('Fixed Amount') ?></option>
        </select>
    </div>
</div>

<div class="bkc-card-grid" id="bkc_coupons_grid">
<?php if (empty($coupons)): ?>
    <div class="bkc-empty-state">
        <?php echo $svgTag ?>
        <h3><?php echo bkntc__('No coupons yet') ?></h3>
        <p><?php echo bkntc__('Create your first discount coupon to get started.') ?></p>
    </div>
<?php else: ?>
    <?php foreach ($coupons as $coupon):
        $now       = Date::epoch();
        $id        = (int)$coupon['id'];
        $code      = htmlspecialchars($coupon['code'] ?? '');
        $type      = $coupon['discount_type'] ?? 'percent';
        $discount  = $coupon['discount'] ?? 0;
        $usageLimit= $coupon['usage_limit'] ?? null;
        $startDate = $coupon['start_date'] ?? null;
        $endDate   = $coupon['end_date'] ?? null;
        $usedTimes = (int)Coupon::numberOfUses($coupon);

        // Determine status
        if (!is_null($startDate) && $now < Date::epoch($startDate)) {
            $status = 'inactive';
        } elseif (!is_null($endDate) && $now > Date::epoch($endDate)) {
            $status = 'expired';
        } elseif (is_null($usageLimit) || ((int)$usageLimit - $usedTimes > 0)) {
            $status = 'active';
        } else {
            $status = 'expired';
        }

        $pillClass  = 'bkc-pill--' . $status;
        $pillLabel  = bkntc__(ucfirst($status));
        $cardClass  = ($status === 'expired' || $status === 'inactive') ? ' bkc-card--hidden' : '';
        $codeClass  = ($status === 'expired') ? ' bkc-coupon-code--expired' : (($status === 'inactive') ? ' bkc-coupon-code--inactive' : '');

        $discountStr = ($type === 'percent')
            ? Math::floor($discount, 2) . '%'
            : Helper::price($discount);

        $typeLabel = ($type === 'percent')
            ? bkntc__('Percentage discount')
            : bkntc__('Fixed amount discount');

        $expiryStr = $endDate
            ? bkntc__('Expires') . ': ' . Date::format($endDate)
            : bkntc__('No expiry');

        $usageStr = $usageLimit
            ? bkntc__('Used') . ' ' . $usedTimes . ' / ' . $usageLimit
            : bkntc__('Used') . ' ' . $usedTimes . ' / ' . bkntc__('unlimited');
    ?>
    <div class="bkc-card<?php echo $cardClass ?>"
         data-id="<?php echo $id ?>"
         data-code="<?php echo strtolower($code) ?>"
         data-status="<?php echo $status ?>"
         data-type="<?php echo $type ?>">
        <div class="bkc-card__body">
            <div class="bkc-coupon-header">
                <span class="bkc-coupon-code<?php echo $codeClass ?>"><?php echo $code ?></span>
                <span class="bkc-pill <?php echo $pillClass ?>"><span class="bkc-pill__dot"></span><?php echo $pillLabel ?></span>
            </div>
            <div class="bkc-coupon-discount"><?php echo $discountStr ?></div>
            <div class="bkc-coupon-type"><?php echo $typeLabel ?></div>
            <div class="bkc-card__meta">
                <div class="bkc-meta-row"><?php echo $svgCal ?><span><?php echo $expiryStr ?></span></div>
                <div class="bkc-meta-row"><?php echo $svgUsers ?><span><?php echo $usageStr ?></span></div>
            </div>
        </div>
        <div class="bkc-card__footer">
            <button class="bkc-action-btn edit_coupon_btn" data-id="<?php echo $id ?>" title="<?php echo bkntc__('Edit') ?>"><?php echo $svgEdit ?></button>
            <button class="bkc-action-btn view_coupon_history_btn" data-id="<?php echo $id ?>" title="<?php echo bkntc__('Usage History') ?>"><?php echo $svgHist ?></button>
            <span class="bkc-action-spacer"></span>
            <?php if ($canDelete): ?>
                <button class="bkc-action-btn bkc-action-btn--danger delete_coupon_btn" data-id="<?php echo $id ?>" title="<?php echo bkntc__('Delete') ?>"><?php echo $svgTrash ?></button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
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
        $(document).on('click', '.edit_coupon_btn', function() {
            var id = $(this).data('id');
            booknetic.dataTable.actionCallbacks['edit']([id]);
        });

        $(document).on('click', '.view_coupon_history_btn', function() {
            var id = $(this).data('id');
            booknetic.loadModal('Coupons.coupons_usage_history', { id: id } , {'width': "800px"} );
        });

        $(document).on('click', '.delete_coupon_btn', function() {
            var id = $(this).data('id');
            booknetic.confirm(booknetic.__('are_you_sure_want_to_delete'), 'danger', 'trash', function() {
                booknetic.dataTable.doAction('delete', [id], {}, function() {
                    booknetic.toast(booknetic.__('Deleted'), 'success', 2000);
                });
            });
        });

        // Filtering
        var searchEl = $('#bkc_coupon_search');
        var statusEl = $('#bkc_coupon_status_filter');
        var typeEl   = $('#bkc_coupon_type_filter');

        function filter(){
            var q = searchEl.val().toLowerCase();
            var status = statusEl.val();
            var type = typeEl.val();
            $('#bkc_coupons_grid .bkc-card').each(function(){
                var card = $(this);
                var code    = (card.data('code') || '').toLowerCase();
                var cStatus = card.data('status');
                var cType   = card.data('type');
                var show    = (!q || code.indexOf(q) > -1)
                           && (!status || cStatus === status)
                           && (!type   || cType   === type);
                if (show) {
                    card.show();
                } else {
                    card.hide();
                }
            });
        }

        searchEl.on('input', filter);
        statusEl.on('change', filter);
        typeEl.on('change', filter);
    });
})(jQuery);
</script>
