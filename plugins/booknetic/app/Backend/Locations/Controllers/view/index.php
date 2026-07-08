<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Math;

/**
 * @var array $parameters
 * $parameters['locations']  – array of location rows
 * $parameters['categories'] – array of category rows [id => name]
 */

$locations  = $parameters['locations'] ?? [];
$categories = $parameters['categories'] ?? [];

$svgPhone   = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 0 1 2-2h3.28a1 1 0 0 1 .948.684l1.498 4.493a1 1 0 0 1-.502 1.21l-2.257 1.13a11.042 11.042 0 0 0 5.516 5.516l1.13-2.257a1 1 0 0 1 1.21-.502l4.493 1.498a1 1 0 0 1 .684.949V19a2 2 0 0 1-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>';
$svgPin     = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>';
$svgEdit    = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
$svgDupe    = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="13" height="13" x="9" y="9" rx="2" ry="2"/><path stroke-linecap="round" d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
$svgShare   = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path stroke-linecap="round" d="m8.59 13.51 6.83 3.98M15.41 6.51l-6.82 3.98"/></svg>';
$svgTrash   = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path stroke-linecap="round" d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>';
$svgSearch  = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>';
$apiKey = Helper::getOption('google_maps_api_key', '', false);
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/bkc-cards.css') ?>">
<link rel="stylesheet" href="<?php echo Helper::assets('css/add_new.css', 'Locations') ?>">
<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=<?php echo urlencode($apiKey)?>&libraries=places" async defer></script>
<script type="text/javascript" src="<?php echo Helper::assets('js/locations.js', 'Locations')?>"></script>

<div class="m_header">
    <div class="m_head_title"><?php echo bkntc__('Locations') ?> <span class="badge badge-secondary"><?php echo count($locations) ?></span></div>
    <div class="m_head_actions">
        <button type="button" class="btn btn-primary" id="addBtn">+ <?php echo bkntc__('ADD LOCATION') ?></button>
    </div>
</div>

<div class="bkc-toolbar-container">
    <div class="bkc-toolbar">
        <div class="bkc-search-wrap">
            <?php echo $svgSearch ?>
            <input class="bkc-search-input" type="text" id="bkc_location_search" placeholder="<?php echo bkntc__('Search by name, address, phone…') ?>">
        </div>
        <select class="bkc-filter-select" id="bkc_location_cat_filter">
            <option value=""><?php echo bkntc__('All Categories') ?></option>
            <?php foreach ($categories as $id => $name): ?>
                <option value="<?php echo (int)$id ?>"><?php echo htmlspecialchars($name) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="bkc-filter-select" id="bkc_location_status_filter">
            <option value=""><?php echo bkntc__('All Statuses') ?></option>
            <option value="1"><?php echo bkntc__('Active') ?></option>
            <option value="0"><?php echo bkntc__('Hidden') ?></option>
        </select>
    </div>
</div>

<div class="bkc-card-grid" id="bkc_locations_grid">
<?php if (empty($locations)): ?>
    <div class="bkc-empty-state">
        <?php echo $svgPin ?>
        <h3><?php echo bkntc__('No locations yet') ?></h3>
        <p><?php echo bkntc__('Add your first location to get started.') ?></p>
    </div>
