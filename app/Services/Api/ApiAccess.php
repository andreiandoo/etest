<?php

namespace App\Services\Api;

use App\Models\ApiKey;
use App\Models\Vertical;

final class ApiAccess
{
    public function allowsVertical(ApiKey $key, Vertical|int $vertical): bool
    {
        $tenant = $key->client->tenant;

        if ($tenant === null) {
            return true;
        }

        $verticalId = $vertical instanceof Vertical ? $vertical->id : $vertical;

        return $tenant->verticals->contains('id', $verticalId);
    }
}
