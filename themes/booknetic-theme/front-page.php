<?php get_header(); ?>

<?php
global $wpdb;

// Dynamically locate the table name for plans (support bkntc_plans and saas_plans)
$tables = $wpdb->get_col("SHOW TABLES LIKE '%bkntc_plans'");
if (empty($tables)) {
    $tables = $wpdb->get_col("SHOW TABLES LIKE '%saas_plans'");
}
$table_name = !empty($tables) ? $tables[0] : '';
$plans = [];
$default_plan_id = 1; // Fallback

if (!empty($table_name)) {
    $plans = $wpdb->get_results("SELECT * FROM {$table_name} WHERE is_active = 1 ORDER BY order_by ASC, id ASC");
    if (!empty($plans)) {
        foreach ($plans as $p) {
            if ($p->is_default == 1) {
                $default_plan_id = $p->id;
                break;
            }
        }
        if ($default_plan_id === 1) {
            $default_plan_id = $plans[0]->id;
        }
    }
}

$img = get_template_directory_uri() . '/images/'; 
?>





<!-- 1. HERO -->
<section class="hero">
  
  <div class="wrap hero-grid">
    <div>
      <div class="eyebrow">Self-hosted booking software</div>
      <h1>Stop losing clients to missed calls.<br><span class="accent">Start booking 24/7.</span></h1>
      <p class="sub">Bookdly gives salons, clinics, studios, and consultants one dashboard for online booking, staff scheduling, payments, and automated reminders — hosted on your own site.</p>
      <div class="cta-row">
        <a href="#pricing" class="btn btn-primary btn-lg">Start free trial</a>
        <a href="#features" class="btn btn-ghost btn-lg">See how it works</a>
      </div>
      <div class="micro-trust mono">No credit card required · 30-day guarantee</div>
    </div>
    <div class="calendar-card">
      <div class="cal-head">
        <span class="mono">Week of June 22</span>
        <div class="cal-dots"><span></span><span></span><span></span></div>
      </div>
      <div class="cal-body">
        <div class="cal-time" style="border-bottom:1px solid #f1f0fa;"></div>
        <div class="cal-col-head">MON</div><div class="cal-col-head">TUE</div><div class="cal-col-head">WED</div><div class="cal-col-head">THU</div><div class="cal-col-head">FRI</div>
        <div class="cal-time">09:00</div><div class="cal-cell"><div class="appt confirmed">Haircut — A. Reyes</div></div><div class="cal-cell"></div><div class="cal-cell"></div><div class="cal-cell"><div class="appt alt confirmed">Consult — J. Kim</div></div><div class="cal-cell"></div>
        <div class="cal-time">10:00</div><div class="cal-cell"></div><div class="cal-cell"><div class="appt confirmed">Massage — D. Patel</div></div><div class="cal-cell"></div><div class="cal-cell"></div><div class="cal-cell"></div>
        <div class="cal-time">11:00</div><div class="cal-cell"></div><div class="cal-cell"></div><div class="cal-cell"></div><div class="cal-cell"></div><div class="cal-cell"><div class="appt alt confirmed">PT Session — M. Lee</div></div>
        <div class="cal-time">12:00</div><div class="cal-cell"></div><div class="cal-cell"></div><div class="cal-cell"></div><div class="cal-cell"></div><div class="cal-cell"></div>
      </div>
    </div>
  </div>
</section>

<!-- 2. SOCIAL PROOF -->
<section class="proof">
  
  <div class="wrap proof-inner">
    <div class="proof-stats">
      <div class="stat"><b>500+</b><span>BUSINESSES</span></div>
      <div class="stat"><b>10,000+</b><span>BOOKINGS PROCESSED</span></div>
      <div class="stat"><b>4.8★</b><span>AVG. RATING</span></div>
    </div>
    <div class="logo-row">
      <span class="lg">Bloom Hair Co.</span>
      <span class="lg">Pulse Fitness</span>
      <span class="lg">Clearview Clinic</span>
      <span class="lg">Atlas Consulting</span>
    </div>
  </div>
</section>

