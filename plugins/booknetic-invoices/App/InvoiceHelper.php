<?php

namespace BookneticAddon\Invoices;

use Booknetic_Mpdf\Booknetic_Mpdf;
use Booknetic_Mpdf\HTMLParserMode;
use Booknetic_Mpdf\Output\Destination;
use BookneticAddon\Invoices\Model\Invoice;
use BookneticApp\Models\Data;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;

class InvoiceHelper
{

	public static function getInvoicePDF( $invoiceId, $data, $shortCodeService )
	{
		$invoiceInf = Invoice::get( $invoiceId );

		$body = $shortCodeService->replace( $invoiceInf['content'], $data );

        $date = Date::format( 'd-m-Y_H-i' );
        $uniqId = uniqid();

        $invoiceFileName = 'invoice_' . $date  . '_' . $uniqId . '.pdf';
        $pdfPath = Helper::uploadFolder( 'invoices' ) . $invoiceFileName;

        //Decides the event, workflows usually insert the first dependency, appointment_id, customer_id, tenant_id
        $firstItemKey = array_keys( $data )[0]; //polyfill for array_key_first()

        if ( ! Data::where([ 'table_name' => 'invoices', 'row_id' => $data[ $firstItemKey ], 'data_key' => $firstItemKey, 'data_value' => $invoiceFileName ])->fetch() )
        {
            Invoice::setData( $data[ $firstItemKey ], $firstItemKey, $invoiceFileName, false );
        }

        if ( ! file_exists( $pdfPath ) )
        {
            self::generateInvoicePDF( $body, $pdfPath );
        }

		return [
			'url'   =>  Helper::uploadedFileURL( $invoiceFileName, 'invoices' ),
			'path'  =>  Helper::uploadedFile( $invoiceFileName, 'invoices' )
		];
	}

    /**
     * @param $body
     * @param string $pdfPath
     * @return void
     * @throws \Booknetic_Mpdf\Booknetic_MpdfException
     */
    private static function generateInvoicePDF( $body, $pdfPath )
    {
        $mpdf = new Booknetic_Mpdf();
        $mpdf->WriteHTML(file_get_contents( __DIR__ . '/../assets/backend/css/pdf_prepend.css' ), HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML($body);
        $mpdf->Output($pdfPath, Destination::FILE);
    }


}
