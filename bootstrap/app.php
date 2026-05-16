<?php

declare(strict_types=1);

use App\Http\ApiResponse;
use App\Http\Middleware\AdminIpWhitelist;
use App\Http\Middleware\ApiVersion;
use App\Http\Middleware\CachePublicResponse;
use App\Http\Middleware\CheckFeature;
use App\Http\Middleware\CheckLimit;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\CompressResponse;
use App\Http\Middleware\Deprecated;
use App\Http\Middleware\DetectLocale;
use App\Http\Middleware\Enforce2fa;
use App\Http\Middleware\EnforceSuperAdmin2fa;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\IdempotencyKey;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\LogAdminActivity;
use App\Http\Middleware\LogApiRequest;
use App\Http\Middleware\LogImpersonatedApiRequests;
use App\Http\Middleware\PreventDuplicateRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTimezone;
use App\Http\Middleware\ThrottleAdminLogin;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Prepend locale detection so errors are in the correct language
        $middleware->prependToGroup('api', [
            DetectLocale::class,
        ]);

        // Append global middleware to all API responses
        $middleware->appendToGroup('api', [
            SecurityHeaders::class,
            CompressResponse::class,
            ApiVersion::class,
            LogApiRequest::class,
        ]);

        $middleware->alias([
            'tenant' => IdentifyTenant::class,
            'super_admin' => EnsureSuperAdmin::class,
            'active' => EnsureActiveUser::class,
            'subscription' => CheckSubscription::class,
            'feature' => CheckFeature::class,
            'limit' => CheckLimit::class,
            'set_timezone' => SetTimezone::class,
            'set_locale' => SetLocale::class,
            'permission' => CheckPermission::class,
            'board.access' => \App\Http\Middleware\EnsureBoardAccess::class,
            'cache.public' => CachePublicResponse::class,
            'admin.ip' => AdminIpWhitelist::class,
            'idempotent' => IdempotencyKey::class,
            'no-duplicate' => PreventDuplicateRequests::class,
            'enforce.2fa' => Enforce2fa::class,
            'admin.audit' => LogAdminActivity::class,
            'admin.2fa' => EnforceSuperAdmin2fa::class,
            'admin.login.throttle' => ThrottleAdminLogin::class,
            'deprecated' => Deprecated::class,
            'impersonation.log' => LogImpersonatedApiRequests::class,
        ]);

        // IMPORTANT: Authenticate must appear before IdentifyTenant so
        // `$request->user()` is populated by the time IdentifyTenant reads
        // it for its priority-4 (authenticated user's home tenant) fallback.
        // Without this, Laravel's SortedMiddleware reorder pulls
        // IdentifyTenant in front of Authenticate:sanctum and every
        // tenant-scoped endpoint 404s "Tenant not found." for users who
        // rely on the fallback rather than an explicit X-Tenant header.
        $middleware->priority([
            \Illuminate\Auth\Middleware\Authenticate::class,
            IdentifyTenant::class,
            SetTimezone::class,
            SetLocale::class,
            SubstituteBindings::class,
        ]);

        // Token-based API auth (no CSRF needed)
        // $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ApprovalRequiredException must come BEFORE the generic ValidationException
        // handler so its richer payload wins. Structured response lets the SPA
        // offer a "Request approval" CTA inline.
        $exceptions->renderable(function (\App\Domain\Workflow\Exceptions\ApprovalRequiredException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $msg = collect($e->errors())->flatten()->first() ?: 'This action requires approval.';
                return response()->json([
                    'error'    => 'approval_required',
                    'message'  => $msg,
                    'errors'   => $e->errors(),
                    'approval' => [
                        'entity_type' => $e->entityType,
                        'entity_id'   => $e->entityId,
                        'amount'      => $e->amount,
                    ],
                ], 422);
            }
        });

        // Standardize all API error responses
        $exceptions->renderable(function (ValidationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::validationError(
                    errors: $e->errors(),
                    message: $e->getMessage(),
                );
            }
        });

        $exceptions->renderable(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::unauthorized();
            }
        });

        $exceptions->renderable(function (AuthorizationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::forbidden($e->getMessage() ?: null);
            }
        });

        $exceptions->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::notFound();
            }
        });

        $exceptions->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $model = class_basename($e->getModel());

                return ApiResponse::notFound(__('messages.error.model_not_found', ['model' => $model]));
            }
        });

        $exceptions->renderable(function (TooManyRequestsHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::tooManyRequests();
            }
        });

        $exceptions->renderable(function (HttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(
                    code: 'http_error',
                    message: $e->getMessage() ?: __('messages.error.server_error'),
                    status: $e->getStatusCode(),
                );
            }
        });

        $exceptions->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $message = app()->isProduction()
                    ? __('messages.error.server_error')
                    : $e->getMessage();

                return ApiResponse::serverError($message);
            }
        });
    })
    ->create();
