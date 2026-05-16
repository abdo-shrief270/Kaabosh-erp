<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Auth\Services\TwoFactorService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    /**
     * Enable 2FA - returns secret, QR URI, and recovery codes.
     */
    public function enable(Request $request): JsonResponse
    {
        $user = $request->user()->fresh();

        if ($user->two_factor_enabled) {
            return response()->json(['message' => '2FA is already enabled.'], 422);
        }

        $result = TwoFactorService::enable($user);

        return response()->json([
            'data' => [
                'secret' => $result['secret'],
                'qr_uri' => $result['qr_uri'],
                'recovery_codes' => $result['recovery_codes'],
            ],
            'message' => '2FA enabled. Store recovery codes securely — they will not be shown again.',
        ]);
    }

    /**
     * Disable 2FA (requires current password for security).
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|current_password']);

        TwoFactorService::disable($request->user());

        return response()->json(['message' => '2FA disabled.']);
    }

    /**
     * Verify a 2FA code (used during login flow).
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|min:6|max:10']);

        $user = $request->user();

        if (! TwoFactorService::verify($user, $request->input('code'))) {
            return response()->json(['message' => 'Invalid 2FA code.'], 422);
        }

        return response()->json(['message' => '2FA verified.']);
    }

    /**
     * Get 2FA status for the current user.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'enabled' => $user->two_factor_enabled ?? false,
            ],
        ]);
    }
}
