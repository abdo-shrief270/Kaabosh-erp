<?php

declare(strict_types=1);

namespace App\Domain\Integration\Services;

use App\Domain\Integration\Models\IntegrationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Workspace integration service.
 * - Calendar: Create events for invoice due dates, meeting reminders
 * - Drive: Upload/download documents to Google Drive folders
 *
 * Requires OAuth2 setup via Google Cloud Console.
 * Credentials stored in integration_settings table.
 */
class GoogleWorkspaceService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const CALENDAR_API = 'https://www.googleapis.com/calendar/v3';

    private const DRIVE_API = 'https://www.googleapis.com/upload/drive/v3';

    private const DRIVE_FILES_API = 'https://www.googleapis.com/drive/v3';

    /**
     * Check if Google integration is configured.
     */
    public static function isConfigured(): bool
    {
        return IntegrationSetting::isActive('google');
    }

    /**
     * Generate OAuth2 authorization URL.
     * User is redirected here to grant access.
     */
    public static function getAuthUrl(string $redirectUri, string $state = ''): string
    {
        $clientId = IntegrationSetting::credential('google', 'client_id');

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/calendar',
                'https://www.googleapis.com/auth/calendar.events',
                'https://www.googleapis.com/auth/drive.file',
            ]),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return self::AUTH_URL.'?'.$params;
    }

    /**
     * Exchange authorization code for access/refresh tokens.
     */
    public static function exchangeCode(string $code, string $redirectUri): ?array
    {
        $clientId = IntegrationSetting::credential('google', 'client_id');
        $clientSecret = IntegrationSetting::credential('google', 'client_secret');

        try {
            $response = Http::post(self::TOKEN_URL, [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if ($response->successful()) {
                $tokens = $response->json();

                // Store refresh token in integration settings
                $setting = IntegrationSetting::forProvider('google');
                if ($setting) {
                    $credentials = $setting->credentials ?? [];
                    $credentials['access_token'] = $tokens['access_token'];
                    $credentials['refresh_token'] = $tokens['refresh_token'] ?? $credentials['refresh_token'] ?? null;
                    $credentials['token_expires_at'] = now()->addSeconds($tokens['expires_in'] ?? 3600)->toISOString();
                    $setting->update(['credentials' => $credentials]);
                }

                return $tokens;
            }

            Log::error('Google OAuth exchange failed', ['body' => $response->body()]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Google OAuth exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Get a valid access token (refresh if expired).
     */
    private static function getAccessToken(): ?string
    {
        $setting = IntegrationSetting::forProvider('google');
        if (! $setting) {
            return null;
        }

        $credentials = $setting->credentials ?? [];
        $accessToken = $credentials['access_token'] ?? null;
        $expiresAt = $credentials['token_expires_at'] ?? null;
        $refreshToken = $credentials['refresh_token'] ?? null;

        // Check if token is still valid
        if ($accessToken && $expiresAt && now()->lt($expiresAt)) {
            return $accessToken;
        }

        // Refresh the token
        if (! $refreshToken) {
            return null;
        }

        try {
            $response = Http::post(self::TOKEN_URL, [
                'client_id' => $credentials['client_id'],
                'client_secret' => $credentials['client_secret'],
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $tokens = $response->json();
                $credentials['access_token'] = $tokens['access_token'];
                $credentials['token_expires_at'] = now()->addSeconds($tokens['expires_in'] ?? 3600)->toISOString();
                $setting->update(['credentials' => $credentials]);

                return $tokens['access_token'];
            }
        } catch (\Throwable $e) {
            Log::error('Google token refresh failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    // ── Calendar ──────────────────────────────────────────────

    /**
     * Create a calendar event (e.g., invoice due date reminder).
     */
    public static function createCalendarEvent(array $event): ?array
    {
        $token = self::getAccessToken();
        if (! $token) {
            return null;
        }

        $calendarId = IntegrationSetting::configValue('google', 'calendar_id', 'primary');

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->post(self::CALENDAR_API."/calendars/{$calendarId}/events", [
                    'summary' => $event['title'],
                    'description' => $event['description'] ?? '',
                    'start' => [
                        'dateTime' => $event['start'],
                        'timeZone' => $event['timezone'] ?? 'Africa/Cairo',
                    ],
                    'end' => [
                        'dateTime' => $event['end'] ?? $event['start'],
                        'timeZone' => $event['timezone'] ?? 'Africa/Cairo',
                    ],
                    'reminders' => [
                        'useDefault' => false,
                        'overrides' => [
                            ['method' => 'popup', 'minutes' => 60],
                            ['method' => 'email', 'minutes' => 1440], // 1 day before
                        ],
                    ],
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Google Calendar event creation failed', ['body' => $response->body()]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Google Calendar exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    // ── Drive ─────────────────────────────────────────────────

    /**
     * Upload a file to Google Drive.
     */
    public static function uploadFile(string $filePath, string $filename, ?string $folderId = null, string $mimeType = 'application/pdf'): ?array
    {
        $token = self::getAccessToken();
        if (! $token) {
            return null;
        }

        $folderId = $folderId ?? IntegrationSetting::configValue('google', 'drive_folder_id');

        try {
            // Create file metadata
            $metadata = ['name' => $filename];
            if ($folderId) {
                $metadata['parents'] = [$folderId];
            }

            $response = Http::withToken($token)
                ->timeout(30)
                ->attach('metadata', json_encode($metadata), 'metadata.json', ['Content-Type' => 'application/json'])
                ->attach('file', file_get_contents($filePath), $filename, ['Content-Type' => $mimeType])
                ->post(self::DRIVE_API.'/files?uploadType=multipart');

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Google Drive upload failed', ['body' => $response->body()]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Google Drive exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * List files in a Drive folder.
     */
    public static function listFiles(?string $folderId = null, int $limit = 20): array
    {
        $token = self::getAccessToken();
        if (! $token) {
            return [];
        }

        $folderId = $folderId ?? IntegrationSetting::configValue('google', 'drive_folder_id');

        try {
            $query = $folderId ? "'{$folderId}' in parents and trashed=false" : 'trashed=false';

            $response = Http::withToken($token)
                ->timeout(10)
                ->get(self::DRIVE_FILES_API.'/files', [
                    'q' => $query,
                    'pageSize' => $limit,
                    'fields' => 'files(id,name,mimeType,size,createdTime,webViewLink)',
                    'orderBy' => 'createdTime desc',
                ]);

            if ($response->successful()) {
                return $response->json('files', []);
            }

            return [];
        } catch (\Throwable $e) {
            Log::error('Google Drive list error', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
