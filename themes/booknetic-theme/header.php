<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Bookdly — The smarter way to manage bookings, appointments, and your entire schedule.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <?php wp_head(); ?>
  <?php
  $css_file = get_template_directory() . '/style.css';
  $css_ver  = file_exists($css_file) ? filemtime($css_file) : time();
  echo '<link rel="stylesheet" href="' . esc_url(get_stylesheet_uri() . '?nocombine&t=' . $css_ver) . '" data-no-combine>' . PHP_EOL;
  ?>
</head>
<body <?php body_class(); ?>>

<header id="site-header" class="<?php echo !is_front_page() ? 'scrolled inner-page-header' : ''; ?>">
  <nav>
    <div class="nav-inner">
      <!-- Logo: white shown on dark hero; dark shown on scrolled/inner pages -->
      <a href="<?php echo home_url('/'); ?>" class="nav-logo">
        <img src="https://bookdly.co.uk/wp-content/uploads/booknetic/base/bookdly-white-logo.png" alt="Bookdly" class="logo-light">
        <img src="https://bookdly.co.uk/wp-content/uploads/booknetic/base/BannerGraphic.png"      alt="Bookdly" class="logo-dark">
      </a>

      <!-- Nav Links -->
      <div class="nav-links" id="nav-links">
        <a href="<?php echo is_front_page() ? '#features'     : esc_url( home_url('/#features') );     ?>">Features</a>
        <a href="<?php echo is_front_page() ? '#pricing'      : esc_url( home_url('/#pricing') );      ?>">Pricing</a>
        <a href="<?php echo is_front_page() ? '#testimonials' : esc_url( home_url('/#testimonials') ); ?>">Reviews</a>
        <a href="<?php echo is_front_page() ? '#faq'          : esc_url( home_url('/#faq') );          ?>">FAQ</a>
      </div>

      <!-- CTA -->
      <div class="nav-cta">
        <a href="<?php echo esc_url( home_url('/sign-in') ); ?>" class="nav-login">Log in</a>
        <a href="<?php echo is_front_page() ? '#pricing' : esc_url( home_url('/#pricing') ); ?>" class="btn btn-primary btn-sm">
          Get Started
          <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
      </div>

      <!-- Mobile Toggle -->
      <button class="nav-toggle" id="nav-toggle" aria-label="Menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>
</header>
