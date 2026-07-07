<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\Invoices\InvoicesAddon;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Invoices\bkntc__;
?>

<script>
    var invoice_all_shortcodes = <?php echo json_encode($parameters['shortcode_list']) ?>;

    var invoice_all_shortcodes_obj = {};
    invoice_all_shortcodes.forEach((value,index)=>{
        invoice_all_shortcodes_obj[value.code] = value.name;
    });
</script>

<script src="<?php echo InvoicesAddon::loadAsset('assets/backend/js/edit.js')?>" id="invoice-script" data-id="<?php echo (int)$parameters['id']?>"></script>
<link rel="stylesheet" href="<?php echo InvoicesAddon::loadAsset('assets/backend/css/edit.css')?>" type="text/css">
<script src="<?php echo Helper::assets('plugins/summernote/summernote-lite.min.js')?>"></script>
<link rel="stylesheet" href="<?php echo Helper::assets('plugins/summernote/summernote-lite.min.css')?>">
<script src="<?php echo Helper::assets('js/summernote.js')?>"></script>
<link rel="stylesheet" href="<?php echo Helper::assets('css/summernote.css')?>" type="text/css">

<div class="m_header clearfix">
	<div class="m_head_title float-left"><?php echo bkntc__('Invoices')?></div>
	<div class="m_head_actions float-right">
		<button type="button" class="btn btn-lg btn-success float-right ml-1" id="invoice_save_btn"><i class="fa fa-check pr-2"></i> <?php echo bkntc__('SAVE CHANGES')?></button>
		<button type="button" class="btn btn-lg btn-outline-secondary float-right" id="download_preview"><i class="fa fa-download pr-2"></i> <?php echo bkntc__('SAVE & DOWNLOAD PREVIEW')?></button>
	</div>
</div>

<div class="fs_separator"></div>

<div class="row m-4">

	<div class="col-xl-12 col-md-12 col-lg-12 p-3 pr-md-1">
		<div class="fs_portlet">
			<div class="fs_portlet_title"><?php echo bkntc__('INVOICE')?></div>
			<div class="fs_portlet_content">

				<div class="form-row">
					<div class="form-group col-md-5">
						<label><?php echo bkntc__('PDF Name')?></label>
						<div class="input-group mb-3">
							<input type="text" class="form-control" placeholder="<?php echo bkntc__('PDF Name')?>" id="input_name" value="<?php echo htmlspecialchars($parameters['info']['name'])?>">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">.pdf</span>
							</div>
						</div>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-12">
						<label><?php echo bkntc__('Content')?></label>
						<div id="invoice_body_rt">
                            <textarea name="" id="invoice_body" cols="30" rows="10"><?php echo htmlspecialchars($parameters['info']['content'])?></textarea>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>

</div>
