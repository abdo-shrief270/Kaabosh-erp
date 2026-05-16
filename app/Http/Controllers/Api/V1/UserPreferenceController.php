<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages user UI preferences including theme (dark mode),
 * keyboard shortcuts, and display settings.
 */
class UserPreferenceController extends Controller
{
    /**
     * Get current user's UI preferences.
     *
     * Returns defaults merged with saved preferences.
     */
    public function show(): JsonResponse
    {
        $user = auth()->user();
        $defaults = self::defaults();
        $saved = $user->ui_preferences ?? [];

        return response()->json([
            'data' => array_replace_recursive($defaults, $saved),
        ]);
    }

    /**
     * Update user UI preferences (partial update — merges with existing).
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'theme' => ['nullable', 'string', 'in:light,dark,system'],
            'language' => ['nullable', 'string', 'in:ar,en'],
            'sidebar_collapsed' => ['nullable', 'boolean'],
            'compact_tables' => ['nullable', 'boolean'],
            'numbers_format' => ['nullable', 'string', 'in:en,ar'],
            'date_format' => ['nullable', 'string', 'in:Y-m-d,d/m/Y,m/d/Y'],
            'keyboard_shortcuts_enabled' => ['nullable', 'boolean'],
            'keyboard_shortcuts' => ['nullable', 'array'],
            'keyboard_shortcuts.*' => ['string', 'max:50'],
        ]);

        $user = auth()->user();
        $current = $user->ui_preferences ?? [];
        $merged = array_merge($current, array_filter($data, fn ($v) => $v !== null));

        $user->update(['ui_preferences' => $merged]);

        return response()->json([
            'data' => array_replace_recursive(self::defaults(), $merged),
        ]);
    }

    /**
     * Reset preferences to defaults.
     */
    public function reset(): JsonResponse
    {
        auth()->user()->update(['ui_preferences' => null]);

        return response()->json([
            'data' => self::defaults(),
            'message' => 'Preferences reset to defaults.',
        ]);
    }

    /**
     * Get default keyboard shortcuts configuration.
     */
    public function shortcuts(): JsonResponse
    {
        return response()->json([
            'data' => self::defaultShortcuts(),
        ]);
    }

    private static function defaults(): array
    {
        return [
            'theme' => 'light',
            'language' => 'ar',
            'sidebar_collapsed' => false,
            'compact_tables' => false,
            'numbers_format' => 'en',
            'date_format' => 'Y-m-d',
            'keyboard_shortcuts_enabled' => true,
            'keyboard_shortcuts' => self::defaultShortcuts(),
        ];
    }

    private static function defaultShortcuts(): array
    {
        return [
            'new_invoice' => 'ctrl+shift+i',
            'new_journal_entry' => 'ctrl+shift+j',
            'new_client' => 'ctrl+shift+c',
            'new_payment' => 'ctrl+shift+p',
            'search' => 'ctrl+k',
            'dashboard' => 'ctrl+shift+d',
            'save' => 'ctrl+s',
            'go_invoices' => 'g then i',
            'go_clients' => 'g then c',
            'go_accounts' => 'g then a',
            'go_reports' => 'g then r',
        ];
    }
}
