<?php

namespace App\Services\Api;

use App\Models\ApiClient;
use App\Models\ApiKey;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ApiKeyIssuer
{
    /**
     * @param  array<int, string>  $scopes
     * @return array{key:ApiKey,plain_text:string}
     */
    public function issue(
        ApiClient $client,
        string $name,
        array $scopes,
        ?int $dailyQuota = null,
        ?int $monthlyQuota = null,
        ?\DateTimeInterface $expiresAt = null,
    ): array {
        if (! $client->is_active) {
            throw new InvalidArgumentException('Cannot issue an API key for an inactive client.');
        }

        $client->loadMissing('tenant');

        if ($client->tenant !== null && ! $client->tenant->is_active) {
            throw new InvalidArgumentException('Cannot issue an API key for an inactive tenant.');
        }

        $scopes = array_values(array_unique($scopes));

        foreach ($scopes as $scope) {
            if (! ApiScopes::isAllowed($scope)) {
                throw new InvalidArgumentException('Unsupported API scope: '.$scope);
            }
        }

        $secret = Str::random(48);
        $plainText = 'et_live_'.$client->id.'_'.Str::random(8).'_'.$secret;

        $key = ApiKey::create([
            'api_client_id' => $client->id,
            'name' => $name,
            'key_prefix' => substr($plainText, 0, 16),
            'key_hash' => hash('sha256', $plainText),
            'scopes' => $scopes,
            'daily_quota' => $dailyQuota,
            'monthly_quota' => $monthlyQuota,
            'expires_at' => $expiresAt,
        ]);

        return [
            'key' => $key,
            'plain_text' => $plainText,
        ];
    }
}
