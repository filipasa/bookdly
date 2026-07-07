<?php

namespace BookneticApp\Providers\Core\Response;

use JsonSerializable;

class PaginatedResponse implements JsonSerializable
{
    private array $data;
    private int $total;
    private int $skip;
    private int $limit;

    public function __construct(array $data, int $total, int $skip, int $limit)
    {
        $this->data = $data;
        $this->total = $total;
        $this->skip = $skip;
        $this->limit = $limit;
    }

    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'meta' => [
                'total' => $this->total,
                'skip'  => $this->skip,
                'limit' => $this->limit,
            ],
        ];
    }
}
