<?php defined('ABSPATH') or die(); ?>

<h3 id="wf-cache-debug-indicator" style="color: #ec4899 !important; font-size: 16px !important; font-weight: 700 !important; margin-bottom: 16px !important; display: block !important;">DEBUG: HOLIDAYS V2 ACTIVE</h3>

<pre id="wf-calendar-source-debug" style="background: #1e293b !important; color: #38bdf8 !important; padding: 16px !important; border-radius: 8px !important; font-size: 11px !important; overflow-x: auto !important; max-width: 860px !important; margin-bottom: 24px !important; white-space: pre-wrap !important;">
    Loading calendar diagnostic...
</pre>

<script>
(function($) {
    var pre = $('#wf-calendar-source-debug');
    if (typeof $.fn.calendar === 'function') {
        var src = $.fn.calendar.toString();
        pre.text("$.fn.calendar source:\n" + src.substring(0, 600) + "\n...");
    } else {
        pre.text("$.fn.calendar is NOT a function!");
    }
})(jQuery);
</script>

<div class="form-section" style="max-width:860px; border:none; padding:0; margin:0; background:transparent;">
  <p style="color:#94a3b8; font-size:13px; margin-bottom:24px;"><?php echo bkntc__('Click any day to mark it as a holiday. Staff will be unavailable on selected dates.')?></p>

  <div class="yearly_calendar"></div>
</div>