<!-- 3. PROBLEM / SOLUTION -->
<section class="ps">
  
  <div class="problem">
    <div class="eyebrow">The problem</div>
    <h2>Manual scheduling is costing you clients and time</h2>
    <ul>
      <li><span class="bullet-x">✕</span>Calls go to voicemail outside business hours, and the client books elsewhere.</li>
      <li><span class="bullet-x">✕</span>Double bookings and calendar mix-ups erode trust with regulars.</li>
      <li><span class="bullet-x">✕</span>No-shows quietly drain revenue with no reminder system in place.</li>
    </ul>
  </div>
  <div class="solution">
    <div class="eyebrow">The Bookdly solution</div>
    <h2>One dashboard. Every booking. Zero hassle.</h2>
    <ul>
      <li><span class="bullet-check">✓</span>A live booking page that takes appointments around the clock.</li>
      <li><span class="bullet-check">✓</span>One shared staff calendar — no overlaps, no confusion.</li>
      <li><span class="bullet-check">✓</span>Automated SMS and email reminders sent before every visit.</li>
    </ul>
  </div>
</section>

<!-- 4. FEATURES -->
<section class="features" id="features">
  
  <div class="wrap">
    <div class="section-head">
      <div class="eyebrow">What's inside</div>
      <h2>Built for how service businesses actually run</h2>
    </div>

    <div class="feature-row">
      <div class="ftext">
        <span class="mono">Online booking</span>
        <h3>A booking page open even when you're closed</h3>
        <p>Clients pick a service, see real availability, and confirm a slot in under a minute — no phone tag, no back-and-forth.</p>
        <ul class="sublist"><li>Embeds on any WordPress page</li><li>Real-time availability sync</li><li>Mobile-optimized booking flow</li></ul>
      </div>
      <div class="fvisual img"><img src="<?php echo $img; ?>online-booking-widget.svg" alt="Online booking widget"></div>
    </div>

    <div class="feature-row rev">
      <div class="ftext">
        <span class="mono">Staff scheduling</span>
        <h3>One calendar your whole team actually uses</h3>
        <p>Assign services per staff member, block off time off, and avoid the double-bookings that come from juggling spreadsheets.</p>
        <ul class="sublist"><li>Per-staff working hours</li><li>Service-to-staff assignment</li><li>Conflict-free booking logic</li></ul>
      </div>
      <div class="fvisual img"><img src="<?php echo $img; ?>staff-scheduling-calendar.png" alt="Staff scheduling calendar"></div>
    </div>

    <div class="feature-row">
      <div class="ftext">
        <span class="mono">Payments</span>
        <h3>Get paid at the moment of booking</h3>
        <p>Take deposits or full payment up front to cut no-shows, with support for the major processors your clients already trust.</p>
        <ul class="sublist"><li>Stripe & PayPal support</li><li>Deposit or full-payment rules</li><li>Automatic invoices</li></ul>
      </div>
      <div class="fvisual img"><img src="<?php echo $img; ?>checkout-and-payments.png" alt="Checkout and payments"></div>
    </div>

    <div class="feature-row rev">
      <div class="ftext">
        <span class="mono">Reminders</span>
        <h3>Reminders that send themselves</h3>
        <p>Automated SMS and email confirmations and reminders go out on your schedule, so fewer clients forget to show up.</p>
        <ul class="sublist"><li>SMS + email reminders</li><li>Custom send-time rules</li><li>Rebooking follow-ups</li></ul>
      </div>
      <div class="fvisual img"><img src="<?php echo $img; ?>automated-reminders.png" alt="Automated reminders"></div>
    </div>

    <div class="feature-row">
      <div class="ftext">
        <span class="mono">Customer records</span>
        <h3>Every client&#39;s history, one click away</h3>
        <p>See past visits, payment totals, and contact details for every customer, so your team always knows who they&#39;re talking to.</p>
        <ul class="sublist"><li>Full booking &amp; payment history</li><li>VIP and group tagging</li><li>Searchable customer directory</li></ul>
      </div>
      <div class="fvisual img"><img src="<?php echo $img; ?>customer-records-and-history.png" alt="Customer records and history"></div>
    </div>
  </div>
</section>

<!-- 5. TESTIMONIALS -->
<section class="testi" id="testimonials">
  
  <div class="wrap">
    <div class="section-head">
      <div class="eyebrow">From businesses like yours</div>
      <h2>Real results, across every type of practice</h2>
    </div>
    <div class="testi-grid">
      <div class="t-card featured">
        <div class="stars">★★★★★</div>
        <p>"We cut no-shows by 40% in the first month just from the automated reminders. It paid for itself."</p>
        <div class="t-person"><div class="avatar">AR</div><div><div class="name">Ana Reyes</div><div class="role">Owner, Bloom Hair Studio</div></div></div>
      </div>
      <div class="t-card">
        <div class="stars">★★★★★</div>
        <p>"Our front desk used to spend two hours a day on the phone booking. Now it's almost entirely self-serve."</p>
        <div class="t-person"><div class="avatar">DP</div><div><div class="name">Dr. D. Patel</div><div class="role">Clearview Clinic</div></div></div>
      </div>
      <div class="t-card">
        <div class="stars">★★★★★</div>
        <p>"Self-hosted was the deciding factor — we keep our client data and skip the monthly SaaS fee."</p>
        <div class="t-person"><div class="avatar">ML</div><div><div class="name">Marcus Lee</div><div class="role">Pulse Fitness Studio</div></div></div>
      </div>
    </div>
  </div>
