<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleToggleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        $next = $user->locale === 'ar' ? 'en' : 'ar';

        $user->forceFill(['locale' => $next])->save();

        return back();
    }
}
