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

        $dataTable = new DataTableUI(new Staff());

        $dataTable->addAction('enable', bkntc__('Enable'), function ($ids) {
            Staff::where('id', 'in', $ids)->update([ 'is_active' => 1 ]);
        }, AbstractDataTableUI::ACTION_FLAG_BULK);
        $dataTable->addAction('disable', bkntc__('Disable'), function ($ids) {
            Staff::where('id', 'in', $ids)->update([ 'is_active' => 0 ]);
        }, AbstractDataTableUI::ACTION_FLAG_BULK);

        $dataTable->addAction('edit', bkntc__('Edit'));

        $dataTable->addAction(
            'duplicate',
            bkntc__('Duplicate'),
            [ $this, 'duplicate' ],
            AbstractDataTableUI::ACTION_FLAG_SINGLE
        );

        $dataTable->addAction('share', bkntc__('Share'));

        if (Permission::isAdministrator() || Capabilities::userCan('staff_delete')) {
            $dataTable->addAction(
                'delete',
                bkntc__('Delete'),
                [ $this, 'delete_staff' ],
                AbstractDataTableUI::ACTION_FLAG_SINGLE | AbstractDataTableUI::ACTION_FLAG_BULK
            );
        }

        $dataTable->setTitle(bkntc__('Staff'));

        if (Permission::isAdministrator() || Capabilities::userCan('staff_add')) {
            $dataTable->addNewBtn(bkntc__('ADD STAFF'));
        }

        $dataTable->searchBy([ "name", 'email', 'phone_number' ]);

        $dataTable->addColumns(bkntc__('ID'), 'id');
        $dataTable->addColumns(
            bkntc__('STAFF NAME'),
            fn ($staff) => Helper::profileCard($staff[ 'name' ], $staff[ 'profile_image' ], '', 'staff'),
            [ 'is_html' => true, 'order_by_field' => "name" ]
        );
        $dataTable->addColumns(bkntc__('EMAIL'), 'email');
        $dataTable->addColumns(bkntc__('PHONE'), 'phone_number');

        $table = $dataTable->renderHTML();

        $edit = Helper::_get('edit', '0', 'int');

        add_filter('bkntc_localization', function ($localization) {
            $localization[ 'delete_associated_wordpress_account' ] = bkntc__('Delete associated WordPress account');
            $localization[ 'link_copied' ]                         = bkntc__('Link copied!');

            return $localization;
        });

        $this->view('index', [
            'table' => $table,
            'edit'  => $edit
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
