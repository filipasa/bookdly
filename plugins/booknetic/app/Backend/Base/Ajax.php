<?php

namespace BookneticApp\Backend\Base;

use BookneticApp\Backend\Base\Services\TranslationService;
use BookneticApp\Backend\Mobile\Clients\FSCodeMobileAppClient;
use BookneticApp\Backend\Mobile\Exceptions\AppPasswordCreatingException;
use BookneticApp\Backend\Settings\Helpers\LocalizationService;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Core\Templates\Applier;
use BookneticApp\Providers\FSCode\Clients\FSCodeAPIClient;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\NotificationHelper;
use BookneticApp\Providers\Helpers\Session;
use BookneticApp\Providers\IoC\Container;
use BookneticApp\Providers\Request\Post;
use ReflectionException;
use RuntimeException;
use WP_Application_Passwords;
use WP_User;

class Ajax extends Controller
{
    private TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    public function switch_language()
    {
        if (!Helper::isSaaSVersion()) {
            return $this->response(false);
        }

        $language = Helper::_post('language', '', 'string');

        if (LocalizationService::isLngCorrect($language)) {
            Session::set('active_language', $language);
        }

        return $this->response(true);
    }

    public function ping()
    {
        return $this->response(true);
    }

    public function direct_link()
    {
        $service_id     = Helper::_post('service_id', 0, 'int');
        $staff_id       = Helper::_post('staff_id', 0, 'int');
        $location_id    = Helper::_post('location_id', 0, 'int');
        $categories = ServiceCategory::fetchAll();
        $services   = Service::fetchAll();
        $staff      = Staff::fetchAll();
        $locations  = Location::fetchAll();

        return $this->modalView('direct_link', compact('categories', 'services', 'staff', 'locations', 'service_id', 'staff_id', 'location_id'));
    }

    public function get_translations()
    {
        $rowId        = Post::int('row_id');
        $tableName    = Post::string('table');
        $columnName   = Post::string('column');
        $translations = Post::array('translations');
        $nodeType     = Post::string('node', 'input', [ 'input', 'textarea' ]);

        if (empty($tableName) || empty($columnName)) {
            return $this->response(false, [
                'message' => 'Fields are not correct',
            ]);
        }

        // translationlari elave edib, sonra modali baglayib yeniden translation modalini acanda inputun translation datasini gonderirikki db da saxlanilmayan translationlari gore bilek
        if (! empty($translations)) {
            return $this->modalView('translations', [
                'translations' => $translations,
                'node'         => $nodeType,
                'id'           => $rowId,
                'column'       => $columnName ,
                'table'        => $tableName
            ]);
        }

        if ($tableName === 'options') {
            $translations = $this->translationService->getAllForOption($columnName);
        } elseif ($rowId > 0) {
            $translations = $this->translationService->getAll($rowId, $columnName, $tableName);
        } else {
            $translations = [];
        }

        return $this->modalView("translations", [
            'translations' => $translations,
            'node'         => $nodeType,
            'id'           => $rowId,
            'column'       => $columnName,
            'table'        => $tableName
        ]);
    }

    public function save_translations()
    {
        Capabilities::mustTenant('dynamic_translations');

        $whiteList    = [ 'services', 'staff', 'service_categories' ,'locations', 'location_categories', 'service_extras', 'form_inputs', 'form_input_choices', 'taxes', 'options' ];
        $translations = Post::json('translations');
        $tableName    = Post::string('table_name', '', apply_filters('bkntc_whitelist_translation_tables', $whiteList));
        $columnName   = Post::string('column_name');
        $rowID        = Post::int('row_id');

        if (empty($tableName) || empty($columnName)) {
            return $this->response(false, [
                'message' => 'Please fill in all required fields correctly',
            ]);
        }

        $this->translationService->save($rowID, $columnName, $tableName, $translations);

        return $this->response(200, [
            'message' => bkntc__('Saved successfully')
        ]);
    }

    public function delete_translation()
    {
        $id = Post::int('id');

        if (empty($id)) {
            return $this->response(false);
        }

        $this->translationService->delete($id);

        return $this->response(true);
    }

    public function get_template_selection_modal()
    {
        if (! Helper::canShowTemplates()) {
            return $this->response(false);
        }

        $templates = $this->getTemplates();

        //if server/saas admin has no templates available, ignore the request and don't show the modal again
        if (! $templates) {
            //set `selected_a_template` option to true
            Helper::setOption('selected_a_template', 1);

            return $this->response(true);
        }

        return $this->modalView('template-selection', [
            'templates' => $templates,
        ]);
    }

