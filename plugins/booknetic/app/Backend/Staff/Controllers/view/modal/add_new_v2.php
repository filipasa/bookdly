<?php

defined('ABSPATH') or die();

use BookneticApp\Backend\Staff\DTOs\Response\StaffGetResponse;
use BookneticApp\Providers\UI\TabUI;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;

function breakTpl($start = '', $end = '', $display = false)
{
    ?>
	<div class="form-row break_line<?php echo $display ? '' : ' hidden' ?>">
		<div class="form-group col-md-9">
			<label for="input_duration" class="breaks-label"><?php echo bkntc__('Breaks')?></label>
			<div class="input-group">
				<div class="col-md-6 p-0 m-0"><select class="form-control break_start" placeholder="<?php echo bkntc__('Break start')?>"><option selected><?php echo ! empty($start) ? Date::time($start) : ''; ?></option></select></div>
				<div class="col-md-6 p-0 m-0"><select class="form-control break_end" placeholder="<?php echo empty($end) ? '' : ($end === "24:00" ? "24:00" : Date::time($end)); ?>"><option selected><?php echo empty($end) ? '' : ($end === "24:00" ? "24:00" : Date::time($end)); ?></option></select></div>
			</div>
		</div>

		<div class="form-group col-md-3">
			<img src="<?php echo Helper::assets('icons/unsuccess.svg')?>" class="delete-break-btn">
		</div>
	</div>
	<?php
}

/**
 * @var StaffGetResponse $parameters
 * @var mixed $_mn
 */
