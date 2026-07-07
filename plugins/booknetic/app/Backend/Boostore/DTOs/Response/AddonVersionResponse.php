<?php

namespace BookneticApp\Backend\Boostore\DTOs\Response;

class AddonVersionResponse
{
    private ?int $version;
    private ?string $versionString;
    private ?string $requiredBookneticVersionString;

    public function __construct(?int $version, ?string $versionString, ?string $requiredBookneticVersionString)
    {
        $this->version = $version;
        $this->versionString = $versionString;
        $this->requiredBookneticVersionString = $requiredBookneticVersionString;
    }

    public static function fromArray(?array $data): ?self
    {
        if ($data === null) {
            return null;
        }

        return new self(
            isset($data['version']) ? (int) $data['version'] : null,
            $data['version_string'] ?? null,
            $data['required_booknetic_version_string'] ?? null
        );
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function getVersionString(): ?string
    {
        return $this->versionString;
    }

    public function getRequiredBookneticVersionString(): ?string
    {
        return $this->requiredBookneticVersionString;
    }

    public function hasVersionString(): bool
    {
        return ! empty($this->versionString);
    }
}
