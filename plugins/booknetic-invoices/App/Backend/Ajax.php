<?php

namespace BookneticAddon\Invoices\Backend;

use BookneticAddon\Invoices\Model\Invoice;
use BookneticApp\Providers\Core\Backend;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use function BookneticAddon\Invoices\bkntc__;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	public function save()
	{
		$id			=	Helper::_post('id', '0', 'integer');
		$name		=	Helper::_post('name', '', 'string');
		$content	=	Helper::_post('content', '', 'string');

		if( $id < 0 || empty( $name ) || empty( $content ) )
		{
			return $this->response(false, bkntc__('Please fill in all required fields correctly!'));
		}

		$sqlData = [
			'name'		=>	$name,
			'content'	=>	$content
		];

		if( $id > 0 )
		{
			Invoice::where('id', $id)->update( $sqlData );
		}
		else
		{
			Invoice::insert( $sqlData );
			$id = DB::lastInsertedId();
		}

		return $this->response(true, [
			'id'	=>	$id
		]);
	}

}
