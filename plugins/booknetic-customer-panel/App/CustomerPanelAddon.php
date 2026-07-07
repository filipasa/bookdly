<?php

namespace BookneticAddon\Customerpanel;


use BookneticAddon\Customerpanel\Integrations\Divi\includes\CustomerPanelDivi;
use BookneticApp\Config;
use BookneticApp\Providers\UI\SettingsMenuUI;
use BookneticSaaS\Providers\UI\SettingsMenuUI as SaaSSettingsMenuUI;
use BookneticAddon\Customerpanel\Backend\Ajax;
use BookneticApp\Providers\Core\AddonLoader;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Core\Route;
use BookneticSaaS\Providers\Core\Route as SaaSRoute;

function bkntc__ ( $text, $params = [], $esc = true )
{
    return \bkntc__( $text, $params, $esc, CustomerPanelAddon::getAddonSlug() );
}

class CustomerPanelAddon extends AddonLoader
{

    public function __construct()
    {
        parent::__construct();
        add_action( 'divi_extensions_init', function (){ new CustomerPanelDivi(); } );
    }

    public function init ()
    {
		if( $this->isCustomerPanelEnabled() )
		{
			Capabilities::register( 'customer_panel_settings', bkntc__( 'Customer Panel settings' ), 'settings' );

            Config::getShortCodeService()->registerShortCode( 'customer_panel_url', [
				'name'      =>  bkntc__('Customer Panel URL'),
				'category'  =>  'others'
			] );

            Config::getShortCodeService()->addReplacer([Listener::class, 'replaceShortCode']);
		}
        Listener::initGutenbergBlocks();
    }

	public function initBackend ()
	{
		if ( $this->isCustomerPanelEnabled() && Capabilities::userCan('customer_panel_settings') )
        {
	        Route::post( 'customerpanel', Ajax::class, ['customer_panel_settings', 'save_customer_panel_settings'] );
            SettingsMenuUI::get( 'frontend' )
                          ->subItem( 'customer_panel_settings', 'customerpanel' )
                          ->setTitle( bkntc__( 'Customer Panel' ) )
                          ->setPriority( 3 );
        }
	}

    public function initSaaS()
    {
        add_action('bkntcsaas_share_page_footer', [Listener::class, 'saasSharePageFooter']);
    }

    public function initSaaSBackend ()
    {
        SaaSRoute::post( 'customerpanel', Ajax::class, ['save_customer_panel_settings_saas', 'customer_panel_settings_saas'] );

        SaaSSettingsMenuUI::get( 'customer_panel_settings_saas', 'customerpanel' )
                          ->setIcon( self::loadAsset('assets/backend/icons/customer-panel-settings.svg') )
                          ->setTitle( bkntc__( 'Customer Panel' ) )
                          ->setDescription( bkntc__('You can customize customer panel settings from here') )
                          ->setPriority( 3 );
	}

	public function initFrontend ()
    {
        add_shortcode('booknetic-cp', [ $this, 'customerPanelShortcode' ]);

	    if( Helper::getOption( 'customer_panel_enable', 'off', false ) != 'on' )
		    return;

	    $this->setFrontendAjaxController( Frontend\Ajax::class );
    }

