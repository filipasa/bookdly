<?php
/**
 * Shared layout for Authentication Pages (Sign In, Sign Up, Forgot Password)
 */
get_header('auth');
$img = get_template_directory_uri() . '/images/';
?>

<div class="auth-shell">
  <div class="brand-panel">
    <div class="brand-top">
      <div class="logo"><img src="https://bookdly.co.uk/wp-content/uploads/booknetic/base/bookdly-white-logo.png" alt="Bookdly" style="height:32px; width:auto; display:block;"></div>
    </div>
    
    <?php if ($auth_type === 'signin'): ?>
      <div class="brand-mid">
        <div class="eyebrow">Welcome back</div>
        <h2>Your whole schedule, exactly where you left it.</h2>
        <p>Pick up where you left off — today's bookings, staff calendar, and payments are all waiting.</p>
        <div class="calendar-card">
          <div class="cal-head"><span class="mono">This week</span><div class="cal-dots"><span></span><span></span><span></span></div></div>
          <div class="cal-body">
            <div class="cal-col-head">MON</div><div class="cal-col-head">TUE</div><div class="cal-col-head">WED</div><div class="cal-col-head">THU</div><div class="cal-col-head">FRI</div>
            <div class="cal-cell"><div class="appt"></div></div><div class="cal-cell"></div><div class="cal-cell"></div><div class="cal-cell"><div class="appt"></div></div><div class="cal-cell"></div>
            <div class="cal-cell"></div><div class="cal-cell"><div class="appt"></div></div><div class="cal-cell"></div><div class="cal-cell"></div><div class="cal-cell"><div class="appt"></div></div>
          </div>
        </div>
      </div>
      <div class="brand-bottom">
        <div class="brand-stat"><b>500+</b><span>BUSINESSES</span></div>
        <div class="brand-stat"><b>4.8★</b><span>AVG RATING</span></div>
      </div>
      
    <?php elseif ($auth_type === 'signup'): ?>
      <div class="brand-mid">
        <div class="eyebrow">Start free — no card required</div>
        <h2>Stop losing clients to missed calls.</h2>
        <p>Set up your booking page, staff calendar, and payments in under 10 minutes.</p>
        <div class="calendar-card">
          <div class="cal-head"><span class="mono">New booking</span><div class="cal-dots"><span></span><span></span><span></span></div></div>
          <div class="cal-body">
            <div class="cal-col-head">MON</div><div class="cal-col-head">TUE</div><div class="cal-col-head">WED</div><div class="cal-col-head">THU</div><div class="cal-col-head">FRI</div>
            <div class="cal-cell"></div><div class="cal-cell"><div class="appt"></div></div><div class="cal-cell"></div><div class="cal-cell"></div><div class="cal-cell"><div class="appt"></div></div>
            <div class="cal-cell"><div class="appt"></div></div><div class="cal-cell"></div><div class="cal-cell"></div><div class="cal-cell"><div class="appt"></div></div><div class="cal-cell"></div>
          </div>
        </div>
      </div>
      <div class="brand-bottom">
        <div class="brand-stat"><b>14 days</b><span>FREE TRIAL</span></div>
        <div class="brand-stat"><b>10 min</b><span>AVG. SETUP TIME</span></div>
      </div>
      
    <?php elseif ($auth_type === 'forgot'): ?>
      <div class="brand-mid">
        <div class="eyebrow">Account recovery</div>
        <h2>We'll have you back in your calendar in a minute.</h2>
        <p>Enter the email on your account and we'll send a secure link to reset your password.</p>
      </div>
      <div class="brand-bottom">
        <div class="brand-stat"><b>500+</b><span>BUSINESSES</span></div>
        <div class="brand-stat"><b>4.8★</b><span>AVG RATING</span></div>
      </div>
    <?php endif; ?>
  </div>

  <div class="form-panel">
    <div class="form-card">
      <div class="top-logo"><img src="https://bookdly.co.uk/wp-content/uploads/booknetic/base/BannerGraphic.png" alt="Bookdly" style="height:32px; width:auto; display:block;"></div>
      
      <?php
      while (have_posts()) :
          the_post();
          the_content();
      endwhile;
      ?>
    </div>
  </div>
</div>

<?php
get_footer('auth');
?>
