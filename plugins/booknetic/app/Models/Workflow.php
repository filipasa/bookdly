<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $when
 * @property-read string $data
 * @property-read int $is_active
 * @property-read int $tenant_id
 * @method WorkflowAction workflow_actions()
 * @method WorkflowLog workflow_logs()
 */
class Workflow extends Model
{
    use MultiTenant;

    public static $relations = [
        'workflow_actions'  => [ WorkflowAction::class, 'workflow_id', 'id' ],
        'workflow_logs'     => [ WorkflowLog::class, 'workflow_id', 'id' ]
    ];
}
