<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\DB\Model;

/**
 * @property int $id
 * @property int $workflow_id
 * @property string $driver
 * @property string $data
 * @property int $is_active
 */
class WorkflowAction extends Model
{
    public static $relations = [
        'workflow'   =>  [ Workflow::class, 'id', 'workflow_id' ]
    ];
}
