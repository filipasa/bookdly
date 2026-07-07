<?php

namespace BookneticApp\Backend\Appointments\Mappers;

use BookneticApp\Backend\Base\DTOs\Response\SelectOptionResponse;

class SelectOptionMapper
{
    public function toResponse(array $rows, callable $textResolver): array
    {
        return array_map(
            static function (array $row) use ($textResolver) {
                return new SelectOptionResponse(
                    (int) $row['id'],
                    htmlspecialchars($textResolver($row))
                );
            },
            $rows
        );
    }
}
