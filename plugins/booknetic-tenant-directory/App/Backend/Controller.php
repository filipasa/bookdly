<?php

namespace BookneticAddon\Tenantdirectory\Backend;

use BookneticApp\Providers\Core\Controller as BaseController;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Models\Location;
use BookneticAddon\Tenantdirectory\Model\TenantDirectory;
use BookneticAddon\Tenantdirectory\Model\BusinessType;
use BookneticAddon\Tenantdirectory\Model\Keyword;
use BookneticAddon\Tenantdirectory\Model\TenantDirectoryKeyword;

class Controller extends BaseController
{
    public function index()
    {
        $tenantId = Permission::tenantId();
        if (!$tenantId) {
            header('Location: admin.php?page=' . Helper::getSlugName() . '&module=dashboard');
            exit();
        }

        // Fetch current tenant directory details
        $directory = TenantDirectory::noTenant()->where('tenant_id', $tenantId)->fetch();
        
        $gallery = [];
        $socialLinks = [];
        $selectedKeywords = [];

        if ($directory) {
            $gallery = json_decode($directory->gallery, true) ?: [];
            $socialLinks = json_decode($directory->social_links, true) ?: [];
            
            // Get selected keywords
            $relations = TenantDirectoryKeyword::where('directory_id', $directory->id)->fetchAll();
            foreach ($relations as $rel) {
                $kw = Keyword::get($rel->keyword_id);
                if ($kw) {
                    $selectedKeywords[] = $kw->name;
                }
            }
        } else {
            $directory = (object)[
                'id' => 0,
                'title' => '',
                'business_type_id' => 0,
                'price_range_type' => 'min_max',
                'price_min' => '',
                'price_max' => '',
                'price_level' => '$',
                'description' => '',
                'contact_email' => '',
                'contact_phone' => '',
                'status' => 'draft',
                'review_notes' => ''
            ];
        }

        // Fetch global business types, keywords, and tenant locations
        $businessTypes = BusinessType::query()->orderBy('sort_number')->fetchAll();
        $keywordsList = Keyword::query()->fetchAll();
        $locations = Location::query()->where('is_active', 1)->fetchAll();

        $priceFormat = Helper::getOption('tenant_directory_price_format', 'min_max');

        $this->view('landing_page', [
            'directory' => $directory,
            'businessTypes' => $businessTypes,
            'keywordsList' => $keywordsList,
            'selectedKeywords' => $selectedKeywords,
            'locations' => $locations,
            'gallery' => $gallery,
            'socialLinks' => $socialLinks,
            'priceFormat' => $priceFormat
        ]);
    }
}
?>
