<?php

namespace BookneticAddon\Zoom\Integration;

use BookneticApp\Backend\Appointments\Helpers\AppointmentSmartObject;
use BookneticApp\Config;
use BookneticApp\Models\Appointment;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Session;
use BookneticVendor\Firebase\JWT\JWT;
use BookneticVendor\GuzzleHttp\Client;

use function BookneticAddon\Zoom\bkntc__;

class ZoomService
{

	private $appointmentID;

	/** @var AppointmentSmartObject */
    public $bookingData;

	/** @var Client */
	private $client;

	public function __construct()
	{
		$token = $this->getBearerToken();

		$this->client = new Client([
			'allow_redirects'	=>	[ 'max' => 10 ],
			'verify'			=>	false,
			'http_errors'		=>	false,
			'headers'			=>	[
				'Authorization'     => 'Bearer ' . $token,
				'Content-type'      => 'application/json'
			]
		]);
	}

	public function me()
	{
		$me = $this->client->get('https://api.zoom.us/v2/users/me');

		$result = json_decode( (string)$me->getBody(), true );

		return isset( $result['id'] ) ? $result : false;
	}

	public function subAccountsList()
	{
		$accounts = [];
		$page_number = 1;

		while( true )
		{
			$list = $this->client->get('https://api.zoom.us/v2/users?status=active&page_size=50&page_number=' . $page_number);

			$result = json_decode( (string)$list->getBody(), true );

			if( !isset( $result['users'] ) )
			{
				break;
			}

			$accounts = array_merge( $accounts, $result['users'] );
			if( $result['page_number'] == $result['page_count'] )
			{
				break;
			}

			$page_number++;
		}

		return $accounts;
	}

    public function setAppointment( $appointmentInf )
    {
        $busyStatuses = Helper::getBusyAppointmentStatuses();

        $group = Appointment::where('location_id', $appointmentInf->location_id)
            ->where('service_id', $appointmentInf->service_id)
            ->where('staff_id', $appointmentInf->staff_id)
            ->where('starts_at', $appointmentInf->starts_at)
            ->where('status', 'in', $busyStatuses)
        ->limit(1)
        ->fetch();

        $this->bookingData = AppointmentSmartObject::load($group->id);
        return $this;

    }

	public function createMeeting()
	{
		if( empty( $this->bookingData ) )
		{
			return null;
		}

		$zoomUserData = $this->bookingData->getStaffInf()->getData( 'zoom_user' );
		$zoomUserData = json_decode( $zoomUserData, true );

		if( empty( $zoomUserData['id'] ) || ! is_string( $zoomUserData['id'] ) )
			return false;

		$zoomUserId = $zoomUserData['id'];

		$result = $this->client->post('https://api.zoom.us/v2/users/' . $zoomUserId . '/meetings', [
			'json'   =>  $this->meetingParameters()
		]);

        if( $result->getStatusCode() == 201 )
        {

            $meetingData = json_decode( (string)$result->getBody(), true );
            $saveArray = [
                'id'            =>  $meetingData['id'],
                'join_url'      =>  $meetingData['join_url'],
                'start_url'     =>  $meetingData['start_url'],
                'password'      =>  isset( $meetingData['password'] ) ? $meetingData['password'] : ''
            ];
        }
        else
        {
            $saveArray = [];
        }

		return $saveArray;
	}

	public function updateMeeting( $meetingId )
	{
		$result = $this->client->patch('https://api.zoom.us/v2/meetings/' . $meetingId, [
			'json'   =>  $this->meetingParameters( true )
		]);
	}

	public function deleteMeeting( $meetingId )
	{
		$this->client->delete('https://api.zoom.us/v2/meetings/' . $meetingId );
	}

