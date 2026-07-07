<?php
defined('ABSPATH') or die();
?>

<div id="booknetic_settings_area">
    <div class="actions_panel clearfix">
        <button type="button" class="btn btn-lg btn-success settings-save-btn float-right">
            <i class="fa fa-check pr-2"></i>
            <?php echo bkntc__('SAVE CHANGES') ?>
        </button>
    </div>

    <div class="settings-light-portlet">
        <div class="ms-title">
            <?php echo bkntc__('Tenant Directory'); ?>
        </div>
        <div class="ms-content">
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
                <div class="fs_data_table_wrapper">
                    <table class="fs_data_table elegant_table" id="tbl_business_types">
                        <thead>
                            <tr>
                                <th><?php echo bkntc__('ID')?></th>
                                <th><?php echo bkntc__('Name')?></th>
                                <th><?php echo bkntc__('Sort Number')?></th>
                                <th><?php echo bkntc__('Usage Count')?></th>
                                <th class="text-right"><?php echo bkntc__('Actions')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($parameters['businessTypes'])): ?>
                                <tr class="empty-row"><td colspan="5" class="text-center"><?php echo bkntc__('No business types created yet.')?></td></tr>
                            <?php else: ?>
                                <?php foreach ($parameters['businessTypes'] as $type): ?>
                                    <tr data-id="<?php echo (int)$type->id?>">
                                        <td><?php echo (int)$type->id?></td>
                                        <td class="col-name"><?php echo htmlspecialchars($type->name)?></td>
                                        <td class="col-sort"><?php echo (int)$type->sort_number?></td>
                                        <td><?php echo (int)($type->in_use_count ?? 0)?></td>
                                        <td class="text-right">
                                            <button type="button" class="btn btn-xs btn-default edit-type-btn"><i class="fa fa-pencil-alt"></i></button>
                                            <button type="button" class="btn btn-xs btn-danger delete-type-btn"><i class="fa fa-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Keywords Tab -->
            <div class="tab-pane" id="tab_keywords">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5><?php echo bkntc__('Keywords')?></h5>
                    <button type="button" class="btn btn-sm btn-primary" id="btn_add_keyword"><i class="fa fa-plus"></i> <?php echo bkntc__('ADD NEW')?></button>
                </div>
                <div class="fs_data_table_wrapper">
                    <table class="fs_data_table elegant_table" id="tbl_keywords">
                        <thead>
                            <tr>
                                <th><?php echo bkntc__('ID')?></th>
                                <th><?php echo bkntc__('Name')?></th>
                                <th><?php echo bkntc__('Usage Count')?></th>
                                <th class="text-right"><?php echo bkntc__('Actions')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($parameters['keywords'])): ?>
                                <tr class="empty-row"><td colspan="4" class="text-center"><?php echo bkntc__('No keywords created yet.')?></td></tr>
                            <?php else: ?>
                                <?php foreach ($parameters['keywords'] as $keyword): ?>
                                    <tr data-id="<?php echo (int)$keyword->id?>">
                                        <td><?php echo (int)$keyword->id?></td>
                                        <td class="col-name"><?php echo htmlspecialchars($keyword->name)?></td>
                                        <td><?php echo (int)($keyword->in_use_count ?? 0)?></td>
                                        <td class="text-right">
                                            <button type="button" class="btn btn-xs btn-default edit-keyword-btn"><i class="fa fa-pencil-alt"></i></button>
                                            <button type="button" class="btn btn-xs btn-danger delete-keyword-btn"><i class="fa fa-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Directory Requests Tab -->
            <div class="tab-pane" id="tab_requests">
                <div class="fs_data_table_wrapper">
                    <table class="fs_data_table elegant_table">
                        <thead>
                            <tr>
                                <th><?php echo bkntc__('Tenant ID')?></th>
                                <th><?php echo bkntc__('Tenant Name')?></th>
                                <th><?php echo bkntc__('Email')?></th>
                                <th><?php echo bkntc__('Business Title')?></th>
                                <th><?php echo bkntc__('Status')?></th>
                                <th class="text-right"><?php echo bkntc__('Actions')?></th>
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
                                            <span class="bkntc-status-badge" style="display: inline-block !important; padding: 6px 16px !important; font-size: 12px !important; font-weight: 700 !important; border-radius: 20px !important; text-align: center !important; white-space: nowrap !important; color: #fff !important; background-color: <?php 
                                                echo $request->status === 'approved' ? '#2ec866' : ($request->status === 'pending' ? '#f5a623' : ($request->status === 'rejected' ? '#ff5c75' : '#8a96a0'));
                                            ?> !important; letter-spacing: 0.5px !important;"><?php echo strtoupper($request->status)?></span>
                                        </td>
                                        <td class="text-right">
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
</div>
</div>

