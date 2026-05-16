<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Services\InboundEmailIngestor;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Inbound mail webhook. Provider-agnostic — Mailgun, SES, Postmark all
 * post compatible payloads if mapped to our minimum shape:
 *   recipient | subject | text | html | from
 *
 * HMAC verification is opt-in via TASK_INBOUND_EMAIL_SECRET. When set, we
 * compare the X-Inbound-Signature header against an HMAC of the raw body.
 */
class InboundEmailController extends Controller
{
    public function ingest(Request $request, InboundEmailIngestor $ingestor): JsonResponse
    {
        $secret = (string) config('services.task_inbound_email.secret', env('TASK_INBOUND_EMAIL_SECRET', ''));
        if ($secret !== '') {
            $given = (string) $request->header('X-Inbound-Signature', '');
            $expected = hash_hmac('sha256', $request->getContent(), $secret);
            if (! hash_equals($expected, $given)) {
                Log::warning('Inbound email: bad HMAC signature');
                return response()->json(['error' => 'bad_signature'], 401);
            }
        }

        $task = $ingestor->ingest($request->only(['recipient', 'subject', 'text', 'html', 'from']));

        return response()->json([
            'received' => $task !== null,
            'task_id' => $task?->id,
        ]);
    }
}