	private function meetingParameters( $isPatch = false )
	{
		$setPassword    = !$isPatch && Helper::getOption('zoom_set_random_password', 'on') == 'on';
		$meetingsTopic  = Helper::getOption('zoom_meeting_title', '');
        $meetingsTopic  = strip_tags($meetingsTopic);
		$meetingsAgenda = Helper::getOption('zoom_meeting_agenda', '');
        $meetingsAgenda = str_replace(['<p>', '</p>' , '&nbsp;'], ['', "\n",' '], $meetingsAgenda);
        $meetingsAgenda = strip_tags($meetingsAgenda);
		$startTime      = Date::format( 'c', $this->bookingData->getInfo()->starts_at );
		$duration       = (int)( $this->bookingData->getInfo()->ends_at - $this->bookingData->getInfo()->starts_at ) / 60;

		$shortCodeData = [
			'appointment_id'   => $this->bookingData->getId(),
			'service_id'                => $this->bookingData->getInfo()->service_id,
			'staff_id'                  => $this->bookingData->getInfo()->staff_id,
			'customer_id'               => $this->bookingData->getInfo()->customer_id,
			'location_id'               => $this->bookingData->getInfo()->location_id
		];

		$meetingParamters = [
			"topic"         =>  Config::getShortCodeService()->replace( $meetingsTopic, $shortCodeData ),
			"type"          =>  "2",
			"start_time"    =>  $startTime,
			"agenda"        =>  substr(Config::getShortCodeService()->replace( $meetingsAgenda, $shortCodeData ) , 0,2000),
			'duration'      =>  $duration
		];

		if( $setPassword )
		{
			$meetingParamters['password'] = rand(100000, 999999);
		}

		return $meetingParamters;
	}

	private function getBearerToken()
	{
        $zoom_integration_method = Helper::getOption('zoom_integration_method', 'oauth', false);
		if( Helper::isSaaSVersion() && $zoom_integration_method == 'oauth' )
		{
			$zoomData = Helper::getOption('zoom_user_data');
			if( empty( $zoomData ) )
			{
				return '';
			}

			$accessToken    = $zoomData['access_token'];
			$expireIn       = $zoomData['expires_in'];

			if( ( Date::epoch() + 60 ) >= $expireIn )
			{
				return $this->refreshToken( $zoomData );
			}

			return $accessToken;
		}
        // This will check if tenant or regular user is activated server_to_server auth app
		else if ( Helper::getOption( 'zoom_integration_method', '' ) == 'server_to_server' )
		{
            $zoomData = Helper::getOption( 'zoom_data' );
            if ( empty( $zoomData ) )
            {
                return $this->newServerToServerToken();
            }

            $accessToken = $zoomData[ 'access_token' ];
            $expireIn    = $zoomData[ 'expires_in' ];

            if( ( Date::epoch() + 60 ) >= $expireIn )
            {
                return $this->newServerToServerToken();
            }

            return $accessToken;
		}
        else
        {
            return $this->generateJWT();
        }
	}

	private function refreshToken( $zoomData )
	{
		$method         = $zoomData['method'];
		$refreshToken   = $zoomData['refresh_token'];

		if( $method == 'personal_app' )
		{
			$zoomClientId       = Helper::getOption('zoom_api_key', '', false);
			$zoomClientSecret   = Helper::getOption('zoom_api_secret', '', false);

			$url = 'https://zoom.us/oauth/token?grant_type=refresh_token&refresh_token='.urlencode( $refreshToken );

			$client = new Client([
				'allow_redirects'	=>	[ 'max' => 10 ],
				'verify'			=>	false,
				'http_errors'		=>	false,
				'headers'			=>	[
					'Authorization'     => 'Basic ' . base64_encode( $zoomClientId . ':' . $zoomClientSecret ),
					'Content-type'      => 'application/json'
				]
			]);

			$response = $client->post( $url );
			$result = json_decode( (string)$response->getBody(), true );

			if( isset( $result['access_token'] ) && isset( $result['refresh_token'] ) && isset( $result['expires_in'] ) )
			{
				$accessToken    = $result['access_token'];
				$refreshToken   = $result['refresh_token'];
				$expires_in     = $result['expires_in'];

				Helper::setOption('zoom_user_data', [
					'access_token'  =>  $accessToken,
					'refresh_token' =>  $refreshToken,
					'expires_in'    =>  Date::epoch() + $expires_in,
					'method'        =>  'personal_app',
					'user_email'    =>  isset( $zoomData['user_email'] ) ? $zoomData['user_email'] : ''
				]);

				return $accessToken;
			}

			return '';
		}
	}

	private function generateJWT()
	{
		$key    = Helper::getOption('zoom_api_key', '');
		$secret = Helper::getOption('zoom_api_secret', '');

		$token = [
			"iss"   => $key,
			"exp"   => Date::epoch( 'now', '+1 week' )
		];

		return JWT::encode( $token, $secret, 'HS256');
	}

