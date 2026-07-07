<?php

namespace BookneticAddon\Tenantdirectory;

use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Models\Location;
use BookneticApp\Models\Timesheet;
use BookneticAddon\Tenantdirectory\Model\TenantDirectory;
use BookneticAddon\Tenantdirectory\Model\BusinessType;
use BookneticAddon\Tenantdirectory\Model\Keyword;

class ShortcodesListener
{
    // === Shortcode 1: [booknetic-saas-tenant-list] ===
    public static function renderTenantList($atts)
    {
        if (isset($_GET['bkntc_page_id'])) {
            return '';
        }

        $atts = shortcode_atts([
            'view' => 'grid', // 'grid' or 'list'
            'disable_filtering' => 'false',
            'default_business_type' => ''
        ], $atts);

        // Fetch all approved directories
        $query = TenantDirectory::noTenant()
            ->leftJoin('business_type', ['name'])
            ->where('status', 'approved');

        // Apply filters if submitted via GET
        $search = sanitize_text_field($_GET['directory_search'] ?? '');
        $business_type_id = intval($_GET['directory_business_type'] ?? $atts['default_business_type']);
        $budget_filter = sanitize_text_field($_GET['directory_budget'] ?? '');

        if (!empty($search)) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        if ($business_type_id > 0) {
            $query->where('business_type_id', $business_type_id);
        }

        if (!empty($budget_filter)) {
            $query->where('price_level', $budget_filter);
        }

        $directories = $query->fetchAll();

        // Load all business types for search filter
        $businessTypes = BusinessType::query()->orderBy('sort_number')->fetchAll();

        // Build list output
        ob_start();
        ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary-color: #6c70dc;
                --primary-hover: #575bc8;
                --text-color-dark: #1e293b;
                --text-color-muted: #64748b;
                --border-color: #e2e8f0;
                --bg-light: #f8fafc;
            }

            .bkntc-directory-container {
                display: flex;
                gap: 30px;
                font-family: 'Outfit', 'Inter', sans-serif;
                color: var(--text-color-dark);
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }

            .bkntc-directory-sidebar {
                width: 300px;
                flex-shrink: 0;
                padding: 24px;
                background: #ffffff;
                border: 1px solid var(--border-color);
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            }

            .bkntc-directory-sidebar label {
                display: block;
                font-size: 14px;
                font-weight: 600;
                margin-bottom: 8px;
                color: #475569;
            }

            .bkntc-directory-sidebar .form-control {
                width: 100%;
                padding: 12px 16px;
                border: 1px solid var(--border-color);
                border-radius: 10px;
                font-size: 14px;
                background: #ffffff;
                box-sizing: border-box;
                transition: border-color 0.2s, box-shadow 0.2s;
                height: auto;
            }

            .bkntc-directory-sidebar .form-control:focus {
                border-color: var(--primary-color);
                outline: none;
                box-shadow: 0 0 0 3px rgba(108, 112, 220, 0.15);
            }

            .bkntc-directory-sidebar select.form-control {
                appearance: none;
                background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
                background-repeat: no-repeat;
                background-position: right 16px center;
                background-size: 14px;
                padding-right: 40px;
            }

            .bkntc-directory-sidebar .btn-primary {
                width: 100%;
                padding: 14px;
                background: var(--primary-color) !important;
                border: none !important;
                color: #ffffff !important;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.2s;
                margin-top: 10px;
            }

            .bkntc-directory-sidebar .btn-primary:hover {
                background: var(--primary-hover) !important;
            }

            .bkntc-directory-content {
                flex: 1;
                min-width: 0;
            }

            .btn-map-toggle {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 20px;
                background: #ffffff;
                color: var(--text-color-dark);
                border: 1px solid var(--border-color);
                border-radius: 10px;
                font-weight: 600;
                font-size: 14px;
                cursor: pointer;
                transition: background 0.2s, box-shadow 0.2s;
                margin-bottom: 24px;
            }

            .btn-map-toggle:hover {
                background: var(--bg-light);
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }

