<?php
defined('ABSPATH') or die();

if (isset($_POST['bkntc_log_error'])) {
    $logData = date('Y-m-d H:i:s') . ' - ' . $_POST['data'] . "\n";
    file_put_contents(WP_CONTENT_DIR . '/js_errors.txt', $logData, FILE_APPEND);
    exit;
}

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\UI\TabUI;

/**
 * @var mixed $parameters
 * @var mixed $_mn
 */
$isEdit = ($parameters['service']['id'] ?? 0) > 0;
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/add_new.css', 'Services')?>">

<script>
window.onerror = function(message, source, lineno, colno, error) {
    var errorData = {
        message: message,
        source: source,
        lineno: lineno,
        colno: colno,
        error: error ? error.stack : ''
    };
    jQuery.ajax({
        type: 'POST',
        url: location.href,
        data: {
            bkntc_log_error: 1,
            data: JSON.stringify(errorData)
        },
        async: false
    });
};
</script>

<style>
/* Wireframe v6 Exact Design System */
#booknetic_service_fullpage_container {
    --primary: #6366f1;
    --primary-hover: #4f46e5;
    --primary-light: #eef2ff;
    --text: #0f172a;
    --text-2: #334155;
    --text-3: #64748b;
    --border: #cbd5e1;
    --border-light: #f1f5f9;
    --bg: #f8fafc;
    --surface: #ffffff;
    --surface-2: #f8fafc;
    --danger: #ef4444;
    --radius-sm: 6px;
    background: var(--bg);
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
    color: var(--text);
    padding-bottom: 80px;
}

#booknetic_service_fullpage_container.fs-modal {
    position: static !important;
    width: auto !important;
    height: auto !important;
    z-index: auto !important;
    background: transparent !important;
}

#booknetic_service_fullpage_container .top-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 32px;
    background: #fff;
    border-bottom: 1.5px solid var(--border-light);
    font-size: 13px;
    font-weight: 600;
    color: var(--text-3);
}

#booknetic_service_fullpage_container .back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    color: var(--text-2);
    cursor: pointer;
    transition: color 0.18s ease;
}
#booknetic_service_fullpage_container .back-link svg {
    width: 14px;
    height: 14px;
}
#booknetic_service_fullpage_container .back-link:hover {
    color: var(--primary);
}

#booknetic_service_fullpage_container .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 32px 12px;
}
#booknetic_service_fullpage_container .appt-id {
    font-size: 24px;
    font-weight: 800;
    color: var(--text);
    font-family: 'Outfit', 'Inter', sans-serif;
}
#booknetic_service_fullpage_container .appt-title {
    font-size: 13px;
    color: var(--text-3);
    margin-top: 4px;
}

#booknetic_service_fullpage_container .header-actions {
    display: flex;
    gap: 12px;
}
#booknetic_service_fullpage_container .btn {
    height: 38px;
    padding: 0 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.18s ease;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
#booknetic_service_fullpage_container .btn-sm {
    height: 34px;
    padding: 0 14px;
}
#booknetic_service_fullpage_container .btn-ghost {
    background: none;
    color: var(--text-2);
    border: 1.5px solid var(--border);
}
#booknetic_service_fullpage_container .btn-ghost:hover {
    background: #f1f5f9;
}
#booknetic_service_fullpage_container .btn-primary {
    background: var(--primary);
    color: #fff;
}
#booknetic_service_fullpage_container .btn-primary:hover {
    background: var(--primary-hover);
}
#booknetic_service_fullpage_container #addServiceSave {
    background: #22c55e !important;
    color: #fff !important;
}
#booknetic_service_fullpage_container #addServiceSave:hover {
    background: #16a34a !important;
}

/* Custom styled tabs for Services */
#booknetic_service_fullpage_container .nav-tabs {
    border-bottom: 2px solid var(--border-light) !important;
    margin-bottom: 24px !important;
    display: flex;
    gap: 24px;
    padding: 0 32px;
}
#booknetic_service_fullpage_container .nav-tabs .nav-item {
    margin: 0 !important;
}
#booknetic_service_fullpage_container .nav-tabs .nav-link {
    font-size: 14px !important;
    font-weight: 700 !important;
    color: var(--text-3) !important;
    border: none !important;
    background: none !important;
    padding: 10px 0 !important;
    cursor: pointer;
    position: relative;
    transition: color 0.2s ease;
}
#booknetic_service_fullpage_container .nav-tabs .nav-link:hover {
    color: var(--text) !important;
}
#booknetic_service_fullpage_container .nav-tabs .nav-link.active {
    color: var(--primary) !important;
}
#booknetic_service_fullpage_container .nav-tabs .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2.5px;
    background: var(--primary);
    border-radius: 2px;
}

#booknetic_service_fullpage_container .tab-content {
    padding: 0 32px;
    max-width: 1200px;
    margin: 0 auto;
}

