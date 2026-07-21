<?php
defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var mixed $parameters
 */
?>

<link rel="stylesheet" href="<?php echo Helper::assets('css/add_new_category.css', 'Services')?>">
<script type="application/javascript" src="<?php echo Helper::assets('js/add_new_category.js', 'Services')?>"></script>

<style>
/* Reset and override Booknetic defaults for this modal */
.fs-modal-title, .fs-modal-footer {
    display: none !important;
}
.fs-modal-body {
    padding: 0 !important;
}
.fs-modal-body-inner {
    padding: 0 !important;
}

/* Custom Wireframe Modal Styles */
.wf-category-modal-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 24px 28px 12px;
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
}
.wf-category-modal-header svg {
    width: 20px;
    height: 20px;
    color: #6366f1;
}
.wf-category-modal-header .close-btn {
    margin-left: auto;
    cursor: pointer;
    color: #94a3b8;
    transition: color 0.18s ease;
}
.wf-category-modal-header .close-btn:hover {
    color: #475569;
}

.wf-category-modal-body {
    padding: 12px 28px 20px;
}

#addServiceForm .form-group {
    margin-bottom: 16px;
}
#addServiceForm .form-group:last-child {
    margin-bottom: 0;
}
#addServiceForm label {
    font-size: 12px;
    font-weight: 600;
    color: #475569 !important;
    margin-bottom: 6px;
    display: inline-block;
}
#addServiceForm label .req {
    color: #ef4444;
    margin-left: 2px;
}
#addServiceForm .form-control {
    height: 40px;
    padding: 0 12px;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 8px !important;
    font-size: 13px;
    color: #0f172a;
    background: #fff;
    outline: none;
    transition: all 0.18s ease;
    width: 100%;
    box-shadow: none !important;
}
#addServiceForm .form-control:focus {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12) !important;
}

/* Restyle Select2 elements */
.select2-container--bootstrap .select2-selection {
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 8px !important;
    height: 40px !important;
    padding: 5px 12px !important;
    font-size: 13px !important;
}
.select2-container--bootstrap.select2-container--focus .select2-selection {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12) !important;
}

.wf-category-modal-footer {
    display: flex;
    gap: 12px;
    padding: 12px 28px 28px;
}
.wf-category-modal-footer button {
    flex: 1;
    height: 40px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.18s ease;
    border: none;
    outline: none !important;
}
.wf-btn-close-category {
    background: none;
    color: #475569;
    border: 1.5px solid #e2e8f0 !important;
}
.wf-btn-close-category:hover {
    background: #f1f5f9;
}
.wf-btn-save-category {
    background: #6366f1;
    color: #fff;
}
.wf-btn-save-category:hover {
    background: #4f46e5;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99,102,241,.35);
}
</style>

<div class="wf-category-modal-header">
    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 2h4l2 2h6v10H2V2z"/></svg>
    <span><?php echo bkntc__('Add Category')?></span>
    <div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="wf-category-modal-body">
    <form id="addServiceForm">
        <div class="form-group">
            <label for="input_parent_category"><?php echo bkntc__('Parent Category')?> <span class="req">*</span></label>
            <select id="input_parent_category" class="form-control">
                <option value="0"><?php echo bkntc__('Root category')?></option>
                <?php
                foreach ($parameters['categories'] as $category) {
                    echo '<option value="' . (int)$category['id'] . '"' . (isset($parameters['category']) && $parameters['category'] == $category['id'] ? ' selected' : '') . '>' . htmlspecialchars($category['name']) . '</option>';
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="new_category_name"><?php echo bkntc__('Category Name')?> <span class="req">*</span></label>
            <input type="text" class="form-control" data-multilang="true" data-multilang-fk="0" id="new_category_name" placeholder="<?php echo bkntc__('e.g. Nail Care')?>">
        </div>
    </form>
</div>

<div class="wf-category-modal-footer">
    <button type="button" class="wf-btn-close-category" data-dismiss="modal"><?php echo bkntc__('Close')?></button>
    <button type="button" class="wf-btn-save-category" id="save_new_category"><?php echo bkntc__('Save')?></button>
</div>