<?php

declare(strict_types=1);

use App\Http\Controllers\PublicDocumentController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

Route::get('/verify/{type}/{uuid}', [PublicDocumentController::class, 'verify'])
    ->where('type', 'invoice|quote|delivery-note')
    ->whereUuid('uuid')
    ->name('public.verify');

Route::get('/{any?}', SpaController::class)->where('any', '.*');
