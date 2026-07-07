<?php

namespace BookneticAddon\Customforms\Helpers;

use BookneticAddon\Customforms\Model\Form;
use BookneticAddon\Customforms\Model\FormInput;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequestData;
use Exception;

class CheckConditions
{
    /**
     * @var AppointmentRequestData
     */
    private static $appointment;

    private static $customFields;

    /**
     * @param $appointment AppointmentRequestData
     * @return array
     * @throws Exception
     */
    public static function Calculate ( $appointment )
    {
        self::$appointment  = $appointment;
        self::$customFields = $appointment->getData( 'custom_fields' );

        if ( empty( self::$customFields ) )
        {
            return [];
        }

        $fields = [];
        $forms  = [];

        foreach ( self::$customFields as $id => $value )
        {
            if( ! ( is_numeric(  $id ) && $id > 0 ) )
            {
                throw new Exception( bkntc__('Please fill in all required fields correctly!') );
            }

            $fieldInf = FormInput::get( $id );

            if( ! $fieldInf )
            {
                throw new Exception( bkntc__('Please fill in all required fields correctly!') );
            }

            $options = json_decode( $fieldInf[ 'options' ], true );

            //delete in the future, old users did not have this key inside arr
            if ( ! isset( $options['visibility'] ) )
            {
                $options[ 'visibility' ] = 'visible';
            }

            //sets form info once
            if ( ! isset( $forms[ $fieldInf[ 'form_id' ] ] ) )
            {
                $forms[ $fieldInf[ 'form_id' ] ] = Form::get( $fieldInf[ 'form_id' ] );
            }

            $conditions = json_decode( $forms[ $fieldInf[ 'form_id' ] ]->conditions, true );

            if ( ! empty( $conditions ) )
            {
                foreach ( $conditions as $condition )
                {
                    $conditionObj = $condition[ 'conditions' ];
                    $events       = $condition[ 'events' ];

                    if ( ! self::getBoolVal( $conditionObj ) )
                        continue;

                    foreach ( $events as $event )
                    {
                        if ( isset( $event[ 'field_id' ] ) && $event[ 'field_id' ] != $id  )
                            continue;

                        switch ( $event[ 'action' ] )
                        {
                            case 'show':
                                $options[ 'visibility' ] = 'visible';
                                break;
                            case 'disable':
                            case 'hide':
                                $options[ 'visibility' ] = 'hidden';
                                break;
                            case 'hide_for_customers':
                                $options[ 'visibility' ] = 'visible_only_admin';
                                break;
                            case 'enable':
                                break;
                            case 'set_value':
                                $value = $event[ 'value' ];
                                break;
                            case 'throw_error':
                                throw new Exception( $event[ 'value' ] );
                        }
                    }
                }
            }

            $fields[] = [
                'id'       => $id,
                'value'    => $value,
                'label'    => $fieldInf[ 'label' ],
                'type'     => $fieldInf[ 'type' ],
                'options'  => $options,
                'required' => !! $fieldInf[ 'is_required' ],
            ];
        }

        return $fields;
    }

    private static function getBoolVal ( $obj )
    {
        $boolValue = self::compare( $obj[ 0 ] );

        array_shift( $obj );

        if ( empty( $obj ) )
        {
            return $boolValue;
        }

        if ( $obj[ 0 ][ 'condition' ] === 'AND' )
        {
            return $boolValue && self::getBoolVal( $obj );
        }

        return $boolValue || self::getBoolVal( $obj );
    }