</section>

<!-- 6. PRICING + CTA -->
<section class="pricing" id="pricing">
  
  <div class="wrap">
    <div class="section-head" style="margin:0 auto 48px; text-align:center;">
      <div class="eyebrow" style="justify-content:center;">Simple, transparent pricing</div>
      <h2>Choose the plan that fits your business</h2>
    </div>

    <div class="pricing-toggle reveal visible" style="display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 40px;">
      <span class="pricing-toggle-label">Monthly</span>
      <label class="toggle-switch" id="billing-toggle" style="position: relative; display: inline-block; width: 44px; height: 24px; margin: 0 8px;">
        <input type="checkbox" id="billing-check" style="opacity: 0; width: 0; height: 0; position: absolute;">
        <span class="toggle-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #e3e2f0; transition: .3s; border-radius: 24px;"></span>
      </label>
      <span class="pricing-toggle-label">Annual</span>
      <span class="save-badge" style="background: #eefdf5; color: #11a75c; font-family: 'IBM Plex Mono', monospace; font-size: 11px; padding: 2px 6px; border-radius: 2px; font-weight: 600;">Save 30%</span>
    </div>

    <div class="pricing-grid">
      <div class="pricing-card reveal reveal-delay-1 visible">
        <div class="pricing-plan-name">Basic</div>
        <div class="pricing-desc"><p>For solo professionals</p></div>
        <div class="pricing-price">
            <span class="price-currency">£</span>
            <span class="price-amount" data-monthly="15" data-annual="126" style="transition: 0.25s;">15</span>
            <span class="price-period">/mo</span>
        </div>
        <ul class="pricing-features">
          <li class="pricing-feature"><span class="check">✓</span>1 staff member</li>
          <li class="pricing-feature"><span class="check">✓</span>Up to 50 bookings/month</li>
          <li class="pricing-feature"><span class="check">✓</span>1 location</li>
          <li class="pricing-feature"><span class="check">✓</span>Basic booking page</li>
          <li class="pricing-feature"><span class="check">✓</span>Email notifications</li>
        </ul>
        <ul class="pricing-features" style="opacity: 0.5;">
          <li class="pricing-feature disabled"><span class="check">—</span>Custom forms</li>
          <li class="pricing-feature disabled"><span class="check">—</span>Payment processing</li>
          <li class="pricing-feature disabled"><span class="check">—</span>Analytics dashboard</li>
        </ul>
        <a href="https://bookdly.co.uk/sign-up/?plan_id=2&amp;billing_cycle=monthly" class="btn btn-outline" style="width:100%; text-align:center;">Choose Plan</a>
      </div>

      <div class="pricing-card pricing-card-featured reveal reveal-delay-2 visible">
        <div class="pricing-badge">Most Popular</div>
        <div class="pricing-plan-name">Standard</div>
        <div class="pricing-desc"><p>Everything you need to run and grow your booking business.</p></div>
        <div class="pricing-price">
            <span class="price-currency">£</span>
            <span class="price-amount" data-monthly="29" data-annual="243" style="transition: 0.25s;">29</span>
            <span class="price-period">/mo</span>
        </div>
        <ul class="pricing-features">
          <li class="pricing-feature"><span class="check">✓</span>Up to 10 staff members</li>
          <li class="pricing-feature"><span class="check">✓</span>Unlimited bookings</li>
          <li class="pricing-feature"><span class="check">✓</span>Multiple locations</li>
          <li class="pricing-feature"><span class="check">✓</span>Custom forms &amp; intake</li>
          <li class="pricing-feature"><span class="check">✓</span>Payment &amp; deposits</li>
          <li class="pricing-feature"><span class="check">✓</span>Automated workflows</li>
          <li class="pricing-feature"><span class="check">✓</span>Analytics &amp; reports</li>
          <li class="pricing-feature"><span class="check">✓</span>Priority support</li>
        </ul>
        <a href="https://bookdly.co.uk/sign-up/?plan_id=3&amp;billing_cycle=monthly" class="btn btn-primary" style="width:100%; text-align:center;">Start 30-day free trial</a>
      </div>

      <div class="pricing-card reveal reveal-delay-3 visible">
        <div class="pricing-plan-name">Premium</div>
        <div class="pricing-desc"><p>Advanced features and dedicated support for large-scale operations.</p></div>
        <div class="pricing-price">
            <span class="price-currency">£</span>
            <span class="price-amount" data-monthly="49" data-annual="410" style="transition: 0.25s;">49</span>
            <span class="price-period">/mo</span>
        </div>
        <ul class="pricing-features">
          <li class="pricing-feature"><span class="check">✓</span>Unlimited staff</li>
          <li class="pricing-feature"><span class="check">✓</span>Unlimited bookings</li>
          <li class="pricing-feature"><span class="check">✓</span>Multi-location management</li>
          <li class="pricing-feature"><span class="check">✓</span>White-label branding</li>
          <li class="pricing-feature"><span class="check">✓</span>API access</li>
          <li class="pricing-feature"><span class="check">✓</span>SLA &amp; dedicated support</li>
          <li class="pricing-feature"><span class="check">✓</span>Custom integrations</li>
          <li class="pricing-feature"><span class="check">✓</span>Onboarding &amp; training</li>
        </ul>
        <a href="https://bookdly.co.uk/sign-up/?plan_id=4&amp;billing_cycle=monthly" class="btn btn-outline" style="width:100%; text-align:center;">Choose Plan</a>
      </div>
    </div>
    <p class="guarantee mono">30-DAY MONEY-BACK GUARANTEE · FREE SETUP SUPPORT FOR THE FIRST 100 SIGNUPS THIS MONTH</p>
  </div>