<?php else: ?>
    <?php foreach ($locations as $loc):
        $isActive  = (int)($loc['is_active'] ?? 1);
        $catName   = htmlspecialchars($loc['category_name'] ?? '');
        $name      = htmlspecialchars($loc['name'] ?? '');
        $phone     = htmlspecialchars($loc['phone_number'] ?? '');
        $address   = htmlspecialchars($loc['address'] ?? '');
        $id        = (int)$loc['id'];
        $initials  = mb_strtoupper(mb_substr($name, 0, 2));
        $pillClass = $isActive ? 'bkc-pill--active' : 'bkc-pill--hidden';
        $pillLabel = $isActive ? bkntc__('Active') : bkntc__('Hidden');
        $cardClass = $isActive ? '' : ' bkc-card--hidden';
        $imgSrc    = Helper::profileImage($loc['image'] ?? '', 'Locations');
    ?>
    <div class="bkc-card<?php echo $cardClass ?>" 
         data-id="<?php echo $id ?>" 
         data-name="<?php echo $name ?>" 
         data-cat="<?php echo (int)($loc['category_id'] ?? 0) ?>" 
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
                    <?php if ($catName): ?>
                        <span class="bkc-cat-badge"><?php echo $catName ?></span>
                    <?php endif; ?>
                </div>
                <span class="bkc-pill <?php echo $pillClass ?>"><span class="bkc-pill__dot"></span><?php echo $pillLabel ?></span>
            </div>
            <div class="bkc-card__meta">
                <?php if ($phone): ?>
                    <div class="bkc-meta-row"><?php echo $svgPhone ?><span><?php echo $phone ?></span></div>
                <?php endif; ?>
                <?php if ($address): ?>
                    <div class="bkc-meta-row"><?php echo $svgPin ?><span><?php echo $address ?></span></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="bkc-card__footer">
            <button class="bkc-action-btn edit_location_btn" data-id="<?php echo $id ?>" title="<?php echo bkntc__('Edit') ?>"><?php echo $svgEdit ?></button>
            <button class="bkc-action-btn duplicate_location_btn" data-id="<?php echo $id ?>" title="<?php echo bkntc__('Duplicate') ?>"><?php echo $svgDupe ?></button>
            <button class="bkc-action-btn share_location_btn" data-id="<?php echo $id ?>" title="<?php echo bkntc__('Share') ?>"><?php echo $svgShare ?></button>
            <span class="bkc-action-spacer"></span>
            <button class="bkc-action-btn bkc-action-btn--danger delete_location_btn" data-id="<?php echo $id ?>" title="<?php echo bkntc__('Delete') ?>"><?php echo $svgTrash ?></button>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<script>
(function($){
    $(document).ready(function(){
        // Mock dataTable functions if needed
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
        $(document).on('click', '.edit_location_btn', function() {
            var id = $(this).data('id');
            booknetic.dataTable.actionCallbacks['edit']([id]);
        });

        $(document).on('click', '.duplicate_location_btn', function() {
            var id = $(this).data('id');
            booknetic.dataTable.doAction('duplicate', [id], {}, function() {
                booknetic.toast(booknetic.__('Duplicated'), 'success', 2000);
            });
        });

        $(document).on('click', '.share_location_btn', function() {
            var id = $(this).data('id');
            booknetic.dataTable.actionCallbacks['share']([id]);
        });

        $(document).on('click', '.delete_location_btn', function() {
            var id = $(this).data('id');
            booknetic.confirm(booknetic.__('are_you_sure_want_to_delete'), 'danger', 'trash', function() {
                booknetic.dataTable.doAction('delete', [id], {}, function() {
                    booknetic.toast(booknetic.__('Deleted'), 'success', 2000);
                });
            });
        });

        // Filtering
        var searchEl  = $('#bkc_location_search');
        var catEl     = $('#bkc_location_cat_filter');
        var statusEl  = $('#bkc_location_status_filter');

        function filter(){
            var q = searchEl.val().toLowerCase();
            var cat = catEl.val();
            var status = statusEl.val();
            $('#bkc_locations_grid .bkc-card').each(function(){
                var card = $(this);
                var name   = (card.data('name') || '').toLowerCase();
                var cardCat= card.data('cat');
                var active = card.data('active');
                var show   = (!q || name.indexOf(q) > -1)
                          && (!cat || String(cardCat) === String(cat))
                          && (status === '' || String(active) === String(status));
                if (show) {
                    card.show();
                } else {
                    card.hide();
                }
            });
        }

        searchEl.on('input', filter);
        catEl.on('change', filter);
        statusEl.on('change', filter);
    });
})(jQuery);
</script>
