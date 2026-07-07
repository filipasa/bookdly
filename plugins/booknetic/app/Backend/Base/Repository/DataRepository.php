<?php

namespace BookneticApp\Backend\Base\Repository;

use BookneticApp\Models\Data;

class DataRepository
{
    public function duplicateForTable(string $tableName, int $oldId, int $newId): void
    {
        $records = Data::query()
            ->where('table_name', $tableName)
            ->where('row_id', $oldId)
            ->fetchAll();

        foreach ($records as $record) {
            Data::query()
                ->insert([
                'table_name' => $tableName,
                'row_id'     => $newId,
                'data_key'   => $record->data_key,
                'data_value' => $record->data_value,
            ]);
        }
    }
}
