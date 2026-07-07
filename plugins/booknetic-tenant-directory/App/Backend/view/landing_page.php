<?php
defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

$directory = $parameters['directory'];
?>

<style>
    #tenant_landing_page_area {
        padding: 30px !important;
        margin: 0 auto !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
    @media (max-width: 768px) {
        #tenant_landing_page_area {
            padding: 15px !important;
        }
        #tenant_landing_page_area .row {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
        #tenant_landing_page_area .col-md-9,
        #tenant_landing_page_area .col-md-3 {
            padding-left: 0 !important;
            padding-right: 0 !important;
            flex: 0 0 100% !important;
            max-width: 100% !important;
            margin-bottom: 20px !important;
        }
    }
    
    .gallery-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 10px;
    }
    .gallery-item {
        position: relative;
        width: 140px;
        height: 110px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e3eaef;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .gallery-item .remove-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: rgba(239, 68, 68, 0.9) !important;
        color: white !important;
        border: none !important;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 11px;
        transition: background 0.2s;
        padding: 0 !important;
    }
    .gallery-item .remove-btn:hover {
        background: rgb(239, 68, 68) !important;
    }
    .gallery-upload-box {
        width: 140px;
        height: 110px;
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        background: #fff;
        transition: border-color 0.2s, background 0.2s;
    }
    .gallery-upload-box:hover {
        border-color: #4f46e5;
        background: #f8fafc;
    }
    .gallery-upload-box .plus-icon {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background: #6366f1;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        margin-bottom: 8px;
    }
    .gallery-upload-box span {
        font-size: 11px;
        font-weight: 500;
        color: #4b5563;
        text-align: center;
    }
</style>

