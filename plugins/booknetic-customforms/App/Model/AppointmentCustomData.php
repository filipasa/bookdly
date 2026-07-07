<?php

namespace BookneticAddon\Customforms\Model;

use BookneticApp\Models\Appointment;
use BookneticApp\Providers\DB\Model;

class AppointmentCustomData extends Model
{

	protected static $tableName = 'appointment_custom_data';

    public static $relations = [
        'appointment'           => [ Appointment::class, 'id', 'appointment_id' ],
        'form_input'            => [ FormInput::class, 'id', 'form_input_id' ],
    ];

}