$isEdit = $parameters->getId() > 0;
?>
<link rel="stylesheet" href="<?php echo Helper::assets('css/intlTelInput.min.css', 'front-end')?>">
<link rel="stylesheet" href="<?php echo Helper::assets('css/add_new.css', 'Staff')?>">
<script type="application/javascript" src="<?php echo Helper::assets('js/intlTelInput.min.js', 'front-end')?>"></script>
<script>
    console.log("CALENDAR JS CHECK: VERSION 4");
    var telInputAssetUrl = "<?php echo Helper::assets('js/utilsIntlTelInput.js', 'front-end')?>";

    (function($) {
        var MONTH_NAMES = ['January','February','March','April','May','June',
                           'July','August','September','October','November','December'];
        var MONTH_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var DAY_HEADERS = ['Su','Mo','Tu','We','Th','Fr','Sa'];

        function createPicker(anchorEl, currentYear, currentMonth, onSelect) {
            $('.hol-picker-overlay').remove();

            var pickerYear = currentYear;
            var overlay = $('<div class="hol-picker-overlay"></div>');
            overlay.css({
                position: 'absolute',
                zIndex: 9999,
                background: '#fff',
                border: '1.5px solid #cbd5e1',
                borderRadius: '12px',
                boxShadow: '0 8px 32px rgba(0,0,0,.08)',
                padding: '16px',
                minWidth: '250px'
            });

            function buildPicker() {
                overlay.innerHTML = '';
                overlay.empty();

                var yearRow = $('<div style="display:flex; align-items:center; justify-content:center; gap:16px; margin-bottom:12px;"></div>');
                var prevYearBtn = $('<button type="button" style="background:none; border:none; cursor:pointer; font-size:20px; color:#6366f1;">\u2039</button>');
                var yearLabel = $('<span style="font-size:16px; font-weight:700; color:#0f172a; min-width:60px; text-align:center;"></span>').text(pickerYear);
                var nextYearBtn = $('<button type="button" style="background:none; border:none; cursor:pointer; font-size:20px; color:#6366f1;">\u203a</button>');

                prevYearBtn.on('click', function() { pickerYear--; buildPicker(); });
                nextYearBtn.on('click', function() { pickerYear++; buildPicker(); });

                yearRow.append(prevYearBtn).append(yearLabel).append(nextYearBtn);
                overlay.append(yearRow);

                var grid = $('<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:6px;"></div>');
                MONTH_SHORT.forEach(function(name, idx) {
                    var isSelected = false;
                    if (pickerYear === currentYear) {
                        if (idx === currentMonth) {
                            isSelected = true;
                        }
                    }
                    var btn = $('<button type="button"></button>').text(name);
                    btn.css({
                        border: 'none',
                        borderRadius: '6px',
                        padding: '8px 4px',
                        fontSize: '12px',
                        fontWeight: isSelected ? '700' : '500',
                        cursor: 'pointer',
                        background: isSelected ? '#6366f1' : 'transparent',
                        color: isSelected ? '#fff' : '#475569'
                    });
                    btn.on('click', function(e) {
                        e.stopPropagation();
                        overlay.remove();
                        onSelect(pickerYear, idx);
                    });
                    grid.append(btn);
                });
                overlay.append(grid);
            }

            buildPicker();

            var offset = anchorEl.position();
            overlay.css({
                top: (offset.top + anchorEl.outerHeight() + 8) + 'px',
                left: (offset.left) + 'px'
            });

            anchorEl.closest('.hol-stripe-wrap').css('position', 'relative').append(overlay);

            setTimeout(function() {
                $(document).one('click', function closer(e) {
                    if (!overlay.is(e.target)) {
                        if (overlay.has(e.target).length === 0) {
                            overlay.remove();
                            return;
                        }
                    }
                    $(document).one('click', closer);
                });
            }, 50);
        }

        var customCalendar = function(options) {
            var element = this;
            var today = new Date();
            var selectedDates = new Set();
            var baseYear = today.getFullYear();
            var baseMonth = today.getMonth();

            // Custom API
            var api = {
                getDataSource: function() {
                    var newArr = [];
                    selectedDates.forEach(function(k) {
                        var parts = k.split('-');
                        var d = new Date(parts[0], parts[1] - 1, parts[2]);
                        newArr.push({
                            id: 0,
                            startDate: d,
                            endDate: d
                        });
                    });
                    return newArr;
                },
                setDataSource: function(data) {
                    selectedDates.clear();
                    data.forEach(function(item) {
                        var d = item.startDate;
                        var key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                        selectedDates.add(key);
                    });
                    render();
                }
            };

            element.data('calendar', api);

            // Render double-month structural layout
            element.empty();
            var wrapper = $('<div class="hol-stripe-wrap" style="background:#fff; border:1.5px solid #cbd5e1; border-radius:14px; padding:28px 32px;"></div>');
            var mainContainer = $('<div style="display:flex; align-items:flex-start; gap:48px; justify-content:space-between;"></div>');

            // Panels
            var leftPanel = $('<div class="hol-month-panel" data-offset="0" style="flex:1;"></div>');
            var rightPanel = $('<div class="hol-month-panel" data-offset="1" style="flex:1;"></div>');
            var divider = $('<div style="width:1px; background:#cbd5e1; align-self:stretch;"></div>');

            mainContainer.append(leftPanel).append(divider).append(rightPanel);
            wrapper.append(mainContainer);
            element.append(wrapper);

            function renderMonth(panelEl, year, month) {
                panelEl.empty();
                
                var headerRow = $('<div style="display:flex; align-items:center; margin-bottom:20px;"></div>');
                var isLeft = panelEl.data('offset') === 0;

                var prevBtn = isLeft ? $('<button type="button" class="hol-prev-month" style="background:none; border:none; cursor:pointer; padding:4px 8px; color:#475569; font-size:20px; line-height:1;">\u2039</button>') : null;
                var nextBtn = !isLeft ? $('<button type="button" class="hol-next-month" style="background:none; border:none; cursor:pointer; padding:4px 8px; color:#475569; font-size:20px; line-height:1;">\u203a</button>') : null;
                
                var labelBtn = $('<button type="button" style="background:none; border:none; cursor:pointer; font-size:16px; font-weight:700; color:#6366f1; display:inline-flex; align-items:center; gap:5px; padding:4px 8px; border-radius:6px; margin:0 auto;"></button>');
                labelBtn.html(MONTH_NAMES[month] + ' ' + year + ' <svg viewBox="0 0 10 6" width="10" height="6" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 1l4 4 4-4"/></svg>');

                labelBtn.on('click', function(e) {
                    e.stopPropagation();
                    createPicker(labelBtn, year, month, function(newYear, newMonth) {
                        if (isLeft) {
                            baseYear = newYear;
                            baseMonth = newMonth;
                        } else {
                            if (newMonth === 0) {
                                baseYear = newYear - 1;
                                baseMonth = 11;
                            } else {
                                baseYear = newYear;
                                baseMonth = newMonth - 1;
                            }
                        }
                        render();
                    });
                });

                if (prevBtn) {
                    prevBtn.on('click', function() {
                        if (baseMonth === 0) { baseMonth = 11; baseYear--; } else baseMonth--;
                        render();
                    });
                    headerRow.append(prevBtn);
                }
                headerRow.append(labelBtn);
                if (nextBtn) {
                    nextBtn.on('click', function() {
                        if (baseMonth === 11) { baseMonth = 0; baseYear++; } else baseMonth++;
                        render();
                    });
                    headerRow.append(nextBtn);
                }

                panelEl.append(headerRow);

                var grid = $('<div style="display:grid; grid-template-columns:repeat(7,1fr); row-gap:4px; column-gap:0;"></div>');
                DAY_HEADERS.forEach(function(h) {
                    grid.append($('<div style="text-align:center; font-size:11px; font-weight:700; color:#94a3b8; padding-bottom:8px; text-transform:uppercase;"></div>').text(h));
                });

                var firstDow = new Date(year, month, 1).getDay();
                var daysInMonth = new Date(year, month + 1, 0).getDate();

                for (var i = 0; i < firstDow; i++) {
                    grid.append($('<div></div>'));
                }

                for (var d = 1; d <= daysInMonth; d++) {
                    (function(dayNum) {
                        var key = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(dayNum).padStart(2, '0');
                        var isSelected = selectedDates.has(key);
                        var isCurrentToday = false;
                        if (today.getFullYear() === year) {
                            if (today.getMonth() === month) {
                                if (today.getDate() === dayNum) {
                                    isCurrentToday = true;
                                }
                            }
                        }

                        var cell = $('<div style="display:flex; align-items:center; justify-content:center; width:34px; height:34px; margin:0 auto; border-radius:50%; font-size:13px; cursor:pointer; position:relative; font-weight:600; transition:all 0.12s ease;"></div>').text(dayNum);

                        if (isSelected) {
                            cell.css({ background: '#6366f1', color: '#fff' });
                        } else if (isCurrentToday) {
                            cell.css({ color: '#6366f1' });
                            cell.append($('<span style="position:absolute; bottom:2px; left:50%; transform:translateX(-50%); width:4px; height:4px; border-radius:50%; background:#6366f1;"></span>'));
                        } else {
                            cell.css({ color: '#475569' });
                        }

                        cell.on('mouseenter', function() {
                            if (!selectedDates.has(key)) {
                                cell.css({ background: '#eef2ff', color: '#6366f1' });
                            }
                        }).on('mouseleave', function() {
                            if (!selectedDates.has(key)) {
                                cell.css({ background: 'transparent', color: isCurrentToday ? '#6366f1' : '#475569' });
                            }
                        });

                        cell.on('click', function() {
                            if (options.clickDay) {
                                options.clickDay({ date: new Date(year, month, dayNum) });
                            }
                        });

                        grid.append(cell);
                    })(d);
                }

                panelEl.append(grid);
            }

            function render() {
                var rightYear = (baseMonth === 11) ? baseYear + 1 : baseYear;
                var rightMonth = (baseMonth + 1) % 12;
                renderMonth(leftPanel, baseYear, baseMonth);
                renderMonth(rightPanel, rightYear, rightMonth);
            }

            if (options.dataSource) {
                api.setDataSource(options.dataSource);
            } else {
                render();
            }
        };

        Object.defineProperty(jQuery.fn, 'calendar', {
            get: function() {
                return customCalendar;
            },
            set: function(val) {
                // Ignore overwrites
            },
            configurable: true
        });
    })(jQuery);
