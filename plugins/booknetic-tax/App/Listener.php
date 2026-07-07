<?php

namespace BookneticAddon\Tax;

use BookneticAddon\Tax\Model\Tax;
use BookneticApp\Backend\Appointments\Helpers\AppointmentRequests as Request;
use BookneticApp\Backend\Appointments\Helpers\AppointmentSmartObject;
use BookneticApp\Models\AppointmentPrice;
use BookneticApp\Providers\Core\Templates\Applier;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Math;

class Listener
{

	public static function calculateTax()
	{
        foreach ( Request::appointments()  as $obj )
        {
            $applicable_taxes = Tax::where( 'is_active', true )
                ->where( fn ( $q ) => $q
                    ->whereFindInSet( 'locations', $obj->locationId )
                    ->orWhere( 'locations', '' ) )
                ->where( fn ( $q ) => $q
                    ->whereFindInSet( 'services', $obj->serviceId )
                    ->orWhere( 'services', '' ) )
                ->withTranslations()
                ->fetchAll();

    //        foreach ( $obj->customers as $customer )
            {
    //            $customerId = $customer['id'];

                foreach ($applicable_taxes as $applicable_tax)
                {
                    $other_taxes = 0;
                    foreach ($obj->getPrices() as $priceKey => $price)
                    {
                        if (substr($priceKey, 0, 4) === "tax-")
                        {
                            $other_taxes += $price->getPrice();
                        }
                    }

                    $tax_type = $applicable_tax['type'];
                    $tax_data = $applicable_tax['value'];
                    if( $tax_type === 'percent' )
                    {
                        $tax = Math::floor( ( ($obj->getSubTotal() - $other_taxes) * $tax_data ) / 100 ,10);
                    }
                    else
                    {
                        $tax = Math::floor( $tax_data * $obj->weight ,10);

                        //patch for 100% coupon ¯\_(ツ)_/¯
                        if ( $obj->getSubTotal() == 0 )
                        {
                            $tax = 0;
                        }
                    }

                    $taxPriceObj = $obj->price('tax-' . $applicable_tax['id']);
                    $taxPriceObj->setLabel( $applicable_tax['name'] );
                    $taxPriceObj->setPrice( $tax );
                    $taxPriceObj->setHidden( $tax <= 0 );
                }

            }
        }
    }

	public static function registerShortCodes($shortCodeService)
	{
        $shortCodeService->registerShortCode( 'appointment_tax_amount', [
			'name'      =>  bkntc__('Total tax amount'),
			'category'  =>  'appointment_info',
			'depends'   =>  'appointment_id'
		]);

        $shortCodeService->registerShortCode( 'recurring_appointments_tax_amount', [
            'name'      =>  bkntc__('Total tax amount of recurring appointments'),
            'category'  =>  'appointment_info',
            'depends'   =>  'appointment_id'
        ]);

		$taxList = Tax::fetchAll();

		foreach ( $taxList AS $taxInf )
		{
            $shortCodeService->registerShortCode( 'appointment_tax_' . $taxInf->id . '_amount', [
				'name'      =>  bkntc__('Tax') . ': ' . $taxInf->name,
				'category'  =>  'appointment_info',
				'depends'   =>  'appointment_id'
			]);

            $shortCodeService->registerShortCode( 'recurring_appointments_tax_' . $taxInf->id . '_amount', [
                'name'      =>  bkntc__('Tax (recurring)') . ': ' . $taxInf->name,
                'category'  =>  'appointment_info',
                'depends'   =>  'appointment_id'
            ]);
		}
	}

	public static function replaceShortCodeText( $text, $data )
	{
		if( ! isset( $data['appointment_id'] ) )
			return $text;

		$appointmentSo = AppointmentSmartObject::load( $data['appointment_id'] );

		$text = preg_replace_callback('/{appointment_tax_([0-9]+)_amount}/', function ( $found ) use ( $appointmentSo )
		{
			if( ! isset( $found[1] ) )
				return $found[0];

			$taxAmount = $appointmentSo->getPrice('tax-' . $found[1]);
			return Helper::price( $taxAmount->price );
		}, $text);

        $text = preg_replace_callback('/{recurring_appointments_tax_([0-9]+)_amount}/', function ( $found ) use ( $appointmentSo )
        {
            if( ! isset( $found[1] ) )
                return $found[0];

            $taxAmount = 0;

            foreach ( $appointmentSo->getAllRecurringAppointmentIds() as $appointmentId )
            {
                $appointmentRec = AppointmentSmartObject::load( $appointmentId );

                $taxAmount += $appointmentRec->getPrice( 'tax-' . $found[ 1 ] )->price;
            }

            return Helper::price( $taxAmount );
        }, $text);

        $recurringSumTax = 0;

        foreach ( $appointmentSo->getAllRecurringAppointmentIds() as $appointmentId )
        {
            $appointmentRec = AppointmentSmartObject::load( $appointmentId );

            foreach ( $appointmentRec->getPrices() AS $priceInf )
            {
                $recurringSumTax += strpos( $priceInf->unique_key ,  'tax' ) === 0 ?  $priceInf->price : 0;
            }
        }

        $text = str_replace( '{recurring_appointments_tax_amount}', Helper::price( $recurringSumTax ), $text );

		$sumTaxAmount = 0;
		foreach ( $appointmentSo->getPrices() AS $priceInf )
		{
			$sumTaxAmount += strpos( $priceInf->unique_key ,  'tax' ) === 0 ?  $priceInf->price : 0;
		}

		return str_replace( '{appointment_tax_amount}', Helper::price( $sumTaxAmount ), $text );
	}

