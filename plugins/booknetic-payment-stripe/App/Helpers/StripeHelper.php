<?php

namespace BookneticAddon\StripePaymentGateway\Helpers;

class StripeHelper
{
    public static function normalizePrice ( $price, $currency )
    {
        $currencies = [
            'BIF' => 1,
            'DJF' => 1,
            'JPY' => 1,
            'KRW' => 1,
            'PYG' => 1,
            'VND' => 1,
            'XAF' => 1,
            'XPF' => 1,
            'CLP' => 1,
            'GNF' => 1,
            'KMF' => 1,
            'MGA' => 1,
            'RWF' => 1,
            'VUV' => 1,
            'XOF' => 1,
            'ISK' => 1,
            'UGX' => 1,
            'UYI' => 1,

            'BHD' => 1000,
            'IQD' => 1000,
            'JOD' => 1000,
            'KWD' => 1000,
            'LYD' => 1000,
            'OMR' => 1000,
            'TND' => 1000,
        ];

        if ( array_key_exists( $currency, $currencies ) )
        {
            return $price * $currencies[ $currency ];
        }
        else
        {
            return $price * 100;
        }
    }
}