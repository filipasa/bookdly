<?php
defined('ABSPATH') or die();
?>

<div class="fs-modal-title">
    <div class="title-icon badge-lg badge-purple"><i class="fa fa-folder-open"></i></div>
    <div class="title-text"><?php echo bkntc__('Tenant Business Directory Settings')?></div>
    <div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>
</div>

<div class="fs-modal-body">
    <div class="fs-modal-body-inner">
        <ul class="nav nav-tabs nav-light" data-tab-group="tenant_directory_settings">
            <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab_general"><?php echo bkntc__('General')?></a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab_business_types"><?php echo bkntc__('Business Types')?></a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab_keywords"><?php echo bkntc__('Keywords')?></a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab_requests"><?php echo bkntc__('Directory Requests')?></a></li>
        </ul>

        <div class="tab-content pt-4">
            <!-- 1. General Settings Tab -->
            <div class="tab-pane active" id="tab_general">
                <form id="generalSettingsForm">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="input_tenant_page"><?php echo bkntc__('Tenant Landing Page')?> <span class="required-star">*</span></label>
                            <select class="form-control" id="input_tenant_page">
                                <option value=""><?php echo bkntc__('Select Page...')?></option>
                                <?php foreach ($parameters['pages'] as $pageId => $pageTitle): ?>
                                    <option value="<?php echo (int)$pageId?>" <?php echo $pageId == $parameters['tenant_page_id'] ? 'selected' : ''?>><?php echo htmlspecialchars($pageTitle)?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted"><?php echo bkntc__('Make sure to select the page containing [booknetic-saas-tenant-page] shortcode.')?></small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="input_search_page"><?php echo bkntc__('Directory Search Page')?> <span class="required-star">*</span></label>
                            <select class="form-control" id="input_search_page">
                                <option value=""><?php echo bkntc__('Select Page...')?></option>
                                <?php foreach ($parameters['pages'] as $pageId => $pageTitle): ?>
                                    <option value="<?php echo (int)$pageId?>" <?php echo $pageId == $parameters['search_page_id'] ? 'selected' : ''?>><?php echo htmlspecialchars($pageTitle)?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted"><?php echo bkntc__('Make sure to select the page containing [booknetic-saas-tenant-list] shortcode.')?></small>
                        </div>
                    </div>

                    <div class="form-row pt-3">
                        <div class="form-group col-md-6">
                            <label for="input_price_format"><?php echo bkntc__('Tenant Price Range Format')?>:</label>
                            <select class="form-control" id="input_price_format">
                                <option value="min_max" <?php echo $parameters['price_format'] === 'min_max' ? 'selected' : ''?>><?php echo bkntc__('Price Range with Min and Max Inputs')?></option>
                                <option value="level" <?php echo $parameters['price_format'] === 'level' ? 'selected' : ''?>><?php echo bkntc__('Price Level Selector ($-$$-$$$-$$$$)')?></option>
                            </select>
                        </div>
                        <div class="form-group col-md-6 d-flex align-items-center justify-content-between pt-4">
                            <label for="input_gallery_required" class="mb-0"><?php echo bkntc__('Gallery image is required')?>:</label>
                            <div class="fs_onoffswitch">
                                <input type="checkbox" class="fs_onoffswitch-checkbox" id="input_gallery_required" <?php echo $parameters['gallery_required'] === 'on' ? 'checked' : ''?>>
                                <label class="fs_onoffswitch-label" for="input_gallery_required"></label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- 2. Business Types Tab -->
            <div class="tab-pane" id="tab_business_types">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5><?php echo bkntc__('Business Types')?></h5>
                    <button type="button" class="btn btn-sm btn-primary" id="btn_add_business_type"><i class="fa fa-plus"></i> <?php echo bkntc__('ADD NEW')?></button>
                </div>
                <table class="table table-striped table-bordered" id="tbl_business_types">
                    <thead>
                        <tr>
                            <th><?php echo bkntc__('ID')?></th>
                            <th><?php echo bkntc__('Name')?></th>
                            <th><?php echo bkntc__('Sort Number')?></th>
                            <th><?php echo bkntc__('Actions')?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parameters['businessTypes'])): ?>
                            <tr class="empty-row"><td colspan="4" class="text-center"><?php echo bkntc__('No business types created yet.')?></td></tr>
                        <?php else: ?>
                            <?php foreach ($parameters['businessTypes'] as $type): ?>
                                <tr data-id="<?php echo (int)$type->id?>">
                                    <td><?php echo (int)$type->id?></td>
                                    <td class="col-name"><?php echo htmlspecialchars($type->name)?></td>
                                    <td class="col-sort"><?php echo (int)$type->sort_number?></td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-default edit-type-btn"><i class="fa fa-pencil-alt"></i></button>
                                        <button type="button" class="btn btn-xs btn-danger delete-type-btn"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- 3. Keywords Tab -->
            <div class="tab-pane" id="tab_keywords">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5><?php echo bkntc__('Keywords')?></h5>
                    <button type="button" class="btn btn-sm btn-primary" id="btn_add_keyword"><i class="fa fa-plus"></i> <?php echo bkntc__('ADD NEW')?></button>
                </div>
                <table class="table table-striped table-bordered" id="tbl_keywords">
                    <thead>
                        <tr>
                            <th><?php echo bkntc__('ID')?></th>
                            <th><?php echo bkntc__('Name')?></th>
                            <th><?php echo bkntc__('Actions')?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parameters['keywords'])): ?>
                            <tr class="empty-row"><td colspan="3" class="text-center"><?php echo bkntc__('No keywords created yet.')?></td></tr>
                        <?php else: ?>
                            <?php foreach ($parameters['keywords'] as $keyword): ?>
                                <tr data-id="<?php echo (int)$keyword->id?>">
                                    <td><?php echo (int)$keyword->id?></td>
                                    <td class="col-name"><?php echo htmlspecialchars($keyword->name)?></td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-default edit-keyword-btn"><i class="fa fa-pencil-alt"></i></button>
                                        <button type="button" class="btn btn-xs btn-danger delete-keyword-btn"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- 4. Directory Requests Tab -->
            <div class="tab-pane" id="tab_requests">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th><?php echo bkntc__('Tenant ID')?></th>
                            <th><?php echo bkntc__('Tenant Name')?></th>
                            <th><?php echo bkntc__('Email')?></th>
                            <th><?php echo bkntc__('Business Title')?></th>
                            <th><?php echo bkntc__('Status')?></th>
                            <th><?php echo bkntc__('Actions')?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parameters['pendingRequests'])): ?>
                            <tr><td colspan="6" class="text-center"><?php echo bkntc__('No directory request entries.')?></td></tr>
                        <?php else: ?>
                            <?php foreach ($parameters['pendingRequests'] as $request): 
                                $tenant = $parameters['tenants'][$request->tenant_id] ?? null;
                            ?>
                                <tr>
                                    <td><?php echo (int)$request->tenant_id?></td>
                                    <td><?php echo htmlspecialchars($tenant ? $tenant->full_name : 'N/A')?></td>
                                    <td><?php echo htmlspecialchars($tenant ? $tenant->email : 'N/A')?></td>
                                    <td><?php echo htmlspecialchars($request->title)?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $request->status === 'approved' ? 'success' : ($request->status === 'pending' ? 'warning' : 'secondary');
                                        ?>"><?php echo strtoupper($request->status)?></span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-primary review-request-btn" 
                                                data-id="<?php echo (int)$request->id?>" 
                                                data-title="<?php echo htmlspecialchars($request->title)?>"
                                                data-status="<?php echo htmlspecialchars($request->status)?>"
                                                data-notes="<?php echo htmlspecialchars($request->review_notes)?>">
                                            <i class="fa fa-search"></i> <?php echo bkntc__('Review')?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="fs-modal-footer">
    <button type="button" class="btn btn-lg btn-default" data-dismiss="modal"><?php echo bkntc__('CLOSE')?></button>
    <button type="button" class="btn btn-lg btn-success" id="btn_save_general_settings"><i class="fa fa-check pr-2"></i> <?php echo bkntc__('SAVE CHANGES')?></button>
