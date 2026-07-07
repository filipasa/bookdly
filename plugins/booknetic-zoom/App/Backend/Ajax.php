<?php

namespace BookneticAddon\Zoom\Backend;

use BookneticAddon\Zoom\Integration\ZoomService;
use BookneticApp\Config;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Safe;
use function BookneticAddon\Zoom\bkntc__;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	public function settings_view()
	{
        $shortcodeList = Config::getShortCodeService()->getShortCodesList();

        return $this->modalView( 'integrations_zoom_settings', [
            'all_shortcode' => $shortcodeList
        ] );
	}

    public function save_settings()
    {
        Capabilities::must('zoom_settings');

        if( Helper::isSaaSVersion() && Helper::getOption('zoom_enable', 'off', false) == 'off' )
        {
            return $this->response( false );
        }

        $zoom_enable		        = Helper::_post('zoom_enable', 'off', 'string', ['on', 'off']);
        $zoom_account_id	        = Helper::_post('zoom_account_id', '', 'string');
        $zoom_client_id  	        = Helper::_post('zoom_client_id', '', 'string');
        $zoom_client_secret         = Helper::_post('zoom_client_secret', '', 'string');
        $zoom_meeting_title	        = Helper::_post('zoom_meeting_title', '', 'string');
        $zoom_meeting_agenda	    = Helper::_post('zoom_meeting_agenda', '', 'string');
        $zoom_set_random_password	= Helper::_post('zoom_set_random_password', 'on', 'string', ['on', 'off']);

        if( $zoom_enable == 'on' )
        {
            if( empty($zoom_meeting_title) || empty($zoom_meeting_agenda) )
            {
                return $this->response(false, bkntc__('Please fill in all required fields correctly!'));
            }

            if( in_array( Helper::getOption('zoom_integration_method', 'oauth', false), [ 'jwt', 'server_to_server' ] ) && ( empty( $zoom_account_id ) || empty( $zoom_client_secret ) || empty( $zoom_client_id ) ) )
            {
                return $this->response(false, bkntc__('Please fill in all required fields correctly!'));
            }
        }

        if( in_array( Helper::getOption('zoom_integration_method', 'oauth', false), [ 'jwt', 'server_to_server' ] ) || !Helper::isSaaSVersion() )
        {
            Helper::setOption('zoom_client_id', $zoom_client_id);
            Helper::setOption('zoom_client_secret', $zoom_client_secret);
            Helper::setOption('zoom_account_id', $zoom_account_id );
            Helper::setOption('zoom_integration_method', 'server_to_server' ); // For tenants and regular users - this option means that user has changed app type to server_to_server app type
        }

        Helper::setOption('zoom_enable', $zoom_enable);
        Helper::setOption('zoom_meeting_title', $zoom_meeting_title);
        Helper::setOption('zoom_meeting_agenda', $zoom_meeting_agenda);
        Helper::setOption('zoom_set_random_password', $zoom_set_random_password);

        return $this->response( true );
    }

    public function connect_zoom()
    {
        return $this->response( true, [
            'url'   =>  ZoomService::oAuthURL()
        ] );
    }

    public function disconnect_zoom()
    {
        $zoom = new ZoomService();
        $zoom->disconnect();

        return $this->response( true );
    }

    public function fetch_zoom_users()
    {
        $staff_id	= Helper::_post('staff_id', '', 'int');
        $search		= Helper::_post('q', '', 'str');

        if( !( $staff_id >= 0 ) )
        {
            return $this->response(false);
        }

        $zoom = new ZoomService();
        $users = $zoom->subAccountsList();

        $data = [];
        foreach ( $users AS $user )
        {
            $text = $user['first_name'] . ' ' . $user['last_name'] . ' ( ' . $user['email'] . ' )';

            if( !empty( $search ) && Safe::strpos( $text, $search, 0, 'UTF-8' ) === false )
                continue;

            $data[] = [
                'id'		=>	htmlspecialchars($user['id']),
                'text'		=>	htmlspecialchars($text)
            ];
        }

        return $this->response( true, [ 'results' => $data ] );
    }

    public function settings_view_saas()
    {
        return $this->modalView( 'saas_integrations_zoom_settings', [] );
    }

	public function save_settings_saas()
	{
		$zoom_enable		        = Helper::_post('zoom_enable', 'off', 'string', ['on', 'off']);
		$zoom_integration_method	= Helper::_post('zoom_integration_method', 'oauth', 'string', ['oauth', 'server_to_server']);
		$zoom_api_key	            = Helper::_post('zoom_api_key', '', 'string');
		$zoom_api_secret	        = Helper::_post('zoom_api_secret', '', 'string');

		if( $zoom_enable == 'on' && $zoom_integration_method == 'oauth' && ( empty($zoom_api_key) || empty($zoom_api_secret) ) )
		{
			return $this->response(false, bkntc__('Please fill in all required fields correctly!'));
		}

		Helper::setOption('zoom_enable', $zoom_enable);
		Helper::setOption('zoom_integration_method', $zoom_integration_method);
		Helper::setOption('zoom_api_key', $zoom_api_key);
		Helper::setOption('zoom_api_secret', $zoom_api_secret);

		return $this->response( true );
	}

}
