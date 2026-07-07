#booknetic_theme_%%id%% *
{
    font-family: '%%fontfamily%%', sans-serif !important;
}
#booknetic_theme_%%id%%
{
    height: %%height%%px;
}

#booknetic_theme_%%id%% .booknetic_appointment_steps
{
    background: %%panel%%;
}

#booknetic_theme_%%id%% .booknetic_badge
{
    background: %%other_steps%%;
}
#booknetic_theme_%%id%% .booknetic_appointment_steps_footer_txt2
{
    color: %%other_steps%%;
}
#booknetic_theme_%%id%% .booknetic_step_title, #booknetic_theme_%%id%% .booknetic_appointment_steps_footer_txt1
{
    color: %%other_steps_txt%%;
}

#booknetic_theme_%%id%% .booknetic_selected_step > .booknetic_badge::after
{
background-color: %%compleated_steps%%;
}
#booknetic_theme_%%id%% .booknetic_selected_step .booknetic_step_title
{
color: %%compleated_steps_txt%%;
}

#booknetic_theme_%%id%% .booknetic_active_step .booknetic_badge, #booknetic_theme_%%id%% .booknetic_calendar_days > div > span > i[a], #booknetic_theme_%%id%% .booknetic_btn_success
{
    background: %%active_steps%%;
}
#booknetic_theme_%%id%% .booknetic_active_step .booknetic_step_title
{
    color: %%active_steps_txt%%;
}

#booknetic_theme_%%id%% .booknetic_btn_primary,
#booknetic_theme_%%id%% .booknetic_selected_time,
#booknetic_theme_%%id%% .booknetic_calendar_selected_day > div
{
    background: %%primary%% !important;
    color: %%primary_txt%% !important;
}

#booknetic_theme_%%id%% .booknetic_service_category, .booknetic_package_category, #booknetic_theme_%%id%% .booknetic_service_extra_title, #booknetic_theme_%%id%% .booknetic_times_title, #booknetic_theme_%%id%% .booknetic_text_primary
{
    color: %%primary%% !important;
}

#booknetic_theme_%%id%% .booknetic_category_accordion .booknetic_service_category span,
#booknetic_theme_%%id%% .booknetic_category_accordion .booknetic_service_extra_title span{
    background: %%primary%% !important;
}

#booknetic_theme_%%id%% .booknetic_appointment_container_header
{
    color: %%title%% !important;
}

#booknetic_theme_%%id%% .booknetic_service_card_selected,
#booknetic_theme_%%id%% .booknetic_card_selected,
#booknetic_theme_%%id%% .booknetic_service_extra_card_selected,
#booknetic_theme_%%id%% .booknetic_payment_method_selected,
#booknetic_theme_%%id%% .booknetic-cart-item.active
{
    border-color: %%border%% !important;
}

#booknetic_theme_%%id%% .booknetic_service_card_price,
#booknetic_theme_%%id%% .booknetic_service_extra_card_price,
#booknetic_theme_%%id%% .booknetic_confirm_details_price:not([data-price-id="discount"] .booknetic_confirm_details_price,.booknetic_gift_discount_price),
#booknetic_theme_%%id%% .booknetic-cart-item-body-cell.amount,
#booknetic_theme_%%id%% .booknetic_sum_price
{
    color: %%price%% !important;
}

/* Package Booking Panel Theming */
#booknetic_theme_%%id%% .bkntc_package-confirmation_header h1,
#booknetic_theme_%%id%% .bkntc_package_summary h2
{
    color: %%title%% !important;
}

#booknetic_theme_%%id%% .bkntc_package_summary,
#booknetic_theme_%%id%% .bkntc_package_warning,
#booknetic_theme_%%id%% .bkntc_package_appointment.booked,
#booknetic_theme_%%id%% .bkntc_package_appointment.empty
{
    border-color: %%border%% !important;
}

.booknetic_package_card_service_el > span:first-child
{
    color: %%primary%% !important;
}

.booknetic_package_card_service_el > span:last-child {
    background: %%primary%% !important;
}

#booknetic_theme_%%id%% .bkntc_package_appointment.empty:hover
{
    border-color: %%primary%% !important;
    background-color: rgba(108, 112, 220, 0.1) !important;
}

#booknetic_theme_%%id%% .bkntc_package-confirmation_header span,
#booknetic_theme_%%id%% .bkntc_package_summary h2
{
    color: %%primary%% !important;
}

%%custom_css%%

%%hide_steps%%

/* ==========================================================================
   MINIMALIST SaaS UI/UX THEME TEMPLATE OVERRIDES
   ========================================================================== */

#booknetic_theme_%%id%%,
#booknetic_theme_%%id%% * {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
}

