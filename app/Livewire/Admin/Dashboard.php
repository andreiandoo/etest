<?php

namespace App\Livewire\Admin;

use App\Enums\PublicationStatus;
use App\Models\AffiliateResource;
use App\Models\ApiClient;
use App\Models\ApiUsageDaily;
use App\Models\ContentImport;
use App\Models\LeadSubmission;
use App\Models\NewsletterSubscription;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\SponsorCampaign;
use App\Models\Tenant;
use App\Models\TestDefinition;
use App\Models\Vertical;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public function render(): View
    {
        return view('livewire.admin.dashboard', [
            'stats' => [
                'verticals' => Vertical::query()->count(),
                'questions' => Question::query()->count(),
                'reviewQuestions' => Question::query()->where('status', PublicationStatus::Review->value)->count(),
                'tests' => TestDefinition::query()->count(),
                'reviewTests' => TestDefinition::query()->where('status', PublicationStatus::Review->value)->count(),
                'openReports' => QuestionReport::query()->where('status', 'open')->count(),
                'imports' => ContentImport::query()->count(),
                'activeSponsors' => SponsorCampaign::query()->currentlyActive()->count(),
                'newLeads' => LeadSubmission::query()->where('status', 'new')->count(),
                'affiliateResources' => AffiliateResource::query()->currentlyActive()->count(),
                'newsletterActive' => NewsletterSubscription::query()->where('status', 'active')->count(),
                'tenants' => Tenant::query()->count(),
                'apiClients' => ApiClient::query()->where('is_active', true)->count(),
                'apiRequests30d' => (int) ApiUsageDaily::query()
                    ->where('usage_date', '>=', now()->subDays(29)->toDateString())
                    ->sum('request_count'),
            ],
        ]);
    }
}
