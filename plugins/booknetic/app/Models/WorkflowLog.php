<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;

class WorkflowLog extends Model
{
    use MultiTenant;

    public static $relations = [
        'workflow' => [ Workflow::class, 'id', 'workflow_id' ]
    ];
}