	public function disconnect()
	{
		$accessToken        = $this->getBearerToken();
		$zoomClientId       = Helper::getOption('zoom_api_key', '', false);
		$zoomClientSecret   = Helper::getOption('zoom_api_secret', '', false);

		$url = 'https://zoom.us/oauth/revoke?token=' . urlencode( $accessToken );
		$client = new Client([
			'allow_redirects'	=>	[ 'max' => 10 ],
			'verify'			=>	false,
			'http_errors'		=>	false,
			'headers'			=>	[
				'Authorization'     => 'Basic ' . base64_encode( $zoomClientId . ':' . $zoomClientSecret ),
				'Content-type'      => 'application/json'
			]
		]);

		$client->post( $url );

		Helper::deleteOption('zoom_user_data');
	}

	public static function redirectUri()
	{
		return site_url() . '/?booknetic_action=zoom_oauth_callback';
	}

	public static function oAuthURL()
	{
		$zoomClientId   = Helper::getOption('zoom_api_key', '', false);
		$state          = uniqid();

		Session::set( 'zoom_state', $state );

		return 'https://zoom.us/oauth/authorize?response_type=code&client_id='.urlencode( $zoomClientId ).'&state='.urlencode( $state ).'&redirect_uri=' . urlencode( self::redirectUri() );
	}

    private function newServerToServerToken()
    {
        $zoom_client_id     = Helper::getOption( 'zoom_client_id', '' );
        $zoom_client_secret = Helper::getOption( 'zoom_client_secret', '' );
        $zoom_account_id    = Helper::getOption( 'zoom_account_id', '' );

        if ( empty( $zoom_client_id ) || empty( $zoom_client_secret ) || empty( $zoom_account_id ) )
        {
            return '';
        }

        $url = "https://zoom.us/oauth/token?grant_type=account_credentials&account_id=$zoom_account_id";
        $client = new Client( [
            'allow_redirects' => [ 'max' => 10 ],
            'verify'          => false,
            'http_errors'     => false,
            'headers'         => [
                'Authorization' => 'Basic ' . base64_encode( $zoom_client_id . ':' . $zoom_client_secret ),
                'Content-Type'  => 'application/json'
            ]
        ] );

        $response = $client->post( $url );
        $result   = json_decode( (string) $response->getBody(), TRUE );

        if ( isset( $result[ 'access_token' ] ) && isset( $result[ 'expires_in' ] ) )
        {
            $accessToken    = $result['access_token'];
            $expires_in     = $result['expires_in'];

            Helper::setOption('zoom_user_data', [
                'access_token'  =>  $accessToken,
                'refresh_token' =>  '',
                'expires_in'    =>  Date::epoch() + $expires_in,
                'method'        =>  'server_to_server_app'
            ]);

            return $accessToken;
        }
        else
        {
            return '';
        }

    }

	public static function getToken( $code )
	{
		$zoomClientId       = Helper::getOption('zoom_api_key', '', false);
		$zoomClientSecret   = Helper::getOption('zoom_api_secret', '', false);

		$url = 'https://zoom.us/oauth/token?grant_type=authorization_code&code='.urlencode( $code ).'&redirect_uri=' . urlencode( self::redirectUri() );

		$client = new Client([
			'allow_redirects'	=>	[ 'max' => 10 ],
			'verify'			=>	false,
			'http_errors'		=>	false,
			'headers'			=>	[
				'Authorization'     => 'Basic ' . base64_encode( $zoomClientId . ':' . $zoomClientSecret ),
				'Content-type'      => 'application/json'
			]
		]);

		$response = $client->post( $url );
		$result = json_decode( (string)$response->getBody(), true );

		if( isset( $result['access_token'] ) && isset( $result['refresh_token'] ) && isset( $result['expires_in'] ) )
		{
			$accessToken    = $result['access_token'];
			$refreshToken   = $result['refresh_token'];
			$expires_in     = $result['expires_in'];

			Helper::setOption('zoom_user_data', [
				'access_token'  =>  $accessToken,
				'refresh_token' =>  $refreshToken,
				'expires_in'    =>  Date::epoch() + $expires_in,
				'method'        =>  'personal_app'
			]);

			$getMe = new ZoomService();
			$me = $getMe->me();
			if( $me !== false )
			{
				Helper::setOption('zoom_user_data', [
					'access_token'  =>  $accessToken,
					'refresh_token' =>  $refreshToken,
					'expires_in'    =>  Date::epoch() + $expires_in,
					'method'        =>  'personal_app',
					'user_email'    =>  $me['email']
				]);
			}

			return [ 'status' => 'true' ];
		}
		else
		{
			return [
				'status'    =>  false,
				'error'     =>  isset( $result['reason'] ) && is_string( $result['reason'] ) ? htmlspecialchars($result['reason']) : bkntc__('An error occurred, please try again later')
			];
		}

	}

}