            .bkntc-directory-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 24px;
            }
            .bkntc-directory-card-link {
                text-decoration: none !important;
                color: inherit !important;
                display: block !important;
            }

            .bkntc-directory-card {
                background: #ffffff;
                border: 1px solid var(--border-color);
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 4px 20px rgba(0,0,0,0.02);
                transition: transform 0.2s, box-shadow 0.2s;
                display: flex;
                flex-direction: column;
            }

            .bkntc-directory-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 30px rgba(0,0,0,0.06);
            }

            .bkntc-directory-img {
                width: 259px !important;
                height: 250px !important;
                object-fit: cover;
                border-bottom: 1px solid var(--border-color);
            }

            .bkntc-directory-details {
                padding: 20px;
                flex: 1;
                display: flex;
                flex-direction: column;
            }

            .bkntc-directory-title {
                font-size: 18px;
                font-weight: 700;
                margin-bottom: 6px;
                color: var(--text-color-dark);
                line-height: 1.3;
            }

            .bkntc-directory-meta {
                font-size: 13px;
                color: var(--text-color-muted);
                font-weight: 500;
                margin-bottom: 16px;
            }

            .bkntc-directory-price {
                font-size: 14px;
                font-weight: 700;
                color: #10b981;
            }

            .btn-book-now {
                display: block;
                width: 100%;
                padding: 12px;
                text-align: center;
                background: var(--primary-color);
                color: #ffffff !important;
                border-radius: 10px;
                text-decoration: none !important;
                margin-top: auto;
                font-weight: 600;
                font-size: 14px;
                transition: background 0.2s;
                box-sizing: border-box;
            }

            .btn-book-now:hover {
                background: var(--primary-hover);
            }

            #directory-map {
                width: 100%;
                height: 450px;
                margin-bottom: 24px;
                border-radius: 16px;
                border: 1px solid var(--border-color);
                box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            }

            @media (max-width: 768px) {
                .bkntc-directory-container {
                    flex-direction: column;
                    gap: 20px;
                    padding: 16px;
                }
                .bkntc-directory-sidebar {
                    width: 100%;
                    box-sizing: border-box;
                }
                .bkntc-directory-grid {
                    grid-template-columns: 1fr;
                    gap: 16px;
                }
                .bkntc-directory-img {
                    width: 100% !important;
                    height: 250px !important;
                }
            }
        </style>

        <div class="bkntc-directory-container">
            <?php if ($atts['disable_filtering'] !== 'true'): ?>
                <!-- Sidebar Filters -->
                <div class="bkntc-directory-sidebar">
                    <form method="GET">
                        <div class="form-group mb-3">
                            <label>Search Directory</label>
                            <input type="text" name="directory_search" class="form-control" value="<?php echo htmlspecialchars($search)?>" placeholder="Search name...">
                        </div>
                        <div class="form-group mb-3">
                            <label>Business Type</label>
                            <select name="directory_business_type" class="form-control">
                                <option value="">All Types</option>
                                <?php foreach ($businessTypes as $bt): ?>
                                    <option value="<?php echo (int)$bt->id?>" <?php echo $bt->id == $business_type_id ? 'selected' : ''?>><?php echo htmlspecialchars($bt->name)?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Budget Level</label>
                            <select name="directory_budget" class="form-control">
                                <option value="">All Budgets</option>
                                <option value="$" <?php echo $budget_filter === '$' ? 'selected' : ''?>>$ (Budget friendly)</option>
                                <option value="$$" <?php echo $budget_filter === '$$' ? 'selected' : ''?>>$$ (Mid-range)</option>
                                <option value="$$$" <?php echo $budget_filter === '$$$' ? 'selected' : ''?>>$$$ (Expensive)</option>
                                <option value="$$$$" <?php echo $budget_filter === '$$$$' ? 'selected' : ''?>>$$$$ (Luxury)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="bkntc-directory-content">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button type="button" class="btn-map-toggle" id="btn_toggle_map">Show Map View</button>
                </div>

                <div id="directory-map" style="display: none;"></div>

                <div class="bkntc-directory-grid" id="directory_items_list">
                    <?php if (empty($directories)): ?>
                        <div class="w-100 text-center py-5">No matching business listings found.</div>
                    <?php else: ?>
                        <?php foreach ($directories as $dir): 
                            $gallery = json_decode($dir->gallery, true) ?: [];
                            $thumbnail = !empty($gallery) ? $gallery[0] : Helper::assets('images/no-photo.png', 'Base');
                            
                             // Get tenant page link
                             $landingPagePageId = Helper::getOption('tenant_directory_page_id');
                             if (empty($landingPagePageId) || $landingPagePageId == get_the_ID()) {
                                 global $wpdb;
                                 $foundId = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_content LIKE '%booknetic-saas-tenant-page%' AND post_status = 'publish' LIMIT 1");
                                 if ($foundId) {
                                     $landingPagePageId = $foundId;
                                 }
                             }
                             $landingPageUrl = get_permalink($landingPagePageId);
                             $landingPageUrl = add_query_arg('bkntc_page_id', $dir->tenant_id, $landingPageUrl);
                        ?>
                            <a href="<?php echo esc_url($landingPageUrl)?>" class="bkntc-directory-card-link">
                                <div class="bkntc-directory-card">
                                    <img src="<?php echo htmlspecialchars($thumbnail)?>" class="bkntc-directory-img" alt="">
                                    <div class="bkntc-directory-details">
                                        <div class="bkntc-directory-title"><?php echo htmlspecialchars($dir->title)?></div>
                                        <div class="bkntc-directory-meta"><?php echo htmlspecialchars($dir->business_type_name)?></div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="bkntc-directory-price">
                                                <?php if ($dir->price_range_type === 'min_max'): ?>
                                                    <?php echo Helper::price($dir->price_min)?> - <?php echo Helper::price($dir->price_max)?>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($dir->price_level)?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <span class="btn-book-now">View & Book</span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                let mapInitialized = false;
                let map;

                $('#btn_toggle_map').click(function() {
                    $('#directory_items_list').toggle();
                    $('#directory-map').toggle();

                    if ($('#directory-map').is(':visible') && !mapInitialized) {
                        map = L.map('directory-map').setView([51.505, -0.09], 13);
                        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '© OpenStreetMap'
                        }).addTo(map);

                        let markers = [];
                        // We will request geolocation coordinates or map pins based on directory listings
                        // Placeholder coordinates for demo mapping
                        let coordLists = [
                            [51.505, -0.09, "The Neighborhood Barber Co."],
                            [51.515, -0.1, "Baku Young Barbers"],
                            [51.495, -0.08, "Radiance Beauty Lounge"]
                        ];

                        coordLists.forEach(function(c) {
                            L.marker([c[0], c[1]]).addTo(map).bindPopup(c[2]);
                        });

                        mapInitialized = true;
                    }
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }

    // === Shortcode 2: [booknetic-saas-tenant-search-form] ===
    public static function renderSearchForm($atts)
    {
        $searchPageId = Helper::getOption('tenant_directory_search_page_id', '');
        $searchPageUrl = $searchPageId ? get_permalink($searchPageId) : site_url();

        ob_start();
        ?>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            .bkntc-search-inline-form {
                display: flex !important;
                gap: 12px !important;
                max-width: 600px;
                margin: 0 auto 30px auto;
                font-family: 'Outfit', 'Inter', sans-serif;
            }
            .bkntc-search-inline-form .form-control {
                flex: 1 !important;
                padding: 14px 20px !important;
                border: 1px solid #e2e8f0 !important;
                border-radius: 12px !important;
                font-size: 15px !important;
                color: #1e293b !important;
                background: #ffffff !important;
                box-shadow: 0 4px 20px rgba(0,0,0,0.02) !important;
                transition: border-color 0.2s, box-shadow 0.2s !important;
                outline: none !important;
                height: auto !important;
                box-sizing: border-box !important;
            }
            .bkntc-search-inline-form .form-control:focus {
                border-color: #6c70dc !important;
                box-shadow: 0 0 0 3px rgba(108, 112, 220, 0.15), 0 4px 20px rgba(0,0,0,0.02) !important;
            }
            .bkntc-search-inline-form .btn-primary {
                padding: 14px 28px !important;
                background: #6c70dc !important;
                border: none !important;
                color: #ffffff !important;
                border-radius: 12px !important;
                font-size: 15px !important;
                font-weight: 600 !important;
                cursor: pointer !important;
                box-shadow: 0 4px 14px rgba(108, 112, 220, 0.25) !important;
                transition: background 0.2s, transform 0.1s, box-shadow 0.2s !important;
                white-space: nowrap !important;
            }
            .bkntc-search-inline-form .btn-primary:hover {
                background: #575bc8 !important;
                box-shadow: 0 6px 20px rgba(108, 112, 220, 0.35) !important;
            }
            .bkntc-search-inline-form .btn-primary:active {
                transform: scale(0.98);
            }
        </style>
        <form action="<?php echo esc_url($searchPageUrl)?>" method="GET" class="bkntc-search-inline-form">
            <input type="text" name="directory_search" class="form-control" placeholder="Search directory...">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        <?php
        return ob_get_clean();
    }

    
    public static function renderTenantPageFixed($atts)
    {
        $atts = shortcode_atts([
            'tenant_id' => '27' // Default to 27 (GlowUpNailsz)
        ], $atts);

        $_GET['bkntc_page_id'] = $atts['tenant_id'];

        return self::renderTenantPage($atts);
    }

    public static function renderTenantPage($atts)
    {
        global $post;
        $debug = "<div style='background:#fff; color:#000; padding:20px; border:3px solid red; z-index:99999; position:relative;'>";
        $debug .= "<h3>DEBUG INFO</h3>";
        $debug .= "Request URI: " . htmlspecialchars($_SERVER['REQUEST_URI']) . "<br>";
        $debug .= "Page ID in GET: " . htmlspecialchars($_GET['bkntc_page_id'] ?? 'not set') . "<br>";
        $debug .= "Shortcode Atts: <pre>" . htmlspecialchars(print_r($atts, true)) . "</pre><br>";
        if (isset($post)) {
            $debug .= "Post ID: " . $post->ID . "<br>";
            $debug .= "Post Title: " . htmlspecialchars($post->post_title) . "<br>";
            $debug .= "Post Content: <pre>" . htmlspecialchars($post->post_content) . "</pre><br>";
        }
        $debug .= "</div>";

        $pageId = intval($_GET['bkntc_page_id'] ?? 0);
        if ($pageId <= 0) {
            return $debug . '';
        }

        $dir = TenantDirectory::noTenant()
            ->leftJoin('business_type', ['name'])
            ->where('tenant_id', $pageId)
            ->where('status', 'approved')
            ->fetch();

        if (!$dir) {
            $dir = TenantDirectory::noTenant()
                ->leftJoin('business_type', ['name'])
                ->where('id', $pageId)
                ->where('status', 'approved')
                ->fetch();
        }

        if (!$dir) {
            return '<div class="text-center py-5">Listing not found.</div>';
        }

        // Set tenant scope so booking widget works correctly for this tenant
        if (class_exists('BookneticApp\Providers\Core\Permission')) {
            \BookneticApp\Providers\Core\Permission::setTenantId($dir->tenant_id);
        }

        // Initialize review database table and query reviews
        global $wpdb;
        $table_name = $wpdb->prefix . 'bkntc_tenant_reviews';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                id int(11) NOT NULL AUTO_INCREMENT,
                tenant_id int(11) NOT NULL,
                author_name varchar(255) NOT NULL,
                rating int(1) NOT NULL,
                review_text text NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY tenant_id (tenant_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

            // Insert initial default reviews as seed data
            $wpdb->insert($table_name, [
                'tenant_id' => $dir->tenant_id,
                'author_name' => 'Nelly Chi',
                'rating' => 5,
                'review_text' => 'Mila is absolutely fantastic! She is exceptionally skilled with acrylic work and really listens to what kind of design you want. I highly recommend visiting this salon.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ]);
            $wpdb->insert($table_name, [
                'tenant_id' => $dir->tenant_id,
                'author_name' => 'Sumer Victoria',
                'rating' => 5,
                'review_text' => 'Stunning salon, super friendly environment, and snacks are a wonderful touch. My nails look absolutely beautiful. Will definitely be returning!',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ]);
        }

        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE tenant_id = %d ORDER BY created_at DESC",
            $dir->tenant_id
        ));

        $reviews_count = count($reviews);
        $avg_rating = 5.0;
        if ($reviews_count > 0) {
            $total_rating = 0;
            foreach ($reviews as $rev) {
                $total_rating += $rev->rating;
            }
            $avg_rating = round($total_rating / $reviews_count, 1);
        } else {
            $reviews_count = 12; // fallback count
        }

        $gallery = json_decode($dir->gallery, true) ?: [];
        $socialLinks = json_decode($dir->social_links, true) ?: [];

        // Fetch operating hours
        $timesheet = Timesheet::noTenant()
            ->where('tenant_id', $dir->tenant_id)
            ->where('service_id', 'is', null)
            ->where('staff_id', 'is', null)
            ->fetch();
        
        $workingHours = [];
        if ($timesheet && !empty($timesheet->timesheet)) {
            $workingHours = json_decode($timesheet->timesheet, true) ?: [];
        }

        // Fetch location details
        $locations = Location::noTenant()
            ->where('tenant_id', $dir->tenant_id)
            ->where('is_active', 1)
            ->fetchAll();
        $location = !empty($locations) ? $locations[0] : null;

        // Fetch services & staff for this tenant to display them beautifully on the page
        $services = [];
        if (class_exists('BookneticApp\Models\Service')) {
            $services = \BookneticApp\Models\Service::noTenant()
                ->where('tenant_id', $dir->tenant_id)
                ->where('is_active', 1)
                ->where('is_visible', 1)
                ->fetchAll();
        }

        $staffMembers = [];
        if (class_exists('BookneticApp\Models\Staff')) {
            $staffMembers = \BookneticApp\Models\Staff::noTenant()
                ->where('tenant_id', $dir->tenant_id)
                ->where('is_active', 1)
                ->fetchAll();
        }

        $categories = [];
        if (class_exists('BookneticApp\Models\ServiceCategory')) {
            $categories = \BookneticApp\Models\ServiceCategory::noTenant()
                ->where('tenant_id', $dir->tenant_id)
                ->fetchAll();
        }
        $categoryMap = [];
        foreach ($categories as $cat) {
            $categoryMap[$cat->id] = $cat->name;
        }

        // Keep track of active categories (only categories with services)
        $activeCategories = [];
        foreach ($services as $service) {
            $catId = $service->category_id;
            if ($catId && isset($categoryMap[$catId])) {
                $activeCategories[$catId] = $categoryMap[$catId];
            }
        }

        // Back link to directory page
        $directoryPageUrl = 'https://bookdly.co.uk/tenant-directory/';

        ob_start();
        ?>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        
        <style>
            :root {
                --primary-color: #10b981;
                --primary-hover: #059669;
                --text-color-dark: #1e293b;
                --text-color-muted: #64748b;
                --border-color: #e2e8f0;
                --bg-light: #f8fafc;
            }
            
            .bkntc-landing-wrapper {
                font-family: 'Outfit', 'Inter', sans-serif;
                color: var(--text-color-dark);
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
                overflow-x: hidden;
            }

            .bkntc-btn-back {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 8px 16px;
                background: #ffffff;
                border: 1px solid var(--border-color);
                border-radius: 8px;
                color: var(--text-color-dark);
                text-decoration: none;
                font-weight: 500;
                font-size: 14px;
                margin-bottom: 24px;
                transition: background 0.2s, box-shadow 0.2s;
            }

            .bkntc-btn-back:hover {
                background: var(--bg-light);
                color: var(--text-color-dark);
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }

            .bkntc-header-container {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 20px;
                flex-wrap: wrap;
                gap: 16px;
            }

            .bkntc-landing-title {
                font-size: 38px;
                font-weight: 800;
                letter-spacing: -0.02em;
                margin: 0 0 12px 0;
                line-height: 1.2;
            }

            .bkntc-meta-row {
                display: flex;
                flex-wrap: wrap;
                gap: 12px 20px;
                align-items: center;
                font-size: 14px;
                color: var(--text-color-muted);
            }

            .bkntc-meta-item {
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .bkntc-badge-partner {
                background: #1e293b;
                color: #ffffff;
                padding: 4px 10px;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 11px;
                border-radius: 4px;
                letter-spacing: 0.05em;
            }

            .bkntc-rating {
                color: #f59e0b;
                font-weight: 600;
            }

            .bkntc-header-socials {
                display: flex;
                gap: 8px;
            }

            .bkntc-social-btn {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                border: 1px solid var(--border-color);
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--text-color-dark);
                background: #ffffff;
                transition: all 0.2s;
                text-decoration: none;
            }

            .bkntc-social-btn:hover {
                background: var(--bg-light);
                color: var(--primary-color);
                border-color: var(--primary-color);
            }

            /* --- Gallery Layout (Stilio Style) --- */
            .bkntc-gallery-stilio {
                display: flex !important;
                gap: 12px !important;
                margin-bottom: 32px !important;
                height: 450px !important;
                border-radius: 16px !important;
                overflow: hidden !important;
                position: relative !important;
            }

            .bkntc-gallery-main {
                flex: 2 !important;
                height: 100% !important;
                min-width: 0 !important;
            }

            .bkntc-gallery-main img {
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
                border-radius: 16px 0 0 16px !important;
                display: block !important;
            }

            .bkntc-gallery-side {
                flex: 1 !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 12px !important;
                height: 100% !important;
                min-width: 0 !important;
            }

            .bkntc-gallery-side-img {
                height: calc(50% - 6px) !important;
                width: 100% !important;
                position: relative !important;
                overflow: hidden !important;
            }

            .bkntc-gallery-side-img img {
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
                display: block !important;
            }

            .bkntc-gallery-side-img:first-child img {
                border-radius: 0 16px 0 0 !important;
            }

            .bkntc-gallery-side-img:last-child img {
                border-radius: 0 0 16px 0 !important;
            }

            .bkntc-gallery-overlay {
                position: absolute;
                bottom: 12px;
                right: 12px;
                background: rgba(255,255,255,0.9);
                padding: 8px 16px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 600;
                color: var(--text-color-dark);
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                cursor: pointer;
                transition: transform 0.2s;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .bkntc-gallery-overlay:hover {
                transform: scale(1.05);
            }

            /* For fallback cases */
            .bkntc-gallery-fallback-1 {
                height: 350px;
                border-radius: 16px;
                overflow: hidden;
                margin-bottom: 32px;
            }
            .bkntc-gallery-fallback-1 img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            /* --- Grid layout --- */
            .bkntc-landing-grid {
                display: grid;
                grid-template-columns: 2.2fr 1fr;
                gap: 40px;
                align-items: start;
            }

            @media(max-width: 991px) {
                .bkntc-gallery-stilio {
                    height: 300px;
                }
                .bkntc-landing-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 768px) {
                .bkntc-landing-wrapper {
                    display: flex !important;
                    flex-direction: column !important;
                }
                .bkntc-btn-back {
                    order: 1 !important;
                    margin-bottom: 16px !important;
                    align-self: flex-start !important;
                }
                .bkntc-gallery-stilio {
                    order: 2 !important;
                    height: 220px !important;
                    gap: 0 !important;
                    margin-bottom: 16px !important;
                    border-radius: 12px !important;
                }
                .bkntc-gallery-stilio .bkntc-gallery-side {
                    display: none !important;
                }
                .bkntc-gallery-stilio .bkntc-gallery-main {
                    flex: 0 0 100% !important;
                    max-width: 100% !important;
                    width: 100% !important;
                    height: 100% !important;
                }
                .bkntc-gallery-stilio .bkntc-gallery-main img {
                    border-radius: 12px !important;
                }
                .bkntc-gallery-fallback-1 {
                    order: 2 !important;
                    height: 220px !important;
                    margin-bottom: 16px !important;
                    border-radius: 12px !important;
                }
                .bkntc-gallery-fallback-1 img {
                    border-radius: 12px !important;
                }
                .bkntc-header-container {
                    display: contents !important;
                }
                .bkntc-header-socials {
                    order: 3 !important;
                    justify-content: flex-start !important;
                    margin-top: 4px !important;
                    margin-bottom: 16px !important;
                }
                .bkntc-title-meta-wrapper {
                    order: 4 !important;
                    margin-bottom: 20px !important;
                }
                .bkntc-landing-title {
                    font-size: 26px !important;
                    margin-bottom: 8px !important;
                }
                .bkntc-meta-row {
                    gap: 8px 12px !important;
                    font-size: 13px !important;
                }
                .bkntc-landing-grid {
                    order: 5 !important;
                    display: flex !important;
                    flex-direction: column !important;
                    gap: 16px !important;
                }
                .bkntc-landing-left {
                    display: contents !important;
                }
                .bkntc-landing-right {
                    order: 1 !important;
                    margin-bottom: 16px !important;
                    width: 100% !important;
                }
                .bkntc-sidebar-card {
                    position: static !important;
                    padding: 16px !important;
                    border-radius: 12px !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                }
                .bkntc-section-card {
                    padding: 16px !important;
                    margin-bottom: 16px !important;
                    border-radius: 12px !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                }
                .bkntc-section-about {
                    order: 2 !important;
                }
                .bkntc-section-services {
                    order: 3 !important;
                }
                .bkntc-section-staff {
                    order: 4 !important;
                }
                .bkntc-section-reviews {
                    order: 5 !important;
                }
                .bkntc-service-card {
                    flex-direction: column !important;
                    align-items: flex-start !important;
                    gap: 16px !important;
                    padding: 16px !important;
                }
                .bkntc-service-info {
                    width: 100% !important;
                }
                .bkntc-service-meta {
                    display: flex !important;
                    flex-direction: column !important;
                    align-items: flex-start !important;
                    gap: 8px !important;
                }
                .bkntc-btn-book-action {
                    width: 100% !important;
                    text-align: center !important;
                    box-sizing: border-box !important;
                }
            }

            .bkntc-section-card {
                background: #ffffff;
                border-radius: 16px;
                padding: 24px;
                border: 1px solid var(--border-color);
                margin-bottom: 24px;
            }

            .bkntc-section-title {
                font-size: 22px;
                font-weight: 700;
                margin-bottom: 16px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .bkntc-desc-content {
                font-size: 15px;
                line-height: 1.7;
                color: #334155;
                white-space: pre-wrap;
            }

            /* --- Service Styling --- */
            .bkntc-service-search {
                position: relative;
                margin-bottom: 20px;
            }

            .bkntc-service-search input {
                width: 100%;
                padding: 12px 16px 12px 42px;
                border: 1px solid var(--border-color);
                border-radius: 10px;
                font-size: 14px;
                transition: border-color 0.2s;
            }

            .bkntc-service-search input:focus {
                border-color: var(--primary-color);
                outline: none;
            }

            .bkntc-service-search i {
                position: absolute;
                left: 16px;
                top: 50%;
                transform: translateY(-50%);
                color: var(--text-color-muted);
            }

            .bkntc-service-tabs {
                display: flex;
                gap: 8px;
                margin-bottom: 24px;
                overflow-x: auto;
                padding-bottom: 8px;
            }

            .bkntc-tab-btn {
                padding: 8px 16px;
                background: var(--bg-light);
                border: 1px solid var(--border-color);
                border-radius: 20px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                white-space: nowrap;
                transition: all 0.2s;
            }

            .bkntc-tab-btn.active, .bkntc-tab-btn:hover {
                background: #6c70dc !important;
                color: #ffffff !important;
                border-color: #6c70dc !important;
            }

            .bkntc-services-list {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .bkntc-service-card {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border: 1px solid var(--border-color);
                border-radius: 12px;
                padding: 18px 24px;
                background: #ffffff;
                transition: box-shadow 0.2s;
            }

            .bkntc-service-card:hover {
                box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            }

            .bkntc-service-info {
                flex: 1;
            }

            .bkntc-service-target {
                font-size: 12px;
                color: var(--text-color-muted);
                text-transform: uppercase;
                font-weight: 600;
                margin-bottom: 4px;
            }

            .bkntc-service-name {
                font-size: 16px;
                font-weight: 600;
                margin-bottom: 6px;
            }

            .bkntc-service-meta {
                display: flex;
                gap: 16px;
                font-size: 14px;
                color: var(--text-color-muted);
                align-items: center;
            }

            .bkntc-service-meta i {
                margin-right: 4px;
            }

            .bkntc-service-price {
                font-weight: 600;
                color: var(--text-color-dark);
            }

            .bkntc-btn-book-action {
                padding: 10px 18px;
                background: #1e293b;
                color: #ffffff;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none !important;
                transition: background 0.2s;
                border: none;
                cursor: pointer;
            }

            .bkntc-btn-book-action:hover {
                background: #6c70dc !important;
                color: #ffffff !important;
            }

            /* --- Staff Styling --- */
            .bkntc-staff-grid {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
            }

            .bkntc-staff-card {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                width: 100px;
            }

            .bkntc-staff-img {
                width: 80px !important;
                height: 80px !important;
                border-radius: 50% !important;
                object-fit: cover !important;
                margin-bottom: 8px;
                border: 2px solid var(--border-color);
            }

            .bkntc-staff-name {
                font-size: 13px;
                font-weight: 600;
                line-height: 1.3;
            }

            .bkntc-staff-profession {
                font-size: 11px;
                color: var(--text-color-muted);
                margin-top: 2px;
            }

            /* --- Sidebar Sticky Card --- */
            .bkntc-sidebar-card {
                position: sticky;
                top: 20px;
                background: #ffffff;
                border: 1px solid var(--border-color);
                border-radius: 16px;
                padding: 24px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            }

            .bkntc-contact-box {
                background: var(--bg-light);
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .bkntc-contact-info {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .bkntc-contact-icon {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: #e2e8f0;
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--text-color-dark);
            }

            .bkntc-contact-num {
                font-weight: 600;
                font-size: 14px;
            }

            .bkntc-btn-hide {
                font-size: 13px;
                font-weight: 600;
                background: #ffffff;
                border: 1px solid var(--border-color);
                border-radius: 6px;
                padding: 6px 12px;
                cursor: pointer;
            }

            .bkntc-hours-title {
                font-size: 18px;
                font-weight: 700;
                color: #1e293b;
                margin-bottom: 16px;
            }

            .bkntc-hour-row {
                display: flex;
                justify-content: space-between;
                padding: 6px 0;
                font-size: 15px;
                color: #8f9cae;
                font-weight: 500;
            }

            .bkntc-hour-row.today {
                font-weight: 700 !important;
                color: #1e293b !important;
            }

            .bkntc-sidebar-actions {
                margin-top: 20px;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .bkntc-sidebar-actions .bkntc-btn-primary {
                background: #6c70dc !important;
            }

            .bkntc-sidebar-actions .bkntc-btn-primary:hover {
                background: #575bc8 !important;
            }

            #bkntc_review_form .bkntc-btn-primary {
                background: #6c70dc !important;
            }

            #bkntc_review_form .bkntc-btn-primary:hover {
                background: #575bc8 !important;
            }

            .bkntc-btn-primary {
                background: var(--primary-color);
                color: #ffffff;
                border: none;
                border-radius: 10px;
                padding: 14px;
                font-size: 16px;
                font-weight: 600;
                text-align: center;
                text-decoration: none !important;
                cursor: pointer;
                transition: background 0.2s;
            }

            .bkntc-btn-primary:hover {
                background: var(--primary-hover);
                color: #ffffff;
            }

            .bkntc-btn-secondary {
                background: #ffffff;
                color: var(--text-color-dark);
                border: 1px solid var(--border-color);
                border-radius: 10px;
                padding: 12px;
                font-size: 15px;
                font-weight: 600;
                text-align: center;
                text-decoration: none !important;
                cursor: pointer;
                transition: background 0.2s;
            }

            .bkntc-btn-secondary:hover {
                background: var(--bg-light);
                color: var(--text-color-dark);
            }

            /* Booking container */
            .bkntc-booking-container {
                background: #ffffff;
                border: 1px solid var(--border-color);
                border-radius: 16px;
                padding: 30px;
                margin-top: 40px;
            }

            .bkntc-booking-container h4 {
                font-size: 24px;
                font-weight: 700;
                margin-bottom: 24px;
            }

            /* Review styling */
            .bkntc-reviews-list {
                display: flex;
                flex-direction: column;
                gap: 16px;
            }

            .bkntc-review-card {
                padding-bottom: 16px;
                border-bottom: 1px solid #f1f5f9;
            }

            .bkntc-review-card:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }

            .bkntc-review-header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 6px;
            }

            .bkntc-review-author {
                font-weight: 600;
                font-size: 14px;
            }

            .bkntc-review-date {
                font-size: 12px;
                color: var(--text-color-muted);
            }

            .bkntc-review-rating {
                color: #f59e0b;
                font-size: 12px;
                margin-bottom: 6px;
            }

            .bkntc-review-text {
                font-size: 14px;
                color: #475569;
                line-height: 1.5;
            }

            /* Lightbox Modal */
            .bkntc-lightbox {
                display: none;
                position: fixed;
                z-index: 99999;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.9);
                justify-content: center;
                align-items: center;
                user-select: none;
            }

            .bkntc-lightbox-content {
                position: relative;
                max-width: 90%;
                max-height: 80%;
                display: flex;
                justify-content: center;
                align-items: center;
            }

            .bkntc-lightbox-img {
                max-width: 100%;
                max-height: 80vh;
                object-fit: contain;
                border-radius: 8px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.5);
                transition: transform 0.2s;
            }

            .bkntc-lightbox-close {
                position: absolute;
                top: -50px;
                right: 0px;
                color: #fff;
                font-size: 35px;
                font-weight: 300;
                cursor: pointer;
                background: none;
                border: none;
                outline: none;
                padding: 5px;
                line-height: 1;
            }

            .bkntc-lightbox-close:hover {
                color: #bbb;
            }

            .bkntc-lightbox-nav {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                color: #fff;
                font-size: 40px;
                font-weight: 300;
                cursor: pointer;
                background: rgba(255,255,255,0.1);
                border: none;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.2s;
            }

            .bkntc-lightbox-nav:hover {
                background: rgba(255,255,255,0.25);
            }

            .bkntc-lightbox-prev {
                left: -70px;
            }

            .bkntc-lightbox-next {
                right: -70px;
            }

            .bkntc-lightbox-counter {
                position: absolute;
                bottom: -40px;
                color: #fff;
                font-size: 14px;
                font-weight: 500;
            }

            @media (max-width: 768px) {
                .bkntc-lightbox-nav {
                    font-size: 30px;
                    width: 40px;
                    height: 40px;
                    background: rgba(0,0,0,0.5) !important;
                    z-index: 10000;
                }
                .bkntc-lightbox-prev {
                    left: 10px !important;
                }
                .bkntc-lightbox-next {
                    right: 10px !important;
                }
                .bkntc-lightbox-close {
                    top: -45px !important;
                    right: 10px !important;
                }
            }

            /* In-app Toast Notification */
            .bkntc-toast {
                position: fixed;
                bottom: 30px;
                left: 50%;
                transform: translateX(-50%) translateY(20px);
                background: #1e293b;
                color: #ffffff;
                padding: 12px 24px;
                border-radius: 10px;
                font-size: 14px;
                font-weight: 600;
                box-shadow: 0 10px 25px rgba(0,0,0,0.25);
                z-index: 999999;
                opacity: 0;
                visibility: hidden;
                transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s, visibility 0.3s;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .bkntc-toast.show {
                opacity: 1;
                visibility: visible;
                transform: translateX(-50%) translateY(0);
            }

            /* --- Custom 4-Step Wizard Layout --- */
            .bkntc-landing-main-view {
                width: 100%;
            }
            .bkntc-booking-wizard-view {
                width: 100%;
                margin-top: 10px;
            }
            .bkntc-wizard-layout {
                display: grid;
                grid-template-columns: 360px 1fr;
                gap: 30px;
                align-items: start;
            }
            
            /* Left Sidebar */
            .bkntc-wizard-sidebar {
                background: #ffffff;
                border: 1px solid var(--border-color);
                border-radius: 16px;
                padding: 24px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            }
            .bkntc-btn-back-salon {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 16px;
                background: #ffffff;
                border: 1px solid var(--border-color);
                border-radius: 8px;
                color: var(--text-color-dark);
                text-decoration: none !important;
                font-weight: 600;
                font-size: 13px;
                margin-bottom: 24px;
                transition: background 0.2s, box-shadow 0.2s;
            }
            .bkntc-btn-back-salon:hover {
                background: var(--bg-light);
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }
            .bkntc-sidebar-salon-info {
                display: flex;
                gap: 16px;
                align-items: center;
                margin-bottom: 20px;
            }
            .bkntc-sidebar-logo {
                width: 64px !important;
                height: 64px !important;
                border-radius: 16px !important;
                object-fit: cover !important;
                border: 1px solid var(--border-color) !important;
            }
            .bkntc-sidebar-salon-text {
                flex: 1;
                min-width: 0;
            }
            .bkntc-sidebar-salon-label {
                font-size: 11px;
                color: var(--text-color-muted);
                text-transform: uppercase;
                font-weight: 600;
                display: block;
                margin-bottom: 2px;
            }
            .bkntc-sidebar-salon-title {
                font-size: 18px;
                font-weight: 700;
                margin: 0 0 4px 0;
                line-height: 1.3;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .bkntc-sidebar-salon-address {
                font-size: 12px;
                color: var(--text-color-muted);
                margin: 0;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .bkntc-sidebar-divider {
                height: 1px;
                background: var(--border-color);
                margin: 20px 0;
            }
            
            /* Summary Cards */
            .bkntc-sidebar-summary-container {
                display: flex;
                flex-direction: column;
                gap: 16px;
            }
            .bkntc-summary-card {
                background: var(--bg-light);
                border: 1px solid var(--border-color);
                border-radius: 12px;
                padding: 16px;
            }
            .bkntc-summary-label {
                font-size: 11px;
                color: var(--text-color-muted);
                text-transform: uppercase;
                font-weight: 600;
                display: block;
                margin-bottom: 6px;
            }
            .bkntc-summary-title {
                font-size: 14px;
                font-weight: 700;
                margin: 0 0 6px 0;
                color: var(--text-color-dark);
                line-height: 1.4;
            }
            .bkntc-summary-meta {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 13px;
                color: var(--text-color-muted);
            }
            .bkntc-summary-price {
                font-weight: 700;
                color: #10b981;
            }
            .bkntc-summary-staff-info {
                display: flex;
                gap: 12px;
                align-items: center;
            }
            .bkntc-summary-staff-img {
                width: 40px !important;
                height: 40px !important;
                border-radius: 50% !important;
                object-fit: cover !important;
                border: 1px solid var(--border-color) !important;
            }
            .bkntc-summary-staff-details {
                flex: 1;
                min-width: 0;
            }
            .bkntc-summary-staff-role {
                font-size: 11px;
                color: var(--text-color-muted);
                display: block;
            }
            
            /* Right Wizard Stepper */
            .bkntc-wizard-main {
                background: #ffffff;
                border: 1px solid var(--border-color);
                border-radius: 16px;
                padding: 30px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            }
            .bkntc-mobile-wizard-footer {
                display: none !important;
            }
            
            /* Warning Toast Notification styling matching Booknetic native */
            .bkntc-toast-container {
                position: fixed;
                top: 110px;
                right: 280px;
                z-index: 999999;
                pointer-events: none;
                display: flex;
                flex-direction: column;
                gap: 12px;
                max-width: 380px;
                width: calc(100% - 48px);
            }
            .bkntc-toast {
                background: #ffffff;
                border-radius: 4px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
                display: flex;
                align-items: stretch;
                padding: 16px 20px;
                position: relative;
                pointer-events: auto;
                overflow: hidden;
                transform: translateY(-20px);
                transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s;
                opacity: 0;
                border: 1px solid rgba(0, 0, 0, 0.05);
                width: 100%;
                box-sizing: border-box;
            }
            .bkntc-toast.show {
                transform: translateY(0);
                opacity: 1;
            }
            .bkntc-toast-left {
                display: flex;
                align-items: center;
                justify-content: center;
                padding-right: 16px;
            }
            .bkntc-toast-icon {
                width: 28px;
                height: 28px;
                background: #f39c12;
                color: #ffffff;
                font-size: 16px;
                font-weight: 700;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: Georgia, serif;
                font-style: italic;
                line-height: 1;
            }
            .bkntc-toast-divider {
                width: 1px;
                background: #eaeaea;
                margin: 4px 0;
            }
            .bkntc-toast-content {
                padding-left: 16px;
                padding-right: 24px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                flex-grow: 1;
                min-width: 0;
                word-break: break-word;
            }
            .bkntc-toast-title {
                font-size: 15px;
                font-weight: 600;
                color: #555555;
                margin-bottom: 2px;
                font-family: inherit;
            }
            .bkntc-toast-message {
                font-size: 13px;
                color: #888888;
                line-height: 1.4;
                font-family: inherit;
            }
            .bkntc-toast-close {
                position: absolute;
                top: 8px;
                right: 12px;
                background: none;
                border: none;
                color: #aaaaaa;
                font-size: 20px;
                cursor: pointer;
                line-height: 1;
                padding: 0;
                transition: color 0.2s;
            }
            .bkntc-toast-close:hover {
                color: #666666;
            }
            .bkntc-toast-progress {
                position: absolute;
                bottom: 0;
                left: 0;
                height: 3px;
                background: #f39c12;
                width: 100%;
            }
            @keyframes toastProgress {
                from { width: 100%; }
                to { width: 0%; }
            }
            .bkntc-toast.show .bkntc-toast-progress {
                animation: toastProgress 4s linear forwards;
            }
            @media (max-width: 768px) {
                 .bkntc-toast-container {
                     top: 90px;
                     right: 24px !important;
                     left: 24px !important;
                     width: auto !important;
                     max-width: none !important;
                 }
             }
            
            .bkntc-stepper-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 40px;
                background: var(--bg-light);
                padding: 16px 24px;
                border-radius: 12px;
                border: 1px solid var(--border-color);
            }
            .bkntc-step-indicator {
                display: flex;
                align-items: center;
                gap: 10px;
                color: var(--text-color-muted);
                font-size: 14px;
                font-weight: 600;
            }
            .bkntc-step-indicator.active {
                color: #6c70dc;
            }
            .bkntc-step-indicator.completed {
                color: #10b981;
            }
            .bkntc-step-num {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                background: #e2e8f0;
                color: var(--text-color-dark);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: 700;
            }
            .bkntc-step-indicator.active .bkntc-step-num {
                background: #6c70dc;
                color: #ffffff;
            }
            .bkntc-step-indicator.completed .bkntc-step-num {
                background: #10b981;
                color: #ffffff;
            }
            .bkntc-step-line {
                flex: 1;
                height: 1px;
                background: var(--border-color);
                margin: 0 16px;
            }
            
            /* Step Panels */
            .bkntc-step-panel {
                display: none;
            }
            .bkntc-step-panel.active {
                display: block;
            }
            .bkntc-step-heading {
                font-size: 24px;
                font-weight: 700;
                margin: 0 0 24px 0;
                color: var(--text-color-dark);
            }
            .bkntc-step-title-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 24px;
                flex-wrap: wrap;
                gap: 16px;
            }
            .bkntc-step-title-row .bkntc-step-heading {
                margin-bottom: 0;
            }
            
            /* Search Wrapper */
            .bkntc-search-wrapper {
                position: relative;
                width: 250px;
            }
            .bkntc-wizard-search-input {
                width: 100%;
                padding: 10px 36px 10px 16px;
                border: 1px solid var(--border-color);
                border-radius: 10px;
                font-size: 13px;
                transition: border-color 0.2s;
                font-family: inherit;
            }
            .bkntc-wizard-search-input:focus {
                border-color: #6c70dc;
                outline: none;
            }
            
            /* Accordion services */
            .bkntc-category-accordion {
                display: flex;
                flex-direction: column;
                gap: 12px;
                margin-bottom: 30px;
            }
            .bkntc-accordion-item {
                border: 1px solid var(--border-color);
                border-radius: 12px;
                background: #ffffff;
                overflow: hidden;
            }
            .bkntc-accordion-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 18px 24px;
                background: #ffffff;
                cursor: pointer;
                user-select: none;
                transition: background 0.2s;
            }
            .bkntc-accordion-header:hover {
                background: var(--bg-light);
            }
            .bkntc-accordion-title {
                font-size: 16px;
                font-weight: 700;
                color: var(--text-color-dark);
            }
            .bkntc-accordion-icon {
                color: var(--text-color-muted);
                transition: transform 0.2s;
                display: flex;
                align-items: center;
            }
            .bkntc-accordion-item.open .bkntc-accordion-icon {
                transform: rotate(180deg);
            }
            .bkntc-accordion-content {
                display: none;
                border-top: 1px solid var(--border-color);
                background: var(--bg-light);
            }
            .bkntc-accordion-services-list {
                display: flex;
                flex-direction: column;
                padding: 12px;
                gap: 8px;
            }
            
            /* Wizard Service Card */
            .bkntc-wizard-service-card {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 14px 20px;
                background: #ffffff;
                border: 1px solid var(--border-color);
                border-radius: 10px;
                cursor: pointer;
                transition: all 0.2s;
            }
            .bkntc-wizard-service-card:hover {
                border-color: #6c70dc;
                box-shadow: 0 2px 8px rgba(108, 112, 220, 0.05);
            }
            .bkntc-wizard-service-card.selected {
                border-color: #6c70dc;
                background: rgba(108, 112, 220, 0.02);
            }
            .bkntc-wservice-info {
                flex: 1;
                min-width: 0;
            }
            .bkntc-wservice-target {
                font-size: 10px;
                color: var(--text-color-muted);
                text-transform: uppercase;
                font-weight: 700;
                margin-bottom: 2px;
            }
            .bkntc-wservice-name {
                font-size: 14px;
                font-weight: 600;
                color: var(--text-color-dark);
                margin-bottom: 4px;
            }
            .bkntc-wservice-meta {
                display: flex;
                gap: 16px;
                font-size: 12px;
                color: var(--text-color-muted);
                align-items: center;
            }
            .bkntc-wservice-price {
                font-weight: 700;
                color: var(--text-color-dark);
            }
            .bkntc-wservice-select {
                display: flex;
                align-items: center;
                margin-left: 16px;
            }
            .bkntc-radio-circle {
                width: 20px;
                height: 20px;
                border-radius: 50%;
                border: 2px solid var(--border-color);
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
                background: #ffffff;
                color: #ffffff;
            }
            .bkntc-wizard-service-card.selected .bkntc-radio-circle {
                border-color: #6c70dc;
                background: #6c70dc;
            }
            .bkntc-wizard-service-card.selected .bkntc-radio-circle::after {
                content: '';
                width: 8px;
                height: 8px;
                background: #ffffff;
                border-radius: 50%;
                display: block;
            }
            
            /* Step Actions */
            .bkntc-wizard-actions {
                display: flex;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid var(--border-color);
            }
            .justify-content-between {
                justify-content: space-between;
            }
            .bkntc-wizard-btn {
                background: #6c70dc;
                color: #ffffff;
                border: none;
                border-radius: 10px;
                padding: 12px 24px;
                font-size: 15px;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.2s;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .bkntc-wizard-btn:hover:not(:disabled) {
                background: #575bc8;
            }
            .bkntc-wizard-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            .bkntc-wizard-btn-secondary {
                background: #ffffff;
                color: var(--text-color-dark);
                border: 1px solid var(--border-color);
                border-radius: 10px;
                padding: 12px 24px;
                font-size: 15px;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.2s;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .bkntc-wizard-btn-secondary:hover {
                background: var(--bg-light);
            }
            
            /* Location Grid */
            .bkntc-wizard-location-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
                margin-bottom: 30px;
            }
            .bkntc-wizard-location-card {
                display: flex;
                align-items: center;
                padding: 16px 20px;
                background: #ffffff;
                border: 1px solid var(--border-color);
                border-radius: 12px;
                cursor: pointer;
                transition: all 0.2s;
                position: relative;
            }
            .bkntc-wizard-location-card:hover {
                border-color: #6c70dc;
                box-shadow: 0 2px 8px rgba(108, 112, 220, 0.05);
            }
            .bkntc-wizard-location-card.selected {
                border-color: #6c70dc;
                background: rgba(108, 112, 220, 0.02);
            }
            .bkntc-wlocation-img {
                width: 48px !important;
                height: 48px !important;
                border-radius: 50% !important;
                object-fit: cover !important;
                border: 1px solid var(--border-color) !important;
                margin-right: 16px !important;
            }
            .bkntc-wlocation-details {
                flex: 1;
                min-width: 0;
            }
            .bkntc-wlocation-role {
                font-size: 11px;
                color: var(--text-color-muted);
                display: block;
                margin-bottom: 2px;
            }
            .bkntc-wlocation-name {
                font-size: 14px;
                font-weight: 600;
                color: var(--text-color-dark);
                margin: 0;
            }
            .bkntc-wlocation-select {
                margin-left: 16px;
            }
            .bkntc-wizard-location-card .bkntc-radio-circle svg {
                display: none;
            }
            .bkntc-wizard-location-card.selected .bkntc-radio-circle {
                border-color: #10b981;
                background: #10b981;
            }
            .bkntc-wizard-location-card.selected .bkntc-radio-circle svg {
                display: block;
                color: #ffffff;
            }
            .bkntc-wizard-location-card.selected .bkntc-radio-circle::after {
                display: none;
            }
            
            /* Step 2: Staff Grid */
            .bkntc-wizard-staff-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
                margin-bottom: 30px;
            }
            .bkntc-wizard-staff-card {
                display: flex;
                align-items: center;
                padding: 16px 20px;
                background: #ffffff;
                border: 1px solid var(--border-color);
                border-radius: 12px;
                cursor: pointer;
                transition: all 0.2s;
                position: relative;
            }
            .bkntc-wizard-staff-card:hover {
                border-color: #6c70dc;
                box-shadow: 0 2px 8px rgba(108, 112, 220, 0.05);
            }
            .bkntc-wizard-staff-card.selected {
                border-color: #6c70dc;
                background: rgba(108, 112, 220, 0.02);
            }
            .bkntc-wstaff-img {
                width: 48px !important;
                height: 48px !important;
                border-radius: 50% !important;
                object-fit: cover !important;
                border: 1px solid var(--border-color) !important;
                margin-right: 16px !important;
            }
            .bkntc-wstaff-details {
                flex: 1;
                min-width: 0;
            }
            .bkntc-wstaff-role {
                font-size: 11px;
                color: var(--text-color-muted);
                display: block;
                margin-bottom: 2px;
            }
            .bkntc-wstaff-name {
                font-size: 14px;
                font-weight: 600;
                color: var(--text-color-dark);
                margin: 0;
            }
            .bkntc-wstaff-select {
                margin-left: 16px;
            }
            .bkntc-wizard-staff-card .bkntc-radio-circle svg {
                display: none;
            }
            .bkntc-wizard-staff-card.selected .bkntc-radio-circle {
                border-color: #10b981;
                background: #10b981;
            }
            .bkntc-wizard-staff-card.selected .bkntc-radio-circle svg {
                display: block;
                color: #ffffff;
            }
            .bkntc-wizard-staff-card.selected .bkntc-radio-circle::after {
                display: none;
            }
            
            /* Responsive Wizard */
            @media (max-width: 991px) {
                .bkntc-wizard-layout {
                    grid-template-columns: 1fr;
                    gap: 20px;
                }
                .bkntc-wizard-staff-grid,
                .bkntc-wizard-location-grid {
                    grid-template-columns: 1fr;
                }
            }
            @media (max-width: 768px) {
                .bkntc-stepper-header {
                    display: none !important;
                }
                .bkntc-wizard-actions {
                    display: none !important;
                }
                .bkntc-wizard-main {
                    padding: 16px;
                    padding-bottom: 90px !important;
                }
                .bkntc-mobile-wizard-footer {
                    display: flex !important;
                    align-items: center;
                    justify-content: space-between;
                    padding: 16px 24px;
                    background: #ffffff;
                    border-top: 1px solid #f1f5f9;
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    z-index: 9999;
                    box-shadow: 0 -4px 20px rgba(0,0,0,0.05);
                }
                .bkntc-mobile-dots {
                    display: flex;
                    gap: 8px;
                    align-items: center;
                    justify-content: center;
                }
                .bkntc-mobile-dot {
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                    background: #cbd5e1;
                    transition: background 0.3s, transform 0.3s;
                }
                .bkntc-mobile-dot.active {
                    background: #10b981;
                    transform: scale(1.2);
                }
                .bkntc-mobile-nav-btn {
                    border: none;
                    outline: none;
                    width: 44px;
                    height: 44px;
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    transition: background 0.2s, opacity 0.2s;
                }
                .bkntc-mobile-prev {
                    background: #f1f5f9;
                    color: #475569;
                }
                .bkntc-mobile-prev:hover {
                    background: #e2e8f0;
                }
                .bkntc-mobile-next {
                    background: #10b981;
                    color: #ffffff;
                }
                .bkntc-mobile-next:hover {
                    background: #059669;
                }
                .bkntc-mobile-next:disabled,
                .bkntc-mobile-next.disabled {
                    background: #a7f3d0;
                    cursor: not-allowed;
                    opacity: 0.6;
                }
                
                body.bkntc-booking-active footer,
                body.bkntc-booking-active .site-footer,
                body.bkntc-booking-active #colophon,
                body.bkntc-booking-active .wp-block-template-part,
                body.bkntc-booking-active [role="contentinfo"],
                body.bkntc-booking-active .elementor-location-footer {
                    display: none !important;
                }
            }
            
            /* Details Page Form Styles */
            .bkntc-custom-info-wrapper {
                padding: 10px 0;
            }
            .bkntc-custom-info-wrapper .form-row {
                display: flex;
                flex-wrap: wrap;
                margin-right: -15px;
                margin-left: -15px;
            }
            .bkntc-custom-info-wrapper .form-group {
                margin-bottom: 20px;
                padding-right: 15px;
                padding-left: 15px;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                width: 100%;
            }
            .bkntc-custom-info-wrapper .col-md-12 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            .bkntc-custom-info-wrapper .col-md-6 {
                flex: 0 0 50%;
                max-width: 50%;
            }
            @media (max-width: 768px) {
                .bkntc-custom-info-wrapper .col-md-6 {
                    flex: 0 0 100%;
                    max-width: 100%;
                }
            }
            .bkntc-custom-info-wrapper label {
                font-size: 13px;
                font-weight: 500;
                color: var(--text-color-dark);
                margin-bottom: 8px;
                display: block;
                text-align: left;
            }
            .bkntc-custom-info-wrapper input[type="text"],
            .bkntc-custom-info-wrapper input[type="email"],
            .bkntc-custom-info-wrapper textarea,
            .bkntc-custom-info-wrapper select {
                width: 100%;
                padding: 12px 16px !important;
                border: 1.5px solid #cbd5e0 !important;
                border-radius: 10px !important;
                font-size: 14px !important;
                outline: none !important;
                background: #ffffff !important;
                color: #2d3748 !important;
                box-sizing: border-box !important;
                transition: border-color 0.2s, box-shadow 0.2s !important;
                height: auto !important;
            }
            .bkntc-custom-info-wrapper .iti {
                width: 100% !important;
                display: block !important;
                position: relative !important;
                border: 1.5px solid #cbd5e0 !important;
                border-radius: 10px !important;
                background: #ffffff !important;
                box-sizing: border-box !important;
                transition: border-color 0.2s, box-shadow 0.2s !important;
            }
            .bkntc-custom-info-wrapper .iti input,
            .bkntc-custom-info-wrapper #bkntc_input_phone_clone {
                width: 100% !important;
                padding: 12px 16px 12px 55px !important; /* Left padding for flag dropdown */
                border: none !important;
                outline: none !important;
                background: transparent !important;
                box-shadow: none !important;
                font-size: 14px !important;
                color: #2d3748 !important;
                box-sizing: border-box !important;
                height: 45px !important;
            }
            .bkntc-custom-info-wrapper .iti:focus-within {
                border-color: #6c70dc !important;
                box-shadow: 0 0 0 3px rgba(108, 112, 220, 0.1) !important;
            }
            .bkntc-custom-info-wrapper input[type="text"]:focus,
            .bkntc-custom-info-wrapper input[type="email"]:focus,
            .bkntc-custom-info-wrapper textarea:focus {
                border-color: #6c70dc !important;
                box-shadow: 0 0 0 3px rgba(108, 112, 220, 0.1) !important;
            }
            
            /* File Upload Styling */
            .bkntc-custom-info-wrapper input[type="file"] {
                display: block;
                width: 100%;
                font-size: 14px;
                color: var(--text-color-muted);
                padding: 12px 16px !important;
                border: 1.5px dashed #cbd5e0 !important;
                border-radius: 10px !important;
                background-color: #f8fafc !important;
                box-sizing: border-box !important;
            }
            .bkntc-custom-info-wrapper input[type="file"]::file-selector-button {
                background-color: #6c70dc;
                color: #ffffff;
                padding: 10px 20px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 13px;
                font-weight: 500;
                margin-right: 12px;
                transition: background-color 0.2s;
            }
            .bkntc-custom-info-wrapper input[type="file"]::file-selector-button:hover {
                background-color: #575ab8;
            }
            
            /* Custom Mockup Checkout Layout */
            .bkntc-mockup-checkout-card {
                max-width: 600px;
                margin: 0 auto;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 30px 24px;
                box-sizing: border-box;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                box-shadow: 0 4px 16px rgba(0,0,0,0.03);
            }
            .bkntc-mockup-title {
                text-align: center;
                font-size: 24px;
                font-weight: 700;
                color: #1a202c;
                margin-top: 0;
                margin-bottom: 24px;
            }
            .bkntc-mockup-card-body {
                display: flex;
                flex-direction: column;
                gap: 16px;
            }
            .bkntc-mockup-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 15px;
                color: #4a5568;
                min-height: 32px;
            }
            .bkntc-mockup-label {
                font-weight: 500;
                text-align: left;
            }
            .bkntc-mockup-value {
                text-align: right;
                color: #2d3748;
            }
            .bkntc-mockup-val-bold {
                font-weight: 600;
                color: #1a202c;
            }
            .bkntc-mockup-staff-val {
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            .bkntc-mockup-staff-avatar {
                width: 32px !important;
                height: 32px !important;
                border-radius: 50% !important;
                object-fit: cover !important;
            }
            .bkntc-mockup-divider {
                height: 1px;
                background-color: #edf2f7;
                margin: 8px 0;
            }
            .bkntc-mockup-price {
                font-size: 16px;
                font-weight: 700;
                color: #5c62d6;
            }
            .bkntc-mockup-btn-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                margin-top: 16px;
                gap: 12px;
            }
            .bkntc-mockup-confirm-btn {
                background-color: #5c62d6;
                color: #ffffff;
                border: none;
                border-radius: 10px;
                padding: 14px 28px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                width: 100%;
                max-width: 340px;
                transition: background-color 0.2s;
                text-align: center;
            }
            .bkntc-mockup-confirm-btn:hover {
                background-color: #4a4fb5;
            }
            .bkntc-mockup-terms {
                font-size: 12px;
                color: #718096;
                text-align: center;
                margin: 0;
            }
            .bkntc-mockup-terms a {
                color: #319795;
                text-decoration: none;
                font-weight: 500;
            }
            .bkntc-mockup-terms a:hover {
                text-decoration: underline;
            }
            
            /* Payment Method Selector Styling */
            .bkntc-payment-methods-selector {
                margin: 8px 0;
            }
            .bkntc-payment-options-grid {
                display: flex;
                gap: 12px;
                margin-top: 10px;
            }
            .bkntc-payment-option-pill {
                flex: 1;
                padding: 14px;
                border: 2px solid #e2e8f0;
                border-radius: 10px;
                text-align: center;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
                background: #ffffff;
                color: #4a5568;
                box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            }
            .bkntc-payment-option-pill:hover {
                border-color: #cbd5e0;
                background-color: #f7fafc;
            }
            .bkntc-payment-option-pill.active {
                border-color: #5c62d6;
                background-color: #f4f5ff;
                color: #5c62d6;
                box-shadow: 0 4px 8px rgba(92, 98, 214, 0.08);
            }
            
            /* Phone Input Alignment Overlay Fix */
            #bkntc_custom_info_wrapper .iti {
                display: block !important;
                position: relative !important;
                width: 100% !important;
            }
            #bkntc_custom_info_wrapper .iti__flag-container {
                position: absolute !important;
                top: 0 !important;
                bottom: 0 !important;
                left: 0 !important;
                display: flex !important;
                align-items: center !important;
                z-index: 10 !important;
            }
            #bkntc_custom_info_wrapper .iti__selected-flag {
                display: flex !important;
                align-items: center !important;
                gap: 6px !important;
                padding: 0 10px !important;
                height: 100% !important;
                background: #f7fafc !important;
                border-right: 1px solid var(--border-color) !important;
                border-top-left-radius: 8px !important;
                border-bottom-left-radius: 8px !important;
            }
            #bkntc_custom_info_wrapper .iti input {
                width: 100% !important;
                padding-left: 95px !important; /* Ensures phone text starts after flag and country code (+44) */
                height: 45px !important;
                border-radius: 8px !important;
                border: 1px solid var(--border-color) !important;
                margin-top: 0 !important;
                box-sizing: border-box !important;
            }
            
            
            /* Promo Code Accordion Styles */
            .bkntc-promo-wrapper {
                padding: 4px 0;
            }
            .bkntc-promo-toggle-link {
                font-size: 13px;
                color: #5c62d6;
                font-weight: 600;
                text-decoration: none !important;
                display: inline-flex;
                align-items: center;
                gap: 4px;
                transition: color 0.2s;
            }
            .bkntc-promo-toggle-link:hover {
                color: #4a4fb5;
            }
            .bkntc-promo-toggle-link::after {
                content: '▼';
                font-size: 9px;
                transition: transform 0.2s;
            }
            .bkntc-promo-toggle-link.open::after {
                transform: rotate(180deg);
            }
            
            /* Style Custom Coupon form */
            .bkntc-custom-coupon-form {
                display: flex !important;
                gap: 8px !important;
                margin: 0 !important;
                width: 100% !important;
                position: relative !important;
            }
            .bkntc-custom-coupon-form input {
                flex: 1 !important;
                height: 38px !important;
                border: 1px solid var(--border-color) !important;
                border-radius: 8px !important;
                padding: 0 12px !important;
                font-size: 13px !important;
                box-sizing: border-box !important;
            }
            .bkntc-custom-coupon-form button {
                height: 38px !important;
                background-color: #5c62d6 !important;
                color: #ffffff !important;
                border: none !important;
                border-radius: 8px !important;
                padding: 0 16px !important;
                font-size: 13px !important;
                font-weight: 600 !important;
                cursor: pointer !important;
                transition: background-color 0.2s !important;
            }
            .bkntc-custom-coupon-form button:hover {
                background-color: #4a4fb5 !important;
            }
            
            .bkntc-custom-coupon-form button.bkntc-btn-remove {
                background-color: #e53e3e !important;
            }
            .bkntc-custom-coupon-form button.bkntc-btn-remove:hover {
                background-color: #c53030 !important;
            }
            
            .bkntc-promo-status-msg {
                margin-top: 6px;
                font-size: 12px;
                font-weight: 500;
            }
            .bkntc-promo-status-msg.success {
                color: #2f855a !important;
            }
            .bkntc-promo-status-msg.error {
                color: #e53e3e !important;
            }
            
            /* Hide native footer buttons in Step 5 and Step 6 */
            #bkntc_wizard_actions_6 .booknetic_prev_step,
            #bkntc_wizard_actions_6 .booknetic_try_again,
            #bkntc_wizard_actions_6 [data-step-id] .booknetic_try_again,
            #bkntc_wizard_actions_5 .booknetic_prev_step,
            #bkntc_wizard_actions_5 .booknetic_try_again,
            #bkntc_wizard_actions_5 [data-step-id] .booknetic_try_again,
            #bkntc_custom_info_wrapper .booknetic_appointment_container_footer,
            #bkntc_custom_checkout_wrapper .booknetic_appointment_container_footer {
                display: none !important;
            }
            
            
            /* Hide location summary in sidebar when single location layout is active */
            .bkntc-wizard-single-location #bkntc_summary_location_card {
                display: none !important;
            }
            
            /* Hide hidden native widget elements and style calendar / confirm forms */
            .bkntc-booking-widget-box-hidden {
                position: absolute !important;
                top: -9999px !important;
                left: -9999px !important;
                width: 1px !important;
                height: 1px !important;
                overflow: hidden !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }
            .bkntc_input_identifier_clear {
                display: none !important;
            }
            
            /* Sub-step positioning styling for custom calendar/checkout wrapper */
            .bkntc-custom-calendar-wrapper, .bkntc-custom-checkout-wrapper {
                background: #ffffff;
                width: 100%;
            }
            /* Style Native Calendar inside wrapper */
            .bkntc-custom-calendar-wrapper .booknetic_date_time_area {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                display: flex !important;
                flex-direction: row !important;
                width: 100% !important;
                gap: 24px;
            }
            @media(max-width: 768px) {
                .bkntc-custom-calendar-wrapper .booknetic_date_time_area {
                    flex-direction: column !important;
                }
            }
            .bkntc-custom-calendar-wrapper .booknetic_calendar_div {
                flex: 1.2 !important;
                border: 1px solid var(--border-color) !important;
                border-radius: 12px !important;
                padding: 16px !important;
                box-shadow: none !important;
            }
            .bkntc-custom-calendar-wrapper .booknetic_time_div {
                flex: 1 !important;
                border: 1px solid var(--border-color) !important;
                border-radius: 12px !important;
                padding: 16px !important;
                box-shadow: none !important;
                background: var(--bg-light) !important;
            }
            .bkntc-custom-calendar-wrapper .booknetic_times {
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .bkntc-custom-calendar-wrapper .booknetic_times_list {
                max-height: 280px !important;
                overflow-y: auto !important;
            }
            .bkntc-custom-calendar-wrapper .booknetic_time_slot {
                background: #ffffff !important;
                border: 1px solid var(--border-color) !important;
                border-radius: 8px !important;
                padding: 10px !important;
                font-weight: 600 !important;
                text-align: center !important;
                cursor: pointer !important;
                transition: all 0.2s !important;
            }
            .bkntc-custom-calendar-wrapper .booknetic_time_slot.booknetic_selected_time {
                background: #6c70dc !important;
                color: #ffffff !important;
                border-color: #6c70dc !important;
            }
            
            /* Native form modifications */
            .bkntc-custom-checkout-wrapper .booknetic_appointment_step_element {
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
            .bkntc-custom-checkout-wrapper .booknetic_appointment_container_footer {
                display: flex !important;
                padding: 20px 0 0 0 !important;
                margin-top: 20px !important;
                border-top: 1px solid var(--border-color) !important;
                width: 100% !important;
                background: none !important;
                position: static !important;
                box-shadow: none !important;
            }
            .bkntc-custom-checkout-wrapper .booknetic_appointment_container_footer .booknetic_prev_step {
                display: none !important; /* Hide native back step button since we have our custom one */
            }
            .bkntc-custom-checkout-wrapper .booknetic_appointment_container_footer .booknetic_confirm_booking_btn {
                background: #10b981 !important;
                border-color: #10b981 !important;
                font-family: inherit !important;
                font-weight: 600 !important;
                font-size: 15px !important;
                padding: 12px 24px !important;
                border-radius: 10px !important;
                color: #ffffff !important;
                box-shadow: none !important;
                cursor: pointer !important;
                margin-left: auto !important;
            }
            .bkntc-custom-checkout-wrapper .booknetic_appointment_container_footer .booknetic_confirm_booking_btn:hover {
                background: #059669 !important;
            }
        </style>

        <div class="bkntc-landing-wrapper">
            <div class="bkntc-landing-main-view" id="bkntc_landing_main_view">
                <!-- Go Back Button -->
                <a href="<?php echo esc_url($directoryPageUrl); ?>" class="bkntc-btn-back">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left" style="display: inline-block; vertical-align: middle; margin-right: 6px;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg> Go back
                </a>

            <!-- Header Title and Meta -->
            <div class="bkntc-header-container">
                <div class="bkntc-title-meta-wrapper">
                    <h1 class="bkntc-landing-title"><?php echo htmlspecialchars($dir->title)?></h1>
                    <div class="bkntc-meta-row">
                        <span class="bkntc-badge-partner">Partner</span>
                        <div class="bkntc-meta-item">
                            <span class="bkntc-rating"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star" style="display: inline-block; vertical-align: middle; margin-right: 2px; color: #f59e0b;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> <?php echo number_format($avg_rating, 1); ?></span>
                            <span>• <?php echo $reviews_count; ?> reviews</span>
                        </div>
                        <?php 
                        $showAddress = ($socialLinks['show_address'] ?? 'on') === 'on';
                        $showCard = ($socialLinks['show_card'] ?? 'on') === 'on';
                        $showCash = ($socialLinks['show_cash'] ?? 'on') === 'on';
                        ?>
                        <?php if ($location && $showAddress): ?>
                            <div class="bkntc-meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-map-pin" style="display: inline-block; vertical-align: middle; margin-right: 4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <span><?php echo htmlspecialchars(!empty($location->address) ? $location->address : $location->name)?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($showCard): ?>
                            <div class="bkntc-meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-credit-card" style="display: inline-block; vertical-align: middle; margin-right: 4px;"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                                <span>Card & terminal</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($showCash): ?>
                            <div class="bkntc-meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-dollar-sign" style="display: inline-block; vertical-align: middle; margin-right: 4px;"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                                <span>Cash</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Header Socials -->
                <div class="bkntc-header-socials">
                    <?php if (!empty($socialLinks['facebook'])): ?>
                        <a href="<?php echo esc_url($socialLinks['facebook'])?>" target="_blank" class="bkntc-social-btn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-facebook" style="display: block;"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></a>
                    <?php endif; ?>
                    <?php if (!empty($socialLinks['instagram'])): ?>
                        <a href="<?php echo esc_url($socialLinks['instagram'])?>" target="_blank" class="bkntc-social-btn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-instagram" style="display: block;"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg></a>
                    <?php endif; ?>
                    <a href="#" id="bkntc_copy_link_btn" class="bkntc-social-btn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-copy" style="display: block;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg></a>
                </div>
            </div>

            <!-- Gallery Stilio Layout -->
            <?php if (!empty($gallery)): ?>
                <?php if (count($gallery) >= 3): ?>
                    <div class="bkntc-gallery-stilio">
                        <div class="bkntc-gallery-main">
                            <img src="<?php echo htmlspecialchars($gallery[0])?>" alt="Gallery Main">
                        </div>
                        <div class="bkntc-gallery-side">
                            <div class="bkntc-gallery-side-img">
                                <img src="<?php echo htmlspecialchars($gallery[1])?>" alt="Gallery 2">
                            </div>
                            <div class="bkntc-gallery-side-img">
                                <img src="<?php echo htmlspecialchars($gallery[2])?>" alt="Gallery 3">
                            </div>
                        </div>
                        <?php if (count($gallery) >= 3): ?>
                            <div class="bkntc-gallery-overlay">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-image" style="display: block;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                <span>See all pictures</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="bkntc-gallery-fallback-1">
                        <img src="<?php echo htmlspecialchars($gallery[0])?>" alt="Gallery Single">
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Main Layout Grid -->
            <div class="bkntc-landing-grid">
                <div class="bkntc-landing-left">
                    
                    <!-- About us -->
                    <div class="bkntc-section-card bkntc-section-about">
                        <h2 class="bkntc-section-title">About us</h2>
                        <div class="bkntc-desc-content"><?php echo trim($dir->description); ?></div>
                    </div>

                    <!-- Services -->
                    <div class="bkntc-section-card bkntc-section-services">
                        <h2 class="bkntc-section-title">Services</h2>
                        
                        <!-- Search Box -->
                        <div class="bkntc-service-search">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-color-muted);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="service_search_input" placeholder="Search services...">
                        </div>

                        <!-- Tabs Category Filtering -->
                        <div class="bkntc-service-tabs">
                            <button type="button" class="bkntc-tab-btn active" data-category="all">All services</button>
                            <?php foreach ($activeCategories as $catId => $catName): ?>
                                <button type="button" class="bkntc-tab-btn" data-category="<?php echo (int)$catId; ?>"><?php echo htmlspecialchars($catName); ?></button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Services List -->
                        <div class="bkntc-services-list" id="bkntc_services_items_list">
                            <?php if (empty($services)): ?>
                                <div class="text-center py-4 text-muted">No services offered currently.</div>
                            <?php else: ?>
                                <?php foreach ($services as $service): 
                                    $durationHours = floor($service->duration / 60);
                                    $durationMins = $service->duration % 60;
                                    $durationStr = '';
                                    if ($durationHours > 0) {
                                        $durationStr .= $durationHours . ' hour' . ($durationHours > 1 ? 's' : '');
                                    }
                                    if ($durationMins > 0) {
                                        if (!empty($durationStr)) $durationStr .= ' ';
                                        $durationStr .= $durationMins . ' min';
                                    }
                                ?>
                                    <div class="bkntc-service-card" data-id="<?php echo $service->id; ?>" data-category="<?php echo (int)$service->category_id; ?>" data-name="<?php echo esc_attr(strtolower($service->name)); ?>">
                                        <div class="bkntc-service-info">
                                            <div class="bkntc-service-target">For Women & Men</div>
                                            <div class="bkntc-service-name"><?php echo htmlspecialchars($service->name)?></div>
                                            <div class="bkntc-service-meta">
                                                <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-clock" style="display: inline-block; vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> <?php echo htmlspecialchars($durationStr)?></span>
                                                <span class="bkntc-service-price"><?php echo Helper::price($service->price)?></span>
                                            </div>
                                        </div>
                                        <a href="#bkntc_booking_widget_box" class="bkntc-btn-book-action book-scroll-btn">Book an Appointment</a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Staff members -->
                    <?php if (!empty($staffMembers)): ?>
                        <div class="bkntc-section-card bkntc-section-staff">
                            <h2 class="bkntc-section-title">Staff</h2>
                            <div class="bkntc-staff-grid">
                                <?php foreach ($staffMembers as $staff): 
                                    $staffAvatar = Helper::profileImage($staff->profile_image, 'Staff');
                                ?>
                                    <div class="bkntc-staff-card">
                                        <img src="<?php echo htmlspecialchars($staffAvatar)?>" class="bkntc-staff-img" alt="">
                                        <div class="bkntc-staff-name"><?php echo htmlspecialchars($staff->name)?></div>
                                        <div class="bkntc-staff-profession"><?php echo htmlspecialchars($staff->profession ?: 'Expert')?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Reviews -->
                    <div class="bkntc-section-card bkntc-section-reviews">
                        <div class="bkntc-section-title">
                            Reviews
                            <button type="button" class="btn btn-sm btn-outline-success" id="bkntc_leave_review_btn">Leave a review</button>
                        </div>
                        <div class="bkntc-reviews-list">
                            <?php if (empty($reviews)): ?>
                                <div class="text-center py-4 text-muted">No reviews yet. Be the first to leave one!</div>
                            <?php else: ?>
                                <?php foreach ($reviews as $rev): 
                                    $timeDiff = human_time_diff(strtotime($rev->created_at), current_time('timestamp')) . ' ago';
                                ?>
                                    <div class="bkntc-review-card">
                                        <div class="bkntc-review-header">
                                            <span class="bkntc-review-author"><?php echo htmlspecialchars($rev->author_name); ?></span>
                                            <span class="bkntc-review-date"><?php echo htmlspecialchars($timeDiff); ?></span>
                                        </div>
                                        <div class="bkntc-review-rating">
                                            <?php for($star_i=0; $star_i < 5; $star_i++): ?>
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="<?php echo $star_i < $rev->rating ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2" class="feather feather-star" style="display: inline-block; color: #f59e0b;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="bkntc-review-text">
                                            <?php echo nl2br(htmlspecialchars($rev->review_text)); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- Right Side Sticky Sidebar -->
                <div class="bkntc-landing-right">
                    <div class="bkntc-sidebar-card">
                        
                        <!-- Contact Box -->
                        <?php if (!empty($dir->contact_phone)): ?>
                            <div class="bkntc-contact-box">
                                <div class="bkntc-contact-info">
                                    <div class="bkntc-contact-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-phone" style="display: block;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                    </div>
                                    <div>
                                        <div style="font-size: 11px; color: var(--text-color-muted);">Contacts</div>
                                        <div class="bkntc-contact-num" id="bkntc_phone_num" data-phone="<?php echo htmlspecialchars($dir->contact_phone); ?>">
                                            <?php echo htmlspecialchars(substr($dir->contact_phone, 0, 5) . '...'); ?>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="bkntc-btn-hide" id="bkntc_toggle_phone">Show</button>
                            </div>
                        <?php endif; ?>

                        <!-- Working Hours -->
                        <div class="bkntc-hours-title">Working hours</div>
                        <div class="bkntc-hours-list">
                            <?php 
                            $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            $currentDayName = date('l');
                            
                            foreach ($daysOfWeek as $index => $day): 
                                $dayLower = strtolower($day);
                                $whDay = $workingHours[$index] ?? null;
                                
                                if ($whDay) {
                                    $start = $whDay['start'] ?? '';
                                    $end = $whDay['end'] ?? '';
                                    $dayOff = ($whDay['day_off'] ?? 0) == 1;
                                } else {
                                    $start = '';
                                    $end = '';
                                    $dayOff = true;
                                }

                                $isToday = $dayLower === strtolower($currentDayName);
                                
                                $timeStr = 'Day Off';
                                if (!$dayOff && !empty($start) && !empty($end)) {
                                    $startFormatted = date('H:i', strtotime($start));
                                    $endFormatted = date('H:i', strtotime($end));
                                    $timeStr = $startFormatted . ' - ' . $endFormatted;
                                }
                            ?>
                                <div class="bkntc-hour-row <?php echo $isToday ? 'today' : ''; ?>">
                                    <span><?php echo htmlspecialchars($day)?></span>
                                    <span><?php echo htmlspecialchars($timeStr)?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Actions -->
                        <div class="bkntc-sidebar-actions">
                            <a href="#bkntc_booking_widget_box" class="bkntc-btn-primary book-scroll-btn">Book an Appointment</a>
                        </div>

                    </div>
                </div>
            </div>



            <!-- Lightbox Markup -->
            <div id="bkntc_lightbox_modal" class="bkntc-lightbox">
                <div class="bkntc-lightbox-content">
                    <button type="button" class="bkntc-lightbox-close" id="bkntc_lightbox_close">&times;</button>
                    <button type="button" class="bkntc-lightbox-nav bkntc-lightbox-prev" id="bkntc_lightbox_prev">&#8249;</button>
                    <img src="" id="bkntc_lightbox_image" class="bkntc-lightbox-img" alt="">
                    <button type="button" class="bkntc-lightbox-nav bkntc-lightbox-next" id="bkntc_lightbox_next">&#8250;</button>
                    <div class="bkntc-lightbox-counter" id="bkntc_lightbox_counter">1 / 1</div>
                </div>
            </div>

            <!-- Toast notification -->
            <div id="bkntc_toast_notification" class="bkntc-toast">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-circle" style="color: #10b981; display: block;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <span>Link copied to clipboard!</span>
            </div>

            <!-- Leave a Review Modal -->
            <div id="bkntc_review_modal" class="bkntc-lightbox">
                <div class="bkntc-lightbox-content" style="background: #ffffff; padding: 30px; border-radius: 16px; width: 100%; max-width: 450px; flex-direction: column; align-items: stretch; position: relative;">
                    <button type="button" class="bkntc-lightbox-close" id="bkntc_review_close" style="top: 15px; right: 15px; color: #1e293b; background: none; border: none; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 1;">&times;</button>
                    <h3 style="font-size: 22px; font-weight: 700; margin-bottom: 20px; color: #1e293b; margin-top: 0;">Leave a Review</h3>
                    <form id="bkntc_review_form">
                        <input type="hidden" name="action" value="bkntc_submit_review">
                        <input type="hidden" name="tenant_id" value="<?php echo htmlspecialchars($dir->tenant_id)?>">
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #475569; text-align: left;">Your Name</label>
                            <input type="text" name="author_name" required style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 10px; font-size: 14px; box-sizing: border-box;" placeholder="e.g. Jane Doe">
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #475569; text-align: left;">Rating</label>
                            <div id="bkntc_rating_stars" style="display: flex; gap: 6px; cursor: pointer; justify-content: flex-start;">
                                <?php for($star_i = 1; $star_i <= 5; $star_i++): ?>
                                    <svg class="star-rating-item" data-value="<?php echo $star_i; ?>" width="28" height="28" viewBox="0 0 24 24" fill="currentColor" stroke="#f59e0b" stroke-width="2" style="color: #f59e0b;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="bkntc_review_rating_val" value="5" required>
                        </div>
                        
                        <div style="margin-bottom: 24px;">
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #475569; text-align: left;">Your Review</label>
                            <textarea name="review_text" required rows="4" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 10px; font-size: 14px; resize: vertical; box-sizing: border-box;" placeholder="Tell others about your experience..."></textarea>
                        </div>
                        
                        <button type="submit" class="bkntc-btn-primary" style="width: 100%; display: block; border-radius: 10px; box-sizing: border-box;">Submit Review</button>
                    </form>
                </div>
            </div>
            </div> <!-- #bkntc_landing_main_view -->

            <!-- New Custom Booking Wizard View -->
            <div class="bkntc-booking-wizard-view" id="bkntc_booking_wizard_view" style="display: none;">
                <div class="bkntc-wizard-layout">
                    
                    <!-- Left Sidebar -->
                    <div class="bkntc-wizard-sidebar">
                        <a href="#" class="bkntc-btn-back-salon" id="bkntc_btn_back_salon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-left" style="display: inline-block; vertical-align: middle; margin-right: 4px;"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            Salon page
                        </a>
                        
                        <div class="bkntc-sidebar-salon-info">
                            <?php if ($location && !empty($location->image)): ?>
                                <img src="<?php echo htmlspecialchars(Helper::profileImage($location->image, 'Locations'))?>" class="bkntc-sidebar-logo" alt="Salon Logo">
                            <?php elseif (!empty($dir->logo)): ?>
                                <img src="<?php echo htmlspecialchars(Helper::profileImage($dir->logo, 'Settings'))?>" class="bkntc-sidebar-logo" alt="Salon Logo">
                            <?php else: ?>
                                <img src="<?php echo Helper::profileImage(Helper::getOption('company_image', ''), 'Settings')?>" class="bkntc-sidebar-logo" alt="Salon Logo">
                            <?php endif; ?>
                            <div class="bkntc-sidebar-salon-text">
                                <span class="bkntc-sidebar-salon-label">Salon</span>
                                <h3 class="bkntc-sidebar-salon-title"><?php echo htmlspecialchars($dir->title)?></h3>
                                <?php if ($location): ?>
                                    <p class="bkntc-sidebar-salon-address">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-map-pin" style="display: inline-block; vertical-align: middle; margin-right: 4px; color: var(--text-color-muted);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                        <?php echo htmlspecialchars(!empty($location->address) ? $location->address : $location->name)?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="bkntc-sidebar-divider"></div>
                        
                        <!-- Summary Section -->
                        <div class="bkntc-sidebar-summary-container">
                            <!-- Selected Service Card -->
                            <div class="bkntc-summary-card" id="bkntc_summary_service_card" style="display: none;">
                                <span class="bkntc-summary-label">Service</span>
                                <h4 class="bkntc-summary-title" id="bkntc_summary_service_name"></h4>
                                <div class="bkntc-summary-meta">
                                    <span class="bkntc-summary-duration"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-clock" style="display: inline-block; vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> <span id="bkntc_summary_service_duration"></span></span>
                                    <span class="bkntc-summary-price" id="bkntc_summary_service_price"></span>
                                </div>
                            </div>
                            
                            <!-- Selected Location Card -->
                            <div class="bkntc-summary-card" id="bkntc_summary_location_card" style="display: none;">
                                <span class="bkntc-summary-label">Location</span>
                                <div class="bkntc-summary-staff-info">
                                    <img src="" class="bkntc-summary-staff-img" id="bkntc_summary_location_img" alt="">
                                    <div class="bkntc-summary-staff-details">
                                        <h4 class="bkntc-summary-title" id="bkntc_summary_location_name"></h4>
                                        <span class="bkntc-summary-staff-role" id="bkntc_summary_location_address"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Service Card -->
                            <div class="bkntc-summary-card" id="bkntc_summary_service_card" style="display: none;">
                                <span class="bkntc-summary-label">Service</span>
                                <h4 class="bkntc-summary-title" id="bkntc_summary_service_name"></h4>
                                <div class="bkntc-summary-meta">
                                    <span class="bkntc-summary-duration"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-clock" style="display: inline-block; vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> <span id="bkntc_summary_service_duration"></span></span>
                                    <span class="bkntc-summary-price" id="bkntc_summary_service_price"></span>
                                </div>
                            </div>
                            
                            <!-- Selected Specialist Card -->
                            <div class="bkntc-summary-card" id="bkntc_summary_staff_card" style="display: none;">
                                <span class="bkntc-summary-label">Staff</span>
                                <div class="bkntc-summary-staff-info">
                                    <img src="" class="bkntc-summary-staff-img" id="bkntc_summary_staff_img" alt="">
                                    <div class="bkntc-summary-staff-details">
                                        <span class="bkntc-summary-staff-role" id="bkntc_summary_staff_role">Specialist</span>
                                        <h4 class="bkntc-summary-title" id="bkntc_summary_staff_name"></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Content Stepper -->
                    <div class="bkntc-wizard-main">
                        
                        <!-- Stepper Progress Header -->
                        <div class="bkntc-stepper-header">
                            <div class="bkntc-step-indicator active" data-step="1">
                                <span class="bkntc-step-num">1</span>
                                <span class="bkntc-step-label">Location</span>
                            </div>
                            <div class="bkntc-step-line"></div>
                            <div class="bkntc-step-indicator" data-step="2">
                                <span class="bkntc-step-num">2</span>
                                <span class="bkntc-step-label">Service</span>
                            </div>
                            <div class="bkntc-step-line"></div>
                            <div class="bkntc-step-indicator" data-step="3">
                                <span class="bkntc-step-num">3</span>
                                <span class="bkntc-step-label">Staff</span>
                            </div>
                            <div class="bkntc-step-line"></div>
                            <div class="bkntc-step-indicator" data-step="4">
                                <span class="bkntc-step-num">4</span>
                                <span class="bkntc-step-label">Date and Time</span>
                            </div>
                            <div class="bkntc-step-line"></div>
                            <div class="bkntc-step-indicator" data-step="5">
                                <span class="bkntc-step-num">5</span>
                                <span class="bkntc-step-label">Confirmation</span>
                            </div>
                            <div class="bkntc-step-line"></div>
                            <div class="bkntc-step-indicator" data-step="6">
                                <span class="bkntc-step-num">6</span>
                                <span class="bkntc-step-label">Checkout</span>
                            </div>
                        </div>
                        
                        <!-- Step Panels Container -->
                        <div class="bkntc-step-panels">
                                                      <!-- Step 1: Location Selection -->
                            <div class="bkntc-step-panel active" id="bkntc_step_panel_1">
                                <h2 class="bkntc-step-heading">Select location</h2>
                                
                                <div class="bkntc-wizard-location-grid" id="bkntc_wizard_location_grid">
                                    <?php foreach ($locations as $loc): 
                                        $locAvatar = Helper::profileImage($loc->image, 'Locations');
                                    ?>
                                        <div class="bkntc-wizard-location-card" data-id="<?php echo $loc->id; ?>" data-name="<?php echo esc_attr($loc->name); ?>" data-avatar="<?php echo esc_attr($locAvatar); ?>" data-address="<?php echo esc_attr($loc->address); ?>">
                                            <img src="<?php echo htmlspecialchars($locAvatar); ?>" class="bkntc-wlocation-img" alt="">
                                            <div class="bkntc-wlocation-details">
                                                <span class="bkntc-wlocation-role">Location</span>
                                                <h4 class="bkntc-wlocation-name"><?php echo htmlspecialchars($loc->name); ?></h4>
                                            </div>
                                            <div class="bkntc-wlocation-select">
                                                <div class="bkntc-radio-circle">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="bkntc-wizard-actions">
                                    <button type="button" class="bkntc-wizard-btn bkntc-wizard-next" id="bkntc_next_step_1">Next <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right" style="display: inline-block; vertical-align: middle; margin-left: 4px;"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
                                </div>
                            </div>
                            
                            <!-- Step 2: Service Selection -->
                            <div class="bkntc-step-panel" id="bkntc_step_panel_2">
                                <div class="bkntc-step-title-row">
                                    <h2 class="bkntc-step-heading">Select the service</h2>
                                    <div class="bkntc-search-wrapper">
                                        <input type="text" id="bkntc_wizard_service_search" class="bkntc-wizard-search-input" placeholder="Search services">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: var(--text-color-muted);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    </div>
                                </div>
                                
                                <!-- Accordion Categories and Services -->
                                <div class="bkntc-category-accordion">
                                    <?php foreach ($categories as $cat): 
                                        $catServices = array_filter($services, function($s) use ($cat) {
                                            return $s->category_id == $cat->id;
                                        });
                                        if (empty($catServices)) continue;
                                    ?>
                                        <div class="bkntc-accordion-item" data-category-id="<?php echo $cat->id; ?>">
                                            <div class="bkntc-accordion-header">
                                                <span class="bkntc-accordion-title"><?php echo htmlspecialchars($cat->name); ?></span>
                                                <span class="bkntc-accordion-icon">
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                                </span>
                                            </div>
                                            <div class="bkntc-accordion-content">
                                                <div class="bkntc-accordion-services-list">
                                                    <?php foreach ($catServices as $service): 
                                                        $durationHours = floor($service->duration / 60);
                                                        $durationMins = $service->duration % 60;
                                                        $durationStr = '';
                                                        if ($durationHours > 0) {
                                                            $durationStr .= $durationHours . ' hour' . ($durationHours > 1 ? 's' : '');
                                                        }
                                                        if ($durationMins > 0) {
                                                            if (!empty($durationStr)) $durationStr .= ' ';
                                                            $durationStr .= $durationMins . ' min';
                                                        }
                                                    ?>
                                                        <div class="bkntc-wizard-service-card" 
                                                             data-id="<?php echo $service->id; ?>" 
                                                             data-name="<?php echo esc_attr(strtolower($service->name)); ?>"
                                                             data-duration="<?php echo esc_attr($durationStr); ?>"
                                                             data-price="<?php echo esc_attr(Helper::price($service->price)); ?>">
                                                            <div class="bkntc-wservice-info">
                                                                <div class="bkntc-wservice-target">For Women & Men</div>
                                                                <div class="bkntc-wservice-name"><?php echo htmlspecialchars($service->name); ?></div>
                                                                <div class="bkntc-wservice-meta">
                                                                    <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-clock" style="display: inline-block; vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> <?php echo htmlspecialchars($durationStr); ?></span>
                                                                    <span class="bkntc-wservice-price"><?php echo Helper::price($service->price); ?></span>
                                                                </div>
                                                            </div>
                                                            <div class="bkntc-wservice-select">
                                                                <div class="bkntc-radio-circle"></div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="bkntc-wizard-actions justify-content-between">
                                    <button type="button" class="bkntc-wizard-btn-secondary" id="bkntc_back_step_2"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-left" style="display: inline-block; vertical-align: middle; margin-right: 4px;"><polyline points="15 18 9 12 15 6"></polyline></svg> Back</button>
                                    <button type="button" class="bkntc-wizard-btn bkntc-wizard-next" id="bkntc_next_step_2">Next <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right" style="display: inline-block; vertical-align: middle; margin-left: 4px;"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
                                </div>
                            </div>
                            
                            <!-- Step 3: Staff Selection -->
                            <div class="bkntc-step-panel" id="bkntc_step_panel_3">
                                <h2 class="bkntc-step-heading">Select specialist</h2>
                                
                                <div class="bkntc-wizard-staff-grid" id="bkntc_wizard_staff_grid">
                                    <?php foreach ($staffMembers as $staff): 
                                        $staffAvatar = Helper::profileImage($staff->profile_image, 'Staff');
                                    ?>
                                        <div class="bkntc-wizard-staff-card" data-id="<?php echo $staff->id; ?>" data-name="<?php echo esc_attr($staff->name); ?>" data-avatar="<?php echo esc_attr($staffAvatar); ?>" data-profession="<?php echo esc_attr($staff->profession ?: 'Specialist'); ?>">
                                            <img src="<?php echo htmlspecialchars($staffAvatar); ?>" class="bkntc-wstaff-img" alt="">
                                            <div class="bkntc-wstaff-details">
                                                <span class="bkntc-wstaff-role"><?php echo htmlspecialchars($staff->profession ?: 'Specialist'); ?></span>
                                                <h4 class="bkntc-wstaff-name"><?php echo htmlspecialchars($staff->name); ?></h4>
                                            </div>
                                            <div class="bkntc-wstaff-select">
                                                <div class="bkntc-radio-circle">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="bkntc-wizard-actions justify-content-between">
                                    <button type="button" class="bkntc-wizard-btn-secondary" id="bkntc_back_step_3"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-left" style="display: inline-block; vertical-align: middle; margin-right: 4px;"><polyline points="15 18 9 12 15 6"></polyline></svg> Back</button>
                                    <button type="button" class="bkntc-wizard-btn bkntc-wizard-next" id="bkntc_next_step_3">Next <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right" style="display: inline-block; vertical-align: middle; margin-left: 4px;"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
                                </div>
                            </div>
                            
                            <!-- Step 4: Date and Time -->
                            <div class="bkntc-step-panel" id="bkntc_step_panel_4">
                                <h2 class="bkntc-step-heading">Select the date and time</h2>
                                
                                <div class="bkntc-custom-calendar-wrapper" id="bkntc_custom_calendar_wrapper">
                                    <!-- Native calendar appended here -->
                                </div>
                                
                                <div class="bkntc-wizard-actions justify-content-between">
                                    <button type="button" class="bkntc-wizard-btn-secondary" id="bkntc_back_step_4"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-left" style="display: inline-block; vertical-align: middle; margin-right: 4px;"><polyline points="15 18 9 12 15 6"></polyline></svg> Back</button>
                                    <button type="button" class="bkntc-wizard-btn bkntc-wizard-next" id="bkntc_next_step_4">Next <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right" style="display: inline-block; vertical-align: middle; margin-left: 4px;"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
                                </div>
                            </div>
                            
                            <!-- Step 5: Confirmation (Customer Details) -->
                            <div class="bkntc-step-panel" id="bkntc_step_panel_5">
                                <h2 class="bkntc-step-heading">Confirm your details</h2>

                                <?php if (\BookneticApp\Providers\Helpers\Helper::getOption('google_login_enable', 'off') == 'on'): ?>
                                    <div class="bkntc-customer-google-login-section" style="margin-bottom: 24px; text-align: center;">
                                        <button type="button" class="bkntc-customer-google-login-btn" style="width: 100%; max-width: 320px; margin: 0 auto; display: flex; align-items: center; justify-content: center; gap: 12px; height: 40px; padding: 0 16px; background: #ffffff; border: 1px solid #dadce0; border-radius: 20px; font-family: 'Google Sans', Roboto, Arial, sans-serif; font-weight: 500; font-size: 14px; color: #1f1f1f; cursor: pointer; transition: background-color 0.2s, box-shadow 0.2s, border-color 0.2s; outline: none; box-shadow: none;" onmouseover="this.style.backgroundColor='#f8f9fa'; this.style.borderColor='#d2d4d7';" onmouseout="this.style.backgroundColor='#ffffff'; this.style.borderColor='#dadce0';">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
                                            </svg>
                                            Continue with Google
                                        </button>
                                        
                                        <div style="text-align: center; margin: 16px 0; color: #a1a1aa; font-size: 12px; position: relative; max-width: 320px; margin-left: auto; margin-right: auto;">
                                            <span style="background: #fff; padding: 0 10px; position: relative; z-index: 1;">Or enter details manually</span>
                                            <div style="position: absolute; top: 50%; left: 0; right: 0; border-top: 1px solid #e4e4e7; z-index: 0;"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="bkntc-custom-info-wrapper" id="bkntc_custom_info_wrapper">
                                    <!-- Native customer info form appended here -->
                                </div>
                                
                                <div class="bkntc-wizard-actions justify-content-between">
                                    <button type="button" class="bkntc-wizard-btn-secondary" id="bkntc_back_step_5"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-left" style="display: inline-block; vertical-align: middle; margin-right: 4px;"><polyline points="15 18 9 12 15 6"></polyline></svg> Back</button>
                                    <button type="button" class="bkntc-wizard-btn bkntc-wizard-next" id="bkntc_next_step_5">Next <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right" style="display: inline-block; vertical-align: middle; margin-left: 4px;"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
                                </div>
                            </div>
                            
                            <!-- Step 6: Checkout (Payment & Booking submit) -->
                            <div class="bkntc-step-panel" id="bkntc_step_panel_6">
                                <h2 class="bkntc-step-heading">Confirm your booking</h2>
                                
                                <div class="bkntc-custom-checkout-wrapper" id="bkntc_custom_checkout_wrapper">
                                    <!-- Native checkout forms and payment methods appended here -->
                                </div>
                                
                                <div class="bkntc-wizard-actions justify-content-between" id="bkntc_wizard_actions_6">
                                    <button type="button" class="bkntc-wizard-btn-secondary" id="bkntc_back_step_6"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-left" style="display: inline-block; vertical-align: middle; margin-right: 4px;"><polyline points="15 18 9 12 15 6"></polyline></svg> Back</button>
                                </div>
                            </div>
                            
                            <!-- Mobile Wizard Footer (shown only on mobile) -->
                            <div class="bkntc-mobile-wizard-footer">
                                <button type="button" class="bkntc-mobile-nav-btn bkntc-mobile-prev" id="bkntc_mobile_prev">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-left"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                </button>
                                <div class="bkntc-mobile-dots">
                                    <!-- Dots generated dynamically via JS -->
                                </div>
                                <button type="button" class="bkntc-mobile-nav-btn bkntc-mobile-next" id="bkntc_mobile_next">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </button>
                            </div>
                            
                            <!-- Warning Toast Container (top-right corner alert) -->
                            <div class="bkntc-toast-container" id="bkntc_toast_container">
                                <div class="bkntc-toast" id="bkntc_warning_toast">
                                    <div class="bkntc-toast-left">
                                        <div class="bkntc-toast-icon">i</div>
                                    </div>
                                    <div class="bkntc-toast-divider"></div>
                                    <div class="bkntc-toast-content">
                                        <div class="bkntc-toast-title">Warning</div>
                                        <div class="bkntc-toast-message" id="bkntc_warning_toast_msg">You have to choose a service</div>
                                    </div>
                                    <button type="button" class="bkntc-toast-close" id="bkntc_warning_toast_close">&times;</button>
                                    <div class="bkntc-toast-progress"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <!-- Hidden Native Booknetic Widget Container -->
            <div id="bkntc_booking_widget_box_hidden" class="bkntc-booking-widget-box-hidden" style="position: fixed !important; left: -9999px !important; top: -9999px !important; width: 400px !important; height: 600px !important; opacity: 0 !important; pointer-events: none !important; z-index: -1 !important;">
                <?php 
                $shortcode_str = '[booknetic';
                if (!empty($dir->tenant_id)) {
                    $shortcode_str .= ' tenant_id="' . (int)$dir->tenant_id . '"';
                }
                $shortcode_str .= ']';
                echo do_shortcode($shortcode_str); 
                ?>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                // Real-time Service Search
                $('#service_search_input').on('keyup', function() {
                    var val = $(this).val().toLowerCase();
                    filterServices();
                });

                // Tab Filter
                $('.bkntc-tab-btn').on('click', function() {
                    $('.bkntc-tab-btn').removeClass('active');
                    $(this).addClass('active');
                    filterServices();
                });

                function filterServices() {
                    var searchVal = $('#service_search_input').val().toLowerCase();
                    var categoryVal = $('.bkntc-tab-btn.active').attr('data-category');

                    $('#bkntc_services_items_list .bkntc-service-card').each(function() {
                        var card = $(this);
                        var name = card.attr('data-name');
                        var category = card.attr('data-category');

                        var matchesSearch = name.indexOf(searchVal) > -1;
                        var matchesCategory = categoryVal === 'all' || category === categoryVal;

                        if (matchesSearch && matchesCategory) {
                            card.show();
                        } else {
                            card.hide();
                        }
                    });
                }

                // Copy Link In-App Toast
                $('#bkntc_copy_link_btn').on('click', function(e) {
                    e.preventDefault();
                    var shareUrl = window.location.href;
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(shareUrl).then(showToast);
                    } else {
                        var tempInput = $('<input>');
                        $('body').append(tempInput);
                        tempInput.val(shareUrl).select();
                        document.execCommand('copy');
                        tempInput.remove();
                        showToast();
                    }

                    function showToast() {
                        var toast = $('#bkntc_toast_notification');
                        toast.addClass('show');
                        setTimeout(function() {
                            toast.removeClass('show');
                        }, 2500);
                    }
                });

                // Phone Toggle
                $('#bkntc_toggle_phone').on('click', function() {
                    var btn = $(this);
                    var container = $('#bkntc_phone_num');
                    var fullPhone = container.attr('data-phone');
                    
                    if (btn.text() === 'Show') {
                        container.text(fullPhone);
                        btn.text('Hide');
                    } else {
                        var hidden = fullPhone.substring(0, 5) + '...';
                        container.text(hidden);
                        btn.text('Show');
                    }
                });

                // --- Custom 4-Step Wizard JS Logic ---
                // Override BookneticData tenant_id dynamically from URL query parameters to ensure AJAX runs for the correct tenant
                if (window.BookneticData) {
                    var urlParams = new URLSearchParams(window.location.search);
                    var queryTenantId = urlParams.get('bkntc_page_id');
                    if (queryTenantId) {
                        window.BookneticData.tenant_id = parseInt(queryTenantId);
                        $('.booknetic_appointment').attr('data-tenant-id', queryTenantId);
                        $('#bkntc_booking_widget_box_hidden').attr('data-tenant-id', queryTenantId);
                        console.log('[Wizard Tenant Fix] Overrode BookneticData.tenant_id and DOM attributes to:', queryTenantId);
                    }
                }
                var currentStep = 1;
                var hasSingleLocation = $('.bkntc-wizard-location-card').length === 1;
                
                if (hasSingleLocation) {
                    $('.bkntc-step-indicator[data-step="1"]').hide();
                    $('.bkntc-step-line').first().hide();
                    $('#bkntc_back_step_2').hide();
                    $('#bkntc_booking_wizard_view').addClass('bkntc-wizard-single-location');
                    
                    // Re-number step indicators to start from 1
                    var currentNum = 1;
                    $('.bkntc-step-indicator').each(function() {
                        if ($(this).data('step') === 1) {
                            $(this).hide();
                        } else {
                            $(this).find('.bkntc-step-num').text(currentNum);
                            currentNum++;
                        }
                    });
                    
                    // Click single location to auto-select it natively
                    setTimeout(function() {
                        var firstLocationCard = $('.bkntc-wizard-location-card').first();
                        if (firstLocationCard.length) {
                            firstLocationCard.click();
                            console.log('[Wizard Init] Single location selected automatically:', firstLocationCard.data('id'));
                        }
                    }, 500);
                }
                
                // Toggle Category Accordion items
                $(document).on('click', '.bkntc-accordion-header', function() {
                    var item = $(this).closest('.bkntc-accordion-item');
                    var content = item.find('.bkntc-accordion-content');
                    
                    if (item.hasClass('open')) {
                        content.slideUp(200);
                        item.removeClass('open');
                    } else {
                        // Close others
                        $('.bkntc-accordion-item').removeClass('open').find('.bkntc-accordion-content').slideUp(200);
                        // Open current
                        content.slideDown(200);
                        item.addClass('open');
                    }
                });
                
                // Real-time Service Search in Wizard
                $('#bkntc_wizard_service_search').on('keyup', function() {
                    var val = $(this).val().toLowerCase();
                    if (val === '') {
                        $('.bkntc-accordion-item').show();
                        $('.bkntc-accordion-content').hide();
                        $('.bkntc-accordion-item').removeClass('open');
                        $('.bkntc-accordion-item').first().addClass('open').find('.bkntc-accordion-content').show();
                        $('.bkntc-wizard-service-card').show();
                    } else {
                        $('.bkntc-accordion-item').each(function() {
                            var accordion = $(this);
                            var matchCount = 0;
                            accordion.find('.bkntc-wizard-service-card').each(function() {
                                var card = $(this);
                                if (card.data('name').indexOf(val) > -1) {
                                    card.show();
                                    matchCount++;
                                } else {
                                    card.hide();
                                }
                            });
                            if (matchCount > 0) {
                                accordion.show().addClass('open').find('.bkntc-accordion-content').show();
                            } else {
                                accordion.hide().removeClass('open').find('.bkntc-accordion-content').hide();
                            }
                        });
                    }
                });
                
                // Location Card Selection
                $(document).on('click', '.bkntc-wizard-location-card', function() {
                    var card = $(this);
                    var locId = card.data('id');
                    
                    $('.bkntc-wizard-location-card').removeClass('selected');
                    card.addClass('selected');
                    
                    // Update Sidebar summary
                    $('#bkntc_summary_location_name').text(card.data('name'));
                    $('#bkntc_summary_location_address').text(card.data('address'));
                    $('#bkntc_summary_location_img').attr('src', card.data('avatar'));
                    $('#bkntc_summary_location_card').fadeIn(200);
                    
                    // Enable next step button
                    $('#bkntc_next_step_1').prop('disabled', false);
                    
                    // Programmatically select native location card in hidden widget
                    var nativeCard = $('.booknetic_appointment [data-step-id="location"] .booknetic_card[data-id="' + locId + '"]');
                    if (nativeCard.length) {
                        $('.booknetic_appointment [data-step-id="location"] .booknetic_card_selected').removeClass('booknetic_card_selected');
                        nativeCard.addClass('booknetic_card_selected');
                        nativeCard.click();
                        console.log('[Wizard Selection] Selected location:', locId);
                    }
                    
                    // Override Booknetic's internal getter values directly
                    if (window.capturedBookneticInstance) {
                        window.capturedBookneticInstance.getSelected.location = function() { return locId; };
                        console.log('[Wizard Cache Override] Overrode getSelected.location to:', locId);
                        if (window.capturedBookneticInstance.cartArr) {
                            window.capturedBookneticInstance.cartArr.forEach(function(item) {
                                item.location = locId;
                            });
                        }
                    }
                });
                
                // Service Card Selection (Multi-select)
                $(document).on('click', '.bkntc-wizard-service-card', function() {
                    var card = $(this);
                    card.toggleClass('selected');
                    
                    var selectedCards = $('.bkntc-wizard-service-card.selected');
                    
                    // Always clear existing dynamic cards first
                    $('.bkntc-summary-service-dynamic-card').remove();
                    
                    if (selectedCards.length === 0) {
                        $('#bkntc_next_step_2').prop('disabled', true);
                        return;
                    }
                    
                    // Enable next step button
                    $('#bkntc_next_step_2').prop('disabled', false);
                    
                    // Generate a separate card for each selected service
                    selectedCards.each(function() {
                        var c = $(this);
                        var name = c.find('.bkntc-wservice-name').text();
                        var duration = c.data('duration');
                        var price = c.data('price');
                        
                        // Clone the template card
                        var clone = $('#bkntc_summary_service_card').clone();
                        clone.removeAttr('id');
                        clone.addClass('bkntc-summary-service-dynamic-card');
                        
                        // Populate clone elements
                        clone.find('.bkntc-summary-title').text(name);
                        clone.find('#bkntc_summary_service_duration').text(duration).removeAttr('id');
                        clone.find('#bkntc_summary_service_price').text(price).removeAttr('id');
                        
                        // Show the clone and insert it into the container
                        clone.show();
                        $('.bkntc-sidebar-summary-container').prepend(clone);
                    });
                });
                
                // Staff Card Selection
                $(document).on('click', '.bkntc-wizard-staff-card', function() {
                    var card = $(this);
                    var staffId = card.data('id');
                    
                    $('.bkntc-wizard-staff-card').removeClass('selected');
                    card.addClass('selected');
                    
                    // Update Sidebar summary (only on desktop)
                    if (window.innerWidth > 768) {
                        $('#bkntc_summary_staff_name').text(card.data('name'));
                        $('#bkntc_summary_staff_role').text(card.data('profession'));
                        $('#bkntc_summary_staff_img').attr('src', card.data('avatar'));
                        $('#bkntc_summary_staff_card').fadeIn(200);
                    }
                    
                    // Enable next step button
                    $('#bkntc_next_step_3').prop('disabled', false);
                    
                    // Programmatically select native staff card in hidden widget
                    var nativeCard = $('.booknetic_appointment [data-step-id="staff"] .booknetic_card[data-id="' + staffId + '"]');
                    if (nativeCard.length) {
                        $('.booknetic_appointment [data-step-id="staff"] .booknetic_card_selected').removeClass('booknetic_card_selected');
                        nativeCard.addClass('booknetic_card_selected');
                        nativeCard.click();
                        console.log('[Wizard Selection] Selected staff:', staffId);
                    }
                    
                    // Override Booknetic's internal getter values directly
                    if (window.capturedBookneticInstance) {
                        window.capturedBookneticInstance.getSelected.staff = function() { return staffId; };
                        console.log('[Wizard Cache Override] Overrode getSelected.staff to:', staffId);
                        if (window.capturedBookneticInstance.cartArr) {
                            window.capturedBookneticInstance.cartArr.forEach(function(item) {
                                item.staff = staffId;
                            });
                        }
                    }
                    
                    // Auto-advance to next step (Date & Time)
                    setTimeout(function() {
                        $('#bkntc_next_step_3').click();
                    }, 300);
                });
                
                // Auto-advance when selecting a Date & Time slot (using Capturing phase to bypass stopPropagation)
                var calendarWrapperEl = document.getElementById('bkntc_custom_calendar_wrapper');
                if (calendarWrapperEl) {
                    calendarWrapperEl.addEventListener('click', function(e) {
                        var timeSlot = e.target.closest('.booknetic_time_slot');
                        if (timeSlot) {
                            setTimeout(function() {
                                $('#bkntc_next_step_4').click();
                            }, 350);
                        }
                    }, true);
                }
                
                // Go back to Salon Page
                $('#bkntc_btn_back_salon').on('click', function(e) {
                    e.preventDefault();
                    $('#bkntc_booking_wizard_view').hide();
                    $('#bkntc_landing_main_view').fadeIn(300);
                    $('html, body').animate({
                        scrollTop: $('#bkntc_landing_main_view').offset().top - 20
                    }, 300);
                });
                
                // Intercept the native booknetic object via hooks
                if (window.bookneticHooks) {
                    bookneticHooks.addFilter('appointment_ajax_data', function(formData, bookneticObj) {
                        try {
                            var cartStr = formData.get('cart');
                            if (cartStr) {
                                var cart = JSON.parse(cartStr);
                                if (cart && cart.length > 1) {
                                    var firstItem = cart[0];
                                    var locId = firstItem.location;
                                    var staffId = firstItem.staff;
                                    var selectedDate = firstItem.date;
                                    var selectedTime = firstItem.time;
                                    var customerId = firstItem.customer_id;
                                    var customerData = firstItem.customer_data;
                                    var broughtPeople = firstItem.brought_people_count;
                                    var locCat = firstItem.location_category;
                                    
                                    for (var i = 1; i < cart.length; i++) {
                                        cart[i].location = locId;
                                        cart[i].location_category = locCat;
                                        cart[i].staff = staffId;
                                        cart[i].date = selectedDate;
                                        cart[i].time = selectedTime;
                                        cart[i].brought_people_count = broughtPeople;
                                        cart[i].customer_id = customerId;
                                        cart[i].customer_data = customerData;
                                        
                                        if (firstItem.recurring_start_date) cart[i].recurring_start_date = firstItem.recurring_start_date;
                                        if (firstItem.recurring_end_date) cart[i].recurring_end_date = firstItem.recurring_end_date;
                                        if (firstItem.recurring_times) cart[i].recurring_times = firstItem.recurring_times;
                                        if (firstItem.appointments) cart[i].appointments = firstItem.appointments;
                                    }
                                    
                                    formData.set('cart', JSON.stringify(cart));
                                    console.log('[Wizard Filter Hook] Synchronized cart payload for AJAX request:', cart);
                                }
                            }
                        } catch (e) {
                            console.error('[Wizard Filter Hook Error]:', e);
                        }
                        return formData;
                    });
                    
                    bookneticHooks.addAction('booking_panel_loaded', function(bookneticObj) {
                        window.capturedBookneticInstance = bookneticObj;
                        console.log('[Wizard Capture] Primary Capture successful:', bookneticObj);
                        bookneticObj.toast = function(title) {
                            if (title === false) return;
                            console.warn('[Wizard Native Toast]:', title);
                            var decodedMsg = bookneticObj.htmlspecialchars_decode(title, 'ENT_QUOTES');
                            showWarningToast(decodedMsg);
                        };
                    });
                    bookneticHooks.addAction('loaded_step', function(bookneticObj, stepId) {
                        window.capturedBookneticInstance = bookneticObj;
                        console.log('[Wizard Capture] Step loaded capture successful:', stepId, bookneticObj);
                        bookneticObj.toast = function(title) {
                            if (title === false) return;
                            console.warn('[Wizard Native Toast]:', title);
                            var decodedMsg = bookneticObj.htmlspecialchars_decode(title, 'ENT_QUOTES');
                            showWarningToast(decodedMsg);
                        };
                        
                        if (stepId === 'location' && hasSingleLocation) {
                            var firstLocationCard = $('.bkntc-wizard-location-card').first();
                            if (firstLocationCard.length) {
                                var locId = firstLocationCard.data('id');
                                var nativeCard = $('.booknetic_appointment [data-step-id="location"] .booknetic_card[data-id="' + locId + '"]');
                                if (nativeCard.length && !nativeCard.hasClass('booknetic_card_selected')) {
                                    nativeCard.click();
                                    console.log('[Wizard Step Hook] Single location clicked natively:', locId);
                                }
                            }
                        }
                        
                        // Enforce overrides on step load transitions in case data resets
                        var selectedLocationCard = $('.bkntc-wizard-location-card.selected');
                        if (selectedLocationCard.length) {
                            var lId = selectedLocationCard.data('id');
                            bookneticObj.getSelected.location = function() { return lId; };
                        }
                        
                        var selectedServiceCard = $('.bkntc-wizard-service-card.selected');
                        if (selectedServiceCard.length) {
                            var sId = selectedServiceCard.data('id');
                            bookneticObj.getSelected.service = function() { return sId; };
                        }
                        
                        var selectedStaffCard = $('.bkntc-wizard-staff-card.selected');
                        if (selectedStaffCard.length) {
                            var stId = selectedStaffCard.data('id');
                            bookneticObj.getSelected.staff = function() { return stId; };
                        }
                        
                        // If we are at Step 5 and native loads confirm_details or cart, advance to Step 6
                        if (currentStep === 5 && (stepId === 'confirm_details' || stepId === 'cart')) {
                            goToWizardStep(6);
                        }
                    });
                    
                    bookneticHooks.addAction('booking_finished_successfully', function(bookneticObj) {
                        console.log('[Wizard Finish] Hook: booking finished successfully');
                        var finishedEl = $('.booknetic_appointment_finished');
                        if (finishedEl.length) {
                            finishedEl.removeClass('booknetic_hidden').show().css('display', 'flex');
                            $('#bkntc_custom_checkout_wrapper').empty().append(finishedEl);
                        }
                        
                        // Hide back navigation buttons on checkout success
                        $('#bkntc_wizard_actions_6').hide();
                        
                        // Mark final step 6 Checkout indicator as completed in indicator bar
                        $('.bkntc-step-indicator[data-step="6"]').addClass('completed').removeClass('active');
                    });
                    
                    bookneticHooks.addAction('payment_error', function(bookneticObj) {
                        console.log('[Wizard Finish] Hook: payment error');
                        var errorStep = $('.booknetic_appointment_finished_with_error');
                        if (errorStep.length) {
                            errorStep.removeClass('booknetic_hidden').show();
                            $('#bkntc_custom_checkout_wrapper').empty().append(errorStep);
                        }
                    });
                }
                
                // Step Navigation
                function goToWizardStep(step) {
                    if (hasSingleLocation && step === 1) {
                        step = 2;
                    }
                    currentStep = step;
                    
                    $('.bkntc-step-indicator').removeClass('active completed');
                    $('.bkntc-step-indicator').each(function() {
                        var stepNum = $(this).data('step');
                        if (stepNum === currentStep) {
                            $(this).addClass('active');
                        } else if (stepNum < currentStep) {
                            $(this).addClass('completed');
                        }
                    });
                    
                    $('.bkntc-step-panel').removeClass('active');
                    $('#bkntc_step_panel_' + step).addClass('active');
                    
                    if (step === 4) {
                        console.log('[Wizard Step 4 Debug] window.capturedBookneticInstance:', window.capturedBookneticInstance);
                        
                        // Force programmatic selection of the single location if it's missing
                        if (hasSingleLocation && window.capturedBookneticInstance) {
                            var firstLocationCard = $('.bkntc-wizard-location-card').first();
                            if (firstLocationCard.length) {
                                var locId = firstLocationCard.data('id');
                                var nativeCard = $('.booknetic_appointment [data-step-id="location"] .booknetic_card[data-id="' + locId + '"]');
                                if (nativeCard.length && !nativeCard.hasClass('booknetic_card_selected')) {
                                    nativeCard.addClass('booknetic_card_selected');
                                    nativeCard.click();
                                    console.log('[Wizard Navigation Step 4] Programmatically clicked native location card:', locId);
                                }
                                window.capturedBookneticInstance.getSelected.location = function() { return locId; };
                            }
                        }
                        
                        // Force a native "Forward" progression to make sure all session caching and AJAX requests trigger correctly
                        if (window.capturedBookneticInstance && window.capturedBookneticInstance.stepManager) {
                            console.log('[Wizard Step 4] Executing stepManager.loadStep("date_time")');
                            window.capturedBookneticInstance.stepManager.loadStep('date_time');
                        } else {
                            console.log('[Wizard Step 4] Fallback: Clicking native next button');
                            $('.booknetic_appointment .booknetic_next_step_btn').click();
                        }

                        // Booknetic loads date_time content asynchronously via AJAX.
                        // Poll until the content appears in the DOM before moving it.
                        var calendarAttempts = 0;
                        var calendarPoll = setInterval(function() {
                            calendarAttempts++;
                            var nativeCalendar = $('.booknetic_appointment_container_body [data-step-id="date_time"]');
                            console.log('[Wizard Step 4] Poll #' + calendarAttempts + ', found:', nativeCalendar.length, 'children:', nativeCalendar.children().length);
                            if (nativeCalendar.length && nativeCalendar.children().length > 0) {
                                clearInterval(calendarPoll);
                                syncCalendarWrapper();
                                console.log('[Wizard Step 4] Calendar content synced successfully');
                                
                                if ($(window).width() <= 768) {
                                    setTimeout(function() {
                                        var heading = $('#bkntc_step_panel_4 .bkntc-step-heading');
                                        if (heading.length) {
                                            $('html, body').animate({
                                                scrollTop: heading.offset().top - 20
                                            }, 400);
                                        }
                                    }, 100);
                                }
                            } else if (calendarAttempts >= 30) {
                                clearInterval(calendarPoll);
                                console.log('[Wizard Step 4] Timeout - calendar content not found after 30 attempts');
                                syncCalendarWrapper();
                            }
                        }, 300);
                    } else if (step === 5) {
                        if (window.capturedBookneticInstance && window.capturedBookneticInstance.stepManager) {
                            console.log('[Wizard Step 5] Loading information step via stepManager');
                            window.capturedBookneticInstance.stepManager.loadStep('information');
                        } else {
                            console.log('[Wizard Step 5] Fallback: Clicking native next button');
                            $('.booknetic_appointment .booknetic_next_step_btn').click();
                        }

                        // Poll until the native information step is loaded and populated
                        var infoAttempts = 0;
                        var infoPoll = setInterval(function() {
                            infoAttempts++;
                            var nativeInfo = $('.booknetic_appointment_container_body [data-step-id="information"]');
                            console.log('[Wizard Step 5] Poll #' + infoAttempts + ', info:', nativeInfo.length, 'children:', nativeInfo.children().length);
                            if ((nativeInfo.length && nativeInfo.children().length > 0) || infoAttempts >= 30) {
                                clearInterval(infoPoll);
                                $('#bkntc_custom_info_wrapper').empty();
                                if (nativeInfo.length && nativeInfo.children().length > 0) {
                                    try {
                                        var clone = nativeInfo.clone();
                                        clone.removeClass('booknetic_hidden').css('display', 'block');
                                        
                                        console.log('[Wizard Step 5] Successfully cloned nativeInfo. Renaming input IDs...');
                                        
                                        clone.find('#bkntc_input_email').attr('id', 'bkntc_input_email_clone');
                                        clone.find('label[for="bkntc_input_email"]').attr('for', 'bkntc_input_email_clone');
                                        
                                        clone.find('#bkntc_input_phone').attr('id', 'bkntc_input_phone_clone');
                                        clone.find('label[for="bkntc_input_phone"]').attr('for', 'bkntc_input_phone_clone');
                                        
                                        clone.find('#bkntc_input_name').attr('id', 'bkntc_input_name_clone');
                                        clone.find('label[for="bkntc_input_name"]').attr('for', 'bkntc_input_name_clone');
                                        
                                        $('#bkntc_custom_info_wrapper').append(clone);
                                        formatCustomInfoStep();
                                        console.log('[Wizard Step 5] Clone successfully appended and formatted.');
                                        
                                        if ($(window).width() <= 768) {
                                            setTimeout(function() {
                                                var wizardView = $('#bkntc_booking_wizard_view');
                                                if (wizardView.length) {
                                                    $('html, body').animate({
                                                        scrollTop: wizardView.offset().top - 12
                                                    }, 400);
                                                }
                                            }, 100);
                                        }
                                    } catch (e) {
                                        console.error('[Wizard Step 5 Clone Error]:', e);
                                    }
                                } else {
                                    console.log('[Wizard Step 5] nativeInfo was not found or has no children. Length:', nativeInfo.length);
                                }
                            }
                        }, 300);
                    } else if (step === 6) {
                        if (window.capturedBookneticInstance && window.capturedBookneticInstance.stepManager) {
                            console.log('[Wizard Step 6] Loading confirm_details step via stepManager');
                            window.capturedBookneticInstance.stepManager.loadStep('confirm_details');
                        } else {
                            console.log('[Wizard Step 6] Fallback: Clicking native next button');
                            $('.booknetic_appointment .booknetic_next_step_btn').click();
                        }

                        // Poll until the native confirm_details step is loaded and populated
                        var confirmAttempts = 0;
                        var confirmPoll = setInterval(function() {
                            confirmAttempts++;
                            var nativeConfirm = $('.booknetic_appointment_container_body [data-step-id="confirm_details"]');
                            console.log('[Wizard Step 6] Poll #' + confirmAttempts + ', confirm:', nativeConfirm.length, 'children:', nativeConfirm.children().length);
                            if ((nativeConfirm.length && nativeConfirm.children().length > 0) || confirmAttempts >= 30) {
                                clearInterval(confirmPoll);
                                syncCheckoutWrapper();
                                
                                if ($(window).width() <= 768) {
                                    setTimeout(function() {
                                        var heading = $('#bkntc_step_panel_6 .bkntc-step-heading');
                                        if (heading.length) {
                                            $('html, body').animate({
                                                scrollTop: heading.offset().top - 20
                                            }, 400);
                                        }
                                    }, 100);
                                }
                            }
                        }, 300);
                    }
                    
                    updateMobileFooter();
                }
                
                // Helper to format custom information step fields as requested
                function formatCustomInfoStep() {
                    var wrapper = $('#bkntc_custom_info_wrapper');
                    if (wrapper.length) {
                        // 1. Hide Social Logins
                        wrapper.find('.booknetic-login-buttons-container').hide();
                        // 2. Hide Surname field wrapper
                        wrapper.find('input[name="last_name"]').closest('.form-group').hide();
                        // 3. Make Name field take full width and change label to "Full Name"
                        var nameGroup = wrapper.find('input[name="first_name"]').closest('.form-group');
                        nameGroup.removeClass('col-md-6').addClass('col-md-12');
                        nameGroup.find('label').text('Full Name');
                        // 4. Hide found account / fill info helper container
                        wrapper.find('.bkntc-information-step-info-container').hide();
                        // 5. Hide native footer buttons
                        wrapper.find('.booknetic_appointment_container_footer').hide();
                        
                        // 6. Strip +44 prefix from the phone input box value after delays (to beat async intlTelInput initializers)
                        setTimeout(function() {
                            var phoneInput = $('#bkntc_custom_info_wrapper #bkntc_input_phone_clone');
                            if (phoneInput.length) {
                                var currentVal = phoneInput.val().trim();
                                if (currentVal.startsWith('+44')) {
                                    phoneInput.val(currentVal.replace('+44', '').trim());
                                }
                            }
                        }, 50);
                        setTimeout(function() {
                            var phoneInput = $('#bkntc_custom_info_wrapper #bkntc_input_phone_clone');
                            if (phoneInput.length) {
                                var currentVal = phoneInput.val().trim();
                                if (currentVal.startsWith('+44')) {
                                    phoneInput.val(currentVal.replace('+44', '').trim());
                                }
                            }
                        }, 250);
                        setTimeout(function() {
                            var phoneInput = $('#bkntc_custom_info_wrapper #bkntc_input_phone_clone');
                            if (phoneInput.length) {
                                var currentVal = phoneInput.val().trim();
                                if (currentVal.startsWith('+44')) {
                                    phoneInput.val(currentVal.replace('+44', '').trim());
                                }
                            }
                        }, 600);
                    }
                }
                
                // Document-level event listener to immediately strip +44 prefix on any user or script interaction
                $(document).on('keyup change focus input', '#bkntc_custom_info_wrapper #bkntc_input_phone_clone', function() {
                    var val = $(this).val().trim();
                    if (val.startsWith('+44')) {
                        $(this).val(val.replace('+44', '').trim());
                    }
                });
                              // Sync Checkout Wrapper Helper (Mockup reservation card layout generator)
                function syncCheckoutWrapper() {
                    var nativeConfirm = $('.booknetic_appointment_container_body [data-step-id="confirm_details"]');
                    if (nativeConfirm.length) {
                        // Extract native prices and calculate correctly for multiple selected services
                        var nativeTotalText = $('.booknetic_appointment_container_body [data-step-id="confirm_details"] .booknetic_sum_price').text().trim();
                        var nativeTotalNumeric = nativeTotalText ? parseFloat(nativeTotalText.replace(/[^0-9.]/g, '')) : 0;
                        
                        var totalNumeric = 0;
                        var currencySymbol = '£';
                        
                        if (nativeTotalNumeric > 0) {
                            totalNumeric = nativeTotalNumeric;
                            var symMatch = nativeTotalText.match(/^[^0-9.]+/);
                            if (symMatch) currencySymbol = symMatch[0];
                        } else {
                            // Calculate total price from selected service cards
                            var selectedServiceCards = $('.bkntc-wizard-service-card.selected');
                            selectedServiceCards.each(function() {
                                var prStr = $(this).data('price') || '';
                                var priceVal = parseFloat(prStr.replace(/[^0-9.]/g, '')) || 0;
                                totalNumeric += priceVal;
                                if (prStr) {
                                    var symMatch = prStr.match(/^[^0-9.]+/);
                                    if (symMatch) currencySymbol = symMatch[0];
                                }
                            });
                        }

                         // Extract payment methods and selection states from hidden native step
                         var paymentMethods = [];
                         $('.booknetic_appointment_container_body [data-step-id="confirm_details"] .booknetic_payment_method').each(function() {
                             var type = $(this).attr('data-type') || $(this).attr('data-payment-type');
                             if (type && paymentMethods.indexOf(type) === -1) {
                                 paymentMethods.push(type);
                             }
                         });
                         
                         var selectedPaymentMethod = $('.booknetic_appointment_container_body [data-step-id="confirm_details"] .booknetic_payment_method_selected').attr('data-type') || $('.booknetic_appointment_container_body [data-step-id="confirm_details"] .booknetic_payment_method_selected').attr('data-payment-type') || 'local';
                         
                         // Extract deposit elements
                         var depositEl = $('.booknetic_appointment_container_body [data-step-id="confirm_details"] .booknetic_deposit_amount_txt');
                         var depositVal = depositEl.length ? depositEl.text().trim() : '0.00';
                         var depositNumeric = parseFloat(depositVal.replace(/[^0-9.]/g, '')) || 0;
                         
                         var payAtAppointmentNumeric = totalNumeric - depositNumeric;
                         if (payAtAppointmentNumeric < 0) payAtAppointmentNumeric = 0;

                         // Determine if deposit settings are active (must exist, not be hidden natively, and have a non-zero value)
                         var nativeDepositBody = $('.booknetic_appointment_container_body [data-step-id="confirm_details"] .booknetic_confirm_deposit_body');
                         var hasDeposit = false;
                         if (depositEl.length && nativeDepositBody.length && !nativeDepositBody.hasClass('booknetic_hidden') && depositNumeric > 0) {
                             hasDeposit = true;
                         }
                         
                         // Format values
                         var totalFormatted = totalNumeric.toFixed(2) + currencySymbol;
                         var depositFormatted = depositNumeric.toFixed(2) + currencySymbol;
                         var payAtAppointmentFormatted = payAtAppointmentNumeric.toFixed(2) + currencySymbol;
                         
                         // Extract details
                         var serviceNames = [];
                         $('.bkntc-wizard-service-card.selected').each(function() {
                             serviceNames.push($(this).find('.bkntc-wservice-name').text());
                         });
                         var serviceName = serviceNames.join(', ') || 'Service';
                         
                         var staffName = $('#bkntc_summary_staff_name').text() || 'Specialist';
                         var staffImg = $('#bkntc_summary_staff_img').attr('src') || '';
                         
                         // Extract date and time
                         var selectedDate = $('.booknetic_appointment_container_body [data-step-id="date_time"] .booknetic_calendar_selected_day').attr('data-date-format') || '';
                         var selectedTime = $('.booknetic_appointment_container_body [data-step-id="date_time"] .booknetic_selected_time > div').first().text() || '';
                         var dateTimeVal = selectedTime && selectedDate ? selectedTime + ', ' + selectedDate : '';
                         
                         // Fallback to native text elements if undefined
                         if (!dateTimeVal) {
                             dateTimeVal = $('.booknetic_appointment_container_body [data-step-id="confirm_details"] .booknetic_confirm_date_time > div').first().find('span').last().text() || '';
                         }
                         
                         var wasPromoOpen = $('#bkntc_promo_toggle_link').hasClass('open');
                         
                         // Safely read previous custom promo input value if it exists
                         var promoInputValue = '';
                         var customInputEl = $('#bkntc_promo_input');
                         if (customInputEl.length) {
                             var inputVal = customInputEl.val();
                             if (inputVal !== undefined && inputVal !== null) {
                                 promoInputValue = inputVal;
                             }
                         }
                         
                         // Read native coupon input value if it exists and matches
                         var nativeCouponVal = $('#booknetic_coupon').val() || '';
                         if (nativeCouponVal && !promoInputValue) {
                             promoInputValue = nativeCouponVal;
                         }
                         
                         var isCouponApplied = false;
                         var nativeCouponWrapper = $('#booknetic_coupon').closest('.booknetic_add_coupon');
                         if (nativeCouponWrapper.length && nativeCouponWrapper.hasClass('booknetic_coupon_ok')) {
                             isCouponApplied = true;
                         }
                         
                         var statusMessageHtml = '';
                         var statusMessageClass = '';
                         var showStatusMessage = false;
                         if (isCouponApplied && promoInputValue) {
                             statusMessageHtml = 'Code ' + promoInputValue + ' applied successfully!';
                             statusMessageClass = 'success';
                             showStatusMessage = true;
                         }
                         
                         // Generate payment selector UI if multiple payment methods are active
                         var paymentSelectorHtml = '';
                         if (paymentMethods.length > 1) {
                             paymentSelectorHtml += `
                                 <div class="bkntc-payment-methods-selector">
                                     <span class="bkntc-mockup-label">Payment Method:</span>
                                     <div class="bkntc-payment-options-grid">
                             `;
                             for (var i = 0; i < paymentMethods.length; i++) {
                                 var slug = paymentMethods[i];
                                 var label = 'Pay at appointment';
                                 if (slug === 'stripe') {
                                     label = 'Credit Card';
                                 } else if (slug === 'paypal') {
                                     label = 'PayPal';
                                 }
                                 var activeClass = (selectedPaymentMethod === slug) ? 'active' : '';
                                 paymentSelectorHtml += `<div class="bkntc-payment-option-pill ${activeClass}" data-type="${slug}">${label}</div>`;
                             }
                             paymentSelectorHtml += `
                                     </div>
                                 </div>
                                 <div class="bkntc-mockup-divider"></div>
                             `;
                         }
                         
                         // Generate pricing breakdown rows dynamically based on deposit settings & selected method
                         var pricingRowsHtml = '';
                         if (hasDeposit) {
                             pricingRowsHtml += `
                                 <div class="bkntc-mockup-row">
                                     <span class="bkntc-mockup-label">Total:</span>
                                     <span class="bkntc-mockup-value bkntc-mockup-price">${totalFormatted}</span>
                                 </div>
                             `;
                             if (selectedPaymentMethod === 'local') {
                                 pricingRowsHtml += `
                                     <div class="bkntc-mockup-row">
                                         <span class="bkntc-mockup-label">Pay At The Appointment:</span>
                                         <span class="bkntc-mockup-value bkntc-mockup-price">${totalFormatted}</span>
                                     </div>
                                 `;
                             } else {
                                 pricingRowsHtml += `
                                     <div class="bkntc-mockup-row">
                                         <span class="bkntc-mockup-label">Deposit To Pay:</span>
                                         <span class="bkntc-mockup-value bkntc-mockup-price">${depositFormatted}</span>
                                     </div>
                                     <div class="bkntc-mockup-row">
                                         <span class="bkntc-mockup-label">Pay At The Appointment:</span>
                                         <span class="bkntc-mockup-value bkntc-mockup-price">${payAtAppointmentFormatted}</span>
                                     </div>
                                 `;
                             }
                         } else {
                             // Deposit settings are OFF
                             if (selectedPaymentMethod === 'local') {
                                 pricingRowsHtml += `
                                     <div class="bkntc-mockup-row">
                                         <span class="bkntc-mockup-label">Pay At The Appointment:</span>
                                         <span class="bkntc-mockup-value bkntc-mockup-price">${totalFormatted}</span>
                                     </div>
                                 `;
                             } else {
                                 pricingRowsHtml += `
                                     <div class="bkntc-mockup-row">
                                         <span class="bkntc-mockup-label">Amount Due:</span>
                                         <span class="bkntc-mockup-value bkntc-mockup-price">${totalFormatted}</span>
                                     </div>
                                 `;
                             }
                         }
                         
                         // Custom HTML template matches the mockup
                         var customHtml = `
                             <div class="bkntc-mockup-checkout-card">
                                 
                                 <div class="bkntc-mockup-card-body">
                                     <div class="bkntc-mockup-row">
                                         <span class="bkntc-mockup-label">Service:</span>
                                         <span class="bkntc-mockup-value bkntc-mockup-val-bold">${serviceName}</span>
                                     </div>
                                     <div class="bkntc-mockup-row">
                                         <span class="bkntc-mockup-label">Date and Time:</span>
                                         <span class="bkntc-mockup-value">${dateTimeVal}</span>
                                     </div>
                                     <div class="bkntc-mockup-row">
                                         <span class="bkntc-mockup-label">Staff:</span>
                                         <span class="bkntc-mockup-value bkntc-mockup-staff-val">
                                             ${staffImg ? `<img src="${staffImg}" class="bkntc-mockup-staff-avatar" alt="">` : ''}
                                             <span class="bkntc-mockup-val-bold">${staffName}</span>
                                         </span>
                                     </div>
                                     
                                     <div class="bkntc-mockup-divider"></div>
                                     
                                     <!-- Payment Selector Option -->
                                     ${paymentSelectorHtml}
                                     
                                     <!-- Promo Code Accordion -->
                                     <div class="bkntc-promo-wrapper">
                                         <a href="#" class="bkntc-promo-toggle-link" id="bkntc_promo_toggle_link">Have a promo code?</a>
                                         <div class="bkntc-promo-container-inner" id="bkntc_promo_container_inner" style="display: none; margin-top: 10px;">
                                             <div class="bkntc-custom-coupon-form">
                                                 <input type="text" id="bkntc_promo_input" placeholder="Promo code..." value="${promoInputValue}" ${isCouponApplied ? 'disabled' : ''}>
                                                 <button type="button" id="bkntc_promo_btn" class="${isCouponApplied ? 'bkntc-btn-remove' : ''}">${isCouponApplied ? 'Remove' : 'Apply'}</button>
                                             </div>
                                             <div id="bkntc_promo_status" class="bkntc-promo-status-msg ${statusMessageClass}" style="display: ${showStatusMessage ? 'block' : 'none'};">${statusMessageHtml}</div>
                                         </div>
                                     </div>
                                     
                                     <div class="bkntc-mockup-divider"></div>
                                     
                                     <!-- Dynamic Pricing Breakdown -->
                                     ${pricingRowsHtml}
                                     
                                     <div class="bkntc-mockup-divider"></div>
                                     
                                     <div class="bkntc-mockup-btn-container">
                                         <button type="button" class="bkntc-mockup-confirm-btn" id="bkntc_mockup_confirm_btn">Confirm Booking</button>
                                         <p class="bkntc-mockup-terms">By creating an account I agree with <a href="/terms-and-conditions" target="_blank">Terms and Conditions</a> and <a href="/privacy-policy" target="_blank">Privacy Policy</a></p>
                                     </div>
                                 </div>
                             </div>
                         `;
                        
                        $('#bkntc_custom_checkout_wrapper').empty().append(customHtml);
                        
                        if (wasPromoOpen) {
                            $('#bkntc_promo_toggle_link').addClass('open');
                            $('#bkntc_promo_container_inner').show();
                        }
                        
                        // Update classes to reflect coupon status (success/error)
                        var customCouponForm = $('.bkntc-custom-coupon-form');
                        if (nativeCouponWrapper.length && customCouponForm.length) {
                            if (nativeCouponWrapper.hasClass('booknetic_coupon_ok')) {
                                customCouponForm.addClass('booknetic_coupon_ok');
                            } else {
                                customCouponForm.removeClass('booknetic_coupon_ok');
                            }
                        }
                        
                        console.log('[Wizard Sync] Custom Checkout mock card generated successfully');
                    }
                }

                // Proxy Confirm Button Click back to native widget submit button
                $(document).on('click', '#bkntc_mockup_confirm_btn', function() {
                    console.log('[Wizard Mockup Submit] Triggering native Booking confirm click');
                    var btn = $(this);
                    if (btn.attr('disabled')) {
                        return;
                    }
                    btn.attr('disabled', true).text('Confirming...');
                    
                    var nativeNextBtn = $('.bkntc-booking-widget-box-hidden .booknetic_confirm_booking_btn');
                    if (!nativeNextBtn.length) {
                        nativeNextBtn = $('.bkntc-booking-widget-box-hidden .booknetic_next_step');
                    }
                    
                    if (nativeNextBtn.length) {
                        nativeNextBtn.last().click();
                    } else {
                        // Fallback click
                        $('.bkntc-booking-widget-box-hidden .booknetic_appointment_container_footer button').last().click();
                    }
                });

                // Proxy Payment Method Selection to native element
                $(document).on('click', '.bkntc-payment-option-pill', function() {
                    var type = $(this).data('type');
                    console.log('[Wizard Payment] Proxying payment method selection to native:', type);
                    var nativeMethod = $('.booknetic_appointment_container_body [data-step-id="confirm_details"] .booknetic_payment_method[data-payment-type="' + type + '"], .booknetic_appointment_container_body [data-step-id="confirm_details"] .booknetic_payment_method[data-type="' + type + '"]');
                    if (nativeMethod.length) {
                        nativeMethod[0].click();
                        // Immediately sync our UI
                        syncCheckoutWrapper();
                    }
                });

                // Custom calendar/hyperlink proxy clicks for Moved success step buttons
                $(document).on('click', '#bkntc_custom_checkout_wrapper #booknetic_add_to_google_calendar_btn', function(e) {
                    e.preventDefault();
                    var url = window.bkntcLastGoogleCalUrl || $(this).data('url') || $(this).attr('data-url');
                    console.log('[Wizard Finish] Google Calendar click, url:', url);
                    if (url) {
                        window.open(url, '_blank');
                    } else {
                        console.warn('[Wizard Finish] No Google Calendar URL found');
                    }
                });
                
                $(document).on('click', '#bkntc_custom_checkout_wrapper #booknetic_add_to_icalendar_btn', function(e) {
                    e.preventDefault();
                    var url = window.bkntcLastICalUrl || $(this).attr('href') || $(this).data('url');
                    console.log('[Wizard Finish] iCal click, url:', url);
                    if (url) {
                        window.open(url, '_blank');
                    } else {
                        console.warn('[Wizard Finish] No iCal URL found');
                    }
                });

                $(document).on('click', '#bkntc_custom_checkout_wrapper #booknetic_start_new_booking_btn', function(e) {
                    e.preventDefault();
                    console.log('[Wizard Finish] Start new booking triggered, reloading page');
                    location.reload();
                });

                $(document).on('click', '#bkntc_custom_checkout_wrapper #booknetic_finish_btn, #bkntc_custom_checkout_wrapper .booknetic_finish_booking_btn, #bkntc_custom_checkout_wrapper #booknetic_finish_booking_btn', function(e) {
                    e.preventDefault();
                    console.log('[Wizard Finish] Finish booking triggered, redirecting');
                    var redirectUrl = $(this).attr('data-redirect-url') || $(this).data('redirect') || '/';
                    window.location.href = redirectUrl;
                });

                // Toggle link click handler for promo code accordion
                $(document).on('click', '#bkntc_promo_toggle_link', function(e) {
                    e.preventDefault();
                    var link = $(this);
                    var container = $('#bkntc_promo_container_inner');
                    link.toggleClass('open');
                    container.slideToggle(200);
                });
                
                // Proxy Promo Code Click to native coupon button
                 $(document).on('click', '#bkntc_promo_btn', function() {
                     var isApplied = $(this).text().trim() === 'Remove';
                     var nativeInput = $('#booknetic_coupon');
                     var nativeBtnOk = $('.booknetic_coupon_ok_btn');
                     var nativeBtnCancel = $('.booknetic_coupon_cancel_btn');
                     
                     console.log('[Wizard Promo Click Debug]', {
                         isApplied: isApplied,
                         nativeInputFound: nativeInput.length,
                         nativeBtnOkFound: nativeBtnOk.length,
                         nativeBtnCancelFound: nativeBtnCancel.length,
                         nativeInputValue: nativeInput.length ? nativeInput.val() : null,
                         nativeBtnOkVisible: nativeBtnOk.length ? nativeBtnOk.is(':visible') : false
                     });
                     
                     if (isApplied) {
                         console.log('[Wizard Promo] Removing coupon via proxy click on native cancel button');
                         if (nativeBtnCancel.length && nativeBtnCancel.is(':visible')) {
                             nativeBtnCancel[0].click();
                             $('#bkntc_promo_input').val('');
                         } else {
                             nativeInput.val('').trigger('change');
                             nativeBtnOk[0].click();
                         }
                     } else {
                         var val = $('#bkntc_promo_input').val().trim();
                         console.log('[Wizard Promo] Applying coupon via proxy:', val);
                         if (nativeInput.length && nativeBtnOk.length) {
                             nativeInput.val(val).trigger('change');
                             nativeBtnOk[0].click();
                             console.log('[Wizard Promo] Clicked nativeBtnOk successfully');
                         } else {
                             console.error('[Wizard Promo Error] Native coupon elements not found in DOM');
                         }
                     }
                 });

                 // Hook into jQuery global AJAX to detect coupon success/failure and display proper user notices
                 $(document).ajaxComplete(function(event, xhr, settings) {
                      var isCouponAjax = false;
                      var actionVal = 'no-action';
                      
                      if (settings.url && settings.url.indexOf('admin-ajax.php') !== -1 && settings.data) {
                          if (typeof settings.data === 'string') {
                              var match = settings.data.match(/(^|&)action=([^&]*)/);
                              if (match) {
                                  actionVal = match[2];
                              }
                              if (actionVal === 'summary_with_coupon' || actionVal === 'bkntc_summary_with_coupon') {
                                  isCouponAjax = true;
                              }
                          } else if (settings.data.constructor && settings.data.constructor.name === 'FormData') {
                              if (typeof settings.data.has === 'function' && settings.data.has('action')) {
                                  actionVal = settings.data.get('action');
                                  if (actionVal === 'summary_with_coupon' || actionVal === 'bkntc_summary_with_coupon') {
                                      isCouponAjax = true;
                                  }
                              }
                          } else if (typeof settings.data === 'object') {
                              actionVal = settings.data.action;
                              if (actionVal === 'summary_with_coupon' || actionVal === 'bkntc_summary_with_coupon') {
                                  isCouponAjax = true;
                              }
                          }
                      }
                      
                       console.log('[Wizard AJAX Complete Debug]', {
                           url: settings.url,
                           isCouponAjax: isCouponAjax,
                           actionValue: actionVal,
                           dataTypeName: settings.data ? settings.data.constructor.name : 'null',
                           response: xhr.responseText ? xhr.responseText.substring(0, 300) : 'no-response'
                       });
                       
                        // Handle booking validation errors (e.g. busy timeslots)
                        if (actionVal === 'bkntc_get_data' || actionVal === 'get_data') {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response && response.status === 'error' && response.error_msg) {
                                    console.warn('[Wizard Submit Error]', response.error_msg);
                                    var errorEl = $('#bkntc_checkout_error');
                                    if (!errorEl.length) {
                                        $('.bkntc-mockup-checkout-card').prepend('<div id="bkntc_checkout_error" class="bkntc-promo-status-msg error" style="margin-bottom: 15px; display: block;"></div>');
                                        errorEl = $('#bkntc_checkout_error');
                                    }
                                    errorEl.text(response.error_msg).show();
                                    
                                    // Re-enable confirm button
                                    var btn = $('#bkntc_mockup_confirm_btn');
                                    if (btn.length) {
                                        btn.removeAttr('disabled').text('Confirm Booking');
                                    }
                                } else if (response && response.google_calendar_url) {
                                     // Store calendar URLs globally for reliable access on the finish page
                                     window.bkntcLastGoogleCalUrl = response.google_calendar_url;
                                     window.bkntcLastICalUrl = response.icalendar_url;
                                     console.log('[Wizard Confirm Success] Captured calendar URLs:', {
                                         google: response.google_calendar_url,
                                         ical: response.icalendar_url
                                     });
                                 }
                            } catch(e) {}
                        }

                       if (isCouponAjax) {
                           try {
                               var response = JSON.parse(xhr.responseText);
                               console.log('[Wizard Promo Response]', response);
                               
                               var statusEl = $('#bkntc_promo_status');
                               var promoInput = $('#bkntc_promo_input');
                               
                               if (response && response.status === 'ok') {
                                   var codeName = promoInput.val() || '';
                                   if (codeName) {
                                       statusEl.removeClass('error').addClass('success').text('Code ' + codeName + ' applied successfully!').show();
                                   } else {
                                       statusEl.hide();
                                   }
                               } else {
                                   // Error response
                                   var errorMsg = (response && response.error) ? response.error : 'Invalid promo code';
                                   statusEl.removeClass('success').addClass('error').text(errorMsg).show();
                               }
                           } catch(e) {
                               console.error('[Wizard Promo Response JSON Parse Error]', e);
                           }
                           
                           setTimeout(function() {
                               syncCheckoutWrapper();
                           }, 400);
                       }
                 });    
                
                // Warning modal helper functions
                var toastTimer = null;
                function showWarningToast(message) {
                    if (toastTimer) {
                        clearTimeout(toastTimer);
                    }
                    
                    var toastContainer = $('#bkntc_toast_container');
                    var toast = $('#bkntc_warning_toast');
                    
                    if (!toastContainer.length || !toast.length) {
                        console.error('[Warning Toast Error] Elements not found in DOM');
                        alert(message);
                        return;
                    }

                    // Dynamically append to body on every call to prevent parent transforms from cropping/shifting it
                    if (toastContainer.parent()[0] !== document.body) {
                        toastContainer.appendTo('body');
                    }

                    // Force inline container style variables
                    var isMobile = window.innerWidth <= 768;
                    toastContainer.css({
                        'position': 'fixed',
                        'top': isMobile ? '100px' : '110px',
                        'left': isMobile ? '24px' : 'auto',
                        'right': isMobile ? '24px' : '280px',
                        'z-index': '99999999', // extremely high z-index
                        'width': isMobile ? 'auto' : '380px',
                        'max-width': '100%',
                        'pointer-events': 'none',
                        'display': 'flex',
                        'flex-direction': 'column',
                        'gap': '12px'
                    });

                    // Set message
                    $('#bkntc_warning_toast_msg').text(message);

                    // Set initial hidden styling
                    toast.css({
                        'width': '100%',
                        'box-sizing': 'border-box',
                        'transition': 'transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s',
                        'transform': 'translateY(-20px)',
                        'opacity': '0'
                    });

                    // Trigger DOM reflow to ensure the transition fires
                    void toast[0].offsetWidth;

                    // Animate to visible state
                    toast.addClass('show').css({
                        'transform': 'translateY(0)',
                        'opacity': '1'
                    });

                    // Auto hide timer
                    toastTimer = setTimeout(function() {
                        toast.removeClass('show').css({
                            'transform': 'translateY(-20px)',
                            'opacity': '0'
                        });
                    }, 4000);
                }

                $(document).on('click', '#bkntc_warning_toast_close', function() {
                    $('#bkntc_warning_toast').removeClass('show').css({
                        'transform': 'translateY(-20px)',
                        'opacity': '0'
                    });
                    if (toastTimer) {
                        clearTimeout(toastTimer);
                        toastTimer = null;
                    }
                });

                // Click handlers for next buttons
                $(document).on('click', '#bkntc_next_step_1', function() {
                    // Check if location selected
                    if (!hasSingleLocation && $('.bkntc-wizard-location-card.selected').length === 0) {
                        showWarningToast('You have to choose a location');
                        return;
                    }
                    goToWizardStep(2);
                });
                
                $(document).on('click', '#bkntc_next_step_2', function() {
                    // Check if service selected
                    var selectedCards = $('.bkntc-wizard-service-card.selected');
                    if (selectedCards.length === 0) {
                        showWarningToast('You have to choose a service');
                        return;
                    }
                    
                    // Populate native booknetic.cartArr with all selected services
                    if (window.capturedBookneticInstance) {
                        var cart = [];
                        var staffId = $('.bkntc-wizard-staff-card.selected').data('id') || -1;
                        var locId = $('.bkntc-wizard-location-card.selected').data('id') || -1;
                        
                        selectedCards.each(function() {
                            var sId = $(this).data('id');
                            cart.push({
                                location: locId,
                                staff: staffId,
                                service: sId,
                                service_extras: [],
                                brought_people_count: 0,
                                customer_id: 0
                            });
                        });
                        
                        window.capturedBookneticInstance.cartArr = cart;
                        window.capturedBookneticInstance.cartCurrentIndex = 0;
                        console.log('[Wizard Multi-Service] Updated cartArr:', cart);
                        
                        var firstServiceId = selectedCards.first().data('id');
                        window.capturedBookneticInstance.getSelected.service = function() { return firstServiceId; };
                        
                        var nativeCard = $('.booknetic_appointment [data-step-id="service"] .booknetic_service_card[data-id="' + firstServiceId + '"]');
                        if (nativeCard.length) {
                            $('.booknetic_appointment [data-step-id="service"] .booknetic_service_card_selected').removeClass('booknetic_service_card_selected');
                            nativeCard.addClass('booknetic_service_card_selected');
                            nativeCard.click();
                            console.log('[Wizard Selection] Selected first service natively:', firstServiceId);
                        }
                    }
                    
                    goToWizardStep(3);
                    
                    setTimeout(function() {
                        var availableStaffIds = [];
                        $('.booknetic_appointment [data-step-id="staff"] .booknetic_card').each(function() {
                            availableStaffIds.push($(this).data('id'));
                        });
                        
                        if (availableStaffIds.length > 0) {
                            $('.bkntc-wizard-staff-card').each(function() {
                                var card = $(this);
                                var id = card.data('id');
                                if (availableStaffIds.indexOf(id) > -1 || availableStaffIds.indexOf(-1) > -1) {
                                    card.show();
                                } else {
                                    card.hide();
                                }
                            });
                        }
                    }, 200);
                });
                
                $(document).on('click', '#bkntc_next_step_3', function() {
                    // Check if staff selected
                    if ($('.bkntc-wizard-staff-card.selected').length === 0) {
                        showWarningToast('You have to choose a specialist');
                        return;
                    }
                    goToWizardStep(4);
                });

                $(document).on('click', '#bkntc_next_step_4', function() {
                    // Check if date and time slot is selected
                    var hasTimeSelected = $('.booknetic_appointment [data-step-id="date_time"] .booknetic_selected_time').length > 0;
                    if (!hasTimeSelected) {
                        showWarningToast('You have to choose a date and time');
                        return;
                    }
                    
                    // Copy selected date and time from first item to all other items in the cart
                    if (window.capturedBookneticInstance && window.capturedBookneticInstance.cartArr) {
                        var cart = window.capturedBookneticInstance.cartArr;
                        if (cart.length > 0) {
                            var firstItem = cart[0];
                            var selectedDate = firstItem.date;
                            var selectedTime = firstItem.time;
                            for (var i = 1; i < cart.length; i++) {
                                cart[i].date = selectedDate;
                                cart[i].time = selectedTime;
                            }
                            console.log('[Wizard Multi-Service] Synced date and time to all items:', selectedDate, selectedTime);
                        }
                    }
                    
                    goToWizardStep(5);
                });
                
                // Real-time synchronization of cloned inputs to native inputs to avoid race conditions
                $(document).on('input change', '#bkntc_custom_info_wrapper input, #bkntc_custom_info_wrapper select, #bkntc_custom_info_wrapper textarea', function() {
                    var cloneInput = $(this);
                    var id = cloneInput.attr('id');
                    var infoWrapper = $('#bkntc_custom_info_wrapper');
                    var nativeWrapper = $('.booknetic_appointment_container_body [data-step-id="information"]');
                    
                    // 1. Sync core fields
                    if (id === 'bkntc_input_name_clone') {
                        var nameParts = cloneInput.val().trim().split(/\s+/);
                        var firstName = nameParts[0] || '';
                        var lastName = nameParts.slice(1).join(' ') || '.';
                        var nativeName = nativeWrapper.find('#bkntc_input_name');
                        if (nativeName.length) {
                            nativeName.val(firstName).trigger('change');
                            if (nativeName[0]) nativeName[0].dispatchEvent(new Event('input', { bubbles: true }));
                        }
                        var nativeSurname = nativeWrapper.find('#bkntc_input_surname');
                        if (nativeSurname.length) {
                            nativeSurname.val(lastName).trigger('change');
                            if (nativeSurname[0]) nativeSurname[0].dispatchEvent(new Event('input', { bubbles: true }));
                        }
                        return;
                    }
                    if (id === 'bkntc_input_email_clone') {
                        var nativeEmail = nativeWrapper.find('#bkntc_input_email');
                        if (nativeEmail.length) {
                            nativeEmail.val(cloneInput.val()).trigger('change');
                            if (nativeEmail[0]) nativeEmail[0].dispatchEvent(new Event('input', { bubbles: true }));
                        }
                        return;
                    }
                    if (id === 'bkntc_input_phone_clone') {
                        var phoneVal = cloneInput.val() ? cloneInput.val().trim() : '';
                        var nativePhoneVal = phoneVal;
                        if (phoneVal && !phoneVal.startsWith('+')) {
                            if (phoneVal.startsWith('0')) {
                                phoneVal = phoneVal.substring(1);
                            }
                            nativePhoneVal = '+44' + phoneVal;
                        }
                        var nativePhone = nativeWrapper.find('#bkntc_input_phone');
                        if (nativePhone.length) {
                            var itiInstance = nativePhone.data('iti');
                            if (itiInstance) {
                                itiInstance.setNumber(nativePhoneVal);
                            } else {
                                nativePhone.val(nativePhoneVal).trigger('change');
                            }
                            if (nativePhone[0]) nativePhone[0].dispatchEvent(new Event('input', { bubbles: true }));
                        }
                        return;
                    }
                    
                    // 2. Sync custom fields by relative DOM order index matching
                    var cloneInputs = infoWrapper.find('input, select, textarea');
                    var nativeInputs = nativeWrapper.find('input, select, textarea');
                    var index = cloneInputs.index(cloneInput);
                    
                    if (index > -1) {
                        var nativeInput = nativeInputs.eq(index);
                        if (nativeInput.length) {
                            if (cloneInput.attr('type') === 'file') {
                                return;
                            }
                            if (cloneInput.is(':checkbox')) {
                                nativeInput.prop('checked', cloneInput.prop('checked')).trigger('change');
                            } else if (cloneInput.is(':radio')) {
                                nativeInput.prop('checked', cloneInput.prop('checked')).trigger('change');
                            } else {
                                nativeInput.val(cloneInput.val()).trigger('change');
                            }
                        }
                    }
                });

                $('#bkntc_next_step_5').on('click', function() {
                    // Copy values from cloned inputs back to native hidden inputs before validating
                    var infoWrapper = $('#bkntc_custom_info_wrapper');
                    var nativeWrapper = $('.booknetic_appointment_container_body [data-step-id="information"]');
                    
                    var emailVal = infoWrapper.find('#bkntc_input_email_clone').val();
                    var phoneVal = infoWrapper.find('#bkntc_input_phone_clone').val() ? infoWrapper.find('#bkntc_input_phone_clone').val().trim() : '';
                    var nameVal = infoWrapper.find('#bkntc_input_name_clone').val();
                    
                    // Validate fields and show warning toast
                    if (!nameVal || nameVal.trim() === '') {
                        showWarningToast('You have to enter your name');
                        return;
                    }
                    if (!emailVal || emailVal.trim() === '') {
                        showWarningToast('You have to enter your email address');
                        return;
                    }
                    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(emailVal.trim())) {
                        showWarningToast('Please enter a valid email address');
                        return;
                    }
                    if (!phoneVal || phoneVal.trim() === '') {
                        showWarningToast('You have to enter your phone number');
                        return;
                    }
                    
                    var nativePhoneVal = phoneVal;
                    if (phoneVal && !phoneVal.startsWith('+')) {
                        // Strip leading 0 if user typed national format
                        if (phoneVal.startsWith('0')) {
                            phoneVal = phoneVal.substring(1);
                        }
                        nativePhoneVal = '+44' + phoneVal;
                    }
                    
                    var nativeEmail = nativeWrapper.find('#bkntc_input_email');
                    var nativePhone = nativeWrapper.find('#bkntc_input_phone');
                    var nativeName = nativeWrapper.find('#bkntc_input_name');
                    var nativeSurname = nativeWrapper.find('#bkntc_input_surname');
                    
                    var nameParts = nameVal.trim().split(/\s+/);
                    var firstName = nameParts[0] || '';
                    var lastName = nameParts.slice(1).join(' ') || '.';
                    
                    nativeEmail.val(emailVal).trigger('change');
                    nativeName.val(firstName).trigger('change');
                    if (nativeSurname.length) {
                        nativeSurname.val(lastName).trigger('change');
                        nativeSurname[0].dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    
                    // Set phone via intl-tel-input instance API to update its internal validator state synchronously
                    var itiInstance = nativePhone.data('iti');
                    if (itiInstance) {
                        console.log('[Wizard Phone] Using setNumber on intlTelInput instance:', nativePhoneVal);
                        itiInstance.setNumber(nativePhoneVal);
                    } else {
                        nativePhone.val(nativePhoneVal).trigger('change');
                    }
                    
                    // Copy all inputs from clone wrapper back to native wrapper by relative index to sync custom fields
                    var cloneInputs = infoWrapper.find('input, select, textarea');
                    var nativeInputs = nativeWrapper.find('input, select, textarea');
                    cloneInputs.each(function(index) {
                        var cloneInput = $(this);
                        var nativeInput = nativeInputs.eq(index);
                        if (nativeInput.length) {
                            var id = cloneInput.attr('id');
                            if (id === 'bkntc_input_email_clone' || id === 'bkntc_input_phone_clone' || id === 'bkntc_input_name_clone') {
                                return;
                            }
                            if (cloneInput.attr('type') === 'file') {
                                return;
                            }
                            if (cloneInput.is(':checkbox')) {
                                nativeInput.prop('checked', cloneInput.prop('checked')).trigger('change');
                            } else if (cloneInput.is(':radio')) {
                                nativeInput.prop('checked', cloneInput.prop('checked')).trigger('change');
                            } else {
                                nativeInput.val(cloneInput.val()).trigger('change');
                            }
                        }
                    });
                    
                    // Populate custom_fields for ALL cart items before validation
                    if (window.capturedBookneticInstance && window.capturedBookneticInstance.cartArr) {
                        var cart = window.capturedBookneticInstance.cartArr;
                        var infoForms = infoWrapper.find('.booknetic_custom_form');
                        
                        infoForms.each(function(index) {
                            var formEl = $(this);
                            var customFields = {};
                            
                            formEl.find("[data-input-id][type!='checkbox'][type!='radio'], [data-input-id][type='checkbox']:checked, [data-input-id][type='radio']:checked").each(function() {
                                var inputId = $(this).data('input-id');
                                var inputVal = $(this).val() || '';
                                
                                if (inputVal != '' && $(this).data('isdatepicker')) {
                                    inputVal = inputVal.replace(/\s+/g, '');
                                    inputVal = window.capturedBookneticInstance.convertDate(inputVal, window.capturedBookneticInstance.datePickerFormat(), 'Y-m-d');
                                }
                                
                                if ($(this).attr('type') == 'file') {
                                    if ($(this).attr('multiple') !== undefined || $(this).data('type') === 'file_multiple') {
                                        var files = (window.capturedBookneticInstance.stagedCustomFiles && window.capturedBookneticInstance.stagedCustomFiles[inputId]) ? window.capturedBookneticInstance.stagedCustomFiles[inputId] : [];
                                        if (files.length > 0) {
                                            var fileList = [];
                                            for (var f = 0; f < files.length; f++) {
                                                var uniqueId = Math.random().toString(36).substring(2, 9);
                                                if (window.capturedBookneticInstance.customFiles === undefined) {
                                                    window.capturedBookneticInstance.customFiles = [];
                                                }
                                                window.capturedBookneticInstance.customFiles.push({
                                                    id: uniqueId,
                                                    file: files[f]
                                                });
                                                fileList.push({
                                                    id: uniqueId,
                                                    name: files[f].name
                                                });
                                            }
                                            customFields[inputId] = {
                                                multiple: true,
                                                files: fileList
                                            };
                                        }
                                    } else if ($(this)[0].files[0]) {
                                        var uniqueId = Math.random().toString(36).substring(2, 9);
                                        if (window.capturedBookneticInstance.customFiles === undefined) {
                                            window.capturedBookneticInstance.customFiles = [];
                                        }
                                        window.capturedBookneticInstance.customFiles.push({
                                            id: uniqueId,
                                            file: $(this)[0].files[0]
                                        });
                                        customFields[inputId] = {
                                            id: uniqueId,
                                            name: $(this)[0].files[0].name
                                        };
                                    }
                                } else {
                                    if (customFields[inputId] === undefined) {
                                        customFields[inputId] = inputVal;
                                    } else {
                                        customFields[inputId] += ',' + inputVal;
                                    }
                                }
                            });
                            
                            if (cart[index]) {
                                cart[index]['custom_fields'] = customFields;
                                console.log('[Wizard Multi-Service] Serialized custom_fields for cart item ' + index + ':', customFields);
                            }
                        });
                    }
                    
                    // Trigger native input events so validation fires
                    nativeEmail[0].dispatchEvent(new Event('input', { bubbles: true }));
                    nativePhone[0].dispatchEvent(new Event('input', { bubbles: true }));
                    nativeName[0].dispatchEvent(new Event('input', { bubbles: true }));
                    
                    // Trigger blur on native fields
                    nativePhone.trigger('blur');
                    nativePhone[0].dispatchEvent(new Event('blur', { bubbles: true }));

                    console.log('[Wizard Info Submit] Copied values back to native fields, waiting for validation to update...');

                    setTimeout(function() {
                        if (window.capturedBookneticInstance && window.capturedBookneticInstance.cartArr) {
                            var cart = window.capturedBookneticInstance.cartArr;
                            if (cart.length > 1) {
                                var firstItem = cart[0];
                                var locId = firstItem.location;
                                var staffId = firstItem.staff;
                                var selectedDate = firstItem.date;
                                var selectedTime = firstItem.time;
                                var customerId = firstItem.customer_id;
                                
                                for (var i = 1; i < cart.length; i++) {
                                    cart[i].location = locId;
                                    cart[i].staff = staffId;
                                    cart[i].date = selectedDate;
                                    cart[i].time = selectedTime;
                                    cart[i].customer_id = customerId;
                                }
                                console.log('[Wizard Multi-Service] Final sync of location, staff, date, time, customer_id to all items:', cart);
                            }
                        }
                        
                        if (window.capturedBookneticInstance && window.capturedBookneticInstance.stepManager) {
                            window.capturedBookneticInstance.stepManager.goForward();
                        } else {
                            $('.booknetic_appointment .booknetic_next_step_btn').click();
                        }
                    }, 200);
                });
                
                // Back buttons
                $('#bkntc_back_step_2').on('click', function() {
                    goToWizardStep(1);
                });
                $('#bkntc_back_step_3').on('click', function() {
                    goToWizardStep(2);
                });
                $('#bkntc_back_step_4').on('click', function() {
                    goToWizardStep(3);
                });
                $('#bkntc_back_step_5').on('click', function() {
                    goToWizardStep(4);
                });
                $('#bkntc_back_step_6').on('click', function() {
                    goToWizardStep(5);
                });

                // Toggling views when click on "Make an appointment"
                $('.book-scroll-btn').on('click', function(e) {
                    e.preventDefault();
                    
                    var serviceCard = $(this).closest('.bkntc-service-card');
                    var hasPreselectedService = false;
                    
                    if (serviceCard.length) {
                        var serviceId = serviceCard.data('id');
                        var wizardServiceCard = $('.bkntc-wizard-service-card[data-id="' + serviceId + '"]');
                        if (wizardServiceCard.length) {
                            wizardServiceCard.click();
                            hasPreselectedService = true;
                            console.log('[Wizard Direct Book] Pre-selected service ID:', serviceId);
                        }
                    }
                    
                    $('#bkntc_landing_main_view').hide();
                    $('#bkntc_booking_wizard_view').fadeIn(300);
                    
                    // Initialize first category accordion item to be open by default
                    $('.bkntc-accordion-item').removeClass('open').find('.bkntc-accordion-content').hide();
                    $('.bkntc-accordion-item').first().addClass('open').find('.bkntc-accordion-content').show();
                    
                    if (hasSingleLocation) {
                        if (hasPreselectedService) {
                            // Skip Service step and go directly to Staff selection
                            goToWizardStep(3);
                        } else {
                            goToWizardStep(2);
                        }
                    } else {
                        goToWizardStep(1);
                    }
                    $('html, body').animate({
                        scrollTop: $('#bkntc_booking_wizard_view').offset().top - 40
                    }, 300);
                });

                // Monitor time slot selection in native widget is no longer disabling step 4 next button.

                // Mobile Wizard Navigation Binds
                $('#bkntc_mobile_next').on('click', function() {
                    $('#bkntc_next_step_' + currentStep).click();
                });
                
                $('#bkntc_mobile_prev').on('click', function() {
                    $('#bkntc_back_step_' + currentStep).click();
                });

                function updateMobileFooter() {
                    var mobileFooter = $('.bkntc-mobile-wizard-footer');
                    if (!mobileFooter.length) return;
                    
                    var dotsContainer = mobileFooter.find('.bkntc-mobile-dots');
                    dotsContainer.empty();
                    
                    var visibleSteps = [];
                    $('.bkntc-stepper-header .bkntc-step-indicator').each(function() {
                        if ($(this).css('display') !== 'none') {
                            visibleSteps.push($(this).data('step'));
                        }
                    });
                    
                    visibleSteps.forEach(function(stepNum) {
                        var isActive = (stepNum === currentStep);
                        var dot = $('<span class="bkntc-mobile-dot"></span>');
                        dot.attr('data-step', stepNum);
                        if (isActive) {
                            dot.addClass('active');
                        }
                        dotsContainer.append(dot);
                    });
                    
                    if (currentStep === visibleSteps[0]) {
                        $('#bkntc_mobile_prev').css('visibility', 'hidden');
                    } else {
                        $('#bkntc_mobile_prev').css('visibility', 'visible');
                    }
                    
                    if (currentStep === 6) {
                        $('#bkntc_mobile_next').css('visibility', 'hidden');
                    } else {
                        $('#bkntc_mobile_next').css('visibility', 'visible');
                    }
                }

                // Poll active Next button state to sync disabled attribute on mobile footer is no longer needed since buttons remain enabled.

                // Sync body active state to hide/show theme footer on mobile
                setInterval(function() {
                    if ($('#bkntc_booking_wizard_view').is(':visible')) {
                        $('body').addClass('bkntc-booking-active');
                    } else {
                        $('body').removeClass('bkntc-booking-active');
                    }
                }, 250);

                // Initial run
                updateMobileFooter();

                // Sync Calendar Helper
                var isSyncingCalendar = false;
                function syncCalendarWrapper() {
                    if (isSyncingCalendar) return;
                    isSyncingCalendar = true;
                    
                    var nativeCalendar = $('.booknetic_appointment_container_body [data-step-id="date_time"]');
                    if (nativeCalendar.length) {
                        // Clone native calendar
                        var clone = nativeCalendar.clone();
                        clone.removeClass('booknetic_hidden').css({
                            'display': 'block',
                            'visibility': 'visible'
                        });
                        $('#bkntc_custom_calendar_wrapper').empty().append(clone);
                        console.log('[Wizard Sync] Calendar clone synced.');
                    }
                    isSyncingCalendar = false;
                }

                // Watch for any changes in the native calendar (e.g. AJAX month load) and sync them automatically
                var observer = new MutationObserver(function(mutations) {
                    console.log('[Wizard Observer] Native calendar DOM changed, syncing...');
                    syncCalendarWrapper();
                });
                
                // Safe observer binding: observe the parent container which is always in the DOM
                var parentNode = $('.booknetic_appointment')[0];
                if (parentNode) {
                    observer.observe(parentNode, { attributes: true, childList: true, subtree: true });
                    console.log('[Wizard Observer] Observer attached to parent .booknetic_appointment successfully.');
                } else {
                    // Fallback polling to attach observer on parent container once loaded
                    var attachAttempts = 0;
                    var attachInterval = setInterval(function() {
                        attachAttempts++;
                        parentNode = $('.booknetic_appointment')[0];
                        if (parentNode) {
                            clearInterval(attachInterval);
                            observer.observe(parentNode, { attributes: true, childList: true, subtree: true });
                            console.log('[Wizard Observer] Observer attached to parent successfully on attempt ' + attachAttempts);
                        } else if (attachAttempts >= 30) {
                            clearInterval(attachInterval);
                        }
                    }, 300);
                }

                // Proxy click events from the clone back to the native calendar (so delegation works!)
                $(document).on('click', '#bkntc_custom_calendar_wrapper .booknetic_calendar_days:not(.booknetic_calendar_empty_day)[data-date]', function(e) {
                    var date = $(this).attr('data-date');
                    console.log('[Wizard Proxy] Day clicked:', date);
                    var nativeEl = $('.booknetic_appointment_container_body [data-step-id="date_time"] .booknetic_calendar_days[data-date="' + date + '"]');
                    if (nativeEl.length) {
                        nativeEl.click();
                    }
                });

                $(document).on('click', '#bkntc_custom_calendar_wrapper .booknetic_prev_month', function(e) {
                    e.preventDefault();
                    console.log('[Wizard Proxy] Prev month clicked');
                    var nativeEl = $('.booknetic_appointment_container_body [data-step-id="date_time"] .booknetic_prev_month');
                    if (nativeEl.length) {
                        nativeEl.click();
                    }
                });

                $(document).on('click', '#bkntc_custom_calendar_wrapper .booknetic_next_month', function(e) {
                    e.preventDefault();
                    console.log('[Wizard Proxy] Next month clicked');
                    var nativeEl = $('.booknetic_appointment_container_body [data-step-id="date_time"] .booknetic_next_month');
                    if (nativeEl.length) {
                        nativeEl.click();
                    }
                });

                $(document).on('click', '#bkntc_custom_calendar_wrapper .booknetic_times_list > div', function(e) {
                    var time = $(this).attr('data-time');
                    console.log('[Wizard Proxy] Time slot clicked:', time);
                    var nativeEl = $('.booknetic_appointment_container_body [data-step-id="date_time"] .booknetic_times_list > div[data-time="' + time + '"]');
                    if (nativeEl.length) {
                        nativeEl.click();
                        setTimeout(function() {
                            // Auto-advance to details input step
                            $('#bkntc_next_step_4').click();
                        }, 200);
                    }
                });

                // Lightbox Image Gallery Slider Logic
                var galleryImages = <?php echo json_encode($gallery); ?>;
                var currentImageIndex = 0;

                // Bind click events on gallery elements
                $('.bkntc-gallery-stilio img, .bkntc-gallery-fallback-1 img, .bkntc-gallery-overlay').on('click', function(e) {
                    e.preventDefault();
                    
                    var clickedSrc = '';
                    if ($(this).hasClass('bkntc-gallery-overlay')) {
                        // Open on the third image (index 2)
                        clickedSrc = galleryImages[2] || galleryImages[0];
                    } else {
                        clickedSrc = $(this).attr('src');
                    }

                    var index = galleryImages.indexOf(clickedSrc);
                    if (index === -1) {
                        // Find matching index via substring
                        for(var i = 0; i < galleryImages.length; i++){
                            if(clickedSrc.indexOf(galleryImages[i]) > -1 || galleryImages[i].indexOf(clickedSrc) > -1){
                                index = i;
                                break;
                            }
                        }
                    }
                    if (index === -1) index = 0;

                    openLightbox(index);
                });

                function openLightbox(index) {
                    if (!galleryImages || galleryImages.length === 0) return;
                    currentImageIndex = index;
                    updateLightboxImage();
                    $('#bkntc_lightbox_modal').css('display', 'flex');
                }

                function updateLightboxImage() {
                    var imgSrc = galleryImages[currentImageIndex];
                    $('#bkntc_lightbox_image').attr('src', imgSrc);
                    $('#bkntc_lightbox_counter').text((currentImageIndex + 1) + ' / ' + galleryImages.length);
                }

                // Vanilla JS Touch Swipe support
                var touchStartX = 0;
                var touchEndX = 0;
                var lightboxImgEl = document.getElementById('bkntc_lightbox_image');
                if (lightboxImgEl) {
                    lightboxImgEl.addEventListener('touchstart', function(e) {
                        touchStartX = e.changedTouches[0].screenX;
                    }, { passive: true });
                    
                    lightboxImgEl.addEventListener('touchend', function(e) {
                        touchEndX = e.changedTouches[0].screenX;
                        var threshold = 50; // swipe threshold in pixels
                        if (touchEndX < touchStartX - threshold) {
                            // Swiped Left -> Next Image
                            $('#bkntc_lightbox_next').click();
                        } else if (touchEndX > touchStartX + threshold) {
                            // Swiped Right -> Prev Image
                            $('#bkntc_lightbox_prev').click();
                        }
                    }, { passive: true });
                }

                $('#bkntc_lightbox_close').on('click', function() {
                    $('#bkntc_lightbox_modal').hide();
                });

                // Backdrop Click Dismiss
                $('#bkntc_lightbox_modal').on('click', function(e) {
                    if (e.target === this) {
                        $(this).hide();
                    }
                });

                $('#bkntc_lightbox_prev').on('click', function(e) {
                    e.stopPropagation();
                    currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
                    updateLightboxImage();
                });

                $('#bkntc_lightbox_next').on('click', function(e) {
                    e.stopPropagation();
                    currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
                    updateLightboxImage();
                });

                // Leave a Review Modal Interaction
                $('#bkntc_leave_review_btn').on('click', function(e) {
                    e.preventDefault();
                    $('#bkntc_review_modal').css('display', 'flex');
                });

                $('#bkntc_review_close').on('click', function() {
                    $('#bkntc_review_modal').hide();
                });

                $('#bkntc_review_modal').on('click', function(e) {
                    if (e.target === this) {
                        $(this).hide();
                    }
                });

                // Star Rating Selection
                $('#bkntc_rating_stars .star-rating-item').on('click', function() {
                    var rating = parseInt($(this).attr('data-value'));
                    $('#bkntc_review_rating_val').val(rating);
                    $('#bkntc_rating_stars .star-rating-item').each(function(idx) {
                        if (idx < rating) {
                            $(this).attr('fill', 'currentColor');
                        } else {
                            $(this).attr('fill', 'none');
                        }
                    });
                });

                // Submit Review Form
                $('#bkntc_review_form').on('submit', function(e) {
                    e.preventDefault();
                    var formData = $(this).serialize();
                    var submitBtn = $(this).find('button[type="submit"]');
                    submitBtn.prop('disabled', true).text('Submitting...');
                    
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: formData,
                        success: function(response) {
                            submitBtn.prop('disabled', false).text('Submit Review');
                            if (response.success) {
                                $('#bkntc_review_modal').hide();
                                $('#bkntc_review_form')[0].reset();
                                
                                // Reset stars to 5 stars filled
                                $('#bkntc_rating_stars .star-rating-item').attr('fill', 'currentColor');
                                $('#bkntc_review_rating_val').val('5');
                                
                                // Show custom toast
                                var toast = $('#bkntc_toast_notification');
                                toast.find('span').text('Thank you! Your review has been submitted.');
                                toast.addClass('show');
                                setTimeout(function() {
                                    toast.removeClass('show');
                                    location.reload(); // Reload page to display new review
                                }, 2000);
                            } else {
                                alert(response.data || 'Failed to submit review.');
                            }
                        },
                        error: function() {
                            submitBtn.prop('disabled', false).text('Submit Review');
                            alert('An error occurred. Please try again.');
                        }
                    });
                });

                // Keyboard controls
                $(document).on('keydown', function(e) {
                    if ($('#bkntc_review_modal').is(':visible') && e.key === 'Escape') {
                        $('#bkntc_review_modal').hide();
                    }
                    if ($('#bkntc_lightbox_modal').is(':visible')) {
                        if (e.key === 'Escape') {
                            $('#bkntc_lightbox_modal').hide();
                        } else if (e.key === 'ArrowLeft') {
                            $('#bkntc_lightbox_prev').click();
                        } else if (e.key === 'ArrowRight') {
                            $('#bkntc_lightbox_next').click();
                        }
                    }
                });

                window.booknetic_on_google_auth = function(data) {
                    if (data) {
                        if (data.first_name || data.last_name) {
                            var fullName = ((data.first_name || '') + ' ' + (data.last_name || '')).trim();
                            $('#bkntc_custom_info_wrapper input[name="first_name"]').val(fullName).trigger('change');
                        }
                        if (data.email) {
                            $('#bkntc_custom_info_wrapper input[name="email"]').val(data.email).trigger('change');
                            $('.booknetic_appointment_container_body [data-step-id="confirm_details"] input[name="email"]').val(data.email);
                        }
                    }
                };

                // Customer Google Sign-In handler
                $(document).on('click', '.bkntc-customer-google-login-btn', function() {
                    var loginUrl = '<?php echo site_url() . "/?" . \BookneticApp\Providers\Helpers\Helper::getSlugName() . "_action=google_login"; ?>';
                    window.open(loginUrl, 'Google Login', 'width=500,height=600');
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }

    public static function submitReview()
    {
        $tenant_id = intval($_POST['tenant_id'] ?? 0);
        $author_name = sanitize_text_field($_POST['author_name'] ?? '');
        $rating = intval($_POST['rating'] ?? 5);
        $review_text = sanitize_textarea_field($_POST['review_text'] ?? '');

        if ($tenant_id <= 0 || empty($author_name) || empty($review_text)) {
            wp_send_json_error('Please fill in all fields.');
        }

        if ($rating < 1 || $rating > 5) {
            wp_send_json_error('Invalid rating.');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'bkntc_tenant_reviews';

        $inserted = $wpdb->insert($table_name, [
            'tenant_id' => $tenant_id,
            'author_name' => $author_name,
            'rating' => $rating,
            'review_text' => $review_text,
            'created_at' => current_time('mysql')
        ]);

        if ($inserted) {
            wp_send_json_success('Review submitted successfully.');
        } else {
            wp_send_json_error('Failed to save review to database.');
        }
    }
}
?>
