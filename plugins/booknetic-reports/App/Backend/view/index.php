<?php

defined( 'ABSPATH' ) or die();

use BookneticAddon\Reports\ReportsAddon;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Reports\bkntc__;


?>
<link rel="stylesheet" href="<?php echo ReportsAddon::loadAsset('assets/backend/css/main.css' )?>">
<script type="application/javascript" src="<?php echo ReportsAddon::loadAsset('assets/backend/js/reports.js' )?>"></script>
<script type="application/javascript" src="<?php echo ReportsAddon::loadAsset('assets/backend/js/chart.min.js' )?>"></script>

<div class="m_header clearfix">
	<div class="m_head_title float-left"><?php echo bkntc__('Reports')?> <span class="badge badge-warning row_count">4</span></div>
	<div class="m_head_actions float-right"></div>
</div>

<div id="module-reports">
    <input id="reports-locale" type="hidden" value="<?php echo get_locale() ?>">

    <div class="row">

        <div class="col-md-6 col-sm-12 col-xs-12 col-lg-6">
	        <div class="fs_portlet">
		        <div class="fs_portlet_title">
			        <div><?php echo bkntc__('Reports by the number of appointments')?></div>
			        <div>
				        <span class="actions_btn" data-toggle="dropdown"><span><?php echo bkntc__('Daily')?></span> <i class="fa fa-chevron-down pl-2"></i></span>
				        <div class="dropdown-menu dropdown-menu-right row-actions-area">
					        <button class="dropdown-item" data-appointment-report-via-count-type="daily" type="button"><?php echo bkntc__('Daily')?></button>
					        <button class="dropdown-item" data-appointment-report-via-count-type="monthly" type="button"><?php echo bkntc__('Monthly')?></button>
					        <button class="dropdown-item" data-appointment-report-via-count-type="annually" type="button"><?php echo bkntc__('Annually')?></button>
				        </div>
			        </div>
		        </div>
		        <div class="fs_portlet_content">
			        <div class="form-row">
				        <div class="form-group col-md-6 col-lg-3">
					        <select class="form-control" data-placeholder="<?php echo bkntc__('Service filter')?>" data-filter="service">
						        <option></option>
								<?php foreach( $parameters['services'] AS $service ):?>
						        <option value="<?php echo (int)$service->id?>"><?php echo htmlspecialchars($service->name)?></option>
						        <?php endforeach;?>
					        </select>
				        </div>
				        <div class="form-group col-md-6 col-lg-3">
					        <select class="form-control" data-placeholder="<?php echo bkntc__('Location filter')?>" data-filter="location">
						        <option></option>
						        <?php foreach( $parameters['locations'] AS $location ):?>
							        <option value="<?php echo (int)$location->id?>"><?php echo htmlspecialchars($location->name)?></option>
						        <?php endforeach;?>
					        </select>
				        </div>
				        <div class="form-group col-md-6 col-lg-3">
					        <select class="form-control" data-placeholder="<?php echo bkntc__('Staff filter')?>" data-filter="staff">
						        <option></option>
						        <?php foreach( $parameters['staff'] AS $staff ):?>
							        <option value="<?php echo (int)$staff->id?>"><?php echo htmlspecialchars($staff->name)?></option>
						        <?php endforeach;?>
					        </select>
				        </div>
                        <div class="form-group col-md-6 col-lg-3">
					        <select class="form-control" data-placeholder="<?php echo bkntc__('Status filter')?>" data-filter="status">
						        <option></option>
						        <?php foreach( $parameters['status'] AS $key => $status ):?>
							        <option value="<?php echo $key?>"><?php echo htmlspecialchars($status['title'])?></option>
						        <?php endforeach;?>
					        </select>
				        </div>
			        </div>
			        <div>
				        <canvas id="appointment-count"></canvas>
			        </div>
		        </div>
	        </div>
        </div>

        <div class="col-md-6 col-sm-12 col-xs-12 col-lg-6 mt-md-0 mt-4">
	        <div class="fs_portlet">
		        <div class="fs_portlet_title">
			        <div><?php echo bkntc__('Reports by appointment earnings')?> (<?php echo Helper::currencySymbol()?>)</div>
			        <div>
				        <span class="actions_btn" data-toggle="dropdown"><span><?php echo bkntc__('Daily')?></span> <i class="fa fa-chevron-down pl-2"></i></span>
				        <div class="dropdown-menu dropdown-menu-right row-actions-area">
					        <button class="dropdown-item" data-appointment-report-via-price-type="daily" type="button"><?php echo bkntc__('Daily')?></button>
					        <button class="dropdown-item" data-appointment-report-via-price-type="monthly" type="button"><?php echo bkntc__('Monthly')?></button>
					        <button class="dropdown-item" data-appointment-report-via-price-type="annually" type="button"><?php echo bkntc__('Annually')?></button>
				        </div>
			        </div>
		        </div>
		        <div class="fs_portlet_content">
			        <div class="form-row">
				        <div class="form-group col-md-6 col-lg-3">
					        <select class="form-control" data-placeholder="<?php echo bkntc__('Service filter')?>" data-filter="service">
						        <option></option>
						        <?php foreach( $parameters['services'] AS $service ):?>
							        <option value="<?php echo (int)$service->id?>"><?php echo htmlspecialchars($service->name)?></option>
						        <?php endforeach;?>
					        </select>
				        </div>
				        <div class="form-group col-md-6 col-lg-3">
					        <select class="form-control" data-placeholder="<?php echo bkntc__('Location filter')?>" data-filter="location">
						        <option></option>
						        <?php foreach( $parameters['locations'] AS $location ):?>
							        <option value="<?php echo (int)$location->id?>"><?php echo htmlspecialchars($location->name)?></option>
						        <?php endforeach;?>
					        </select>
				        </div>
				        <div class="form-group col-md-6 col-lg-3">
					        <select class="form-control" data-placeholder="<?php echo bkntc__('Staff filter')?>" data-filter="staff">
						        <option></option>
						        <?php foreach( $parameters['staff'] AS $staff ):?>
							        <option value="<?php echo (int)$staff->id?>"><?php echo htmlspecialchars($staff->name)?></option>
						        <?php endforeach;?>
					        </select>
				        </div>
                        <div class="form-group col-md-6 col-lg-3">
                            <select class="form-control" data-placeholder="<?php echo bkntc__('Status filter')?>" data-filter="status">
                                <option></option>
                                <?php foreach( $parameters['status'] AS $key => $status ):?>
                                    <option value="<?php echo $key?>"><?php echo htmlspecialchars($status['title'])?></option>
                                <?php endforeach;?>
                            </select>
                        </div>
			        </div>
			        <div>
				        <canvas id="appointment-price"></canvas>
			        </div>
		        </div>
	        </div>
        </div>
    </div>
	<div class="row mt-4">

        <div class="col-md-6 col-sm-12 col-xs-12 col-lg-6">
	        <div class="fs_portlet">
		        <div class="fs_portlet_title">
			        <div><?php echo bkntc__('Most earning locations')?></div>
			        <div>
				        <span class="actions_btn" data-toggle="dropdown"><span><?php echo bkntc__('This week')?></span> <i class="fa fa-chevron-down pl-2"></i></span>
				        <div class="dropdown-menu dropdown-menu-right row-actions-area">
					        <button class="dropdown-item" data-report-by-location-type="this-week" type="button"><?php echo bkntc__('This week')?></button>
					        <button class="dropdown-item" data-report-by-location-type="previous-week" type="button"><?php echo bkntc__('Previous week')?></button>
					        <button class="dropdown-item" data-report-by-location-type="this-month" type="button"><?php echo bkntc__('This month')?></button>
					        <button class="dropdown-item" data-report-by-location-type="previous-month" type="button"><?php echo bkntc__('Previous month')?></button>
					        <button class="dropdown-item" data-report-by-location-type="this-year" type="button"><?php echo bkntc__('This year')?></button>
					        <button class="dropdown-item" data-report-by-location-type="previous-year" type="button"><?php echo bkntc__('Previous year')?></button>
				        </div>
			        </div>
		        </div>
		        <div class="fs_portlet_content">
			        <canvas id="location-report"></canvas>
		        </div>
	        </div>
        </div>

        <div class="col-md-6 col-sm-12 col-xs-12 col-lg-6 mt-md-0 mt-4">
	        <div class="fs_portlet">
		        <div class="fs_portlet_title">
			        <div><?php echo bkntc__('Most earning staffs')?></div>
			        <div>
				        <span class="actions_btn" data-toggle="dropdown"><span><?php echo bkntc__('This week')?></span> <i class="fa fa-chevron-down pl-2"></i></span>
				        <div class="dropdown-menu dropdown-menu-right row-actions-area">
					        <button class="dropdown-item" data-report-by-staff-type="this-week" type="button"><?php echo bkntc__('This week')?></button>
					        <button class="dropdown-item" data-report-by-staff-type="previous-week" type="button"><?php echo bkntc__('Previous week')?></button>
					        <button class="dropdown-item" data-report-by-staff-type="this-month" type="button"><?php echo bkntc__('This month')?></button>
					        <button class="dropdown-item" data-report-by-staff-type="previous-month" type="button"><?php echo bkntc__('Previous month')?></button>
					        <button class="dropdown-item" data-report-by-staff-type="this-year" type="button"><?php echo bkntc__('This year')?></button>
					        <button class="dropdown-item" data-report-by-staff-type="previous-year" type="button"><?php echo bkntc__('Previous year')?></button>
				        </div>
			        </div>
		        </div>
		        <div class="fs_portlet_content">
			        <canvas id="staff-report"></canvas>
		        </div>
	        </div>
        </div>
    </div>
</div>