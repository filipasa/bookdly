<?php

namespace BookneticAddon\Invoices;

use BookneticAddon\Invoices\Model\Invoice;
use BookneticApp\Config;
use BookneticApp\Models\Data;
use BookneticApp\Providers\Core\Templates\Applier;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Invoices\bkntc__;


class Listener
{

	public static function registerShortCodes($shortCodeService)
	{
		$invoices = Invoice::select([ 'id', 'name' ], true)->fetchAll();

		foreach ( $invoices AS $invoiceInf )
		{
            $shortCodeService->registerShortCode( 'invoice_' . $invoiceInf->id . '_url', [
				'name'      =>  bkntc__('Invoice') . ': ' . $invoiceInf->name . ' [URL]',
				'kind'      =>  'url'
			]);

            $shortCodeService->registerShortCode( 'invoice_' . $invoiceInf->id . '_path', [
				'name'      =>  bkntc__('Invoice') . ': ' . $invoiceInf->name . ' [PATH]',
				'kind'      =>  'file'
			]);
		}
	}

	public static function replaceShortCodes( $text, $data, $shortCodeService )
	{
		$text = preg_replace_callback('/{invoice_([0-9]+)_url}/', function ( $found ) use ($shortCodeService, $data )
		{
			if( ! isset( $found[1] ) )
				return $found[0];

			return InvoiceHelper::getInvoicePDF( $found[1], $data, $shortCodeService )['url'];
		}, $text);

		$text = preg_replace_callback('/{invoice_([0-9]+)_path}/', function ( $found ) use ($shortCodeService, $data )
		{
			if( ! isset( $found[1] ) )
				return $found[0];

			return InvoiceHelper::getInvoicePDF( $found[1], $data, $shortCodeService )['path'];
		}, $text);

        return $text;
	}

    public static function addInfoTab( $appointmentId )
    {
        $appointmentInvoices = Data::select([ 'data_value' ])->where([
            'table_name' => 'invoices',
            'row_id' => $appointmentId,
            'data_key' => 'appointment_id'
        ])->fetchAll();

        $invoices = [];

        foreach( $appointmentInvoices AS $invoice )
        {
            $value = $invoice[ 'data_value' ];

            if ( substr( $value, -4 ) !== '.pdf' )
            {
                $value = 'invoice_' . $value . '.pdf';
            }

            $exists = file_exists( Helper::uploadedFile( $value, 'invoices' ) );

            $invoices[] = [
                'name'      => 'invoice_' . substr( $value, 0 , 25 ) . '...pdf',
                'href'      => $exists ? Helper::uploadedFileURL( $value, 'invoices' ) : '#',
                'exists'    => $exists,
            ];
        }

        return [
            'invoices' => $invoices
        ];

    }

    public static function beforeTenantDelete($tenantId)
    {
        Invoice::noTenant()->where('tenant_id', $tenantId)->delete();
    }

    public static function setTemplateField( $fields )
    {
        $fields[ 'invoices' ] = true;

        return $fields;
    }

    public static function setTemplateFieldLabel( $labels )
    {
        $labels[ 'invoices' ] = bkntc__( 'Invoices' );

        return $labels;
    }

    public static function applyTemplate( Applier $applier )
    {
        if ( ! $applier->isEnabled( 'invoices' ) || ! $applier->get( 'invoices' ) )
            return;

        foreach ( $applier->get( 'invoices' ) as $invoice )
        {
            unset( $invoice[ 'id' ] );
            unset( $invoice[ 'tenant_id' ] );

            Invoice::insert( $invoice );
        }
    }

    public static function fetchTemplateData( $collector )
    {
        $invoices = Invoice::noTenant()->where( 'tenant_id', $collector->getTenantId() )->fetchAll();

        foreach ( $invoices as $invoice )
        {
            $collector->addRow( 'invoices', $invoice->toArray() );
        }
    }

    public static function templateFieldCount( $data, $collector )
    {
        if ( $collector->get( 'invoices' ) )
        {
            $data[ 'invoices' ] = count( $collector->get( 'invoices' ) );
        }
        else if ( $collector->getTenantId() )
        {
            $data[ 'invoices' ] = Invoice::noTenant()->where( 'tenant_id', $collector->getTenantId() )->count();
        }
        else
        {
            $data[ 'invoices' ] = 0;
        }

        return $data;
    }
}
