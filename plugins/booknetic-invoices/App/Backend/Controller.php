<?php

namespace BookneticAddon\Invoices\Backend;

use Booknetic_Mpdf\Booknetic_Mpdf;
use Booknetic_Mpdf\HTMLParserMode;
use Booknetic_Mpdf\Output\Destination;
use BookneticAddon\Invoices\InvoiceHelper;
use BookneticAddon\Invoices\Model\Invoice;
use BookneticApp\Providers\Common\ShortCodeService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Route;
use BookneticApp\Providers\UI\DataTableUI;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Invoices\bkntc__;

class Controller extends \BookneticApp\Providers\Core\Controller
{

    /**
     * @var ShortCodeService
     */
    private $shortCodeService;

    /**
     * @param $shortCodeService
     */
    public function __construct($shortCodeService)
    {
        $this->shortCodeService = $shortCodeService;
    }


    public function index()
	{
        $dataTable = new DataTableUI( new Invoice() );

        $dataTable->addAction('edit', bkntc__('Edit'));

        if (Capabilities::userCan('invoices_delete'))
        {
            $dataTable->addAction('delete', bkntc__('Delete'), function ($ids)
            {
                Invoice::where('id', $ids)->delete();
            }, DataTableUI::ACTION_FLAG_BULK_SINGLE );
        }


        $dataTable->setTitle( bkntc__('Invoices') );
		$dataTable->addNewBtn( bkntc__('ADD INVOICE') );
		$dataTable->searchBy( ['id', 'name'] );

		$dataTable->addColumns(bkntc__('№'), DataTableUI::ROW_INDEX);
		$dataTable->addColumns(bkntc__('NAME'), 'name');

		$table = $dataTable->renderHTML();

		$this->view( 'index', ['table' => $table] );
	}

	public function edit()
	{
		$invoiceId = Helper::_get('invoice_id', null, 'int');

		if( $invoiceId > 0 )
		{
		    Capabilities::must('invoices_edit');

			$invoiceInf = Invoice::get( $invoiceId );
		}
		else
		{
		    Capabilities::must('invoices_add');

			$invoiceInf	= [
				'id'            =>  0,
				'name'          =>  '',
				'content'   	=>  ''
			];
		}

		$this->view( 'edit_invoice', [
			'id'		        =>	$invoiceId,
			'info'		        =>	$invoiceInf,
            'shortcode_list'    =>  $this->shortCodeService->getShortCodesList()
		] );
	}

	public function download()
	{
	    Capabilities::must('invoices');

		$invoiceId = Helper::_get('invoice_id', null, 'int');
		$invoiceInf = Invoice::get( $invoiceId );

		if( ! $invoiceInf )
		{
			Helper::redirect( Route::getURL( 'invoices' ) );
		}

		$mpdf = new Booknetic_Mpdf();
        $mpdf->WriteHTML(file_get_contents( __DIR__ . '/../../assets/backend/css/pdf_prepend.css' ), HTMLParserMode::HEADER_CSS);
		$mpdf->WriteHTML( $invoiceInf->content );
		$mpdf->Output( $invoiceInf->name . '.pdf', Destination::DOWNLOAD );

		exit();
	}

}
