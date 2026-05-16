<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Auth\Services\PermissionService;
use App\Domain\Client\Models\Client;
use App\Domain\Document\Models\Document;
use App\Domain\Payroll\Models\Employee;
use App\Domain\Payroll\Models\PayrollRun;
use App\Domain\Shared\Enums\UserRole;
use App\Domain\Shared\Models\FeatureFlag;
use App\Domain\Shared\Observers\FeatureFlagObserver;
use App\Domain\Shared\Services\QueryAnalyzer;
use App\Domain\Subscription\Models\Plan;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Observers\PlanObserver;
use App\Domain\Subscription\Observers\SubscriptionObserver;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Observers\TaskObserver;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\TimeTracking\Models\TimesheetEntry;
use App\Models\User;
use App\Policies\ClientPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\PayrollRunPolicy;
use App\Policies\SuperAdmin\FeatureFlagPolicy as SuperAdminFeatureFlagPolicy;
use App\Policies\SuperAdmin\PlanPolicy as SuperAdminPlanPolicy;
use App\Policies\SuperAdmin\SubscriptionPolicy as SuperAdminSubscriptionPolicy;
use App\Policies\SuperAdmin\TenantPolicy as SuperAdminTenantPolicy;
use App\Policies\SuperAdmin\UserPolicy as SuperAdminUserPolicy;
use App\Policies\TimesheetEntryPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Single-company-per-account: legacy tenant bindings retained as
        // no-ops so any straggling tenant() / tenant.id resolutions don't
        // explode. Multi-tenancy removal will excise these.
        $this->app->singleton('tenant', fn () => null);
        $this->app->singleton('tenant.id', fn () => null);
    }

    public function boot(): void
    {
        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            $basename = class_basename($modelName);

            return "Database\\Factories\\{$basename}Factory";
        });

        $this->registerPolicies();
        $this->registerPermissionGates();
        $this->configureRateLimiting();
        $this->registerModelObservers();
        $this->registerAutomationListeners();
        $this->registerSocketIdDedup();
        $this->configurePasswordPolicy();
        $this->configurePasswordResetUrl();

        QueryAnalyzer::register(threshold: 5);
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        if (! $this->app->isProduction()) {
            DB::listen(function ($query): void {
                if ($query->time > 500) {
                    logger()->warning('Slow query detected', [
                        'sql' => $query->sql,
                        'time' => $query->time.'ms',
                        'bindings' => $query->bindings,
                    ]);
                }
            });
        }
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(120)->by($request->user()->id)
                : Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('admin', fn (Request $request) => Limit::perMinute(200)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('admin-login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('reports', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('exports', fn (Request $request) => Limit::perMinute(5)->by($request->user()?->id ?: $request->ip()));
    }

    private function registerModelObservers(): void
    {
        Plan::observe(PlanObserver::class);
        Subscription::observe(SubscriptionObserver::class);
        FeatureFlag::observe(FeatureFlagObserver::class);
        Task::observe(TaskObserver::class);
    }

    /**
     * Wire task-board domain events to the automation listener so user
     * actions (create / move / assign) fan out into rule evaluation
     * without each controller having to know about automation.
     */
    private function registerAutomationListeners(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \App\Domain\TaskBoard\Events\TaskCreated::class,
            [\App\Domain\TaskBoard\Listeners\EvaluateAutomationRules::class, 'handleTaskCreated'],
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Domain\TaskBoard\Events\TaskMoved::class,
            [\App\Domain\TaskBoard\Listeners\EvaluateAutomationRules::class, 'handleTaskMoved'],
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Domain\TaskBoard\Events\TaskAssigneesChanged::class,
            [\App\Domain\TaskBoard\Listeners\EvaluateAutomationRules::class, 'handleTaskAssigneesChanged'],
        );

        // Outbound webhooks — fan domain events out to tenant-configured
        // subscribers (Slack / Discord / generic HTTP).
        \Illuminate\Support\Facades\Event::listen(
            \App\Domain\TaskBoard\Events\TaskCreated::class,
            [\App\Domain\TaskBoard\Listeners\FanoutWebhookEvents::class, 'handleTaskCreated'],
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Domain\TaskBoard\Events\TaskMoved::class,
            [\App\Domain\TaskBoard\Listeners\FanoutWebhookEvents::class, 'handleTaskMoved'],
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Domain\TaskBoard\Events\TaskUpdated::class,
            [\App\Domain\TaskBoard\Listeners\FanoutWebhookEvents::class, 'handleTaskUpdated'],
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Domain\TaskBoard\Events\TaskDeleted::class,
            [\App\Domain\TaskBoard\Listeners\FanoutWebhookEvents::class, 'handleTaskDeleted'],
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Domain\TaskBoard\Events\CommentAdded::class,
            [\App\Domain\TaskBoard\Listeners\FanoutWebhookEvents::class, 'handleCommentAdded'],
        );
    }

    /**
     * Auto-populate every ShouldBroadcast event's `socket` property from
     * the X-Socket-ID request header so the originator's own Echo channel
     * doesn't receive an echo of the change they just made. Equivalent to
     * `broadcast(new Event)->toOthers()` but works for every dispatch
     * site without touching call sites.
     *
     * The wildcard listener fires synchronously during dispatch — before
     * Laravel queues the BroadcastEvent job that reads $event->socket.
     */
    private function registerSocketIdDedup(): void
    {
        \Illuminate\Support\Facades\Event::listen('*', function ($eventName, array $payload): void {
            if (! request()->hasHeader('X-Socket-ID')) {
                return;
            }
            foreach ($payload as $event) {
                if (! is_object($event)) continue;
                if (! $event instanceof \Illuminate\Contracts\Broadcasting\ShouldBroadcast) continue;
                if (! property_exists($event, 'socket')) continue;
                if ($event->socket !== null) continue; // caller already set it
                $event->socket = (string) request()->header('X-Socket-ID');
            }
        });
    }

    private function registerPolicies(): void
    {
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(PayrollRun::class, PayrollRunPolicy::class);
        Gate::policy(TimesheetEntry::class, TimesheetEntryPolicy::class);

        Gate::policy(Tenant::class, SuperAdminTenantPolicy::class);
        Gate::policy(Plan::class, SuperAdminPlanPolicy::class);
        Gate::policy(Subscription::class, SuperAdminSubscriptionPolicy::class);
        Gate::policy(FeatureFlag::class, SuperAdminFeatureFlagPolicy::class);
        Gate::policy(User::class, SuperAdminUserPolicy::class);
    }

    private function configurePasswordPolicy(): void
    {
        Password::defaults(function () {
            if ($this->app->isProduction()) {
                return Password::min(10)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised();
            }

            return Password::min(8);
        });
    }

    private function configurePasswordResetUrl(): void
    {
        ResetPassword::createUrlUsing(function ($notifiable, string $token): string {
            $base = config('app.spa_reset_password_url')
                ?: rtrim((string) config('app.url'), '/').'/auth/reset-password';

            return $base.'?'.http_build_query([
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });

        ResetPassword::toMailUsing(function ($notifiable, string $token): MailMessage {
            $url = call_user_func(ResetPassword::$createUrlCallback, $notifiable, $token);

            $previousLocale = app()->getLocale();
            $userLocale = $notifiable->locale ?? $previousLocale;
            $locale = in_array($userLocale, ['ar', 'en'], true) ? $userLocale : 'ar';
            app()->setLocale($locale);

            $appName = config('app.name', 'Kaabosh');
            $expiresMinutes = config('auth.passwords.users.expire', 60);

            $message = (new MailMessage)
                ->subject(__('emails.reset_password.subject', ['app' => $appName]))
                ->greeting(__('emails.reset_password.greeting', ['name' => $notifiable->name ?? '']))
                ->line(__('emails.reset_password.line_intro'))
                ->action(__('emails.reset_password.action'), $url)
                ->line(__('emails.reset_password.line_expires', ['count' => $expiresMinutes]))
                ->line(__('emails.reset_password.line_ignore'))
                ->salutation(__('emails.reset_password.salutation', ['app' => $appName]));

            app()->setLocale($previousLocale);

            return $message;
        });
    }

    private function registerPermissionGates(): void
    {
        Gate::before(fn (User $user) => $user->role === UserRole::SuperAdmin ? true : null);

        Gate::define('viewLogViewer', fn (?User $user) => $user?->role === UserRole::SuperAdmin);

        Gate::after(function (User $user, string $ability, ?bool $result) {
            if ($result !== null) {
                return $result;
            }

            return PermissionService::hasPermission($user, $ability) ?: null;
        });
    }
}