<div id="tenant_landing_page_area">
    <div class="mb-4 text-right">
        <button type="button" class="btn btn-lg btn-success" id="btn_save_landing"><i class="fa fa-check pr-2"></i> <?php echo bkntc__('SAVE CHANGES')?></button>
    </div>

    <div class="row pt-4">
        <!-- Left Column: Details form -->
        <div class="col-md-9">
            <div class="card p-4">
                <h5 class="mb-4"><?php echo bkntc__('Landing Page Details')?></h5>
                <form id="landingPageForm">
                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label for="input_title"><?php echo bkntc__('Business Title')?> <span class="required-star">*</span></label>
                            <input type="text" class="form-control" id="input_title" value="<?php echo htmlspecialchars($directory->title)?>" placeholder="<?php echo bkntc__('e.g. The Neighborhood Barber Co.')?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="input_business_type"><?php echo bkntc__('Business Type')?> <span class="required-star">*</span></label>
                            <select class="form-control" id="input_business_type">
                                <option value=""><?php echo bkntc__('Select Business Type...')?></option>
                                <?php foreach ($parameters['businessTypes'] as $type): ?>
                                    <option value="<?php echo (int)$type->id?>" <?php echo $type->id == $directory->business_type_id ? 'selected' : ''?>><?php echo htmlspecialchars($type->name)?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if ($parameters['priceFormat'] === 'min_max'): ?>
                            <div class="form-group col-md-3">
                                <label for="input_price_min"><?php echo bkntc__('Min Price')?> ($)</label>
                                <input type="number" class="form-control" id="input_price_min" value="<?php echo htmlspecialchars($directory->price_min)?>" min="0">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="input_price_max"><?php echo bkntc__('Max Price')?> ($)</label>
                                <input type="number" class="form-control" id="input_price_max" value="<?php echo htmlspecialchars($directory->price_max)?>" min="0">
                            </div>
                        <?php else: ?>
                            <div class="form-group col-md-6">
                                <label for="input_price_level"><?php echo bkntc__('Price Level')?></label>
                                <select class="form-control" id="input_price_level">
                                    <option value="$" <?php echo $directory->price_level === '$' ? 'selected' : ''?>>$ (<?php echo bkntc__('Budget friendly')?>)</option>
                                    <option value="$$" <?php echo $directory->price_level === '$$' ? 'selected' : ''?>>$$ (<?php echo bkntc__('Mid-range')?>)</option>
                                    <option value="$$$" <?php echo $directory->price_level === '$$$' ? 'selected' : ''?>>$$$ (<?php echo bkntc__('Expensive')?>)</option>
                                    <option value="$$$$" <?php echo $directory->price_level === '$$$$' ? 'selected' : ''?>>$$$$ (<?php echo bkntc__('Luxury')?>)</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label for="input_keywords"><?php echo bkntc__('Keywords')?>:</label>
                            <select class="form-control" id="input_keywords" multiple="multiple">
                                <?php foreach ($parameters['keywordsList'] as $kw): ?>
                                    <option value="<?php echo htmlspecialchars($kw->name)?>" <?php echo in_array($kw->name, $parameters['selectedKeywords']) ? 'selected' : ''?>><?php echo htmlspecialchars($kw->name)?></option>
                                <?php endforeach; ?>
                                <?php foreach (array_diff($parameters['selectedKeywords'], array_column($parameters['keywordsList'], 'name')) as $customKw): ?>
                                    <option value="<?php echo htmlspecialchars($customKw)?>" selected><?php echo htmlspecialchars($customKw)?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted"><?php echo bkntc__('Type and press Enter to add new custom keywords.')?></small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label><?php echo bkntc__('Image Gallery')?>:</label>
                            <div class="gallery-grid" id="gallery_grid_container">
                                <?php foreach ($parameters['gallery'] as $imgUrl): ?>
                                    <div class="gallery-item">
                                        <img src="<?php echo htmlspecialchars($imgUrl) ?>" alt="Gallery Image">
                                        <input type="hidden" class="gallery-input" value="<?php echo htmlspecialchars($imgUrl) ?>">
                                        <button type="button" class="remove-btn remove-gallery-item"><i class="fa fa-times"></i></button>
                                    </div>
                                <?php endforeach; ?>
                                <div class="gallery-upload-box" id="btn_upload_image">
                                    <div class="plus-icon"><i class="fa fa-plus"></i></div>
                                    <span>Add new image</span>
                                </div>
                            </div>
                            <input type="file" id="gallery_file_input" style="display: none;" accept="image/*" multiple>
                        </div>
                    </div>

                    <div class="form-row pt-3">
                        <div class="form-group col-md-6">
                            <label for="input_email"><?php echo bkntc__('Contact Email')?></label>
                            <input type="email" class="form-control" id="input_email" value="<?php echo htmlspecialchars($directory->contact_email)?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="input_phone"><?php echo bkntc__('Contact Phone')?></label>
                            <input type="text" class="form-control" id="input_phone" value="<?php echo htmlspecialchars($directory->contact_phone)?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label><?php echo bkntc__('Social Media Links')?>:</label>
                            <div class="d-flex mb-2">
                                <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                                <input type="text" class="form-control social-input" data-platform="facebook" value="<?php echo htmlspecialchars($parameters['socialLinks']['facebook'] ?? '')?>" placeholder="https://facebook.com/...">
                            </div>
                            <div class="d-flex mb-2">
                                <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                <input type="text" class="form-control social-input" data-platform="instagram" value="<?php echo htmlspecialchars($parameters['socialLinks']['instagram'] ?? '')?>" placeholder="https://instagram.com/...">
                            </div>
                            <div class="d-flex mb-2">
                                <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                <input type="text" class="form-control social-input" data-platform="twitter" value="<?php echo htmlspecialchars($parameters['socialLinks']['twitter'] ?? '')?>" placeholder="https://twitter.com/...">
                            </div>
                        </div>
                    </div>

                    <div class="form-row pt-3">
                        <div class="form-group col-md-12">
                            <label><?php echo bkntc__('Display on Landing Page')?>:</label>
                            <div class="d-flex align-items-center flex-wrap pt-2">
                                <div class="custom-control custom-switch pr-4 mb-2">
                                    <input type="checkbox" class="custom-control-input visibility-switch" id="switch_show_address" data-setting="show_address" <?php echo ($parameters['socialLinks']['show_address'] ?? 'on') === 'on' ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="switch_show_address"><?php echo bkntc__('Show Address')?></label>
                                </div>
                                <div class="custom-control custom-switch pr-4 mb-2">
                                    <input type="checkbox" class="custom-control-input visibility-switch" id="switch_show_card" data-setting="show_card" <?php echo ($parameters['socialLinks']['show_card'] ?? 'on') === 'on' ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="switch_show_card"><?php echo bkntc__('Show Card & terminal')?></label>
                                </div>
                                <div class="custom-control custom-switch mb-2">
                                    <input type="checkbox" class="custom-control-input visibility-switch" id="switch_show_cash" data-setting="show_cash" <?php echo ($parameters['socialLinks']['show_cash'] ?? 'on') === 'on' ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="switch_show_cash"><?php echo bkntc__('Show Cash')?></label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row pt-3">
                        <div class="form-group col-md-12">
                            <label for="input_description"><?php echo bkntc__('Description')?>:</label>
                            <textarea id="input_description" class="form-control" rows="8"><?php echo htmlspecialchars($directory->description)?></textarea>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Status info & submit controls -->
        <div class="col-md-3">
            <div class="card p-4">
                <h5 class="mb-3"><?php echo bkntc__('Publish Status')?></h5>
                <div class="mb-4">
                    <span style="display: inline-block !important; padding: 6px 16px !important; font-size: 12px !important; font-weight: 700 !important; border-radius: 20px !important; text-align: center !important; white-space: nowrap !important; color: #fff !important; background-color: <?php 
                        echo $directory->status === 'approved' ? '#2ec866' : ($directory->status === 'pending' ? '#f5a623' : ($directory->status === 'rejected' ? '#ff5c75' : '#8a96a0'));
                    ?> !important; letter-spacing: 0.5px !important;"><?php echo strtoupper($directory->status)?></span>
                </div>

                <?php if (!empty($directory->review_notes)): ?>
                    <div class="alert alert-info p-3 mb-4">
                        <strong><?php echo bkntc__('Latest Admin Notes')?>:</strong><br>
                        <?php echo nl2br(htmlspecialchars($directory->review_notes))?>
                    </div>
                <?php endif; ?>

                <?php if ($directory->status === 'draft' || $directory->status === 'rejected'): ?>
                    <button type="button" class="btn btn-block btn-primary mb-2" id="btn_request_review"><i class="fa fa-paper-plane mr-2"></i> <?php echo bkntc__('REQUEST REVIEW')?></button>
                <?php elseif ($directory->status === 'pending'): ?>
                    <button type="button" class="btn btn-block btn-danger mb-2" id="btn_cancel_request"><i class="fa fa-times mr-2"></i> <?php echo bkntc__('CANCEL REQUEST')?></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    (function($) {
        "use strict";

        // Initialize Select2 for keywords tag selection
        $('#input_keywords').select2({
            theme: 'bootstrap',
            tags: true,
            placeholder: booknetic.__('Select or add keywords...')
        });

        // Initialize rich editor for description
        if (typeof $.fn.summernote !== 'undefined') {
            $('#input_description').summernote({
                height: 250,
                tabsize: 2,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link']],
                    ['view', ['fullscreen', 'codeview']]
                ]
            });
        }

        // Gallery Upload / Grid Actions
        $('#btn_upload_image').click(function() {
            $('#gallery_file_input').trigger('click');
        });

        $('#gallery_file_input').change(function() {
            var files = this.files;
            if (files.length === 0) return;

            // Store files array reference and reset input value to allow uploading same file
            var fileInput = this;

            for (var i = 0; i < files.length; i++) {
                (function(file) {
                    // Create loading placeholder card
                    var placeholderId = 'upload_' + Math.random().toString(36).substr(2, 9);
                    var placeholderHtml = `
                        <div class="gallery-item" id="${placeholderId}">
                            <div class="spinner-border spinner-border-sm text-primary" role="status" style="width: 1.5rem; height: 1.5rem; border-width: 0.2em;">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    `;
                    $('#btn_upload_image').before(placeholderHtml);

                    var formData = new FormData();
                    formData.append('file', file);

                    booknetic.ajax('tenant_directory_landing.upload_gallery_image', formData, function(response) {
                        $('#' + placeholderId).remove();
                        if (response.status === 'ok') {
                            var itemHtml = `
                                <div class="gallery-item">
                                    <img src="${response.url}" alt="Gallery Image">
                                    <input type="hidden" class="gallery-input" value="${response.url}">
                                    <button type="button" class="remove-btn remove-gallery-item"><i class="fa fa-times"></i></button>
                                </div>
                            `;
                            $('#btn_upload_image').before(itemHtml);
                        } else {
                            booknetic.toast(response.error_msg || response.message || 'File upload failed.', 'unsuccess');
                        }
                    });
                })(files[i]);
            }
            $(fileInput).val(''); // Reset file input so same file can be uploaded again
        });

        $(document).on('click', '.remove-gallery-item', function() {
            $(this).closest('.gallery-item').remove();
        });

        // Collect Form Data Helper
        function getFormData(action) {
            let galleryUrls = [];
            $('.gallery-input').each(function() {
                let val = $(this).val().trim();
                if (val !== '') {
                    galleryUrls.push(val);
                }
            });

            let socialLinks = {};
            $('.social-input').each(function() {
                let platform = $(this).data('platform');
                socialLinks[platform] = $(this).val().trim();
            });
            // Collect visibility switch states
            $('.visibility-switch').each(function() {
                let settingName = $(this).data('setting');
                socialLinks[settingName] = $(this).is(':checked') ? 'on' : 'off';
            });

            let descriptionText = $('#input_description').val();
            if (typeof $.fn.summernote !== 'undefined') {
                descriptionText = $('#input_description').summernote('code');
            }

            return {
                title: $('#input_title').val(),
                business_type_id: $('#input_business_type').val(),
                price_range_type: $('#input_price_min').length > 0 ? 'min_max' : 'level',
                price_min: $('#input_price_min').val() || 0,
                price_max: $('#input_price_max').val() || 0,
                price_level: $('#input_price_level').val() || '$',
                keywords: $('#input_keywords').val() || [],
                gallery: galleryUrls,
                contact_email: $('#input_email').val(),
                contact_phone: $('#input_phone').val(),
                social_links: socialLinks,
                description: descriptionText,
                submit_action: action
            };
        }

        // Save Click
        $('#btn_save_landing').click(function() {
            let data = getFormData('save');
            booknetic.ajax('tenant_directory_landing.save_landing_page', data, function(response) {
                booknetic.toast(response.message, 'success');
                location.reload();
            });
        });

        // Request Review Click
        $('#btn_request_review').click(function() {
            let data = getFormData('request_review');
            booknetic.ajax('tenant_directory_landing.save_landing_page', data, function(response) {
                booknetic.toast(response.message, 'success');
                location.reload();
            });
        });

        // Cancel Request Click
        $('#btn_cancel_request').click(function() {
            let data = getFormData('cancel_request');
            booknetic.ajax('tenant_directory_landing.save_landing_page', data, function(response) {
                booknetic.toast(response.message, 'success');
                location.reload();
            });
        });

    })(jQuery);
</script>