</script>
<script type="application/javascript" src="<?php echo Helper::assets('js/add_new.js', 'Staff')?>" id="add_new_JS" data-mn="<?php echo $_mn?>" data-staff-id="<?php echo $parameters->getId()?>" data-holidays="<?php echo htmlspecialchars($parameters->getHolidays())?>"></script>

<style>
/* Wireframe v6 Exact Design System for Staff Standalone Full-page */
#booknetic_staff_fullpage_container.fs-modal {
    position: relative !important;
    top: auto !important;
    left: auto !important;
    width: 100% !important;
    height: auto !important;
    max-width: 100% !important;
    margin: 0 !important;
    border-radius: 0 !important;
    background: #f8fafc !important;
    z-index: 1 !important;
    display: block !important;
    min-height: calc(100vh - 120px) !important;
    font-family: 'Inter', sans-serif;
    color: #0f172a;
    padding-bottom: 80px;
}

/* Fail-safe styling if loaded inside a Booknetic modal wrapper (e.g. from cached JS) */
.fs-modal:has(#addStaffForm):not(#booknetic_staff_fullpage_container) {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    max-width: 100% !important;
    margin: 0 !important;
    border-radius: 0 !important;
    background: #f8fafc !important;
    z-index: 99999 !important;
    overflow-y: auto !important;
}

