<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Models\Task;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Public iCal feed (RFC 5545). The bearer for the URL is a per-user
 * `ical_token`; rotating it invalidates the old subscription. The feed
 * lists every task with a due_date that the user is assigned to or
 * watching, across all boards in their tenant.
 *
 * The route is intentionally public — calendar clients can't run a
 * bearer auth flow. Treat the token like a password.
 */
class TaskBoardICalController extends Controller
{
    public function feed(string $token): Response
    {
        $user = User::query()->where('ical_token', $token)->first();
        abort_unless($user, 404);

        $now = now();
        $tasks = Task::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $user->tenant_id)
            ->whereNotNull('due_date')
            ->whereNull('archived_at')
            ->where(function ($q) use ($user) {
                $q->where('primary_assignee_id', $user->id)
                    ->orWhereHas('assignees', fn ($a) => $a->where('users.id', $user->id))
                    ->orWhereHas('watchers',  fn ($a) => $a->where('users.id', $user->id));
            })
            ->with(['board:id,name'])
            ->orderBy('due_date')
            ->limit(1000)
            ->get();

        $domain = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'kaabosh.local';
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Kaabosh//Task board//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$this->escape('Kaabosh — '.$user->name),
            'X-WR-TIMEZONE:UTC',
            'X-PUBLISHED-TTL:PT15M',
        ];

        foreach ($tasks as $t) {
            $start = \Carbon\Carbon::parse($t->due_date)->utc();
            $end = $start->copy()->addHour();
            // Use task.created_at as the DTSTAMP — required by RFC 5545.
            $stamp = $t->created_at ? \Carbon\Carbon::parse($t->created_at)->utc() : $now;

            $url = rtrim((string) config('app.frontend_url', config('app.url')), '/')
                .'/task-board/'.$t->board_id.'?task='.$t->id;
            $summary = '['.($t->reference ?? '').'] '.$t->title;
            if ($t->board?->name) {
                $summary .= ' · '.$t->board->name;
            }
            $description = Str::limit((string) ($t->description ?? ''), 800);

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:task-'.$t->id.'@'.$domain;
            $lines[] = 'DTSTAMP:'.$stamp->format('Ymd\THis\Z');
            $lines[] = 'DTSTART:'.$start->format('Ymd\THis\Z');
            $lines[] = 'DTEND:'.$end->format('Ymd\THis\Z');
            $lines[] = 'SUMMARY:'.$this->escape($summary);
            if ($description !== '') {
                $lines[] = 'DESCRIPTION:'.$this->escape($description);
            }
            $lines[] = 'URL:'.$this->escape($url);
            $lines[] = 'STATUS:'.($t->completed_at ? 'CANCELLED' : 'CONFIRMED');
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        // RFC 5545 line endings + 75-octet folding for safety.
        $body = implode("\r\n", array_map([$this, 'foldLine'], $lines))."\r\n";

        return response($body, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="kaabosh-tasks.ics"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function token(Request $request): JsonResponse
    {
        $authUser = $request->user();
        abort_unless($authUser, 401);
        // Re-fetch so every column (including ical_token) is loaded.
        // Sanctum's user provider may select a subset, and strict-mode
        // models throw on missing-attribute access.
        $user = User::query()->whereKey($authUser->id)->firstOrFail();
        if (! $user->ical_token) {
            $user->forceFill(['ical_token' => $this->newToken()])->save();
        }
        return response()->json([
            'url' => url('/api/v1/ical/tasks/'.$user->ical_token.'.ics'),
            'token' => $user->ical_token,
        ]);
    }

    public function rotate(Request $request): JsonResponse
    {
        $authUser = $request->user();
        abort_unless($authUser, 401);
        $user = User::query()->whereKey($authUser->id)->firstOrFail();
        $user->forceFill(['ical_token' => $this->newToken()])->save();
        return response()->json([
            'url' => url('/api/v1/ical/tasks/'.$user->ical_token.'.ics'),
            'token' => $user->ical_token,
        ]);
    }

    private function newToken(): string
    {
        return bin2hex(random_bytes(24));
    }

    /** Escape RFC 5545 special characters in TEXT values. */
    private function escape(string $value): string
    {
        $v = str_replace(
            ['\\', "\n", "\r\n", ',', ';'],
            ['\\\\', '\\n', '\\n', '\\,', '\\;'],
            $value,
        );
        return $v;
    }

    /** Fold lines longer than 75 octets per RFC 5545 §3.1. */
    private function foldLine(string $line): string
    {
        if (strlen($line) <= 75) return $line;
        $out = '';
        $first = true;
        while (strlen($line) > 0) {
            $chunkLen = $first ? 75 : 74; // continuation lines start with a space
            $chunk = substr($line, 0, $chunkLen);
            $line = substr($line, $chunkLen);
            $out .= ($first ? '' : "\r\n ").$chunk;
            $first = false;
        }
        return $out;
    }
}
