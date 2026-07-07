<?php

namespace BookneticApp\Backend\Base\Services;

use BookneticApp\Backend\Base\Repository\TranslationRepository;

class TranslationService
{
    private TranslationRepository $repository;

    public function __construct(TranslationRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllForOption(string $key): array
    {
        return $this->repository->getAllForOption($key);
    }

    public function getAll(int $rowId, string $columnName, string $tableName): array
    {
        return $this->repository->getAllByTableRowAndColumn($rowId, $columnName, $tableName);
    }

    public function saveOptions($translations, $options = []): bool
    {
        $translations = json_decode($translations, true);

        if (empty($translations) || ! is_array($translations)) {
            return false;
        }

        foreach ($translations as $optionName => $translation) {
            if (! in_array($optionName, $options)) {
                continue;
            }

            foreach ($translation as $t) {
                if (!isset($t['locale']) || !isset($t['value'])) {
                    continue;
                }

                $prevValue = $this->repository->getByTableColumnAndLocale($optionName, $t['locale'], 'options');

                if ($prevValue) {
                    $this->repository->update($prevValue->id, [
                        'value' => $t['value']
                    ]);
                } else {
                    $this->repository->create([
                        'column_name' => $optionName,
                        'table_name' => 'options',
                        'locale' => $t['locale'],
                        'value' => $t['value']
                    ]);
                }
            }
        }

        return true;
    }

    public function save(int $rowId, string $columnName, string $tableName, array $translations)
    {
        foreach ($translations as $translation) {
            $id  = ! empty($translation[ 'id' ]) ? $translation [ 'id' ] : 0;
            $locale = ! empty($translation[ 'locale' ]) ? $translation[ 'locale' ] : '';
            $value  = $translation['value'] ?? '';

            if (empty($locale)) {
                continue;
            }

            if ($id > 0) {
                $this->repository->update($id, [
                    'locale' => $locale,
                    'value'  => $value
                ]);
            } else {
                $this->repository->create([
                    'row_id'       => $rowId,
                    'column_name'  => $columnName,
                    'table_name'   => $tableName,
                    'locale'       => $locale,
                    'value'        => $value
                ]);
            }
        }
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}
