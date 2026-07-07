<?php
function bookdly_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption']);
    register_nav_menus(['primary' => __('Primary Menu', 'bookdly')]);
}
add_action('after_setup_theme', 'bookdly_setup');
function bookdly_assets() {
    // We add 'booknetic' as a dependency if it's enqueued, or force load style.css with priority 9999
    wp_enqueue_style('bookdly-style', get_stylesheet_uri(), [], '5.4.' . time());
    wp_enqueue_script('bookdly-main', get_template_directory_uri() . '/js/main.js', [], '5.2', true);
}
// Using priority 9999 to make sure it loads after plugin styles
add_action('wp_enqueue_scripts', 'bookdly_assets', 9999);
// Force SSL on stylesheet links to prevent mixed content
add_filter('style_loader_src', 'bookdly_force_ssl', 10, 2);
add_filter('script_loader_src', 'bookdly_force_ssl', 10, 2);
function bookdly_force_ssl($src, $handle) {
    if (is_ssl() && strpos($src, 'http://') === 0) {
        $src = str_replace('http://', 'https://', $src);
    }
    return $src;
}

// Register custom shortcode to display booking wizard for salon (tenant_id 27)
function bkntc_render_custom_wizard_shortcode($atts) {
    $atts = shortcode_atts([
        'tenant_id' => '27' // Default fallback
    ], $atts);

    $tenant_id = null;

    // 1. Check if bkntc_page_id or tenant_id is explicitly set in GET parameters
    if (isset($_GET['bkntc_page_id']) && is_numeric($_GET['bkntc_page_id'])) {
        $tenant_id = (int)$_GET['bkntc_page_id'];
    } elseif (isset($_GET['tenant_id']) && is_numeric($_GET['tenant_id'])) {
        $tenant_id = (int)$_GET['tenant_id'];
    }

    // 2. Check if Booknetic's Permission class has a tenant ID set
    if (!$tenant_id && class_exists('\BookneticApp\Providers\Core\Permission')) {
        $active_tenant = \BookneticApp\Providers\Core\Permission::tenantId();
        if ($active_tenant > 0) {
            $tenant_id = $active_tenant;
        }
    }

    // 3. Try to detect tenant domain from the current URL slug/domain using Booknetic SaaS helper
    if (!$tenant_id && class_exists('\BookneticSaaS\Providers\Helpers\Helper') && class_exists('\BookneticSaaS\Models\Tenant')) {
        $currentDomain = \BookneticSaaS\Providers\Helpers\Helper::getCurrentDomain();
        if (!empty($currentDomain)) {
            $tenant = \BookneticSaaS\Models\Tenant::where('domain', $currentDomain)->fetch();
            if ($tenant) {
                $tenant_id = (int)$tenant->id;
            }
        }
    }

    // 4. Fallback to the shortcode attribute
    if (!$tenant_id) {
        $tenant_id = (int)$atts['tenant_id'];
    }

    // Save current query param to restore later
    $orig_val = isset($_GET['bkntc_page_id']) ? $_GET['bkntc_page_id'] : null;

    // Force tenant_id parameter for directory shortcode
    $_GET['bkntc_page_id'] = $tenant_id;

    $output = '';
    // Call the directory addon's page handler
    if (class_exists('BookneticAddon\Tenantdirectory\ShortcodesListener')) {
        $output = BookneticAddon\Tenantdirectory\ShortcodesListener::renderTenantPage([]);
        
        $landing_url = 'https://bookdly.co.uk/booking-panel/?bkntc_page_id=' . $tenant_id;
        
        // Inject styles to show back button and set its click action to go to landing page
        $output .= '
        <style>
            #bkntc_landing_main_view {
                display: none !important;
            }
            #bkntc_booking_wizard_view {
                display: block !important;
            }
            #bkntc_btn_back_salon {
                display: inline-flex !important;
            }
        </style>
        <script>
            jQuery(document).ready(function($) {
                // Ensure browser unbinds the default custom click handler which toggles landing view display
                $("#bkntc_btn_back_salon").attr("href", "' . esc_url($landing_url) . '");
                
                // Unbind previous toggle actions and click directly to change page location
                $(document).off("click", "#bkntc_btn_back_salon");
                $("#bkntc_btn_back_salon").off("click").on("click", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.location.href = "' . esc_url($landing_url) . '";
                });

                // Auto-expand the first accordion category on load
                setTimeout(function() {
                    var firstAccordion = $(".bkntc-accordion-item").first();
                    if (firstAccordion.length) {
                        firstAccordion.addClass("open").find(".bkntc-accordion-content").show();
                        console.log("[Wizard Init] Opened first category accordion by default.");
                    }
                }, 300);
            });
        </script>
        ';
    } else {
        $output = '<p>Error: Tenant directory addon class not found.</p>';
    }

    // Restore query param
    if ($orig_val !== null) {
        $_GET['bkntc_page_id'] = $orig_val;
    } else {
        unset($_GET['bkntc_page_id']);
    }

    return $output;
}
add_shortcode('booknetic-custom-wizard', 'bkntc_render_custom_wizard_shortcode');