/* Declarative hide of the background list to prevent double layout */
body:has(#booknetic_staff_fullpage_container:not(:empty)) .m_header,
body:has(#booknetic_staff_fullpage_container:not(:empty)) .bkc-page-container,
body:has(.fs-modal #addStaffForm:not(#booknetic_staff_fullpage_container)) .m_header,
body:has(.fs-modal #addStaffForm:not(#booknetic_staff_fullpage_container)) .bkc-page-container {
    display: none !important;
}

#addStaffForm {
    display: flex;
    flex-direction: column;
}

.fs-modal-title {
    display: none !important; /* We will render our own top bar inside the form */
}

/* Custom top bar matching wireframe_v6.html */
.wf-staff-top-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 32px;
    background: #fff;
    border-bottom: 1.5px solid #f1f5f9;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
}
.wf-staff-top-bar .back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    color: #334155;
    cursor: pointer;
    transition: color 0.18s ease;
}
.wf-staff-top-bar .back-link svg {
    width: 14px;
    height: 14px;
}
.wf-staff-top-bar .back-link:hover {
    color: #6366f1;
}
.wf-staff-top-bar .sep {
    color: #cbd5e1;
}
.wf-staff-top-bar .crumb.active {
    color: #0f172a;
}

/* Page Header block */
.wf-staff-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 32px 12px;
    background: #fff;
}
.wf-staff-page-header .appt-id {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
}
.wf-staff-page-header .appt-title {
    font-size: 13px;
    color: #64748b;
    margin-top: 4px;
}

/* Custom Styled Tabs for Staff */
.wf-staff-nav-tabs {
    border-bottom: 2px solid #f1f5f9 !important;
    background: #fff;
    margin-bottom: 24px !important;
    display: flex;
    gap: 24px;
    padding: 0 32px;
    list-style: none;
}
.wf-staff-nav-tabs .nav-item {
    margin-bottom: -2px;
}
.wf-staff-nav-tabs .nav-link {
    display: block;
    padding: 12px 0 14px;
    font-size: 14px;
    font-weight: 600;
    color: #64748b;
    border: none;
    border-bottom: 2px solid transparent;
    text-decoration: none;
    transition: all 0.15s ease;
    cursor: pointer;
}
.wf-staff-nav-tabs .nav-link:hover {
    color: #0f172a;
}
.wf-staff-nav-tabs .nav-link.active {
    color: #6366f1 !important;
    border-bottom: 2px solid #6366f1 !important;
}

.fs-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 0 !important;
    background: #f8fafc;
    margin-bottom: 80px;
}
.fs-modal-body-inner {
    padding: 0 !important;
}

.wf-staff-tab-content {
    padding: 0 32px;
    max-width: 1200px;
    margin: 0 auto 40px;
}

/* Layout for Details tab - 2 Columns grid */
.wf-staff-appt-layout {
    display: grid !important;
    grid-template-columns: 1fr 320px !important;
    gap: 24px !important;
    align-items: start !important;
    width: 100% !important;
}

.wf-staff-left-column {
    display: flex !important;
    flex-direction: column !important;
}

/* Sections Styling matching wireframe */
.form-section {
    background: #fff !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 12px !important;
    padding: 24px !important;
    margin-bottom: 20px !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02) !important;
}

