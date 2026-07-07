<?php

namespace BookneticAddon\Customforms\Helpers;


use BookneticAddon\Customforms\Model\FormInput;
use BookneticAddon\Customforms\Model\FormInputChoice;

class ConditionalFields
{
    private static $choiceFields;
    private static $inputFields;


    public static function fetchChoiceFields( $id )
    {
        if ( ! self::$choiceFields )
        {
            $result = FormInputChoice::select([ 'id', 'title' ])->fetchAll();

            $result = $result ?: [ 'id' => 'null', 'title' => 'null' ]; //if db is empty we set it to imaginary arr, so we don't have to query again

            self::$choiceFields = array_combine( array_column( $result, 'id' ), array_column( $result, 'title' ) );
        }

        if ( isset( self::$choiceFields[ $id ] ) )
        {
            return self::$choiceFields[ $id ];
        }

        return false;
    }

    public static function fetchInputFields( $id )
    {
        if ( ! self::$inputFields )
        {
            $result = FormInput::select([ 'id', 'label' ])->fetchAll();

            $result = $result ?: [ 'id' => 'null', 'label' => 'null' ];

            self::$inputFields = array_combine( array_column( $result, 'id' ), array_column( $result, 'label' ) );
        }

        if ( isset( self::$inputFields[ $id ] ) )
        {
            return self::$inputFields[ $id ];
        }

        return false;
    }
}