<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use App\Models\Vertical;

final class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function isWhiteLabel(): bool
    {
        return $this->tenant !== null;
    }

    public function allowsVertical(Vertical|int $vertical): bool
    {
        if ($this->tenant === null) {
            return true;
        }

        $verticalId = $vertical instanceof Vertical ? $vertical->id : $vertical;

        if ($this->tenant->relationLoaded('verticals')) {
            return $this->tenant->verticals->contains('id', $verticalId);
        }

        return $this->tenant->verticals()->whereKey($verticalId)->exists();
    }

    /**
     * @return array<int, int>|null
     */
    public function allowedVerticalIds(): ?array
    {
        if ($this->tenant === null) {
            return null;
        }

        if (! $this->tenant->relationLoaded('verticals')) {
            $this->tenant->load('verticals');
        }

        return $this->tenant->verticals
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function branding(): array
    {
        if ($this->tenant === null) {
            return [];
        }

        return $this->tenant->branding ?? [];
    }

    public function brand(string $key, mixed $default = null): mixed
    {
        return data_get($this->branding(), $key, $default);
    }
}