/* Layout for Details tab - 2 Columns grid */
#tab_service_details {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}

/* Push image/color picker to the right sidebar column */
#tab_service_details .service_picture_div {
    grid-column: 2;
    grid-row: 1 / 20;
    background: #fff;
    border: 1.5px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Keep form fields in the left column */
#tab_service_details .form-row {
    grid-column: 1;
    background: #fff;
    border: 1.5px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 0;
    margin-left: 0;
    margin-right: 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

/* Style titles for form sections inside native panels */
#tab_service_details .form-row::before {
    font-size: 15px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 20px;
    border-bottom: 1px solid var(--border-light);
    padding-bottom: 12px;
    display: block;
    width: 100%;
}

/* Customize section titles dynamically based on first label contents */
#tab_service_details .form-row:nth-of-type(2)::before {
    content: "General Info";
}
#tab_service_details .form-row:nth-of-type(3)::before {
    content: "Pricing & Deposit";
}
#tab_service_details .form-row:nth-of-type(4)::before {
    content: "Deposit Value Settings";
}
#tab_service_details .form-row:nth-of-type(5)::before {
    content: "Duration & Buffers";
}
#tab_service_details .form-row:nth-of-type(6)::before {
    content: "Visibility Settings";
}
#tab_service_details .form-row:nth-of-type(7)::before {
    content: "Capacity Settings";
}
#tab_service_details .form-row:nth-of-type(8)::before {
    content: "Extra Settings & Notes";
}

/* Ensure Select2 container matches general styling */
.select2-container--bootstrap .select2-selection--single {
    border: 1.5px solid var(--border) !important;
    border-radius: 8px !important;
    height: 40px !important;
    padding: 5px 12px !important;
}
</style>

<div id="booknetic_service_fullpage_container" class="fs-modal">
  <form id="addServiceForm" class="validate-form">
    <div class="top-bar">
      <a class="back-link wf-back-to-table">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 3L5 8l5 5"/></svg>
        <?php echo bkntc__('Back to Services') ?>
      </a>
      <span class="sep">/</span>
      <span class="crumb active"><?php echo $isEdit ? bkntc__('Edit Service') : bkntc__('Add Service') ?></span>
    </div>

    <div class="page-header" style="margin-bottom:0; border-bottom:none; padding-bottom:12px;">
      <div>
        <div class="appt-id"><?php echo $isEdit ? bkntc__('Edit Service') : bkntc__('Add New Service') ?></div>
        <div class="appt-title"><?php echo bkntc__('Create a new service with customized details, staff, hours, extras, and advanced settings.') ?></div>
      </div>
      <div class="header-actions">
        <button type="button" class="btn btn-ghost btn-sm wf-back-to-table"><?php echo bkntc__('Cancel') ?></button>
        <button type="button" class="btn btn-primary btn-sm" id="addServiceSave">
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8l4 4 6-6"/></svg>
          <?php echo $isEdit ? bkntc__('Save Changes') : bkntc__('Add Service') ?>
        </button>
      </div>
    </div>

    <!-- Navigation tabs list -->
    <ul class="nav nav-tabs nav-light" data-tab-group="services_add">
      <?php foreach (TabUI::get('services_add')->getSubItems() as $tab): ?>
        <li class="nav-item">
          <a class="nav-link <?php echo $tab->getSlug() == 'details' ? 'active' : '' ?>" data-tab="<?php echo $tab->getSlug(); ?>" href="#">
            <?php echo $tab->getTitle(); ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <!-- Dynamic native tabs rendering -->
    <div class="tab-content">
      <?php foreach (TabUI::get('services_add')->getSubItems() as $tab): ?>
        <div class="tab-pane <?php echo $tab->getSlug() == 'details' ? 'active' : '' ?>" data-tab-content="services_add_<?php echo $tab->getSlug(); ?>" id="tab_<?php echo $tab->getSlug(); ?>">
          <?php echo $tab->getContent($parameters); ?>
        </div>
      <?php endforeach; ?>
    </div>
  </form>
</div>

<script>
    var serviceAssetUrl = "<?php echo Helper::assets('/', 'Services')?>";
</script>
<script type="application/javascript" src="<?php echo Helper::assets('js/add_new.js', 'Services')?>" id="add_new_JS" data-mn="<?php echo $_mn?>" data-service-id="<?php echo (int)($parameters['service']['id'] ?? 0)?>" data-staff-count="<?php echo count($parameters['staff'] ?? [])?>"></script>

<script>
$(document).off('click', '.wf-back-to-table').on('click', '.wf-back-to-table', function() {
    $('#booknetic_service_fullpage_container').hide();
    $('.data_table_search_panel, .m_header, .fs_data_table_wrapper, .m_bottom_fixed, .table-wrap, #services_map, #select_add_type').show();
});
</script>