.form-section-title {
    font-size: 13px !important;
    font-weight: 700 !important;
    color: #475569 !important;
    text-transform: uppercase !important;
    letter-spacing: .05em !important;
    padding-bottom: 16px !important;
    border-bottom: 1.5px solid #f1f5f9 !important;
    margin-bottom: 20px !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
}

.wf-staff-sidebar {
    display: flex !important;
    flex-direction: column !important;
    width: 320px !important;
}

.wf-staff-sidebar-card {
    background: #fff !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 12px !important;
    padding: 24px !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02) !important;
}

.wf-staff-sidebar-card-title {
    font-size: 13px !important;
    font-weight: 700 !important;
    color: #475569 !important;
    text-transform: uppercase !important;
    letter-spacing: .05em !important;
    padding-bottom: 12px !important;
    border-bottom: 1.5px solid #f1f5f9 !important;
    margin-bottom: 16px !important;
}

.wf-profile-avatar-wrap {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    border: 2px dashed #cbd5e1;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    overflow: hidden;
}

.wf-profile-avatar {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.wf-profile-avatar-placeholder {
    font-size: 28px;
    color: #94a3b8;
}

/* Style form elements to match wireframe */
#addStaffForm label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
#addStaffForm .form-control {
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 8px !important;
    height: 40px !important;
    padding: 8px 12px !important;
    font-size: 13px !important;
    color: #0f172a !important;
    box-shadow: none !important;
}
#addStaffForm .form-control:focus {
    border-color: #6366f1 !important;
}
#addStaffForm textarea.form-control {
    height: auto !important;
}

/* Weekly Schedule card design */
#set_specific_timesheet .form-row {
    background: #fff;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 16px;
    margin-left: 0;
    margin-right: 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    align-items: center;
}
#set_specific_timesheet .timesheet-label {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}
#set_specific_timesheet .copy_time_to_all {
    color: #6366f1;
    cursor: pointer;
    font-size: 14px;
}
#set_specific_timesheet .day_off_checkbox {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 24px;
}
#set_specific_timesheet .days_divider2 {
    display: none;
}
#set_specific_timesheet .add-break-btn {
    margin-bottom: 24px;
    color: #6366f1;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* Special Days Tab styling */
#tab_special_days .special-day-row {
    background: #fff;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
#tab_special_days .sd_break_footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 16px;
    border-top: 1px solid #f1f5f9;
    padding-top: 14px;
}
#tab_special_days .special-day-add-break-btn {
    color: #6366f1;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
}
#tab_special_days .remove-special-day-btn {
    color: #ef4444;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
}
#tab_special_days .add-special-day-btn {
    background: #6366f1 !important;
    border: none !important;
    border-radius: 8px !important;
    height: 40px !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    padding: 0 20px !important;
    color: #fff !important;
}

/* Holidays tab */
#tab_holidays .yearly_calendar {
    background: #fff;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

/* Footer layout */
.fs-modal-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 72px;
    background: #fff;
    border-top: 1.5px solid #f1f5f9;
    padding: 0 32px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    z-index: 99999;
}
.fs-modal-footer .btn {
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
}
.fs-modal-footer .btn-outline-secondary {
    background: none;
    color: #64748b;
    border: 1.5px solid #cbd5e1;
}
.fs-modal-footer .btn-outline-secondary:hover {
    background: #f1f5f9;
}
.fs-modal-footer .btn-primary {
    background: #22c55e !important;
    color: #fff !important;
}
.fs-modal-footer .btn-primary:hover {
    background: #16a34a !important;
}
</style>

