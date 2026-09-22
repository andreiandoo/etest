<?php

namespace App\Http\Controllers;

use App\Enums\PublicationStatus;
use App\Models\TestDefinition;
use App\Models\Vertical;
use Illuminate\Contracts\View\View;

class TestController extends Controller
{
    public function show(Vertical $vertical, TestDefinition $test): View
    {
        abort_unless(
            $vertical->is_active
            && $test->vertical_id === $vertical->id
            && $test->status === PublicationStatus::Published
            && $test->published_at?->isPast(),
            404
        );

        return view('tests.show', compact('vertical', 'test'));
    }
}