<script type="text/javascript">
    (function($) {
        "use strict";

        // Safe HTML escaping using string replacement (no DOM creation)
        function esc(str) {
            return String(str == null ? "" : str)
                .replace(/[&]/g, String.fromCharCode(38) + "amp;")
                .replace(/[<]/g, String.fromCharCode(38) + "lt;")
                .replace(/[>]/g, String.fromCharCode(38) + "gt;")
                .replace(/["]/g, String.fromCharCode(38) + "quot;");
        }

        // Initialize Select2 dropdowns
        $("#input_tenant_page, #input_search_page, #input_price_format").select2({
            theme: "bootstrap",
            placeholder: booknetic.__("select"),
            width: "100%"
        });

        // 1. General Settings Save via portlet button
        $("#booknetic_settings_area").on("click", ".settings-save-btn", function() {
            var page_id = $("#input_tenant_page").val();
            var search_page_id = $("#input_search_page").val();
            var price_format = $("#input_price_format").val();
            var gallery_required = $("#input_gallery_required").is(":checked") ? "on" : "off";

            if (!page_id || !search_page_id) {
                booknetic.toast(booknetic.__("Please fill in all required fields."), "unsuccess");
                return;
            }

            booknetic.ajax("tenant_directory_settings.settings_save", {
                tenant_directory_page_id: page_id,
                tenant_directory_search_page_id: search_page_id,
                tenant_directory_price_format: price_format,
                tenant_directory_gallery_required: gallery_required
            }, function(response) {
                booknetic.toast(response.message, "success");
            });
        });

        // === Helper: build a Business Type table row ===
        function buildTypeRow(id, name, sortNumber, usageCount) {
            var tr = document.createElement("tr");
            tr.setAttribute("data-id", id);
            tr.innerHTML = "<td>" + esc(id) + "</td>"
                + '<td class="col-name">' + esc(name) + "</td>"
                + '<td class="col-sort">' + esc(sortNumber) + "</td>"
                + "<td>" + esc(usageCount) + "</td>"
                + "<td>"
                + '<button type="button" class="btn btn-xs btn-default edit-type-btn"><i class="fa fa-pencil-alt"></i></button> '
                + '<button type="button" class="btn btn-xs btn-danger delete-type-btn"><i class="fa fa-trash"></i></button>'
                + "</td>";
            return tr;
        }

        // === Helper: build a Keyword table row ===
        function buildKeywordRow(id, name, usageCount) {
            var tr = document.createElement("tr");
            tr.setAttribute("data-id", id);
            tr.innerHTML = "<td>" + esc(id) + "</td>"
                + '<td class="col-name">' + esc(name) + "</td>"
                + "<td>" + esc(usageCount) + "</td>"
                + "<td>"
                + '<button type="button" class="btn btn-xs btn-default edit-keyword-btn"><i class="fa fa-pencil-alt"></i></button> '
                + '<button type="button" class="btn btn-xs btn-danger delete-keyword-btn"><i class="fa fa-trash"></i></button>'
                + "</td>";
            return tr;
        }

        // 2. Business Types CRUD
        function openBusinessTypeModal(id, oldName, oldSort) {
            oldName = oldName || "";
            oldSort = oldSort || 0;
            var titleText = id > 0 ? booknetic.__("Edit Business Type") : booknetic.__("Add Business Type");
            var iconClass = id > 0 ? "fa fa-pencil-alt" : "fa fa-plus";
            var modalHtml = '<div class="fs-modal-title">'
                + '<div class="title-icon badge-lg badge-purple"><i class="' + iconClass + '"></i></div>'
                + '<div class="title-text">' + esc(titleText) + '</div>'
                + '<div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>'
                + '</div>'
                + '<div class="fs-modal-body"><div class="fs-modal-body-inner">'
                + '<form id="modalBusinessTypeForm">'
                + '<div class="form-row"><div class="form-group col-md-12">'
                + '<label for="modal_type_name">' + booknetic.__("Name") + ' <span class="required-star">*</span></label>'
                + '<input type="text" class="form-control" id="modal_type_name" value="' + esc(oldName) + '">'
                + '</div></div>'
                + '<div class="form-row"><div class="form-group col-md-12">'
                + '<label for="modal_type_sort">' + booknetic.__("Sort Number") + '</label>'
                + '<input type="number" class="form-control" id="modal_type_sort" value="' + esc(oldSort) + '">'
                + '</div></div>'
                + '</form>'
                + '</div></div>'
                + '<div class="fs-modal-footer">'
                + '<button type="button" class="btn btn-lg btn-outline-secondary" data-dismiss="modal">' + booknetic.__("CANCEL") + '</button>'
                + '<button type="button" class="btn btn-lg btn-primary" id="modal_save_type_btn">' + booknetic.__("SAVE") + '</button>'
                + '</div>';
            var modal = booknetic.modal(modalHtml, { width: "500px" });

            $("#modal_save_type_btn").on("click", function() {
                var name = $("#modal_type_name").val();
                var sort = $("#modal_type_sort").val();
                if (!name) {
                    booknetic.toast(booknetic.__("Name is required!"), "unsuccess");
                    return;
                }
                booknetic.ajax("tenant_directory_settings.business_types_save", {
                    id: id,
                    name: name,
                    sort_number: parseInt(sort) || 0
                }, function(response) {
                    booknetic.toast(response.message, "success");
                    booknetic.modalHide($(modal[2]));

                    var savedId = response.id;
                    var savedName = response.name;
                    var savedSort = response.sort_number;
                    var tbody = $("#tbl_business_types tbody");

                    // Remove the "no data" empty row if present
                    tbody.find("tr.empty-row").remove();

                    if (id > 0) {
                        // Update existing row
                        var row = tbody.find("tr[data-id=" + id + "]");
                        row.find(".col-name").text(savedName);
                        row.find(".col-sort").text(savedSort);
                    } else {
                        // Append new row
                        tbody.append(buildTypeRow(savedId, savedName, savedSort, 0));
                    }
                });
            });
        }

        $("#btn_add_business_type").click(function() {
            openBusinessTypeModal(0);
        });

        $(document).off("click", ".edit-type-btn").on("click", ".edit-type-btn", function() {
            var row = $(this).closest("tr");
            var id = row.data("id");
            var oldName = row.find(".col-name").text();
            var oldSort = row.find(".col-sort").text();
            openBusinessTypeModal(id, oldName, parseInt(oldSort) || 0);
        });

        $(document).off("click", ".delete-type-btn").on("click", ".delete-type-btn", function() {
            var row = $(this).closest("tr");
            var id = row.data("id");

            var deleteConfirmHtml = '<div class="p-3 text-center"><p>' + booknetic.__("Are you sure you want to delete this business type?") + '</p></div>';
            booknetic.confirmV2(deleteConfirmHtml, booknetic.__("DELETE"), booknetic.__("CANCEL"), booknetic.__("Delete Business Type"), function() {
                booknetic.ajax("tenant_directory_settings.business_types_delete", { id: id }, function(response) {
                    booknetic.toast(response.message, "success");
                    row.remove();
                    if ($("#tbl_business_types tbody tr").length === 0) {
                        var emptyTr = document.createElement("tr");
                        emptyTr.className = "empty-row";
                        emptyTr.innerHTML = '<td colspan="5" class="text-center">' + booknetic.__("No business types created yet.") + '</td>';
                        $("#tbl_business_types tbody").append(emptyTr);
                    }
                });
            });
        });

        // 3. Keywords CRUD
        function openKeywordModal(id, oldName) {
            oldName = oldName || "";
            var titleText = id > 0 ? booknetic.__("Edit Keyword") : booknetic.__("Add Keyword");
            var iconClass = id > 0 ? "fa fa-pencil-alt" : "fa fa-plus";
            var modalHtml = '<div class="fs-modal-title">'
                + '<div class="title-icon badge-lg badge-purple"><i class="' + iconClass + '"></i></div>'
                + '<div class="title-text">' + esc(titleText) + '</div>'
                + '<div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>'
                + '</div>'
                + '<div class="fs-modal-body"><div class="fs-modal-body-inner">'
                + '<form id="modalKeywordForm">'
                + '<div class="form-row"><div class="form-group col-md-12">'
                + '<label for="modal_keyword_name">' + booknetic.__("Name") + ' <span class="required-star">*</span></label>'
                + '<input type="text" class="form-control" id="modal_keyword_name" value="' + esc(oldName) + '">'
                + '</div></div>'
                + '</form>'
                + '</div></div>'
                + '<div class="fs-modal-footer">'
                + '<button type="button" class="btn btn-lg btn-outline-secondary" data-dismiss="modal">' + booknetic.__("CANCEL") + '</button>'
                + '<button type="button" class="btn btn-lg btn-primary" id="modal_save_keyword_btn">' + booknetic.__("SAVE") + '</button>'
                + '</div>';
            var modal = booknetic.modal(modalHtml, { width: "500px" });

            $("#modal_save_keyword_btn").on("click", function() {
                var name = $("#modal_keyword_name").val();
                if (!name) {
                    booknetic.toast(booknetic.__("Name is required!"), "unsuccess");
                    return;
                }
                booknetic.ajax("tenant_directory_settings.keywords_save", {
                    id: id,
                    name: name
                }, function(response) {
                    booknetic.toast(response.message, "success");
                    booknetic.modalHide($(modal[2]));

                    var savedId = response.id;
                    var savedName = response.name;
                    var tbody = $("#tbl_keywords tbody");

                    // Remove the "no data" empty row if present
                    tbody.find("tr.empty-row").remove();

                    if (id > 0) {
                        // Update existing row
                        var row = tbody.find("tr[data-id=" + id + "]");
                        row.find(".col-name").text(savedName);
                    } else {
                        // Append new row
                        tbody.append(buildKeywordRow(savedId, savedName, 0));
                    }
                });
            });
        }

        $("#btn_add_keyword").click(function() {
            openKeywordModal(0);
        });

        $(document).off("click", ".edit-keyword-btn").on("click", ".edit-keyword-btn", function() {
            var row = $(this).closest("tr");
            var id = row.data("id");
            var oldName = row.find(".col-name").text();
            openKeywordModal(id, oldName);
        });

        $(document).off("click", ".delete-keyword-btn").on("click", ".delete-keyword-btn", function() {
            var row = $(this).closest("tr");
            var id = row.data("id");

            var deleteConfirmHtml = '<div class="p-3 text-center"><p>' + booknetic.__("Are you sure you want to delete this keyword?") + '</p></div>';
            booknetic.confirmV2(deleteConfirmHtml, booknetic.__("DELETE"), booknetic.__("CANCEL"), booknetic.__("Delete Keyword"), function() {
                booknetic.ajax("tenant_directory_settings.keywords_delete", { id: id }, function(response) {
                    booknetic.toast(response.message, "success");
                    row.remove();
                    if ($("#tbl_keywords tbody tr").length === 0) {
                        var emptyTr = document.createElement("tr");
                        emptyTr.className = "empty-row";
                        emptyTr.innerHTML = '<td colspan="4" class="text-center">' + booknetic.__("No keywords created yet.") + '</td>';
                        $("#tbl_keywords tbody").append(emptyTr);
                    }
                });
            });
        });

        // 4. Directory Request Review
        $(document).off("click", ".review-request-btn").on("click", ".review-request-btn", function() {
            var btn = $(this);
            var id = btn.data("id");
            var title = btn.data("title");
            var status = btn.data("status");
            var notes = btn.data("notes") || "";

            var reviewHtml = '<div class="fs-modal-title">'
                + '<div class="title-icon badge-lg badge-purple"><i class="fa fa-search"></i></div>'
                + '<div class="title-text">' + booknetic.__("Review Directory Request") + '</div>'
                + '<div class="close-btn" data-dismiss="modal"><i class="fa fa-times"></i></div>'
                + '</div>'
                + '<div class="fs-modal-body"><div class="fs-modal-body-inner">'
                + '<div class="form-row"><div class="form-group col-md-12">'
                + '<label><strong>' + booknetic.__("Business Title") + ':</strong></label>'
                + '<div class="form-control-plaintext">' + esc(title) + '</div>'
                + '</div></div>'
                + '<div class="form-row"><div class="form-group col-md-12">'
                + '<label for="review_status">' + booknetic.__("Status") + ' <span class="required-star">*</span></label>'
                + '<select class="form-control" id="review_status">'
                + '<option value="approved"' + (status === "approved" ? " selected" : "") + '>' + booknetic.__("Approved") + '</option>'
                + '<option value="rejected"' + (status === "rejected" ? " selected" : "") + '>' + booknetic.__("Rejected") + '</option>'
                + '<option value="hidden"' + (status === "hidden" ? " selected" : "") + '>' + booknetic.__("Hidden") + '</option>'
                + '<option value="pending"' + (status === "pending" ? " selected" : "") + '>' + booknetic.__("Pending") + '</option>'
                + '</select>'
                + '</div></div>'
                + '<div class="form-row"><div class="form-group col-md-12">'
                + '<label for="review_notes">' + booknetic.__("Review Notes") + '</label>'
                + '<textarea class="form-control" rows="4" id="review_notes">' + esc(notes) + '</textarea>'
                + '</div></div>'
                + '</div></div>'
                + '<div class="fs-modal-footer">'
                + '<button type="button" class="btn btn-lg btn-outline-secondary" data-dismiss="modal">' + booknetic.__("CANCEL") + '</button>'
                + '<button type="button" class="btn btn-lg btn-primary" id="modal_submit_review_btn">' + booknetic.__("SUBMIT") + '</button>'
                + '</div>';

            var modal = booknetic.modal(reviewHtml, { width: "500px" });

            $("#review_status").select2({
                theme: "bootstrap",
                placeholder: booknetic.__("select"),
                width: "100%"
            });

            $("#modal_submit_review_btn").on("click", function() {
                var newStatus = $("#review_status").val();
                var newNotes = $("#review_notes").val();

                booknetic.ajax("tenant_directory_settings.review_directory_request", {
                    id: id,
                    status: newStatus,
                    review_notes: newNotes
                }, function(response) {
                    booknetic.toast(response.message, "success");
                    booknetic.modalHide($(modal[2]));

                    // Update the status badge and button data in the row
                    var statusRow = btn.closest("tr");
                    var badgeBg = newStatus === "approved" ? "#2ec866" : (newStatus === "pending" ? "#f5a623" : (newStatus === "rejected" ? "#ff5c75" : "#8a96a0"));
                    statusRow.find(".bkntc-status-badge").attr("style", "display: inline-block !important; padding: 6px 16px !important; font-size: 12px !important; font-weight: 700 !important; border-radius: 20px !important; text-align: center !important; white-space: nowrap !important; color: #fff !important; background-color: " + badgeBg + " !important; letter-spacing: 0.5px !important;").text(newStatus.toUpperCase());
                    btn.data("status", newStatus);
                    btn.data("notes", newNotes);
                });
            });
        });

    })(jQuery);
</script>
