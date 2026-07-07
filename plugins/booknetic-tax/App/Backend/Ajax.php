<?php

namespace BookneticAddon\Tax\Backend;

use BookneticAddon\Tax\Model\Tax;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\UI\TabUI;
use function \BookneticAddon\Tax\bkntc__;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

    public function add_new()
    {
        $id = Helper::_post('id', -1, 'integer');

        if( $id > 0 )
        {
	        Capabilities::must( 'tax_edit' );
        }
        else
        {
	        Capabilities::must( 'tax_add' );
        }

        $taxInf    = Tax::get( $id );
        $locations = [];
        $services  = [];

        if ( $taxInf === null )
        {
            $taxInf = [
                'id'                =>  null,
                'name'              =>  null,
                'type'              =>  null,
                'value'             =>  null,
                'is_active'         =>  true,
            ];
        }

        if ( !empty( $taxInf['locations'] ) )
        {
            $locationIds = explode(',', $taxInf['locations']);

            foreach ($locationIds as $locationId)
            {
            	$locationInf = Location::get( $locationId );

				if( $locationInf )
				{
					$locations[] = [ $locationId, $locationInf->name ];
				}
            }
        }

        if ( !empty( $taxInf['services'] ) )
        {
            $serviceIds = explode(',', $taxInf['services']);

            foreach ($serviceIds as $serviceId)
            {
                $serviceInf = Service::get( $serviceId );

                if( $serviceInf )
                {
                    $services[] = [ $serviceId, $serviceInf->name ];
                }
            }
        }

        TabUI::get( 'tax_add_new' )
            ->item( 'details' )
            ->setTitle( bkntc__( 'Tax Details' ) )
            ->addView( __DIR__ . '/view/tab/tax_add_new_details.php' )
            ->setPriority( 1 );

        return $this->modalView('add_new', [
            'tax'	    =>	$taxInf,
            'locations'	=>	$locations,
            'services'	=>	$services,
        ]);
    }

    public function save_tax()
    {
        $id = Helper::_post('id', 0, 'int');

	    if( $id > 0 )
	    {
		    Capabilities::must( 'tax_edit' );
	    }
	    else
	    {
		    Capabilities::must( 'tax_add' );
	    }

        $name       = Helper::_post('name', '', 'str');
        $type       = Helper::_post('type', '', 'str', ['percent', 'absolute']);
        $value      = Helper::_post('value', 0, 'float');
        $locations  = Helper::_post('locations', '', 'string');
        $services   = Helper::_post('services', '', 'string');
        $is_active  = Helper::_post('is_active', 'true', 'string') === 'true';

        if ( empty( $name ) )
        {
            return $this->response(false, bkntc__('Please fill tax name'));
        }

        if ( empty( $type ) )
        {
            return $this->response(false, bkntc__("Invalid tax type"));
        }

        if ( $value < 0 )
        {
            return $this->response(false, bkntc__('Tax amount must be non-negative'));
        }

        $sqlData = [
            'name'      => $name,
            'type'      => $type,
            'value'     => $value,
            'locations' => implode(',', json_decode($locations)),
            'services'  => implode(',', json_decode($services)),
            'is_active' => $is_active
        ];

        if ( $id > 0 )
        {
            Tax::where('id', $id)->update( $sqlData );
        }
        else
        {
            Tax::insert($sqlData);
            $id = Tax::lastId();
        }

        Tax::handleTranslation( $id );

        return $this->response(true);
    }

    public function get_locations()
    {
    	Capabilities::must( 'tax' );

        $search		= Helper::_post('q', '', 'string');

        $locations  = Location::where('name', 'LIKE', '%'.$search.'%')->fetchAll();
        $data       = [];

        foreach ( $locations AS $location )
        {
            $data[] = [
                'id'    =>	(int)$location['id'],
                'text'  =>	htmlspecialchars($location['name'])
            ];
        }

        return $this->response(true, [ 'results' => $data ]);
    }

    public function get_services()
    {
        Capabilities::must( 'tax' );

        $search		= Helper::_post('q', '', 'string');

        $services   = Service::where('name', 'LIKE', '%'.$search.'%')->fetchAll();
        $data       = [];

        foreach ( $services AS $service )
        {
            $data[] = [
                'id'    =>	(int)$service['id'],
                'text'  =>	htmlspecialchars($service['name'])
            ];
        }

        return $this->response(true, [ 'results' => $data ]);
    }

}