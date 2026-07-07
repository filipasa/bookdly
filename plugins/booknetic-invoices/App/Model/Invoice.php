<?php

namespace BookneticAddon\Invoices\Model;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;

class Invoice extends Model
{
	use MultiTenant;

}