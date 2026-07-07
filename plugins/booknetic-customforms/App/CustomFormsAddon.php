<?php

namespace BookneticAddon\Customforms;

use BookneticAddon\Customforms\Model\Form;
use BookneticAddon\Customforms\Model\FormInput;
use BookneticAddon\Customforms\Model\FormInputChoice;
use BookneticApp\Models\Appointment;
use BookneticApp\Config;
use BookneticApp\Models\Service;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\UI\TabUI;
use BookneticApp\Providers\UI\MenuUI;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Route;
use BookneticSaaS\Models\Tenant;
use BookneticAddon\Customforms\Model\AppointmentCustomData;

function bkntc__ ( $text, $params = [], $esc = true )
{
    return \bkntc__( $text, $params, $esc, CustomFormsAddon::getAddonSlug() );
}

class CustomFormsAddon extends AddonLoader
{

	public function init ()
	{
		Capabilities::registerTenantCapability( 'custom_forms', bkntc__('Custom Forms') );

		if( ! Capabilities::tenantCan( 'custom_forms' ) )
			return;

		add_action( 'bkntc_appointment_deleted', [ Listener::class, 'shared_on_appointment_deleted' ]);
        add_filter( 'bkntc_datatable_after_render', [ Listener::class , 'formsExportCsv'] , 10 , 2);
        add_filter( 'bkntc_conditional_prices_fields', [ Listener::class, 'initConditionalFields' ] );


        Capabilities::register('custom_forms', bkntc__('Custom Forms'));
        Capabilities::register('custom_forms_add', bkntc__('Add New') , 'custom_forms');
        Capabilities::register('custom_forms_edit', bkntc__('Edit') , 'custom_forms');
        Capabilities::register('custom_forms_delete', bkntc__('Delete') , 'custom_forms');
        Capabilities::register('appointments_customforms_tab', bkntc__('Custom Forms Tab') , 'appointments');

		Config::getShortCodeService()->addReplacer([ Listener::class, 'replace_short_code_text' ]);
        Config::getShortCodeService()->registerShortCodesLazily([ Listener::class, 'registerShortCodes' ]);

		add_filter( 'bkntc_localization' , function ($lang){
		    return array_merge(
		        [
		            'select_services'				=> bkntc__('Select services...(empty for all services)'),
                    'changes_saved'					=> bkntc__('Changes has been saved!')
                ],
                $lang
            );
        });
    }

    public function initBackend ()
    {
	    if( ! Capabilities::tenantCan( 'custom_forms' ) )
		    return;

        Route::post( 'customforms', Backend\Ajax::class );

        if( Capabilities::userCan('custom_forms') )
        {
            Route::get( 'customforms', Backend\Controller::class );

            MenuUI::get( 'customforms' )
                  ->setTitle( bkntc__( 'Custom Forms' ) )
                  ->setIcon( 'fa fa-magic' )
                  ->setPriority( 920 );
        }

        // doit: action silindi, validate ucun yeni action var, AppointmentRequests::validate() methoduna baxin
        add_action( 'bkntc_before_appointment_created',    [ Listener::class,  'backend_validate' ]);
        add_action( 'bkntc_appointment_created', [ Listener::class, 'backend_on_appointment_created' ] );

        add_action( 'bkntc_appointment_before_edit', [ Listener::class,  'backend_validate' ]);
        add_action( 'bkntc_appointment_after_edit', [ Listener::class,  'backend_on_appointment_edited' ] );

        add_action( 'bkntc_enqueue_assets', [ self::class, 'enqueueAssets' ], 10, 2 );

        if( Capabilities::userCan('appointments_customforms_tab') )
        {
            TabUI::get( 'appointments_info' )
                ->item( 'customforms' )
                ->setTitle( bkntc__( 'Custom fields' ) )
                ->addView( __DIR__ . '/Backend/view/tabs/appointment_info_fields.php', [ Listener::class, 'backend_add_info_tab' ] );

            TabUI::get( 'appointments_edit' )
                ->item( 'custom_fields_edit' )
                ->setTitle( bkntc__( 'Custom fields' ) )
                ->addView( __DIR__ . '/Backend/view/tabs/appointment_edit_fields.php' )
                ->setPriority( 3 );

            TabUI::get( 'appointments_add_new' )
                ->item( 'custom_fields_add' )
                ->setTitle( bkntc__( 'Custom fields' ) )
                ->addView( __DIR__ . '/Backend/view/tabs/appointment_add_fields.php' )
                ->setPriority( 3 );
        }

        Service::onDeleted( function ( $serviceId )
        {
            DB::DB()->query( DB::DB()->prepare("UPDATE `".DB::table('forms')."` SET service_ids=TRIM(BOTH ',' FROM REPLACE(CONCAT(',',`service_ids`,','),%s,'')) WHERE FIND_IN_SET(%d, `service_ids`)", [",{$serviceId},", $serviceId]) );

        });

        add_filter('bkntc_add_tables_for_export', [ self::class, 'getAddonTables' ]);
    }

