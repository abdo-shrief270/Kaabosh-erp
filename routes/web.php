<?php

use App\Http\Controllers\Admin\LocaleToggleController;
use App\Http\Controllers\AdminHealthController;
use Illuminate\Support\Facades\Route;

// SuperAdmin operational health probe. Guarded by auth + super_admin so only
// platform operators can hit it; returns 503 when DB or cache are unreachable.
// Path moved from `/admin/health` after the Filament panel was remounted at
// the site root; the route name is unchanged so blade `route('admin.health')`
// references keep working.
Route::middleware(['web', 'auth', 'super_admin'])
    ->get('/health', [AdminHealthController::class, 'show'])
    ->name('admin.health');

Route::middleware(['web', 'auth', 'super_admin'])
    ->post('/locale/toggle', LocaleToggleController::class)
    ->name('admin.locale.toggle');
