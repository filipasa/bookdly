<?php

namespace BookneticAddon\PaypalPaymentGateway\Backend;


use BookneticAddon\PaypalPaymentGateway\Helpers\PaypalSplitHelper;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Helper;

class Ajax extends \BookneticApp\Providers\Core\Controller
{
    public function create_account()
    {
        $result = PaypalSplitHelper::createSellerAccount( Permission::tenantInf() );

        if ( ! $result )
            return $this->response(false, 'Something went wrong');

        if ( $result['status'] != 201 )
            return $this->response(false, 'Something went wrong');

        if (  empty($result['body']) )
            return $this->response(false, 'Something went wrong');

        if ( ! isset($result['body']['links']) )
            return $this->response(false, 'Something went wrong');


        $links = $result['body']['links'];


        if ( ! isset ( $links[1] ) && ! isset( $links[1]['rel'] ) && $links[1]['rel'] != 'action_url' )
            return $this->response(false, 'Something went wrong');

        return $this->response( true, [ 'url' => $links[1]['href'] ] );
    }
}