</section>

<div class="cta-band">
  <div class="wrap">
    <h2>Join 500+ businesses that already automated their bookings</h2>
    <div class="cta-row"><a href="#pricing" class="btn btn-primary btn-lg">Start free trial</a><a href="#faq" class="btn btn-ghost-light btn-lg">Read the FAQ</a></div>
  </div>
</div>

<!-- 7. FAQ -->
<section class="faq" id="faq">
  
  <div class="section-head">
    <div class="eyebrow">Before you start</div>
    <h2>Common questions</h2>
  </div>

  <div class="faq-item open">
    <div class="faq-q"><span><span class="idx">01</span>Do I need any technical skills to set this up?</span><span class="faq-toggle">+</span></div>
    <div class="faq-a">No. Bookdly installs as a standard WordPress plugin with a guided setup wizard. Most businesses are live within an hour, and our setup support team can help with anything trickier.</div>
  </div>
  <div class="faq-item">
    <div class="faq-q"><span><span class="idx">02</span>Will Bookdly work with my existing website?</span><span class="faq-toggle">+</span></div>
    <div class="faq-a">Yes — the booking widget embeds directly into any WordPress page or post, and matches your existing site's styling automatically.</div>
  </div>
  <div class="faq-item">
    <div class="faq-q"><span><span class="idx">03</span>What happens to my client data — is it secure, and do I own it?</span><span class="faq-toggle">+</span></div>
    <div class="faq-a">Because Bookdly is self-hosted, your client data lives on your own server, not a third-party cloud. You retain full ownership and control at all times.</div>
  </div>
  <div class="faq-item">
    <div class="faq-q"><span><span class="idx">04</span>Can I cancel or get a refund if it's not right for my business?</span><span class="faq-toggle">+</span></div>
    <div class="faq-a">Yes. Every plan comes with a 30-day money-back guarantee, no questions asked.</div>
  </div>
  <div class="faq-item">
    <div class="faq-q"><span><span class="idx">05</span>Does it support online payments and automated reminders out of the box?</span><span class="faq-toggle">+</span></div>
    <div class="faq-a">Yes — Stripe and PayPal payments, deposit rules, and SMS/email reminders are all included starting on the Professional plan.</div>
  </div>
</section>



<script>
  document.querySelectorAll('.faq-item').forEach(item=>{
    item.querySelector('.faq-q').addEventListener('click',()=>{
      const isOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item').forEach(i=>i.classList.remove('open'));
      if(!isOpen) item.classList.add('open');
    });
  });
</script>




<script>
document.querySelectorAll('.faq-item').forEach(item => {
  const q = item.querySelector('.faq-q');
  if (q) {
    q.addEventListener('click', () => {
      const isOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
      if (!isOpen) {
        item.classList.add('open');
      }
    });
  }
});
</script>


<?php get_footer(); ?>