    public function apply_template()
    {
        $id = Post::int('id');

        if (! $id) {
            return $this->response(false);
        }

        $template = $this->getTemplate($id);

        if (! $template) {
            return $this->response(false);
        }

        //create an applier instance
        $applier = new Applier($template);

        //apply the given template
        $applier->apply();

        //set `selected_a_template` option to true
        Helper::setOption('selected_a_template', 1);

        return $this->response(true);
    }

    public function skip_template_selection()
    {
        //set `selected_a_template` option to true
        Helper::setOption('selected_a_template', 1);

        return $this->response(true);
    }

    /**
     * @throws AppPasswordCreatingException
     * @throws ReflectionException
     */
    public function regenerate_password()
    {
        $user = wp_get_current_user();
        $seatId = Post::int('seatId');

        $userId = $user->ID;

        if ($userId === 0) {
            return $this->response(false);
        }

        $username = $user->user_login;

        $appPasswordOption = Helper::getOption('app_password', []);

        foreach ($appPasswordOption as $index => $password) {
            if ($password['seat_id'] !== $seatId) {
                continue;
            }

            WP_Application_Passwords::delete_application_password($password['user_id'], $password['uuid']);

            unset($appPasswordOption[$index]);
        }

        $client = Container::get(FSCodeMobileAppClient::class);
        $client->logoutSeat($seatId);

        $result = WP_Application_Passwords::create_new_application_password($userId, [ 'name' => 'booknetic_mobile_app' ]);

        if (empty($result) || is_wp_error($result)) {
            throw new AppPasswordCreatingException();
        }

        $appPasswordOption[] = [
            'seat_id' => $seatId,
            'uuid' =>  $result[1]['uuid'],
            'user_id' => $userId,
        ];

        Helper::setOption('app_password', $appPasswordOption);

        $appPassword = $result[0];

        return $this->response(true, [
            'app_password' => $appPassword,
            'username' => $username
        ]);
    }

    public function getAllByUsername()
    {
        $user = wp_get_current_user();

        if (!($user instanceof WP_User)) {
            throw new RuntimeException('User not found.');
        }

        $client = Container::get(FSCodeMobileAppClient::class);
        $response = $client->getSeatsByUsername($user->user_login);

        return $this->response(true, ['result' => $response->getAssignedSeats()]);
    }

    /**
     * @throws ReflectionException
     */
    public function join_beta()
    {
        $apiClient = Container::get(FSCodeAPIClient::class);

        $response = $apiClient->requestNew('booknetic/product/join_beta', 'POST');

        if ($response->isError()) {
            return $this->error($response->getErrorMessage());
        }

        Helper::setOption('joined_beta', true);

        return $this->success();
    }

    public function leave_beta()
    {
        $apiClient = Container::get(FSCodeAPIClient::class);

        $response = $apiClient->requestNew('booknetic/product/leave_beta', 'POST');

        if ($response->isError()) {
            return $this->error($response->getErrorMessage());
        }

        Helper::setOption('joined_beta', false);

        return $this->success();
    }

    public function dismiss_notification()
    {
        $slug = Post::string('slug');

        if (empty($slug)) {
            return $this->response(true);
        }

        $notifications = NotificationHelper::getAll();

        if (empty($notifications) || empty($notifications[ $slug ])) {
            return $this->success();
        }

        $notifications[ $slug ][ 'visible' ] = false;

        NotificationHelper::save($notifications);

        return $this->success();
    }

    public function dismiss_all_notifications()
    {
        $notifications = NotificationHelper::getAll();

        if (empty($notifications)) {
            return $this->success();
        }

        foreach ($notifications as $slug => $notification) {
            $notifications[ $slug ][ 'visible' ] = false;
        }

        NotificationHelper::save($notifications);

        return $this->success();
    }

    private function getTemplate(int $id)
    {
        if (Helper::isSaaSVersion()) {
            return apply_filters('bkntc_template_get', [], $id);
        }

        return [];
    }

    private function getTemplates()
    {
        if (Helper::isSaaSVersion()) {
            return apply_filters('bkntc_templates_get_all', []);
        }

        return [];
    }
}
