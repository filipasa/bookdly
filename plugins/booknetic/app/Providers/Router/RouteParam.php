<?php

namespace BookneticApp\Providers\Router;

class RouteParam
{
    public string $name;
    public string $type;
    public string $source;
    public ?string $alias;
    public bool $isOptional;
    /** @var mixed */
    public $default;

    public function __construct(
        string $name,
        string $type,
        string $source,
        ?string $alias,
        bool $isOptional,
        $default
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->source = $source;
        $this->alias = $alias;
        $this->isOptional = $isOptional;
        $this->default = $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function __set_state(array $data): self
    {
        return new self(
            $data['name'],
            $data['type'],
            $data['source'],
            $data['alias'],
            $data['isOptional'],
            $data['default']
        );
    }
}
