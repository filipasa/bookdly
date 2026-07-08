<?php

namespace BookneticApp\Backend\Staff\Controllers;

use BookneticApp\Backend\Staff\Exceptions\StaffNotFoundException;
use BookneticApp\Backend\Staff\Exceptions\StaffPermissionException;
use BookneticApp\Backend\Staff\Exceptions\StaffValidationException;
use BookneticApp\Backend\Staff\Services\StaffService;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\IoC\Container;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Request\Post;
use BookneticApp\Providers\UI\Abstracts\AbstractDataTableUI;
use BookneticApp\Providers\UI\DataTableUI;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;

class StaffController extends \BookneticApp\Providers\Core\Controller
{
    private StaffService $staffService;

    public function __construct()
    {
        $this->staffService = Container::get(StaffService::class);
    }

    /**
     * @throws CapabilitiesException
     */
    public function index()
    {
        Capabilities::must('staff');

        // Intercept DataTable actions manually since we bypass DataTableUI
        if (isset($_POST['fs-data-table-action'])) {
            $action = Post::string('fs-data-table-action');
            $ids = Post::array('ids');
            if ($action === 'delete') {
                return $this->delete_staff();
            }
            if ($action === 'duplicate') {
                $this->duplicate($ids);
                return $this->response(true);
            }
        }

        $edit = Helper::_get('edit', '0', 'int');

        add_filter('bkntc_localization', function ($localization) {
            $localization['delete_associated_wordpress_account'] = bkntc__('Delete associated WordPress account');
            $localization['link_copied']                         = bkntc__('Link copied!');
            return $localization;
        });

        $staffList = Staff::fetchAll();

        $this->view('index', [
            'staff' => $staffList,
            'edit'  => $edit,
        ]);
    }

    /**
     * @param int[] $ids
     */
    public function duplicate(array $ids)
    {
        Capabilities::must('staff_add');

        $this->staffService->duplicate($ids[0]);
    }

    /**
     * @throws StaffValidationException
     * @throws StaffNotFoundException
     * @throws StaffPermissionException
     */
    public function delete_staff()
    {
        if (!(Permission::isAdministrator() || Capabilities::userCan('staff_delete'))) {
            throw new StaffPermissionException(
                bkntc__('You do not have sufficient permissions to perform this action.')
            );
        }

        $ids = Post::array('ids');
        $deleteWpUser = (bool) Post::int('delete_wp_user', 1);

        $allowWpDelete = $deleteWpUser
            && (Permission::isAdministrator() || Capabilities::userCan('staff_delete_wordpress_account'));

        $result = $this->staffService->delete($ids, $allowWpDelete);

        return $this->response($result);
    }
}
