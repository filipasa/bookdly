<?php

namespace BookneticAddon\Tenantdirectory\Backend;

use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Request\Post;
use BookneticApp\Providers\Core\Permission;
use BookneticAddon\Tenantdirectory\Model\TenantDirectory;
use BookneticAddon\Tenantdirectory\Model\BusinessType;
use BookneticAddon\Tenantdirectory\Model\Keyword;
use BookneticAddon\Tenantdirectory\Model\TenantDirectoryKeyword;

class Ajax extends Controller
{
    // === Tenant Dashboard Actions ===
    public function save_landing_page()
    {
        $tenantId = Permission::tenantId();
        if (!$tenantId) {
            return $this->response(false, 'Unauthorized tenant!');
        }

        $title = Post::string('title');
        $business_type_id = Post::int('business_type_id');
        $price_range_type = Post::string('price_range_type', 'min_max', ['min_max', 'level']);
        $price_min = Post::float('price_min');
        $price_max = Post::float('price_max');
        $price_level = Post::string('price_level', '$', ['$', '$$', '$$$', '$$$$']);
        $gallery = Post::array('gallery');
        $contact_email = Post::string('contact_email');
        $contact_phone = Post::string('contact_phone');
        $social_links = Post::array('social_links');
        $description = Post::string('description');
        $submit_action = Post::string('submit_action', 'save'); // 'save' or 'request_review' or 'cancel_request'

        // Validation
        if (empty($title) || empty($business_type_id)) {
            return $this->response(false, 'Please fill in all required fields.');
        }

        // Check if gallery image is required
        $gallery_required = Helper::getOption('tenant_directory_gallery_required', 'off') === 'on';
        if ($gallery_required && empty($gallery)) {
            return $this->response(false, 'At least one gallery image is required.');
        }

        $directoryData = [
            'title' => $title,
            'business_type_id' => $business_type_id,
            'price_range_type' => $price_range_type,
            'price_min' => $price_min,
            'price_max' => $price_max,
            'price_level' => $price_level,
            'gallery' => json_encode($gallery),
            'contact_email' => $contact_email,
            'contact_phone' => $contact_phone,
            'social_links' => json_encode($social_links),
            'description' => $description
        ];

        if ($submit_action === 'request_review') {
            $directoryData['status'] = 'pending';
        } elseif ($submit_action === 'cancel_request') {
            $directoryData['status'] = 'draft';
        }

        // Find or create directory entry for the tenant
        $directory = TenantDirectory::noTenant()->where('tenant_id', $tenantId)->fetch();
        if ($directory) {
            TenantDirectory::noTenant()->where('tenant_id', $tenantId)->update($directoryData);
            $directoryId = $directory->id;
        } else {
            $directoryData['tenant_id'] = $tenantId;
            TenantDirectory::noTenant()->insert($directoryData);
            $directoryId = TenantDirectory::lastId();
        }

        // Keywords mapping
        $keywords = Post::array('keywords');
        TenantDirectoryKeyword::where('directory_id', $directoryId)->delete();
        foreach ($keywords as $kwName) {
            $kwName = trim(sanitize_text_field($kwName));
            if (empty($kwName)) continue;
            
            // Get or insert keyword
            $keyword = Keyword::where('name', $kwName)->fetch();
            if ($keyword) {
                $keywordId = $keyword->id;
            } else {
                Keyword::insert(['name' => $kwName]);
                $keywordId = Keyword::lastId();
            }
            
            TenantDirectoryKeyword::insert([
                'directory_id' => $directoryId,
                'keyword_id' => $keywordId
            ]);
        }

        return $this->response(true, [
            'message' => 'Landing page saved successfully.'
        ]);
    }

