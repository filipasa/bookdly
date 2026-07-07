<?php

namespace BookneticApp\Backend\Boostore\DTOs\Response;

class AddonCategoryResponse
{
    private int $id;
    private string $slug;
    private string $name;

    public function __construct(int $id, string $slug, string $name)
    {
        $this->id = $id;
        $this->slug = $slug;
        $this->name = $name;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['id'] ?? 0),
            (string) ($data['slug'] ?? ''),
            (string) ($data['name'] ?? '')
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
