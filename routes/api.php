<?php

use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\QuestionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['api.key', 'api.quota'])
    ->group(function () {
        Route::get('/status', [CatalogController::class, 'status'])
            ->name('api.v1.status');

        Route::get('/verticals', [CatalogController::class, 'verticals'])
            ->middleware('api.scope:catalog:read')
            ->name('api.v1.verticals');

        Route::get('/verticals/{vertical:slug}/tests', [CatalogController::class, 'tests'])
            ->middleware('api.scope:tests:read')
            ->name('api.v1.vertical-tests');

        Route::get('/tests/{test:id}', [CatalogController::class, 'test'])
            ->whereNumber('test')
            ->middleware('api.scope:tests:read')
            ->name('api.v1.tests.show');

        Route::get('/tests/{test:id}/questions', [QuestionController::class, 'index'])
            ->whereNumber('test')
            ->middleware('api.scope:questions:read')
            ->name('api.v1.test-questions');
    });
