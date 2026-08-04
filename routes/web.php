<?php

declare(strict_types=1);

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

Route::get('/{any?}', SpaController::class)->where('any', '.*');