    public function initFrontend ()
    {
	    if( ! Capabilities::tenantCan( 'custom_forms' ) )
		    return;

        $this->setFrontendAjaxController( Frontend\Ajax::class );

        add_filter('bkntc_frontend_localization',function ($localization) {
            $localization['min_length'] = bkntc__('Minimum length of "%s" field is %d!');
            $localization['max_length'] = bkntc__('Maximum length of "%s" field is %d!');
            return $localization;
        });

        add_action('bkntc_after_booking_panel_shortcode', function ()
        {
            wp_enqueue_script( 'booknetic-customforms-init-conditions', self::loadAsset( 'assets/frontend/js/init_conditions.js' ), [ 'booknetic' ] );
            wp_enqueue_script( 'booknetic-customforms-init', self::loadAsset( 'assets/frontend/js/init.js' ), [ 'booknetic' ] );
            wp_enqueue_style( 'booknetic-customforms-init', self::loadAsset( 'assets/frontend/css/custom_forms.css' ), [ 'booknetic' ] );
        });

        add_action( 'bkntc_after_information_inputs',           [ Listener::class, 'frontend_render_ui']);
        add_action( 'bkntc_appointment_request_data_validate',  [ Listener::class, 'frontend_validate' ] );
        add_action( 'bkntc_appointment_created',                [ Listener::class, 'frontend_on_appointment_created' ], 2 );

        add_filter( 'bkntc_add_files_through_ajax', [ self::class, 'addFilesThroughAjax' ] );
    }

    public function initSaaSBackend()
    {
		Tenant::onDeleting( [ Listener::class, 'beforeTenantDelete' ] );
    }


    public static function addFilesThroughAjax ( $result )
    {
        $result[ 'files' ] = array_merge( $result[ 'files' ], [
            [
                'type' => 'js',
                'src'  => self::loadAsset( 'assets/frontend/js/init_conditions.js' ),
                'id'   => 'booknetic-customforms-init-conditions',
            ],
            [
                'type' => 'js',
                'src'  => self::loadAsset( 'assets/frontend/js/init.js' ),
                'id'   => 'booknetic-customforms-init',
            ],
            [
                'type' => 'css',
                'src'  => self::loadAsset( 'assets/frontend/css/custom_forms.css' ),
                'id'   => 'booknetic-customforms-init',
            ]
        ] );

        return $result;
    }

    public static function enqueueAssets( $module, $action )
    {
        if ( $module === 'conditional_prices' && $action === 'add_new' )
        {
            $customFields = FormInput::select([ 'id', 'type' ])->fetchAll();

//            $customFields = null;

            echo '<script type="application/javascript">var customConditionFields = ' . json_encode( $customFields ) . ';</script>';

            echo '<script type="application/javascript" src="' . self::loadAsset('assets/backend/js/conditional_prices.js') . '"></script>';
        }
    }

    public static function getAddonTables($tables)
    {
        return array_merge($tables, [
            AppointmentCustomData::getTableName(),
            Form::getTableName(),
            FormInput::getTableName(),
            FormInputChoice::getTableName()
        ] );
    }
}
