<?php

namespace BookneticAddon\Tax\Backend;

use BookneticAddon\Tax\Model\Tax;
use BookneticApp\Models\Location;
use BookneticApp\Providers\Helpers\Math;
use BookneticApp\Providers\UI\DataTableUI;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;
use function \BookneticAddon\Tax\bkntc__;

class Controller extends \BookneticApp\Providers\Core\Controller
{

    public function index()
    {
    	Capabilities::must( 'tax' );

        $dataTable = new DataTableUI(new Tax());

        $dataTable->addAction('edit', bkntc__('Edit'));

        if (Capabilities::userCan('tax_delete'))
        {
            $dataTable->addAction('delete', bkntc__('Delete'), function ($ids)
            {
                Tax::where('id', $ids)->delete();
            }, DataTableUI::ACTION_FLAG_BULK_SINGLE );
        }


        $dataTable->setTitle(bkntc__('Taxes'));
        $dataTable->addNewBtn(bkntc__('NEW TAX'));

        $dataTable->addColumns(bkntc__('ID'), 'id');
        $dataTable->addColumns(bkntc__('NAME'), 'name');

        $dataTable->addColumns(bkntc__('AMOUNT'), function ($row)
        {
            if ($row['type'] == 'percent')
            {
                return Math::floor( $row[ 'value' ], 2 ) . '%';
            }

            if ($row['type'] == 'absolute')
            {
                return Helper::price( $row['value'] );
            }

            return $row['value'] . ' ' . $row['type'];
        }, ['is_sortable' => true, 'order_by_field' => 'value']);

        $dataTable->addColumns(bkntc__('LOCATIONS'), function ($row)
        {
            if ( empty( $row['locations'] ) )
            {
                return bkntc__( 'All locations' );
            }

            $locationIds = explode(',', $row['locations']);
            $firstLocation = Location::get( $locationIds[0] );
            $badge = '';

            if ( count( $locationIds ) > 1 )
            {
                $badge = '<button type="button" class="btn btn-xs btn-light-default ml-1">+' . (count($locationIds) - 1) . '</button>';
            }

            return ($firstLocation ? htmlspecialchars($firstLocation->name) : '-') . $badge;
        }, ['is_html' => true, 'is_sortable' => true, 'order_by_field' => 'locations']);

        $dataTable->addColumns(bkntc__('STATUS'), function ($row)
        {
            $text = $row['is_active'] ? bkntc__( 'Enabled' ) : bkntc__( 'Disabled' );
            $cls = $row['is_active'] ? 'success' : 'warning';

            return '<button type="button" class="btn btn-xs btn-light-'.$cls.'">'.$text.'</button>';
        }, ['is_html' => true, 'is_sortable' => true, 'order_by_field' => 'is_active']);

        $this->view('index', [
            'count'         => Tax::count(),
            'table_html'    => $dataTable->renderHTML()
        ]);
    }

}