<form id="addStaffForm" class="validate-form">
    <!-- Top breadcrumb bar -->
    <div class="wf-staff-top-bar">
      <a class="back-link wf-staff-back-btn">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 3L5 8l5 5"/></svg>
        <?php echo bkntc__('Back to Staff List') ?>
      </a>
      <span class="sep">/</span>
      <span class="crumb active"><?php echo $isEdit ? bkntc__('Edit Staff') : bkntc__('Add Staff') ?></span>
    </div>

    <!-- Header bar -->
    <div class="wf-staff-page-header">
      <div>
        <div class="appt-id"><?php echo $isEdit ? bkntc__('Edit Staff Member') : bkntc__('Add Staff Member') ?></div>
        <div class="appt-title"><?php echo bkntc__('Set up a new staff profile, configure custom hours, weekly schedules, holidays, and login permissions.') ?></div>
      </div>
    </div>

    <!-- Navigation tabs list -->
    <ul class="wf-staff-nav-tabs nav nav-tabs nav-light" data-tab-group="staff_add">
      <?php foreach (TabUI::get('staff_add')->getSubItems() as $index => $tab): ?>
        <li class="nav-item">
          <a class="nav-link <?php echo $index == 0 ? 'active' : '' ?>" data-tab="<?php echo $tab->getSlug(); ?>" href="#">
            <?php echo $tab->getTitle(); ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <!-- Modal body with scrollable tabs content -->
    <div class="fs-modal-body">
      <div class="fs-modal-body-inner">
        <div class="tab-content wf-staff-tab-content">
          <?php foreach (TabUI::get('staff_add')->getSubItems() as $index => $tab): ?>
            <div class="tab-pane <?php echo $index == 0 ? 'active' : '' ?>" data-tab-content="staff_add_<?php echo $tab->getSlug(); ?>" id="tab_<?php echo $tab->getSlug(); ?>">
              <?php echo $tab->getContent($parameters); ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Footer fixed action buttons -->
    <div class="fs-modal-footer">
      <?php if ($isEdit): ?>
        <button type="button" class="btn btn-outline-secondary" id="hideStaffBtn"><?php echo $parameters->getStaff()->isActive() ? bkntc__('HIDE STAFF') : bkntc__('UNHIDE STAFF')?></button>
      <?php endif; ?>
      <button type="button" class="btn btn-outline-secondary wf-staff-back-btn"><?php echo bkntc__('CANCEL')?></button>
      <button type="button" class="btn btn-primary" id="addStaffSave">
        <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;"><path d="M3 8l4 4 6-6"/></svg>
        <?php echo $isEdit ? bkntc__('SAVE STAFF') : bkntc__('ADD STAFF')?>
      </button>
    </div>
</form>

<?php
echo breakTpl();
?>

<script>
(function($) {
    $(document).ready(function() {
        // Hijack the modal container if loaded inside Booknetic modal popup
        var modalWrapper = $('#addStaffForm').closest('.fs-modal');
        if (modalWrapper.length) {
            $('.m_header, .bkc-page-container').hide();
            var container = $('#booknetic_staff_fullpage_container');
            if (!container.length) {
                container = $('<div id="booknetic_staff_fullpage_container" class="fs-modal"></div>');
                $('.bkc-page-container').first().parent().append(container);
            }
            container.empty().append($('#addStaffForm')).show();
            modalWrapper.remove();
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('overflow', '');
        } else {
            // Hide the default list views if loaded as standalone page directly
            $('.m_header, .bkc-page-container').hide();
        }

        // Inline back/cancel transition handler
        $(document).off('click', '.wf-staff-back-btn').on('click', '.wf-staff-back-btn', function(e) {
            e.preventDefault();
            $('#booknetic_staff_fullpage_container').hide().empty();
            $('.m_header, .bkc-page-container').show();
            if (booknetic.dataTable) {
                booknetic.dataTable.reload();
            }
        });

        // Dynamic Profile Card Sync
        $(document).off('input', '#input_name').on('input', '#input_name', function() {
            var val = $(this).val().trim();
            $('.wf-profile-name').text(val ? val : 'Staff Name');
        });
        $(document).off('input', '#input_profession').on('input', '#input_profession', function() {
            var val = $(this).val().trim();
            $('.wf-profile-profession').text(val ? val : 'Profession');
        });
        $(document).off('change', '#input_image').on('change', '#input_image', function() {
            var input = this;
            if (input.files) {
                if (input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var wrap = $('.wf-profile-avatar-wrap');
                        wrap.empty();
                        $('<img>', {
                            src: e.target.result,
                            class: 'wf-profile-avatar'
                        }).appendTo(wrap);
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }
        });
    });
})(jQuery);
</script>
