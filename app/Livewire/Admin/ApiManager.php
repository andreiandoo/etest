<?php

namespace App\Livewire\Admin;

use App\Models\ApiClient;
use App\Models\ApiKey;
use App\Models\ApiUsageDaily;
use App\Models\Tenant;
use App\Services\Api\ApiKeyIssuer;
use App\Services\Api\ApiScopes;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class ApiManager extends Component
{
    public const SCOPES = ApiScopes::ALL;

    public ?int $editingClientId = null;

    public string $clientName = '';

    public string $contactEmail = '';

    public ?int $tenantId = null;

    public bool $clientActive = true;

    public ?int $keyClientId = null;

    public string $keyName = '';

    /** @var array<int, string> */
    public array $selectedScopes = [
        'catalog:read',
        'tests:read',
    ];

    public ?int $dailyQuota = 1000;

    public ?int $monthlyQuota = 20000;

    public string $expiresAt = '';

    public ?string $newPlainTextKey = null;

    public function editClient(int $id): void
    {
        $client = ApiClient::query()->findOrFail($id);

        $this->editingClientId = $client->id;
        $this->clientName = $client->name;
        $this->contactEmail = $client->contact_email ?? '';
        $this->tenantId = $client->tenant_id;
        $this->clientActive = $client->is_active;
    }

    public function saveClient(): void
    {
        $validated = $this->validate([
            'clientName' => ['required', 'string', 'max:160'],
            'contactEmail' => ['nullable', 'email:rfc', 'max:255'],
            'tenantId' => ['nullable', 'exists:tenants,id'],
            'clientActive' => ['boolean'],
        ]);

        ApiClient::query()->updateOrCreate(
            ['id' => $this->editingClientId],
            [
                'name' => $validated['clientName'],
                'contact_email' => $this->nullable($validated['contactEmail']),
                'tenant_id' => $validated['tenantId'],
                'is_active' => $validated['clientActive'],
            ],
        );

        $this->resetClientForm();
        session()->flash('admin_message', 'Clientul API a fost salvat.');
    }

    public function issueKey(ApiKeyIssuer $issuer): void
    {
        $validated = $this->validate([
            'keyClientId' => ['required', 'exists:api_clients,id'],
            'keyName' => ['required', 'string', 'max:160'],
            'selectedScopes' => ['required', 'array', 'min:1'],
            'selectedScopes.*' => ['string', Rule::in(self::SCOPES)],
            'dailyQuota' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            'monthlyQuota' => ['nullable', 'integer', 'min:1', 'max:1000000000'],
            'expiresAt' => ['nullable', 'date', 'after:now'],
        ]);

        $client = ApiClient::query()->with('tenant')->findOrFail($validated['keyClientId']);

        if (! $client->is_active) {
            $this->addError('keyClientId', 'Clientul API este inactiv.');

            return;
        }

        if ($client->tenant !== null && ! $client->tenant->is_active) {
            $this->addError('keyClientId', 'Tenantul asociat clientului API este inactiv.');

            return;
        }

        $issued = $issuer->issue(
            $client,
            $validated['keyName'],
            $validated['selectedScopes'],
            $validated['dailyQuota'],
            $validated['monthlyQuota'],
            $this->nullable($validated['expiresAt']) !== null
                ? Carbon::parse($validated['expiresAt'])
                : null,
        );

        $this->newPlainTextKey = $issued['plain_text'];
        $this->resetKeyForm(keepPlainText: true);
        session()->flash('admin_message', 'Cheia API a fost emisă. Copiaz-o acum; nu mai poate fi recuperată.');
    }

    public function revokeKey(int $id): void
    {
        ApiKey::query()->findOrFail($id)->forceFill([
            'revoked_at' => now(),
        ])->save();

        session()->flash('admin_message', 'Cheia API a fost revocată.');
    }

    public function clearPlainTextKey(): void
    {
        $this->newPlainTextKey = null;
    }

    public function resetClientForm(): void
    {
        $this->reset(['editingClientId', 'clientName', 'contactEmail', 'tenantId']);
        $this->clientActive = true;
        $this->resetValidation();
    }

    public function resetKeyForm(bool $keepPlainText = false): void
    {
        $plain = $this->newPlainTextKey;

        $this->reset(['keyClientId', 'keyName', 'selectedScopes', 'dailyQuota', 'monthlyQuota', 'expiresAt']);
        $this->selectedScopes = ['catalog:read', 'tests:read'];
        $this->dailyQuota = 1000;
        $this->monthlyQuota = 20000;

        if ($keepPlainText) {
            $this->newPlainTextKey = $plain;
        }

        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.api-manager', [
            'clients' => ApiClient::query()->with('tenant')->orderBy('name')->get(),
            'keys' => ApiKey::query()
                ->with('client.tenant')
                ->latest()
                ->get(),
            'tenants' => Tenant::query()->orderBy('name')->get(),
            'scopeOptions' => self::SCOPES,
            'usage30Days' => [
                'requests' => (int) ApiUsageDaily::query()
                    ->where('usage_date', '>=', now()->subDays(29)->toDateString())
                    ->sum('request_count'),
                'errors' => (int) ApiUsageDaily::query()
                    ->where('usage_date', '>=', now()->subDays(29)->toDateString())
                    ->sum('error_count'),
                'bytes' => (int) ApiUsageDaily::query()
                    ->where('usage_date', '>=', now()->subDays(29)->toDateString())
                    ->sum('response_bytes'),
            ],
        ]);
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
