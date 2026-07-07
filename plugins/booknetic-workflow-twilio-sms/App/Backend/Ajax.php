<?php

namespace BookneticAddon\TwilioSMS\Backend;

use BookneticAddon\TwilioSMS\TwilioSMSWorkflowDriver;
use BookneticApp\Models\Workflow;
use BookneticApp\Models\WorkflowAction;
use BookneticApp\Providers\Common\ShortCodeService;
use BookneticApp\Providers\Common\WorkflowEventsManager;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\Helpers\Helper;

class Ajax extends \BookneticApp\Providers\Core\Controller
{
    /**
     * @var WorkflowEventsManager
     */
    private $workflowEventsManager;

    public function __construct($workflowEventsManager)
    {
        $this->workflowEventsManager = $workflowEventsManager;
    }

	public function settings_view()
	{
		Capabilities::must('twilio_sms_settings');

		return $this->modalView( __DIR__ . '/view/sms_settings.php', [] );
	}

	public function save_settings()
	{
	    Capabilities::must('twilio_sms_settings');

		$sms_account_sid		        = Helper::_post('sms_account_sid', '', 'string');
		$sms_auth_token			        = Helper::_post('sms_auth_token', '', 'string');
		$sender_phone_number	        = Helper::_post('sender_phone_number', '', 'string');

		Helper::setOption('sms_account_sid', $sms_account_sid);
		Helper::setOption('sms_auth_token', $sms_auth_token);
		Helper::setOption('sender_phone_number', $sender_phone_number);

		return $this->response(true);
	}

	public function workflow_action_edit_view()
	{
		$id = Helper::_post('id', 0, 'int');

		$workflowActionInfo = WorkflowAction::get( $id );
		if( ! $workflowActionInfo )
		{
			return $this->response( false );
		}

		$data = json_decode( $workflowActionInfo->data, true );

        $availableParams = $this->workflowEventsManager->get(Workflow::get($workflowActionInfo->workflow_id)['when'])
            ->getAvailableParams();

        $toShortcodes   = $this->workflowEventsManager->getShortcodeService()->getShortCodesList($availableParams, ['phone']);
        $bodyShortcodes = $this->workflowEventsManager->getShortcodeService()->getShortCodesList($availableParams);

        $data['to_value'] = isset($data['to']) ? explode(',',   $data['to']) : [];

        $toAllShortcodeList = $this->shortcodeListGenerate($toShortcodes , $data['to_value']);


		return $this->modalView( __DIR__ . '/view/workflow_action_edit.php', [
			'action_info'   =>  $workflowActionInfo,
			'data'          =>  $data,
            'to_shortcodes'  =>  $toAllShortcodeList,
            'body_shortcodes'=>  $bodyShortcodes,
		], [ 'workflow_action_id' => $id ] );
	}

    private function shortcodeListGenerate($shortcodeList,$shortcodeDbValue)
    {
        $list = [];

        foreach ( $shortcodeList as $value )
        {
            $list['{'.$value['code'].'}']['value'] = $value['name'];
        }

        foreach ( $shortcodeDbValue as $value )
        {
            if( empty($value) ) continue;

            if( ! array_key_exists($value , $list) )
            {
                $list[$value]['value'] = $value;
            }

            $list[$value]['selected'] = true;
        }

        return $list;
    }


    public function workflow_action_save_data()
	{
		$id     = Helper::_post( 'id', 0, 'int' );
		$to     = Helper::_post( 'to', '', 'string' );
		$body   = Helper::_post( 'body', '', 'string' );
        $is_active = Helper::_post( 'is_active', 1, 'num' );

		$checkWorkflowActionExist = WorkflowAction::get( $id );
		if( ! $checkWorkflowActionExist )
		{
			return $this->response( false );
		}

		$newData = [
			'to'    =>  $to,
			'body'  =>  $body
		];

		WorkflowAction::where('id', $id)->update([ 'data' => json_encode( $newData ), 'is_active' => $is_active ]);

		return $this->response( true );
	}

    public function workflow_action_send_test_data ()
    {
        $to = Helper::_post('to', '', 'string');
        $actionId = Helper::_post('id', 0, 'int');

        if( !empty( $to ) && $actionId > 0 )
        {
            $actionInf = WorkflowAction::get( $actionId );
            $settings = json_decode( $actionInf->data, true );
            $settings['to'] = $to;
            $actionInf->data = json_encode($settings);
            $actionInf->when = 'send_test';
            $driver = new TwilioSMSWorkflowDriver();
            $driver->handle(new Collection(), $actionInf, new ShortCodeService());
        }

        return $this->response( true );
    }


}