</div>

<script type="text/javascript">
    (function($) {
        "use strict";

        // 1. General Settings Save
        $(document).off('click', '#btn_save_general_settings').on('click', '#btn_save_general_settings', function() {
            let page_id = $('#input_tenant_page').val();
            let search_page_id = $('#input_search_page').val();
            let price_format = $('#input_price_format').val();
            let gallery_required = $('#input_gallery_required').is(':checked') ? 'on' : 'off';

            if (!page_id || !search_page_id) {
                booknetic.toast(booknetic.__('Please fill in all required fields.'), 'unsuccess');
                return;
            }

            booknetic.ajax('tenant_directory_settings.settings_save', {
                tenant_directory_page_id: page_id,
                tenant_directory_search_page_id: search_page_id,
                tenant_directory_price_format: price_format,
                tenant_directory_gallery_required: gallery_required
            }, function(response) {
                booknetic.toast(response.message, 'success');
                booknetic.modalHide($(".fs-modal"));
            });
        });

        // 2. Business Types CRUD
        $('#btn_add_business_type').click(function() {
            let name = prompt(booknetic.__('Enter Business Type Name:'));
            if (!name) return;
            let sort = prompt(booknetic.__('Enter Sort Order (integer):'), '0');
            
            booknetic.ajax('tenant_directory_settings.business_types_save', {
                id: 0,
                name: name,
                sort_number: parseInt(sort) || 0
            }, function(response) {
                booknetic.toast(response.message, 'success');
                // Reload settings modal
                booknetic.loadModal('tenant_directory_settings.settings_view', {});
            });
        });

        $(document).off('click', '.edit-type-btn').on('click', '.edit-type-btn', function() {
            let row = $(this).closest('tr');
            let id = row.data('id');
            let oldName = row.find('.col-name').text();
            let oldSort = row.find('.col-sort').text();

            let name = prompt(booknetic.__('Edit Business Type Name:'), oldName);
            if (!name) return;
            let sort = prompt(booknetic.__('Edit Sort Order (integer):'), oldSort);

            booknetic.ajax('tenant_directory_settings.business_types_save', {
                id: id,
                name: name,
                sort_number: parseInt(sort) || 0
            }, function(response) {
                booknetic.toast(response.message, 'success');
                booknetic.loadModal('tenant_directory_settings.settings_view', {});
            });
        });

        $(document).off('click', '.delete-type-btn').on('click', '.delete-type-btn', function() {
            let id = $(this).closest('tr').data('id');
            if (!confirm(booknetic.__('Are you sure you want to delete this business type?'))) return;

            booknetic.ajax('tenant_directory_settings.business_types_delete', { id: id }, function(response) {
                booknetic.toast(response.message, 'success');
                booknetic.loadModal('tenant_directory_settings.settings_view', {});
            });
        });

        // 3. Keywords CRUD
        $('#btn_add_keyword').click(function() {
            let name = prompt(booknetic.__('Enter Keyword Name:'));
            if (!name) return;

            booknetic.ajax('tenant_directory_settings.keywords_save', {
                id: 0,
                name: name
            }, function(response) {
                booknetic.toast(response.message, 'success');
                booknetic.loadModal('tenant_directory_settings.settings_view', {});
            });
        });

        $(document).off('click', '.edit-keyword-btn').on('click', '.edit-keyword-btn', function() {
            let row = $(this).closest('tr');
            let id = row.data('id');
            let oldName = row.find('.col-name').text();

            let name = prompt(booknetic.__('Edit Keyword Name:'), oldName);
            if (!name) return;

            booknetic.ajax('tenant_directory_settings.keywords_save', {
                id: id,
                name: name
            }, function(response) {
                booknetic.toast(response.message, 'success');
                booknetic.loadModal('tenant_directory_settings.settings_view', {});
            });
        });

        $(document).off('click', '.delete-keyword-btn').on('click', '.delete-keyword-btn', function() {
            let id = $(this).closest('tr').data('id');
            if (!confirm(booknetic.__('Are you sure you want to delete this keyword?'))) return;

            booknetic.ajax('tenant_directory_settings.keywords_delete', { id: id }, function(response) {
                booknetic.toast(response.message, 'success');
                booknetic.loadModal('tenant_directory_settings.settings_view', {});
            });
        });

        // 4. Directory Request Review
        $(document).off('click', '.review-request-btn').on('click', '.review-request-btn', function() {
            let id = $(this).data('id');
            let title = $(this).data('title');
            let status = $(this).data('status');
            let notes = $(this).data('notes') || '';

            let reviewHtml = `
                <div class="p-3">
                    <p><strong>${booknetic.__('Business Title')}:</strong> ${title}</p>
                    <div class="form-group">
                        <label>${booknetic.__('Status')}:</label>
                        <select class="form-control" id="review_status">
                            <option value="approved" ${status === 'approved' ? 'selected' : ''}>${booknetic.__('Approved')}</option>
                            <option value="rejected" ${status === 'rejected' ? 'selected' : ''}>${booknetic.__('Rejected')}</option>
                            <option value="hidden" ${status === 'hidden' ? 'selected' : ''}>${booknetic.__('Hidden')}</option>
                            <option value="pending" ${status === 'pending' ? 'selected' : ''}>${booknetic.__('Pending')}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>${booknetic.__('Review Notes')}:</label>
                        <textarea class="form-control" rows="4" id="review_notes">${notes}</textarea>
                    </div>
                </div>
            `;

            booknetic.confirmV2(reviewHtml, booknetic.__('SUBMIT'), booknetic.__('CANCEL'), booknetic.__('Review Directory Request'), function() {
                let newStatus = $('#review_status').val();
                let newNotes = $('#review_notes').val();

                booknetic.ajax('tenant_directory_settings.review_directory_request', {
                    id: id,
                    status: newStatus,
                    review_notes: newNotes
                }, function(response) {
                    booknetic.toast(response.message, 'success');
                    booknetic.loadModal('tenant_directory_settings.settings_view', {});
                });
            });
        });

    })(jQuery);
</script>