    public function upload_gallery_image()
    {
        $tenantId = Permission::tenantId();
        if (!$tenantId) {
            return $this->response(false, 'Unauthorized tenant!');
        }

        if (empty($_FILES['file'])) {
            return $this->response(false, 'No file uploaded.');
        }

        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $uploadedfile = $_FILES['file'];
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            return $this->response(true, [
                'url' => $movefile['url']
            ]);
        } else {
            return $this->response(false, $movefile['error']);
        }
    }

    // === Super-Admin Settings Actions ===
    public function settings_view()
    {
        try {
            $businessTypes = BusinessType::query()->orderBy('sort_number')->fetchAll();
            foreach ($businessTypes as $type) {
                $type->in_use_count = TenantDirectory::noTenant()->where('business_type_id', $type->id)->count();
            }

            $keywords = Keyword::query()->fetchAll();
            foreach ($keywords as $keyword) {
                $keyword->in_use_count = TenantDirectoryKeyword::where('keyword_id', $keyword->id)->count();
            }

            $pendingRequests = TenantDirectory::noTenant()
                ->leftJoin('business_type', ['name'])
                ->fetchAll();

            // Get tenant names for display
            $tenantsData = [];
            if (class_exists('BookneticSaaS\Models\Tenant')) {
                $tenants = \BookneticSaaS\Models\Tenant::query()->fetchAll();
                foreach ($tenants as $tenant) {
                    $tenantsData[$tenant->id] = $tenant;
                }
            }

            // Get WordPress pages list
            $wpPages = get_pages();
            $pagesList = [];
            foreach ($wpPages as $wpPage) {
                $pagesList[$wpPage->ID] = $wpPage->post_title;
            }

            $parameters = [
                'businessTypes' => $businessTypes,
                'keywords' => $keywords,
                'pendingRequests' => $pendingRequests,
                'tenants' => $tenantsData,
                'pages' => $pagesList,
                'tenant_page_id' => Helper::getOption('tenant_directory_page_id', ''),
                'search_page_id' => Helper::getOption('tenant_directory_search_page_id', ''),
                'price_format' => Helper::getOption('tenant_directory_price_format', 'min_max'),
                'gallery_required' => Helper::getOption('tenant_directory_gallery_required', 'off')
            ];

            ob_start();
            $this->view('modal.settings', $parameters, false, true);
            $html = ob_get_clean();

            // Use ENT_COMPAT to match Booknetic JS htmlspecialchars_decode() which only decodes double quotes
            return $this->response(true, [
                'html' => htmlspecialchars($html, ENT_COMPAT, 'UTF-8', true)
            ]);
        } catch (\Exception $e) {
            return $this->response(false, 'PHP Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    public function settings_save()
    {
        $page_id = Post::int('tenant_directory_page_id');
        $search_page_id = Post::int('tenant_directory_search_page_id');
        $price_format = Post::string('tenant_directory_price_format', 'min_max', ['min_max', 'level']);
        $gallery_required = Post::string('tenant_directory_gallery_required', 'off', ['on', 'off']);

        Helper::setOption('tenant_directory_page_id', $page_id);
        Helper::setOption('tenant_directory_search_page_id', $search_page_id);
        Helper::setOption('tenant_directory_price_format', $price_format);
        Helper::setOption('tenant_directory_gallery_required', $gallery_required);

        return $this->response(true, [
            'message' => 'Settings saved successfully.'
        ]);
    }

    public function business_types_save()
    {
        $id = Post::int('id');
        $name = Post::string('name');
        $sort_number = Post::int('sort_number');

        if (empty($name)) {
            return $this->response(false, 'Name is required.');
        }

        if ($id > 0) {
            $type = BusinessType::get($id);
            if (!$type) {
                return $this->response(false, 'Business type not found.');
            }
            BusinessType::where('id', $id)->update([
                'name' => $name,
                'sort_number' => $sort_number
            ]);
            $savedId = $id;
        } else {
            BusinessType::insert([
                'name' => $name,
                'sort_number' => $sort_number
            ]);
            $savedId = BusinessType::lastId();
        }

        return $this->response(true, [
            'message' => 'Business type saved successfully.',
            'id' => (int)$savedId,
            'name' => $name,
            'sort_number' => (int)$sort_number
        ]);
    }

    public function business_types_delete()
    {
        $id = Post::int('id');
        if ($id > 0) {
            // Check usage
            $inUseCount = TenantDirectory::noTenant()->where('business_type_id', $id)->count();
            if ($inUseCount > 0) {
                return $this->response(false, 'This business type is currently in use and cannot be deleted.');
            }
            BusinessType::where('id', $id)->delete();
        }
        return $this->response(true, [
            'message' => 'Business type deleted successfully.'
        ]);
    }

    public function keywords_save()
    {
        $id = Post::int('id');
        $name = Post::string('name');

        if (empty($name)) {
            return $this->response(false, 'Name is required.');
        }

        if ($id > 0) {
            $keyword = Keyword::get($id);
            if (!$keyword) {
                return $this->response(false, 'Keyword not found.');
            }
            Keyword::where('id', $id)->update([
                'name' => $name
            ]);
            $savedId = $id;
        } else {
            Keyword::insert([
                'name' => $name
            ]);
            $savedId = Keyword::lastId();
        }

        return $this->response(true, [
            'message' => 'Keyword saved successfully.',
            'id' => (int)$savedId,
            'name' => $name
        ]);
    }

    public function keywords_delete()
    {
        $id = Post::int('id');
        if ($id > 0) {
            Keyword::where('id', $id)->delete();
            TenantDirectoryKeyword::where('keyword_id', $id)->delete();
        }
        return $this->response(true, [
            'message' => 'Keyword deleted successfully.'
        ]);
    }

    public function review_directory_request()
    {
        $id = Post::int('id');
        $status = Post::string('status', 'draft', ['draft', 'pending', 'approved', 'rejected', 'hidden']);
        $notes = Post::string('review_notes');

        if ($id <= 0) {
            return $this->response(false, 'Invalid request ID.');
        }

        $directory = TenantDirectory::noTenant()->where('id', $id)->fetch();
        if (!$directory) {
            return $this->response(false, 'Tenant directory request not found.');
        }

        TenantDirectory::noTenant()->where('id', $id)->update([
            'status' => $status,
            'review_notes' => $notes
        ]);

        return $this->response(true, [
            'message' => 'Request updated successfully.'
        ]);
    }
}
?>
