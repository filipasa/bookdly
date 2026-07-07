<?php

namespace BookneticAddon\Googlecalendar\Integration;

use BookneticAddon\Googlecalendar\Helpers\CalendarHelper;
use BookneticApp\Config;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Location;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticVendor\Google\Service\Calendar\Event;

class GoogleCalendarEvent
{

	/**
	 * @var GoogleCalendarService
	 */
	private $google_service;
	private $calendarId;

    private $attendees;

	public function __construct( $service )
	{
		$this->google_service = $service;
	}

	public function setAppointmentInf( $appointmentInf )
	{
        $busyStatuses                   = CalendarHelper::getNecessaryStatus();

        $group = Appointment::where('location_id', $appointmentInf->location_id)
            ->where('service_id', $appointmentInf->service_id)
            ->where('staff_id', $appointmentInf->staff_id)
            ->where('starts_at', $appointmentInf->starts_at)
            ->where('status', 'in', $busyStatuses)
            ->where( function ($query){
                $query->where('payment_method' ,'local')->orWhere('payment_status','paid');
            } )
            ->leftJoin('customer', [ 'email', 'first_name', 'last_name' ]);

        $this->attendees = $group->fetchAll();

        if (empty($this->attendees)) {
            $currentAppQuery = Appointment::where(Appointment::getField('id'), $appointmentInf->id)
                ->leftJoin('customer', [ 'email', 'first_name', 'last_name' ]);
            $currentApp = $currentAppQuery->fetch();
            if ($currentApp) {
                $this->attendees = [$currentApp];
            } else {
                $this->attendees = [$appointmentInf];
            }
        }

        return $this;
	}

	public function setCalendarId( $calendarId )
	{
		$this->calendarId = $calendarId;

		return $this;
	}

	public function insert()
	{
		try
		{
			$google_calendar_send_notification = Helper::getOption('google_calendar_send_notification', 'off', false) == 'on';
			$saveEvent = $this->google_service->getService()->events->insert( $this->calendarId, $this->getEventObj(), ['sendNotifications' => $google_calendar_send_notification] );
			$eventId = $saveEvent->getId();
		}
		catch ( \Exception $e )
		{
			$eventId = null;
		}

		return $eventId;
	}

	public function update( $eventId )
	{
		try
		{
			$google_calendar_send_notification = Helper::getOption('google_calendar_send_notification', 'off', false) == 'on';
			$saveEvent = $this->google_service->getService()->events->update( $this->calendarId, $eventId, $this->getEventObj(), ['sendNotifications' => $google_calendar_send_notification] );
			$eventIdr = $saveEvent->getId();
		}
		catch ( \Exception $e )
		{
			$eventIdr = null;
		}

		return $eventIdr;
	}

	public function delete( $event_id )
	{
		if( empty( $event_id ) )
			return false;

		try
		{
			$this->google_service->getService()->events->delete( $this->calendarId, $event_id );
		}
		catch ( \Exception $e )
		{
            return false;
		}

		return true;
	}

	private function getEventObj()
	{
        $firstAttendee = $this->attendees[0];
		$shortCodeData = [
			'appointment_id'            => $firstAttendee->id,
			'service_id'                => $firstAttendee->service_id,
			'staff_id'                  => $firstAttendee->staff_id,
			'customer_id'               => $firstAttendee->customer_id,
			'location_id'               => $firstAttendee->location_id
		];

		$summary = Helper::getOption('google_calendar_event_title', '', false);
        $summary = strip_tags($summary);
		$summary = Config::getShortCodeService()->replace( $summary, $shortCodeData);

		$description = Helper::getOption('google_calendar_event_description', '', false);
		$description = Config::getShortCodeService()->replace( $description, $shortCodeData);

		return new Event([
			'summary'					=> $summary,
			'description'				=> $description,
			'location'					=> Location::get($firstAttendee->location_id)->address,

			'start'						=> [ 'dateTime'	=>	Date::UTCDateTime( $firstAttendee->starts_at ) ],
			'end'						=> [ 'dateTime'	=>	Date::UTCDateTime( $firstAttendee->ends_at ) ],

			'attendees'					=> $this->appointmentAttendees(),
			'guestsCanSeeOtherGuests'	=> Helper::getOption('google_calendar_can_see_attendees', 'off', false) == 'on',

			'extendedProperties'		=> [
				'private'	=>	[
                    'BookneticStaffId'	        =>	$firstAttendee->staff_id
                ]
			]
		]);
	}

	private function appointmentAttendees()
	{
		if( Helper::getOption('google_calendar_add_attendees', 'off', false) == 'off' )
		{
			return [ ];
		}

		$attendees = [];

		foreach ( $this->attendees AS $attendee )
		{
			if( empty( $attendee['customer_email'] ) || !filter_var($attendee['customer_email'], FILTER_VALIDATE_EMAIL) )
				continue;

			$attendees[] = [
				'email'			=>	$attendee['customer_email'],
				'displayName'	=>	$attendee['customer_first_name'] . ' ' . $attendee['customer_last_name']
			];
		}

		return $attendees;
	}

}