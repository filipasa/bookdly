<?php

namespace BookneticAddon\TwilioSMS;

use BookneticApp\Models\WorkflowLog;
use BookneticApp\Providers\Common\WorkflowDriver;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use Twilio\Rest\Client;
use function BookneticAddon\TwilioSMS\bkntc__;

class TwilioSMSWorkflowDriver extends WorkflowDriver
{

    protected $driver = 'twilio-sms';

    public function __construct()
    {
        $this->setName( bkntc__('Send SMS message via Twilio') );
		$this->setEditAction( 'twilio_sms_workflow', 'workflow_action_edit_view' );
    }

    public function handle( $eventData, $actionSettings, $shortCodeService )
    {
        $actionData = json_decode($actionSettings['data'],true);
        if ( empty( $actionData ) )
        {
            return;
        }

        $sendTo = $shortCodeService->replace( $actionData['to'], $eventData );
        $body   = $shortCodeService->replace( $actionData['body'], $eventData );

        if( ! empty( $sendTo ) )
        {
	        $sendToArr = explode( ',', $sendTo );
			foreach ( $sendToArr AS $sendTo )
			{
                $body = str_replace(['<p>' ,'</p>','&nbsp;'] , ['','<br>',' '], $body );
                $body = preg_replace( '/<br\s?\/?>/i', "\n", $body );
                $this->send( trim( $sendTo ), strip_tags( htmlspecialchars_decode( trim( $body ) ) ), $actionSettings );
			}
        }
    }

    public function send( $sendTo, $body, $actionSettings )
    {
	    if( empty( $sendTo ) )
		    return false;

	    $logCount = $this->getUsage();

	    if( Capabilities::getLimit( 'twilio_sms_allowed_max_number' ) <= $logCount && Capabilities::getLimit( 'twilio_sms_allowed_max_number' ) > -1 )
	    {
		    return false;
	    }

	    $sms_account_sid		= Helper::getOption('sms_account_sid', '', false);
	    $sms_auth_token			= Helper::getOption('sms_auth_token', '', false);
	    $sender_phone_number	= Helper::getOption('sender_phone_number', '', false);

	    if(
		    empty( $sms_account_sid )
		    || empty( $sms_auth_token )
		    || empty( $sender_phone_number )
	    )
	    {
		    return false;
	    }

	    $client = new Client( $sms_account_sid, $sms_auth_token );

	    try
	    {
		    $message = $client->messages->create( $sendTo, [
			    'from' => $sender_phone_number,
			    'body' => $body
		    ]);

		    $success = true;

            WorkflowLog::insert([
                'workflow_id'   => $actionSettings['workflow_id'],
                'when'          => $actionSettings->when,
                'driver'    =>  $this->getDriver(),
                'date_time' =>  Date::dateTimeSQL(),
                'data'      =>  json_encode([
                    'to'        =>$sendTo,
                    'body'      =>$body,
                ]),
            ]);

	    }
	    catch ( \Twilio\Exceptions\TwilioException $e )
	    {
		    $success = false;
		    $error_message = $e->getMessage();
	    }

		return $success;
    }

    public function getUsage()
    {
        $startDateToCheck = Date::format( 'Y-m-01 00:00' );
        $endDateToCheck = Date::format( 'Y-m-t 23:59:59' );
        return  WorkflowLog::where( 'driver', $this->getDriver() )
            ->where( 'date_time', 'BETWEEN', DB::field( "'{$startDateToCheck}' AND '{$endDateToCheck}'" ) )
            ->count();
    }

}