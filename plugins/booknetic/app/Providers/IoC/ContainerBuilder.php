<?php

namespace BookneticApp\Providers\IoC;

class ContainerBuilder
{
    /** @var array{id: string, factory: mixed, lifetime: string}[] */
    private array $registrations = [];

    /** @var array{interface: string, concrete: string|callable}[] */
    private array $bindings = [];

    public function add(string $id, $factory = null): self
    {
        $this->registrations[] = [
            'id'       => $id,
            'factory'  => $factory,
            'lifetime' => ServiceLifetime::SINGLETON,
        ];

        return $this;
    }

    public function addScoped(string $id, $factory = null): self
    {
        $this->registrations[] = [
            'id'       => $id,
            'factory'  => $factory,
            'lifetime' => ServiceLifetime::SCOPED,
        ];

        return $this;
    }

    public function addTransient(string $id, $factory = null): self
    {
        $this->registrations[] = [
            'id'       => $id,
            'factory'  => $factory,
            'lifetime' => ServiceLifetime::TRANSIENT,
        ];

        return $this;
    }

    public function bind(string $interface, $concrete): self
    {
        $this->bindings[] = [
            'interface' => $interface,
            'concrete'  => $concrete,
        ];

        return $this;
    }

    public function getRegistrations(): array
    {
        return $this->registrations;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function applyToContainer(): void
    {
        foreach ($this->registrations as $reg) {
            switch ($reg['lifetime']) {
                case ServiceLifetime::SINGLETON:
                    Container::add($reg['id'], $reg['factory']);
                    break;
                case ServiceLifetime::SCOPED:
                    Container::addScoped($reg['id'], $reg['factory']);
                    break;
                case ServiceLifetime::TRANSIENT:
                    Container::addTransient($reg['id'], $reg['factory']);
                    break;
            }
        }

        foreach ($this->bindings as $binding) {
            Container::bind($binding['interface'], $binding['concrete']);
        }
    }
}
