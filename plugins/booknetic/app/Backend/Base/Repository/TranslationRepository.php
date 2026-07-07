<?php

namespace BookneticApp\Backend\Base\Repository;

use BookneticApp\Models\Translation;
use BookneticApp\Providers\DB\Collection;

class TranslationRepository
{
    public function duplicateForTable(string $tableName, int $oldId, int $newId): void
    {
        /**
         * @var Translation[] $records
         */
        $records = Translation::query()
            ->where('table_name', $tableName)
            ->where('row_id', $oldId)
            ->fetchAll();

        foreach ($records as $record) {
            Translation::query()
                ->insert([
                    'table_name' => $tableName,
                    'row_id' => $newId,
                    'column_name' => $record->column_name,
                    'locale' => $record->locale,
                    'value' => $record->value,
                ]);
        }
    }

    /**
     * @return Translation|Collection|null
     */
    public function getByTableColumnAndLocale(string $column, string $locale, string $tableName): ?Collection
    {
        return Translation::query()
            ->where('column_name', $column)
            ->where('locale', $locale)
            ->where('table_name', $tableName)
            ->fetch();
    }

    /**
     * @param int $rowId
     * @param string $column
     * @param string $table
     * @return array<Translation|Collection>
     */
    public function getAllByTableRowAndColumn(int $rowId, string $column, string $table): array
    {
        return Translation::query()
            ->where('row_id', $rowId)
            ->where('column_name', $column)
            ->where('table_name', $table)
            ->fetchAll();
    }

    /**
     * @param string $column
     * @return array<Translation|Collection>
     */
    public function getAllForOption(string $column): array
    {
        return Translation::query()
            ->where('column_name', $column)
            ->where('table_name', 'options')
            ->fetchAll();
    }

    public function update(int $id, array $data)
    {
        Translation::query()
            ->where('id', $id)
            ->update($data);
    }

    public function create(array $data): int
    {
        Translation::query()->insert($data);

        return Translation::lastId();
    }

    public function delete(int $id)
    {
        Translation::query()->where('id', $id)->delete();
    }
}