	public function customerPanelShortcode()
	{
        if( Helper::getOption('customer_panel_enable', 'off', false) != 'on' )
			return '';

        wp_enqueue_script( 'intlTelInput-bkntc', Helper::assets('js/intlTelInput.min.js', 'front-end'), [ 'jquery' ] );
        wp_add_inline_script( 'intlTelInput-bkntc', 'window.intlTelInput = window.intlTelInput || window.bookneticIntlTelInput;' );
        wp_enqueue_script( 'custom-cp', CustomerPanelAddon::loadAsset('assets/frontend/js/custom.js'), [ 'jquery' ], time() );
		wp_enqueue_script( 'booknetic-cp', CustomerPanelAddon::loadAsset('assets/frontend/js/booknetic-cp.js'), [ 'jquery', 'intlTelInput-bkntc' ], time() );

		if( ! Permission::userId() )
		{
            //todo: Bu kod silinecek, bizde onsuzda wp_login_urller filterlenir, bizim sign-in pagelere.
            // Ona gore manual redirectlere ehtiyac yoxdur. birbasha wp_login_url( ::customerPanelURL() ) kifayet edir.
            $regularSingInPage = Helper::getOption('regular_sing_in_page', '', false);

            if( Helper::isSaaSVersion() && empty( $regularSingInPage ) )
			{
				$redirectUrl = get_permalink( Helper::getOption('sign_in_page', '', false) );
			}
			else
			{
                //why?
                if( ! empty( $regularSingInPage ) )
                {
                    $redirectUrl = get_permalink( $regularSingInPage ) . "?redirect_to=" . CustomerPanelHelper::customerPanelURL();
                }else{
                    $redirectUrl = wp_login_url( CustomerPanelHelper::customerPanelURL() );
                }
			}

			wp_add_inline_script( 'booknetic-cp', 'location.href="' . $redirectUrl . '";' );
			return bkntc__('Redirecting...');
		}


        wp_add_inline_script( 'booknetic-cp', 'const bkntc_preview=' . Helper::_any('bkntc_preview', 0, 'int'));

		wp_localize_script( 'booknetic-cp', 'BookneticData', [
			'ajax_url'		    => admin_url( 'admin-ajax.php' ),
			'assets_url'	    => Helper::assets('/', 'front-end') ,
			'date_format'	    => Helper::getOption('date_format', 'Y-m-d'),
			'week_starts_on'    => Helper::getOption('week_starts_on', 'sunday') == 'monday' ? 'monday' : 'sunday',
			'client_timezone'   => htmlspecialchars(Helper::getOption('client_timezone_enable', 'off')),
			'tz_offset_param'   => htmlspecialchars(Helper::_get('client_time_zone', '-', 'str')),
			'localization'      => [
				// months
				'January'               => bkntc__('January'),
				'February'              => bkntc__('February'),
				'March'                 => bkntc__('March'),
				'April'                 => bkntc__('April'),
				'May'                   => bkntc__('May'),
				'June'                  => bkntc__('June'),
				'July'                  => bkntc__('July'),
				'August'                => bkntc__('August'),
				'September'             => bkntc__('September'),
				'October'               => bkntc__('October'),
				'November'              => bkntc__('November'),
				'December'              => bkntc__('December'),

				//days of week
				'Mon'                   => bkntc__('Mon'),
				'Tue'                   => bkntc__('Tue'),
				'Wed'                   => bkntc__('Wed'),
				'Thu'                   => bkntc__('Thu'),
				'Fri'                   => bkntc__('Fri'),
				'Sat'                   => bkntc__('Sat'),
				'Sun'                   => bkntc__('Sun'),

				// select placeholders
				'select'                => bkntc__('Select...'),
				'searching'				=> bkntc__('Searching...'),
			]
		]);

		wp_enqueue_script( 'bootstrap', Helper::assets('js/bootstrap.min.js'), [ 'jquery' ] );
		wp_enqueue_script( 'bootstrap-datepicker-booknetic', Helper::assets('js/bootstrap-datepicker.min.js'), [ 'bootstrap' ] );
		wp_enqueue_script( 'select2-bkntc', Helper::assets('js/select2.min.js') );
		
        wp_enqueue_script( 'flatpickr-cp', CustomerPanelAddon::loadAsset('assets/frontend/js/flatpickr.js'), [ 'jquery' ] );

		wp_enqueue_style('Booknetic-font', '//fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap');

        wp_enqueue_style('booknetic-grid', CustomerPanelAddon::loadAsset('assets/frontend/css/booknetic-grid.css' ));
        wp_enqueue_style('booknetic-flatpickr', CustomerPanelAddon::loadAsset('assets/frontend/css/flatpickr.min.css' ));
        wp_enqueue_style('intlTelInput', Helper::assets('css/intlTelInput.min.css', 'front-end'));
        wp_enqueue_style('booknetic-override', CustomerPanelAddon::loadAsset('assets/frontend/css/override.css' ));
        wp_enqueue_style('booknetic-cp', CustomerPanelAddon::loadAsset('assets/frontend/css/custom.css' ), [], time() );
        wp_enqueue_style('booknetic-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');

       wp_enqueue_style('select2', Helper::assets('css/select2.min.css'));
       wp_enqueue_style('select2-bootstrap', Helper::assets('css/select2-bootstrap.css'));

        $customer = CustomerPanelHelper::myCustomer();
        $is_valid_customer = true;
        if ( is_null( $customer ) ) {
            $is_valid_customer = false;
            $customer = Permission::userInfo();
            $customer->email = $customer->user_email;
            $customer->first_name = $customer->user_nicename;
        }

        $viewResult = Helper::renderView( __DIR__ . '/Frontend/view/customer_panel.php', [
			'customer'          => $customer,
            'is_valid_customer' => $is_valid_customer
		] );

        do_action('bkntc_after_customer_panel_shortcode');

        return $viewResult;
    }

	private function isCustomerPanelEnabled()
	{
		return ! Helper::isSaaSVersion() || Helper::getOption( 'customer_panel_enable', 'off', false ) == 'on';
	}

}