#booknetic_theme_%%id%%.booknetic_appointment {
    background-color: #F8F9FA !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04) !important;
    border: 1px solid #E5E7EB !important;
}

#booknetic_theme_%%id%% .booknetic_appointment_steps {
    background-color: #FFFFFF !important;
    border-right: 1px solid #E5E7EB !important;
    border-top-left-radius: 12px !important;
    border-bottom-left-radius: 12px !important;
}

#booknetic_theme_%%id%% .booknetic_appointment_step_element {
    color: #9CA3AF !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    margin-bottom: 24px !important;
}

#booknetic_theme_%%id%% .booknetic_badge {
    background: #F3F4F6 !important;
    color: #6B7280 !important;
    font-weight: 500 !important;
    border-radius: 50% !important;
    transition: all 0.2s ease !important;
}

#booknetic_theme_%%id%% .booknetic_active_step {
    color: #111111 !important;
    font-weight: 600 !important;
}
#booknetic_theme_%%id%% .booknetic_active_step .booknetic_badge {
    background: #111111 !important;
    color: #FFFFFF !important;
}

#booknetic_theme_%%id%% .booknetic_selected_step {
    color: #10B981 !important;
}
#booknetic_theme_%%id%% .booknetic_selected_step .booknetic_badge {
    background: #E6F4EA !important;
    color: #10B981 !important;
}

#booknetic_theme_%%id%% .booknetic_appointment_container {
    background: #F8F9FA !important;
}

#booknetic_theme_%%id%% .booknetic_appointment_container_header {
    background: #F8F9FA !important;
    border-bottom: none !important;
    padding: 24px 32px 12px 32px !important;
    font-size: 20px !important;
    font-weight: 600 !important;
    color: #111111 !important;
}

#booknetic_theme_%%id%% .booknetic_appointment_container_body {
    padding: 16px 32px 32px 32px !important;
}

#booknetic_theme_%%id%% .booknetic_appointment_container_footer {
    background: #FFFFFF !important;
    border-top: 1px solid #E5E7EB !important;
    padding: 20px 32px !important;
    border-bottom-right-radius: 12px !important;
}

#booknetic_theme_%%id%% .booknetic_card {
    background: #FFFFFF !important;
    border: 1px solid #E5E7EB !important;
    box-shadow: none !important;
    border-radius: 12px !important;
    padding: 24px !important;
    margin-right: 16px !important;
    margin-bottom: 16px !important;
    transition: all 0.2s ease-in-out !important;
}
#booknetic_theme_%%id%% .booknetic_card:hover {
    border-color: #9CA3AF !important;
    background: #F9FAFB !important;
}

#booknetic_theme_%%id%% .booknetic_card:not(.booknetic_card_selected):after {
    background-image: none !important;
}

#booknetic_theme_%%id%% .booknetic_card.booknetic_card_selected {
    border: 2px solid #111111 !important;
    background: #FFFFFF !important;
    padding: 23px !important;
    box-shadow: none !important;
}

#booknetic_theme_%%id%% .booknetic_card_title {
    font-size: 15px !important;
    font-weight: 600 !important;
    color: #111111 !important;
}
#booknetic_theme_%%id%% .booknetic_card_description {
    font-size: 13px !important;
    color: #6B7280 !important;
}

#booknetic_theme_%%id%% .booknetic_service_category, 
#booknetic_theme_%%id%% .booknetic_location_category {
    color: #111111 !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    margin-top: 24px !important;
    margin-bottom: 12px !important;
}

#booknetic_theme_%%id%% .booknetic_category_accordion .booknetic_service_category span, 
#booknetic_theme_%%id%% .booknetic_category_accordion .booknetic_service_extra_title span, 
#booknetic_theme_%%id%% .booknetic_location_category span {
    background: #111111 !important;
}

#booknetic_theme_%%id%% .form-control {
    border: 1px solid #D1D5DB !important;
    border-radius: 10px !important;
    height: 48px !important;
    font-size: 14px !important;
    color: #111111 !important;
    background-color: #FFFFFF !important;
    padding: 10px 16px !important;
    box-shadow: none !important;
    transition: all 0.2s ease !important;
}

#booknetic_theme_%%id%% .form-control:focus {
    border-color: #111111 !important;
    box-shadow: 0 0 0 2px rgba(17, 17, 17, 0.1) !important;
}

#booknetic_theme_%%id%% .form-group > label,
#booknetic_theme_%%id%% .form-row label {
    font-size: 14px !important;
    font-weight: 500 !important;
    color: #374151 !important;
    margin-bottom: 8px !important;
}

