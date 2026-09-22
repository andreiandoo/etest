<?php

namespace App\Http\Controllers;

use App\Enums\AttemptStatus;
use App\Models\TestAttempt;
use App\Services\Seo\PublicUrlGenerator;
use App\Services\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class AttemptResultController extends Controller
{
    public function __invoke(
        TestAttempt $attempt,
        PublicUrlGenerator $urls,
        TenantContext $tenantContext,
    ): View {
        $attempt->loadMissing('test.vertical');

        abort_unless(
            $attempt->user_id === Auth::id()
            && $attempt->status === AttemptStatus::Completed
            && $tenantContext->allowsVertical($attempt->test->vertical),
            404
        );

        $attempt->load([
            'test.vertical',
            'questions.answer',
        ]);

        return view('attempts.results', [
            'attempt' => $attempt,
            'presentationUrl' => $urls->test($attempt->test),
        ]);
    }
}
