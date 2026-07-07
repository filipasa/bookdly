<?php

namespace BookneticApp\Backend\Boostore\DTOs\Response;

class AddonDetailResponse
{
    private string $slug;
    private string $name;
    private string $description;
    private string $cover;
    private int $downloads;
    private bool $released;
    private string $purchaseStatus;
    private bool $isInstalled;
    private bool $inCart;
    private ?string $errorMessage;
    private array $plans;
    private array $info;
    private AddonCategoryResponse $category;
    private AddonPriceResponse $price;
    private ?AddonVersionResponse $latestVersion;
    private ?AddonVersionResponse $latestCompatibleVersion;

    public static function fromArray(array $data): self
    {
        $instance = new self();

        $instance->slug = (string) ($data['slug'] ?? '');
        $instance->name = (string) ($data['name'] ?? '');
        $instance->description = (string) ($data['description'] ?? '');
        $instance->cover = (string) ($data['cover'] ?? '');
        $instance->downloads = (int) ($data['downloads'] ?? 0);
        $instance->released = (bool) ($data['released'] ?? false);
        $instance->purchaseStatus = (string) ($data['purchase_status'] ?? 'unowned');
        $instance->isInstalled = (bool) ($data['is_installed'] ?? false);
        $instance->inCart = (bool) ($data['in_cart'] ?? false);
        $instance->errorMessage = $data['error_message'] ?? null;
        $instance->plans = is_array($data['plans'] ?? null) ? $data['plans'] : [];
        $instance->info = is_array($data['info'] ?? null) ? $data['info'] : [];

        $instance->category = AddonCategoryResponse::fromArray(
            is_array($data['category'] ?? null) ? $data['category'] : []
        );

        $instance->price = AddonPriceResponse::fromArray(
            is_array($data['price'] ?? null) ? $data['price'] : []
        );

        $instance->latestVersion = AddonVersionResponse::fromArray($data['latest_version'] ?? null);
        $instance->latestCompatibleVersion = AddonVersionResponse::fromArray($data['latest_compatible_version'] ?? null);

        return $instance;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCover(): string
    {
        return $this->cover;
    }

    public function getDownloads(): int
    {
        return $this->downloads;
    }

    public function isReleased(): bool
    {
        return $this->released;
    }

    public function getPurchaseStatus(): string
    {
        return $this->purchaseStatus;
    }

    public function isOwned(): bool
    {
        return $this->purchaseStatus === 'owned';
    }

    public function isUnowned(): bool
    {
        return $this->purchaseStatus === 'unowned';
    }

    public function isPending(): bool
    {
        return $this->purchaseStatus === 'pending';
    }

    public function isInstalled(): bool
    {
        return $this->isInstalled;
    }

    public function isInCart(): bool
    {
        return $this->inCart;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function hasErrorMessage(): bool
    {
        return ! empty($this->errorMessage);
    }

    public function getPlans(): array
    {
        return $this->plans;
    }

    public function isIncludedInPlan(?string $planSlug): bool
    {
        if ($planSlug === null) {
            return false;
        }

        return in_array($planSlug, $this->plans, true);
    }

    public function getInfo(): array
    {
        return $this->info;
    }

    public function getCategory(): AddonCategoryResponse
    {
        return $this->category;
    }

    public function getPrice(): AddonPriceResponse
    {
        return $this->price;
    }

    public function getLatestVersion(): ?AddonVersionResponse
    {
        return $this->latestVersion;
    }

    public function getLatestCompatibleVersion(): ?AddonVersionResponse
    {
        return $this->latestCompatibleVersion;
    }

    public function hasLatestVersion(): bool
    {
        return $this->latestVersion !== null && $this->latestVersion->hasVersionString();
    }

    public function isLatestVersionCompatible(): bool
    {
        if ($this->latestVersion === null || $this->latestCompatibleVersion === null) {
            return true;
        }

        $latestVer = $this->latestVersion->getVersion();
        $compatibleVer = $this->latestCompatibleVersion->getVersion();

        if ($latestVer === null || $compatibleVer === null) {
            return true;
        }

        return $latestVer <= $compatibleVer;
    }
}