    public static function priceName( $key )
    {
        if (substr($key, 0, 4) === "tax-")
        {
            $taxId = explode('-', $key)[1];
            $tax = Tax::get($taxId);
            if ( $tax )
				return $tax->name;
        }

        return $key;
    }

    public static function beforeTenantDelete( $tenantId )
    {
        Tax::noTenant()->where('tenant_id', $tenantId)->delete();
    }

    public static function frontend_render_ui( $items )
    {
        if(  Helper::getOption('hide_tax_excluded_text' ,'on') == 'on') return;
        echo '<span class="bkntc_tax_excluded bkntc_tax_top "> ' . bkntc__("* Price does not include taxes"). '</span>';

        //doit: if there are complains about bottom tax text, we can revert it back. Removed on V3.2.3
//        if( count( $items ) > 4 )
//        {
//            echo '<span class="bkntc_tax_excluded bkntc_tax_bottom booknetic_fade"> ' . bkntc__("* Price does not include taxes"). '</span>';
//        }
    }

    public static function paymentExportCsv( $data , $dataTable )
    {
        if( $dataTable->getModule() !=='payments' || $dataTable->getExportCSV() !== true )
            goto end;

        $offset = 8;

        $appointmentIds = array_map(function ($item){
            return $item['id'];
        },$data['tbody']);

        $taxes = AppointmentPrice::where('appointment_id',$appointmentIds)
            ->where('unique_key','LIKE','tax-%')
            ->select(["SUM(price) as price" , 'appointment_id'])
            ->groupBy('appointment_id')
            ->fetchAll();

        $taxes = Helper::assocByKey($taxes,'appointment_id');

        $thead = [
            'name' => 'TAX',
            'is_sortable' => true,
            'order_by_field' => 'tax',
        ];

        array_splice($data['thead'] , $offset , 0 , [ $thead ]);

        foreach ($data['tbody'] as $key=>$row)
        {
            $insertData = [
                'content'=> Helper::price(isset($taxes[ $row['id'] ]->price) ? $taxes[ $row['id'] ]->price : 0),
                'attributes'=>[]
            ];
            array_splice($data['tbody'][$key]['data'] , $offset,0,[ $insertData ]);
        }

        end:
        return $data;
    }

    public static function setTemplateField( $fields )
    {
        $fields[ 'taxes' ] = true;

        return $fields;
    }

    public static function setTemplateFieldLabel( $labels )
    {
        $labels[ 'taxes' ] = bkntc__( 'Taxes' );

        return $labels;
    }

    public static function applyTemplate( Applier $applier )
    {
        if ( ! $applier->isEnabled( 'taxes' ) || ! $applier->get( 'taxes' ) )
            return;

        foreach ( $applier->get( 'taxes' ) as $tax )
        {
            unset( $tax[ 'id' ] );
            unset( $tax[ 'tenant_id' ] );

            Tax::insert( $tax );
        }
    }

    public static function fetchTemplateData( $collector )
    {
        $taxes = Tax::noTenant()->where( 'tenant_id', $collector->getTenantId() )->fetchAll();

        foreach ( $taxes as $tax )
        {
            $collector->addRow( 'taxes', $tax->toArray() );
        }
    }

    public static function templateFieldCount( $data, $collector )
    {
        if ( $collector->get( 'taxes' ) )
        {
            $data[ 'taxes' ] = count( $collector->get( 'taxes' ) );
        }
        else if ( $collector->getTenantId() )
        {
            $data[ 'taxes' ] = Tax::noTenant()->where( 'tenant_id', $collector->getTenantId() )->count();
        }
        else
        {
            $data[ 'taxes' ] = 0;
        }

        return $data;
    }
}