#booknetic_theme_%%id%% .booknetic_btn_primary {
    height: 46px !important;
    padding: 12px 28px !important;
    border-radius: 10px !important;
    background-color: #111111 !important;
    color: #FFFFFF !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    text-transform: none !important;
    letter-spacing: normal !important;
    transition: all 0.2s ease !important;
    border: none !important;
    box-shadow: none !important;
}
#booknetic_theme_%%id%% .booknetic_btn_primary:hover {
    background-color: #2D2D2D !important;
}

#booknetic_theme_%%id%% .booknetic_btn_secondary {
    height: 46px !important;
    padding: 12px 28px !important;
    border-radius: 10px !important;
    background-color: #FFFFFF !important;
    color: #374151 !important;
    border: 1px solid #E5E7EB !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    text-transform: none !important;
    letter-spacing: normal !important;
    transition: all 0.2s ease !important;
    box-shadow: none !important;
}
#booknetic_theme_%%id%% .booknetic_btn_secondary:hover {
    background-color: #F9FAFB !important;
    border-color: #D1D5DB !important;
}

#booknetic_theme_%%id%% #booknetic_calendar_area {
    border-radius: 12px !important;
    border: 1px solid #E5E7EB !important;
    box-shadow: none !important;
    background-color: #FFFFFF !important;
    overflow: hidden !important;
    padding: 16px !important;
}

#booknetic_theme_%%id%% .booknetic_week_names > .booknetic_td {
    color: #9CA3AF !important;
    font-weight: 500 !important;
    border-bottom: none !important;
    padding: 12px 5px !important;
}

#booknetic_theme_%%id%% .booknetic_calendar_days {
    padding: 4px !important;
}
#booknetic_theme_%%id%% .booknetic_calendar_days > div {
    background-color: transparent !important;
    border-radius: 8px !important;
    color: #111111 !important;
    font-weight: 500 !important;
    transition: all 0.2s ease !important;
}
#booknetic_theme_%%id%% .booknetic_calendar_days:not(.booknetic_calendar_empty_day) > div:hover {
    background-color: #F3F4F6 !important;
}

#booknetic_theme_%%id%% .booknetic_calendar_days.booknetic_calendar_selected_day > div {
    background-color: #111111 !important;
    color: #FFFFFF !important;
}

#booknetic_theme_%%id%% .booknetic_calendar_days.booknetic_calendar_empty_day > div {
    color: #D1D5DB !important;
}

#booknetic_theme_%%id%% .booknetic_calendar_days > div > span > i[a] {
    background: #10B981 !important;
}
#booknetic_theme_%%id%% .booknetic_calendar_days > div > span > i[b] {
    background: #E5E7EB !important;
}

#booknetic_theme_%%id%% .booknetic_times {
    border-radius: 12px !important;
    border: 1px solid #E5E7EB !important;
    box-shadow: none !important;
    background-color: #FFFFFF !important;
    padding: 16px !important;
    height: auto !important;
    width: 320px !important;
}

#booknetic_theme_%%id%% .booknetic_times_title {
    color: #111111 !important;
    font-weight: 600 !important;
    border-bottom: none !important;
    padding: 12px 0 !important;
}

#booknetic_theme_%%id%% .booknetic_times_list {
    padding: 8px 0 !important;
}
#booknetic_theme_%%id%% .booknetic_times_list > div {
    background-color: #FFFFFF !important;
    border: 1px solid #E5E7EB !important;
    border-radius: 8px !important;
    color: #374151 !important;
    box-shadow: none !important;
    margin: 4px !important;
    width: calc(33.33% - 8px) !important;
    height: 48px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-weight: 500 !important;
    transition: all 0.2s ease !important;
}
#booknetic_theme_%%id%% .booknetic_times_list > div:hover {
    background-color: #F3F4F6 !important;
    border-color: #D1D5DB !important;
}

#booknetic_theme_%%id%% .booknetic_times_list > div.booknetic_selected_time {
    background-color: #111111 !important;
    border-color: #111111 !important;
    color: #FFFFFF !important;
}

#booknetic_theme_%%id%% .booknetic_time_group_num {
    background: #10B981 !important;
    border-radius: 6px !important;
}

#booknetic_theme_%%id%% .booknetic_confirm_details:before {
    border-bottom: 1px dashed #E5E7EB !important;
}
#booknetic_theme_%%id%% .booknetic_confirm_details > .booknetic_confirm_details_title,
#booknetic_theme_%%id%% .booknetic_confirm_details > .booknetic_confirm_details_price {
    background: #FFFFFF !important;
}
#booknetic_theme_%%id%% .booknetic_confirm_step_body .booknetic_portlet {
    border: 1px solid #E5E7EB !important;
    border-radius: 12px !important;
    box-shadow: none !important;
    background: #FFFFFF !important;
    overflow: hidden !important;
}
#booknetic_theme_%%id%% .booknetic_confirm_sum_price {
    border-top: 1px solid #E5E7EB !important;
}

