<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Shared\Models\NotificationPreference;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    /**
     * Available notification types.
     */
    private const NOTIFICATION_TYPES = [
        'invoice_sent',
        'payment_received',
        'invoice_overdue',
        'team_invite',
        'document_shared',
        'eta_status_change',
        'payroll_ready',
        'timesheet_approved',
        'timesheet_rejected',
    ];

    /**
     * Available channels.
     */
    private const CHANNELS = ['email', 'database', 'sms'];

    /**
     * Return the authenticated user's notification preferences, grouped by type.
     */
    public function index(Request $request): JsonResponse
    {
        $preferences = NotificationPreference::where('user_id', $request->user()->id)->get();

        // Build a complete map with defaults (enabled=true) for any missing entries
        $grouped = [];

        foreach (self::NOTIFICATION_TYPES as $type) {
            $channels = [];

            foreach (self::CHANNELS as $channel) {
                $pref = $preferences->first(
                    fn (NotificationPreference $p) => $p->type === $type && $p->channel === $channel,
                );

                $channels[$channel] = $pref ? $pref->enabled : true;
            }

            $grouped[$type] = $channels;
        }

        return response()->json(['data' => $grouped]);
    }

    /**
     * Bulk upsert notification preferences.
     *
     * Accepts: { preferences: [{ type: string, channel: string, enabled: bool }] }
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*.type' => ['required', 'string', 'in:'.implode(',', self::NOTIFICATION_TYPES)],
            'preferences.*.channel' => ['required', 'string', 'in:'.implode(',', self::CHANNELS)],
            'preferences.*.enabled' => ['required', 'boolean'],
        ]);

        $userId = $request->user()->id;

        foreach ($validated['preferences'] as $pref) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $userId,
                    'type' => $pref['type'],
                    'channel' => $pref['channel'],
                ],
                [
                    'enabled' => $pref['enabled'],
                ],
            );
        }

        return response()->json(['message' => 'Notification preferences updated successfully.']);
    }
}