    private static function compare ( $condition )
    {
        $conditionValue = $condition[ 'value' ];
        $id             = $condition[ 'field' ];
        $valueToCompare = '';

        if ( is_numeric( $id ) ) //custom field id
        {
            $valueToCompare = isset( self::$customFields[ $id ] ) ? self::$customFields[ $id ] : null;
        }
        else if ( $id === 'service_id' )
        {
            $valueToCompare = self::$appointment->serviceId;
        }
        else if ( $id === 'staff_id' )
        {
            $valueToCompare = self::$appointment->staffId;
        }
        else if ( $id === 'location_id' )
        {
            $valueToCompare = self::$appointment->locationId;
        }

        if ( $condition[ 'field_data' ] === 'length' )
        {
            $valueToCompare = strlen( $valueToCompare );
        }
        else if ( $condition[ 'field_data' ] === 'file_size' )
        {
            //Till confirmation step, front does not send the uploaded file, so the validation's skipped if the file's not present
            if ( ! isset( $_FILES[ 'custom_files' ] ) )
            {
                return true;
            }

            if ( is_array( $valueToCompare ) && isset( $valueToCompare['multiple'] ) && $valueToCompare['multiple'] == 'true' )
            {
                $totalSize = 0;
                if ( isset( $valueToCompare['new_files'] ) && is_array( $valueToCompare['new_files'] ) )
                {
                    foreach ( $valueToCompare['new_files'] as $fileRef )
                    {
                        $fileId = $fileRef['id'];
                        if ( isset( $_FILES[ 'custom_files' ][ 'tmp_name' ][ $fileId ] ) && is_file( $_FILES[ 'custom_files' ][ 'tmp_name' ][ $fileId ] ) )
                        {
                            $totalSize += filesize( $_FILES[ 'custom_files' ][ 'tmp_name' ][ $fileId ] );
                        }
                    }
                }
                $valueToCompare = round( $totalSize / 1024, 2 );
            }
            else
            {
                $fileId = is_array($valueToCompare) && isset($valueToCompare['id']) ? $valueToCompare['id'] : $valueToCompare;
                if ( isset( $_FILES[ 'custom_files' ][ 'tmp_name' ][ $fileId ] ) && is_file( $_FILES[ 'custom_files' ][ 'tmp_name' ][ $fileId ] ) )
                {
                    $size           = filesize( $_FILES[ 'custom_files' ][ 'tmp_name' ][ $fileId ] );
                    $valueToCompare = round( $size / 1024, 2 );
                }
                else
                {
                    $valueToCompare = 0;
                }
            }
        }

        if ( is_array( $conditionValue ) )//this is only the case for checkbox values
        {
            $valueToCompare = explode( ',', $valueToCompare );
        }

        switch ( $condition[ 'operator' ] )
        {
            case '=':
                return $valueToCompare == $conditionValue;
            case '!=':
                return $valueToCompare != $conditionValue;
            case '>':
                if ( $valueToCompare === null || $valueToCompare === '' )
                {
                    return false;
                }

                return $valueToCompare > $conditionValue;
            case '>=':
                if ( $valueToCompare === null || $valueToCompare === '' )
                {
                    return false;
                }

                return $valueToCompare >= $conditionValue;
            case '<':
                if ( $valueToCompare === null || $valueToCompare === '' )
                {
                    return false;
                }

                return $valueToCompare < $conditionValue;
            case '<=':
                if ( $valueToCompare === null || $valueToCompare === '' )
                {
                    return false;
                }

                return $valueToCompare <= $conditionValue;
            case 'is_empty':
                return empty( $valueToCompare );
            case 'is_not_empty':
                return ! empty( $valueToCompare );
            case 'contains':
                if ( is_array( $conditionValue ) )
                {
                    $contains = true;
                    foreach ( $conditionValue as $v )
                    {
                        if ( ! in_array( $v, $valueToCompare ) )
                        {
                            $contains = false;
                            break;
                        }
                    }

                    return $contains;
                }

                return is_numeric( strpos( (string) $valueToCompare, (string) $conditionValue ) );
            case 'regex':
                return preg_match( "/$conditionValue/", $valueToCompare );
            case '!contains':
                if ( is_array( $conditionValue ) )
                {
                    $notContains = true;

                    foreach ( $conditionValue as $value )
                    {
                        if ( in_array( $value, $valueToCompare ) )
                        {
                            $notContains = false;
                            break;
                        }
                    }

                    return $notContains;
                }

                return ! is_numeric( strpos( (string) $valueToCompare, (string) $conditionValue ) );
            case 'starts_with':
                return substr( $valueToCompare, 0, strlen( $conditionValue ) ) === $conditionValue;
            case '!starts_with':
                return substr( $valueToCompare, 0, strlen( $conditionValue ) ) !== $conditionValue;
            case 'ends_with':
                return substr( $valueToCompare,  -strlen( $conditionValue ) ) === $conditionValue;
            case '!ends_with':
                return substr( $valueToCompare, -strlen( $conditionValue ) ) !== $conditionValue;
            default:
                return false;
        }
    }
}