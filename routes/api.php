<?php

declare(strict_types=1);

use App\Domain\Auth\Controllers\AuthController;
use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AddOnCatalogController;
use App\Http\Controllers\Api\V1\Admin\AdminActivityLogController;
use App\Http\Controllers\Api\V1\Admin\AdminApiLogController;
use App\Http\Controllers\Api\V1\Admin\AdminAuditLogController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminEmailTemplateController;
use App\Http\Controllers\Api\V1\Admin\AdminFeatureFlagController;
use App\Http\Controllers\Api\V1\Admin\AdminIntegrationController;
use App\Http\Controllers\Api\V1\Admin\AdminMetricsController;
use App\Http\Controllers\Api\V1\Admin\AdminPlatformSettingsController;
use App\Http\Controllers\Api\V1\Admin\AdminRoleController;
use App\Http\Controllers\Api\V1\Admin\AdminSubscriptionController;
use App\Http\Controllers\Api\V1\Admin\AdminTenantController;
use App\Http\Controllers\Api\V1\Admin\AdminUsageController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\AlertRuleController;
use App\Http\Controllers\Api\V1\ApiDocsController;
use App\Http\Controllers\Api\V1\ApprovalController;
use App\Http\Controllers\Api\V1\ApprovalWorkflowController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuditComplianceController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\CurrencyController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\EngagementController;
use App\Http\Controllers\Api\V1\Firm\FirmAddOnsController;
use App\Http\Controllers\Api\V1\Firm\FirmAssignmentsController;
use App\Http\Controllers\Api\V1\Firm\FirmBillingController;
use App\Http\Controllers\Api\V1\Firm\FirmController;
use App\Http\Controllers\Api\V1\Firm\FirmDashboardController;
use App\Http\Controllers\Api\V1\Firm\FirmStaffController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\LaborLawController;
use App\Http\Controllers\Api\V1\LeaveController;
use App\Http\Controllers\Api\V1\LoanController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\NotificationPreferenceController;
use App\Http\Controllers\Api\V1\PayrollController;
use App\Http\Controllers\Api\V1\PayslipController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\RbacController;
use App\Http\Controllers\Api\V1\SalaryComponentController;
use App\Http\Controllers\Api\V1\SocialInsuranceController;
use App\Http\Controllers\Api\V1\SubscriptionAddOnController;
use App\Http\Controllers\Api\V1\TaskBoard\AutomationRuleController;
use App\Http\Controllers\Api\V1\TaskBoard\BoardColumnController;
use App\Http\Controllers\Api\V1\TaskBoard\BoardController;
use App\Http\Controllers\Api\V1\TaskBoard\BoardCustomFieldController;
use App\Http\Controllers\Api\V1\TaskBoard\BoardMemberController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskCustomFieldValueController;
use App\Http\Controllers\Api\V1\TaskBoard\BoardInsightsController;
use App\Http\Controllers\Api\V1\TaskBoard\InboundEmailController;
use App\Http\Controllers\Api\V1\TaskBoard\InboxController;
use App\Http\Controllers\Api\V1\TaskBoard\MentionRoleController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskShareController;
use App\Http\Controllers\Api\V1\TaskBoard\CommentReactionController;
use App\Http\Controllers\Api\V1\TaskBoard\TagController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskActivityController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskApprovalController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskAttachmentController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskBulkController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskChecklistController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskCommentController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskCsvController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskArchiveController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskPersonalFlagController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskReminderController;
use App\Http\Controllers\Api\V1\TaskBoard\SprintController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskBoardICalController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskBoardViewController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskGithubLinkController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskBoardWebhookController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskReactionController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskTemplateController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskDependencyController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskRecurrenceController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskTimerController;
use App\Http\Controllers\Api\V1\TaskBoard\TaskTypeController;
use App\Http\Controllers\Api\V1\TaskBoard\VersionController;
use App\Http\Controllers\Api\V1\TaskBoard\WorkflowTransitionController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\TenantBrandingController;
use App\Http\Controllers\Api\V1\TimeBillingController;
use App\Http\Controllers\Api\V1\TimerController;
use App\Http\Controllers\Api\V1\TimesheetController;
use App\Http\Controllers\Api\V1\TwoFactorController;
use App\Http\Controllers\Api\V1\UserPreferenceController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\V1\WebhookEndpointController;
use App\Http\Controllers\Api\V1\WorkingPaperController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Kaabosh ERP API Routes — v1
|--------------------------------------------------------------------------
| Single-company-per-account. No tenant switching. Each authenticated user
| belongs to exactly one Company; data is scoped via user.company_id.
*/

Route::prefix('v1')->group(function (): void {

    // ──────────────────────────────────────
    // Public
    // ──────────────────────────────────────
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1')->name('auth.register');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')->name('auth.login');

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1')->name('auth.forgot-password');

    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:5,1')->name('auth.reset-password');

    Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/{plan}', [PlanController::class, 'show'])->name('plans.show');
    Route::get('/add-ons', [AddOnCatalogController::class, 'index'])->name('add-ons.index');


    Route::get('/health', HealthCheckController::class)->name('health');

    // Public read-only share link — no auth, token in URL.
    Route::get('/public/shared-task/{token}', [TaskShareController::class, 'publicShow'])
        ->where('token', '[A-Za-z0-9]+')
        ->middleware('throttle:60,1')
        ->name('public.shared-task');

    // Public iCal feed — bearer is the token in the URL.
    Route::get('/ical/tasks/{token}.ics', [TaskBoardICalController::class, 'feed'])
        ->where('token', '[A-Za-z0-9]+')
        ->middleware('throttle:60,1')
        ->name('public.ical-feed');

    // Inbound email webhook — provider-agnostic, HMAC-verified when secret is set.
    Route::post('/inbound-email', [InboundEmailController::class, 'ingest'])
        ->middleware('throttle:120,1')
        ->name('inbound-email');
    Route::get('/docs', [ApiDocsController::class, 'ui'])->name('docs.ui');
    Route::get('/docs/spec', [ApiDocsController::class, 'spec'])->name('docs.spec');

    // Payment gateway webhooks (signature-verified)
    Route::post('/webhooks/paymob', [WebhookController::class, 'paymob'])->name('webhooks.paymob');
    Route::post('/webhooks/fawry', [WebhookController::class, 'fawry'])->name('webhooks.fawry');

    // ──────────────────────────────────────
    // Authenticated
    // ──────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function (): void {

        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::put('/profile', [AuthController::class, 'updateProfile'])->name('auth.update-profile');
        Route::post('/change-password', [AuthController::class, 'changePassword'])
            ->middleware('throttle:3,1')->name('auth.change-password');

        // 2FA
        Route::prefix('2fa')->name('2fa.')->group(function (): void {
            Route::get('status', [TwoFactorController::class, 'status'])->name('status');
            Route::post('enable', [TwoFactorController::class, 'enable'])->name('enable');
            Route::post('disable', [TwoFactorController::class, 'disable'])->middleware('throttle:3,1')->name('disable');
            Route::post('verify', [TwoFactorController::class, 'verify'])->middleware('throttle:5,1')->name('verify');
        });

        // Notification preferences (per-user)
        Route::get('notification-preferences', [NotificationPreferenceController::class, 'index'])->name('notification-preferences.index');
        Route::put('notification-preferences', [NotificationPreferenceController::class, 'update'])->name('notification-preferences.update');

        // User preferences
        Route::prefix('preferences')->name('preferences.')->group(function (): void {
            Route::get('/', [UserPreferenceController::class, 'show'])->name('show');
            Route::put('/', [UserPreferenceController::class, 'update'])->name('update');
            Route::post('reset', [UserPreferenceController::class, 'reset'])->name('reset');
            Route::get('shortcuts', [UserPreferenceController::class, 'shortcuts'])->name('shortcuts');
        });

        // Device tokens
        Route::get('device-tokens', [DeviceTokenController::class, 'index'])->name('device-tokens.index');
        Route::post('device-tokens', [DeviceTokenController::class, 'store'])->name('device-tokens.store');
        Route::delete('device-tokens', [DeviceTokenController::class, 'destroy'])->name('device-tokens.destroy');

        // ──────────────────────────────────
        // Company (firm-scope kept under /firm prefix for URL compat with the
        // existing frontend's apiClient calls). Read company profile/settings,
        // billing, add-ons, dashboard, staff, branding.
        // ──────────────────────────────────
        Route::middleware('tenant')->prefix('firm')->name('firm.')->group(function (): void {
            Route::get('/', [FirmController::class, 'show'])->name('show');
            Route::get('settings', [FirmController::class, 'settings'])->name('settings.show');
            Route::put('settings', [FirmController::class, 'updateSettings'])->name('settings.update');
            Route::get('dashboard', [FirmDashboardController::class, 'show'])->name('dashboard.show');

            // Billing
            Route::get('billing', [FirmBillingController::class, 'show'])->name('billing.show');
            Route::post('billing/change-plan', [FirmBillingController::class, 'changePlan'])
                ->middleware('throttle:5,1')->name('billing.change-plan');

            // Add-ons
            Route::get('add-ons/catalog', [FirmAddOnsController::class, 'catalog'])->name('add-ons.catalog');
            Route::get('add-ons', [FirmAddOnsController::class, 'active'])->name('add-ons.active');
            Route::get('add-ons/effective-limits', [FirmAddOnsController::class, 'effectiveLimits'])->name('add-ons.effective-limits');
            Route::post('add-ons', [FirmAddOnsController::class, 'purchase'])
                ->middleware('throttle:10,1')->name('add-ons.purchase');
            Route::post('add-ons/{addon}/cancel', [FirmAddOnsController::class, 'cancel'])
                ->whereNumber('addon')->middleware('throttle:10,1')->name('add-ons.cancel');

            // Staff (employees in ERP context, kept under /firm/staff URL for compat)
            Route::get('staff', [FirmStaffController::class, 'index'])->name('staff.index');
            Route::post('staff', [FirmStaffController::class, 'store'])
                ->middleware('throttle:20,1')->name('staff.store');
            Route::put('staff/{staff}', [FirmStaffController::class, 'update'])
                ->whereNumber('staff')->name('staff.update');
            Route::post('staff/{staff}/deactivate', [FirmStaffController::class, 'deactivate'])
                ->whereNumber('staff')->name('staff.deactivate');
            Route::post('staff/{staff}/reactivate', [FirmStaffController::class, 'reactivate'])
                ->whereNumber('staff')->name('staff.reactivate');

            // Per-staff assignments
            Route::get('staff/{staff}/assignments', [FirmStaffController::class, 'assignments'])
                ->whereNumber('staff')->name('staff.assignments');
            Route::post('staff/{staff}/assignments/{user}', [FirmAssignmentsController::class, 'assign'])
                ->whereNumber(['staff', 'user'])->middleware('throttle:30,1')->name('staff.assignments.assign');
            Route::delete('staff/{staff}/assignments/{user}', [FirmAssignmentsController::class, 'unassign'])
                ->whereNumber(['staff', 'user'])->middleware('throttle:30,1')->name('staff.assignments.unassign');
        });

        // ──────────────────────────────────
        // Core ERP — auth resolves the user's single Company via `tenant`
        // middleware (single-company-per-account: app('tenant.id') ===
        // user.tenant_id). Existing BelongsToTenant scope filters all
        // tenant-owned models by this id.
        // ──────────────────────────────────
        Route::middleware(['tenant', 'set_timezone', 'set_locale'])->group(function (): void {

            // Dashboard
            Route::middleware('permission:view_dashboard')->group(function (): void {
                Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
            });

            // Activity log
            Route::prefix('activity-log')->name('activity-log.')->group(function (): void {
                Route::get('/', [ActivityLogController::class, 'index'])->name('index');
                Route::get('stats', [ActivityLogController::class, 'stats'])->name('stats');
                Route::get('{activityId}', [ActivityLogController::class, 'show'])->name('show');
            });

            // Audit log feed — raw activity_log rows for the SPA viewer.
            Route::middleware(['permission:view_activity_log'])->group(function (): void {
                Route::get('audit-log/entries', [AuditLogController::class, 'index'])->name('audit-log.entries');
                Route::get('audit-log/subject-types', [AuditLogController::class, 'subjectTypes'])->name('audit-log.subject-types');
            });

            // Audit compliance (audit_log feature gate retained)
            Route::middleware(['feature:audit_log', 'permission:view_audit'])
                ->prefix('audit-compliance')->name('audit-compliance.')->group(function (): void {
                    Route::get('user-access', [AuditComplianceController::class, 'userAccess'])->name('user-access');
                    Route::get('changes', [AuditComplianceController::class, 'changes'])->name('changes');
                    Route::get('high-risk', [AuditComplianceController::class, 'highRisk'])->name('high-risk');
                    Route::get('segregation', [AuditComplianceController::class, 'segregation'])->name('segregation');
                    Route::get('export', [AuditComplianceController::class, 'export'])->name('export');
                    Route::get('summary', [AuditComplianceController::class, 'summary'])->name('summary');
                });

            // Notifications
            Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
            Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
            Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
            Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

            // Subscription
            Route::middleware('permission:manage_subscription')->group(function (): void {
                Route::get('subscription', [SubscriptionController::class, 'show'])->name('subscription.show');
                Route::post('subscription/subscribe', [SubscriptionController::class, 'subscribe'])
                    ->middleware(['throttle:5,1', 'idempotent', 'no-duplicate'])->name('subscription.subscribe');
                Route::post('subscription/cancel', [SubscriptionController::class, 'cancel'])
                    ->middleware(['throttle:5,1', 'no-duplicate'])->name('subscription.cancel');
                Route::post('subscription/renew', [SubscriptionController::class, 'renew'])
                    ->middleware(['throttle:5,1', 'idempotent', 'no-duplicate'])->name('subscription.renew');
                Route::post('subscription/change-plan', [SubscriptionController::class, 'changePlan'])
                    ->middleware(['throttle:5,1', 'idempotent'])->name('subscription.change-plan');
                Route::get('subscription/usage', [SubscriptionController::class, 'usage'])->name('subscription.usage');
                Route::get('subscription/usage-history', [SubscriptionController::class, 'usageHistory'])->name('subscription.usage-history');
                Route::get('subscription/payments', [SubscriptionController::class, 'payments'])->name('subscription.payments');

                Route::get('subscription/add-ons', [SubscriptionAddOnController::class, 'index'])->name('subscription.add-ons.index');
                Route::get('subscription/credits', [SubscriptionAddOnController::class, 'credits'])->name('subscription.credits');
                Route::get('subscription/add-ons/{subscriptionAddOn}', [SubscriptionAddOnController::class, 'show'])->name('subscription.add-ons.show');
                Route::post('subscription/add-ons', [SubscriptionAddOnController::class, 'store'])
                    ->middleware(['throttle:10,1', 'idempotent', 'no-duplicate'])->name('subscription.add-ons.store');
                Route::delete('subscription/add-ons/{subscriptionAddOn}', [SubscriptionAddOnController::class, 'destroy'])
                    ->middleware(['throttle:10,1'])->name('subscription.add-ons.destroy');
            });

            // Clients (slim — external parties referenced by engagements + time billing)
            Route::middleware('permission:manage_clients')->group(function (): void {
                Route::post('clients/{client}/restore', [ClientController::class, 'restore'])->whereNumber('client')->name('clients.restore');
                Route::patch('clients/{client}/toggle-active', [ClientController::class, 'toggleActive'])->name('clients.toggle-active');
                Route::apiResource('clients', ClientController::class);
            });

            // Currencies (reference data; payroll/expenses need it)
            Route::prefix('currencies')->name('currencies.')->group(function (): void {
                Route::get('/', [CurrencyController::class, 'index'])->name('index');
                Route::get('rates', [CurrencyController::class, 'rates'])->name('rates');
            });

            // Documents (HR contracts, payslips, engagement docs, etc.)
            Route::middleware('permission:manage_documents')->group(function (): void {
                // Static + nested routes before apiResource so {document}
                // doesn't swallow "bulk"/"quota".
                Route::post('documents/bulk', [DocumentController::class, 'bulkStore'])->name('documents.bulk');
                Route::get('documents/quota', [DocumentController::class, 'quota'])->name('documents.quota');
                Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
                Route::post('documents/{document}/archive', [DocumentController::class, 'archive'])->name('documents.archive');
                Route::post('documents/{document}/unarchive', [DocumentController::class, 'unarchive'])->name('documents.unarchive');
                Route::apiResource('documents', DocumentController::class);
            });

            // ── Payroll & HR (firm employees) ──
            Route::middleware(['feature:payroll', 'permission:manage_payroll'])->group(function (): void {
                // Payroll runs — explicit routes (controller uses
                // calculate/approve/markPaid/items, not the apiResource verbs).
                Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
                Route::post('payroll', [PayrollController::class, 'store'])->name('payroll.store');
                Route::get('payroll/{payrollRun}', [PayrollController::class, 'show'])->whereNumber('payrollRun')->name('payroll.show');
                Route::delete('payroll/{payrollRun}', [PayrollController::class, 'destroy'])->whereNumber('payrollRun')->name('payroll.destroy');
                Route::get('payroll/{payrollRun}/items', [PayrollController::class, 'items'])->whereNumber('payrollRun')->name('payroll.items');
                Route::post('payroll/{payrollRun}/calculate', [PayrollController::class, 'calculate'])->whereNumber('payrollRun')->name('payroll.calculate');
                Route::post('payroll/{payrollRun}/approve', [PayrollController::class, 'approve'])->whereNumber('payrollRun')->name('payroll.approve');
                Route::post('payroll/{payrollRun}/mark-paid', [PayrollController::class, 'markPaid'])->whereNumber('payrollRun')->name('payroll.mark-paid');

                // Employees — controller uses listEmployees/storeEmployee/etc.
                Route::get('employees', [PayrollController::class, 'listEmployees'])->name('employees.index');
                Route::post('employees', [PayrollController::class, 'storeEmployee'])->name('employees.store');
                Route::get('employees/{employee}', [PayrollController::class, 'showEmployee'])->whereNumber('employee')->name('employees.show');
                Route::put('employees/{employee}', [PayrollController::class, 'updateEmployee'])->whereNumber('employee')->name('employees.update');
                Route::delete('employees/{employee}', [PayrollController::class, 'destroyEmployee'])->whereNumber('employee')->name('employees.destroy');
                Route::apiResource('salary-components', SalaryComponentController::class);
                Route::apiResource('attendance', AttendanceController::class);
                Route::apiResource('leave-requests', LeaveController::class);
                Route::apiResource('employee-loans', LoanController::class);

                Route::get('payslips', [PayslipController::class, 'index'])->name('payslips.index');
                Route::get('payslips/{payslip}', [PayslipController::class, 'show'])->whereNumber('payslip')->name('payslips.show');
                Route::get('payslips/{payslip}/pdf', [PayslipController::class, 'pdf'])->whereNumber('payslip')->name('payslips.pdf');

                Route::get('social-insurance/rates', [SocialInsuranceController::class, 'rates'])->name('social-insurance.rates');
                Route::post('social-insurance/calculate', [SocialInsuranceController::class, 'calculate'])->name('social-insurance.calculate');
                Route::post('social-insurance/register', [SocialInsuranceController::class, 'register'])->name('social-insurance.register');
                Route::get('social-insurance/monthly-report', [SocialInsuranceController::class, 'monthlyReport'])->name('social-insurance.monthly-report');

                Route::get('labor-law/holidays', [LaborLawController::class, 'holidays'])->name('labor-law.holidays');
                Route::post('labor-law/overtime', [LaborLawController::class, 'calculateOvertime'])->name('labor-law.overtime');
                Route::post('labor-law/end-of-service', [LaborLawController::class, 'calculateEndOfService'])->name('labor-law.end-of-service');
                Route::get('labor-law/leave-entitlement/{employee}', [LaborLawController::class, 'leaveEntitlement'])->whereNumber('employee')->name('labor-law.leave-entitlement');
                Route::post('labor-law/validate-wage', [LaborLawController::class, 'validateWage'])->name('labor-law.validate-wage');
                Route::post('labor-law/social-insurance', [LaborLawController::class, 'socialInsurance'])->name('labor-law.social-insurance');
            });

            // ── Engagements / Projects ──
            Route::middleware(['feature:engagements', 'permission:manage_engagements'])->group(function (): void {
                Route::apiResource('engagements', EngagementController::class);
                Route::apiResource('engagements.working-papers', WorkingPaperController::class)
                    ->scoped()->parameters(['working-papers' => 'workingPaper']);
            });

            // ── Time tracking ──
            Route::middleware(['feature:timesheets', 'permission:manage_timesheets'])->group(function (): void {
                // Static routes before apiResource so {timesheet} doesn't
                // swallow "summary"/"bulk-*".
                Route::get('timesheets/summary', [TimesheetController::class, 'summary'])->name('timesheets.summary');
                Route::post('timesheets/bulk-submit', [TimesheetController::class, 'bulkSubmit'])->name('timesheets.bulk-submit');
                Route::post('timesheets/bulk-approve', [TimesheetController::class, 'bulkApprove'])->name('timesheets.bulk-approve');
                Route::apiResource('timesheets', TimesheetController::class);
                Route::post('timesheets/{timesheet}/submit', [TimesheetController::class, 'submit'])->whereNumber('timesheet')->name('timesheets.submit');
                Route::post('timesheets/{timesheet}/approve', [TimesheetController::class, 'approve'])->whereNumber('timesheet')->name('timesheets.approve');
                Route::post('timesheets/{timesheet}/reject', [TimesheetController::class, 'reject'])->whereNumber('timesheet')->name('timesheets.reject');

                // Timers — controller surface is start/stop/current/discard.
                Route::get('timers/current', [TimerController::class, 'current'])->name('timers.current');
                Route::post('timers/start', [TimerController::class, 'start'])->name('timers.start');
                Route::post('timers/{timer}/stop', [TimerController::class, 'stop'])->whereNumber('timer')->name('timers.stop');
                Route::delete('timers/{timer}', [TimerController::class, 'discard'])->whereNumber('timer')->name('timers.discard');

                Route::get('time-billing', [TimeBillingController::class, 'index'])->name('time-billing.index');
            });

            // ── Team / Employees (alt surface) ──
            Route::middleware('permission:manage_team')->group(function (): void {
                // TeamController surface: index/invite/update/toggleActive/destroy/assignRole.
                Route::get('team', [TeamController::class, 'index'])->name('team.index');
                Route::post('team/invite', [TeamController::class, 'invite'])->name('team.invite');
                Route::put('team/{user}', [TeamController::class, 'update'])->whereNumber('user')->name('team.update');
                Route::patch('team/{user}/toggle-active', [TeamController::class, 'toggleActive'])->whereNumber('user')->name('team.toggle-active');
                Route::post('team/{user}/assign-role', [TeamController::class, 'assignRole'])->whereNumber('user')->name('team.assign-role');
                Route::delete('team/{user}', [TeamController::class, 'destroy'])->whereNumber('user')->name('team.destroy');
            });

            // ── Alerts & Approvals (workflow) ──
            Route::middleware('permission:manage_alerts')->group(function (): void {
                Route::apiResource('alert-rules', AlertRuleController::class);
            });
            Route::middleware(['feature:approvals', 'permission:manage_approvals'])->group(function (): void {
                Route::apiResource('approvals', ApprovalController::class);
                Route::post('approvals/{approval}/submit', [ApprovalWorkflowController::class, 'submit'])->whereNumber('approval')->name('approvals.submit');
                Route::post('approvals/{approval}/approve', [ApprovalWorkflowController::class, 'approve'])->whereNumber('approval')->name('approvals.approve');
                Route::post('approvals/{approval}/reject', [ApprovalWorkflowController::class, 'reject'])->whereNumber('approval')->name('approvals.reject');
            });

            // ── Company branding ──
            Route::middleware('permission:manage_settings')->group(function (): void {
                Route::get('branding', [TenantBrandingController::class, 'show'])->name('branding.show');
                Route::put('branding', [TenantBrandingController::class, 'update'])->name('branding.update');
            });


            // ── RBAC (roles + permissions) ──
            // role-presets is read-only metadata that feeds the team-
            // management "Apply preset" UI, so it's gated by manage_team
            // (every team admin needs it). The mutating RBAC-admin
            // endpoints stay behind the stricter manage_roles gate.
            Route::middleware('permission:manage_team')->group(function (): void {
                Route::get('rbac/role-presets', [RbacController::class, 'rolePresets'])->name('rbac.role-presets');
            });
            Route::middleware('permission:manage_roles')->group(function (): void {
                Route::get('rbac/roles', [RbacController::class, 'roles'])->name('rbac.roles');
                Route::get('rbac/permissions', [RbacController::class, 'permissions'])->name('rbac.permissions');
                Route::put('rbac/users/{user}/roles', [RbacController::class, 'assignRoles'])->whereNumber('user')->name('rbac.assign-roles');
            });

            // ── Webhooks (outgoing endpoint mgmt) ──
            Route::middleware('permission:manage_webhooks')->group(function (): void {
                Route::apiResource('webhook-endpoints', WebhookEndpointController::class);
            });

            // ── Task Board ──
            Route::middleware(['feature:task_board'])->group(function (): void {
                // Boards
                Route::get('boards', [BoardController::class, 'index'])->middleware('permission:view_tasks')->name('boards.index');
                Route::get('boards/{board}', [BoardController::class, 'show'])->middleware('permission:view_tasks')->name('boards.show');
                Route::post('boards', [BoardController::class, 'store'])->middleware('permission:manage_boards')->name('boards.store');
                Route::put('boards/{board}', [BoardController::class, 'update'])->middleware('permission:manage_boards')->name('boards.update');
                Route::delete('boards/{board}', [BoardController::class, 'destroy'])->middleware('permission:manage_boards')->name('boards.destroy');
                Route::post('boards/{board}/inbox', [BoardController::class, 'toggleInbox'])->middleware('permission:manage_boards')->name('boards.inbox.toggle');

                // Custom field definitions (per board) + values (per task)
                Route::get('boards/{board}/custom-fields', [BoardCustomFieldController::class, 'index'])
                    ->middleware(['permission:view_tasks', 'board.access:viewer'])->name('boards.custom-fields.index');
                Route::post('boards/{board}/custom-fields', [BoardCustomFieldController::class, 'store'])
                    ->middleware('board.access:admin')->name('boards.custom-fields.store');
                Route::put('board-custom-fields/{customField}', [BoardCustomFieldController::class, 'update'])
                    ->middleware('board.access:admin')->name('board-custom-fields.update');
                Route::delete('board-custom-fields/{customField}', [BoardCustomFieldController::class, 'destroy'])
                    ->middleware('board.access:admin')->name('board-custom-fields.destroy');
                Route::put('tasks/{task}/custom-fields', [TaskCustomFieldValueController::class, 'upsert'])
                    ->middleware(['permission:edit_tasks', 'board.access:editor'])->name('tasks.custom-fields.upsert');

                // CSV import/export
                Route::get('boards/{board}/csv', [TaskCsvController::class, 'export'])
                    ->middleware(['permission:view_tasks', 'board.access:viewer'])->name('boards.csv.export');
                Route::post('boards/{board}/csv', [TaskCsvController::class, 'import'])
                    ->middleware(['permission:create_tasks', 'throttle:10,1', 'board.access:editor'])->name('boards.csv.import');

                // Columns (board-scoped create + reorder; flat update/destroy)
                Route::post('boards/{board}/columns', [BoardColumnController::class, 'store'])
                    ->middleware('board.access:admin')->name('boards.columns.store');
                Route::post('boards/{board}/columns/reorder', [BoardColumnController::class, 'reorder'])
                    ->middleware('board.access:admin')->name('boards.columns.reorder');
                Route::put('board-columns/{column}', [BoardColumnController::class, 'update'])
                    ->middleware('board.access:admin')->name('board-columns.update');
                Route::delete('board-columns/{column}', [BoardColumnController::class, 'destroy'])
                    ->middleware('board.access:admin')->name('board-columns.destroy');

                // Tasks
                Route::get('tasks', [TaskController::class, 'index'])->middleware('permission:view_tasks')->name('tasks.index');
                Route::get('tasks/{task}', [TaskController::class, 'show'])
                    ->middleware(['permission:view_tasks', 'board.access:viewer'])->name('tasks.show');
                Route::post('tasks', [TaskController::class, 'store'])
                    ->middleware(['permission:create_tasks', 'board.access:editor'])->name('tasks.store');
                Route::put('tasks/{task}', [TaskController::class, 'update'])
                    ->middleware(['permission:edit_tasks', 'board.access:editor'])->name('tasks.update');
                Route::delete('tasks/{task}', [TaskController::class, 'destroy'])
                    ->middleware(['permission:delete_tasks', 'board.access:editor'])->name('tasks.destroy');

                // Movement & relationships
                Route::post('tasks/{task}/move', [TaskController::class, 'move'])
                    ->middleware('board.access:editor')->name('tasks.move');
                Route::post('tasks/{task}/move-cross-board', [TaskController::class, 'moveCrossBoard'])
                    ->middleware(['permission:edit_tasks', 'board.access:editor'])->name('tasks.move-cross-board');
                Route::put('tasks/{task}/assignees', [TaskController::class, 'syncAssignees'])
                    ->middleware('board.access:editor')->name('tasks.assignees.sync');
                Route::post('tasks/{task}/primary-assignee', [TaskController::class, 'setPrimaryAssignee'])
                    ->middleware('board.access:editor')->name('tasks.primary-assignee');
                Route::post('tasks/{task}/watch', [TaskController::class, 'watch'])
                    ->middleware('board.access:viewer')->name('tasks.watch');
                Route::delete('tasks/{task}/watch', [TaskController::class, 'unwatch'])
                    ->middleware('board.access:viewer')->name('tasks.unwatch');

                // Archive / unarchive
                Route::post('tasks/{task}/archive', [TaskArchiveController::class, 'store'])
                    ->middleware(['permission:edit_tasks', 'board.access:editor'])->name('tasks.archive');
                Route::delete('tasks/{task}/archive', [TaskArchiveController::class, 'destroy'])
                    ->middleware(['permission:edit_tasks', 'board.access:editor'])->name('tasks.unarchive');

                // Per-board membership
                Route::get('boards/{board}/members', [BoardMemberController::class, 'index'])->name('boards.members.index');
                Route::post('boards/{board}/members', [BoardMemberController::class, 'store'])->name('boards.members.store');
                Route::put('board-members/{member}', [BoardMemberController::class, 'update'])->name('board-members.update');
                Route::delete('board-members/{member}', [BoardMemberController::class, 'destroy'])->name('board-members.destroy');

                // Workflow transition rules (per board, per task type)
                Route::get('boards/{board}/workflows', [WorkflowTransitionController::class, 'index'])
                    ->middleware(['permission:view_tasks', 'board.access:viewer'])->name('boards.workflows.index');
                Route::put('boards/{board}/workflows', [WorkflowTransitionController::class, 'replace'])
                    ->middleware('board.access:admin')->name('boards.workflows.replace');

                // Outbound webhooks (Slack / Discord / generic JSON)
                Route::get('task-board/webhooks', [TaskBoardWebhookController::class, 'index'])
                    ->middleware('permission:view_tasks')->name('task-board.webhooks.index');
                Route::post('task-board/webhooks', [TaskBoardWebhookController::class, 'store'])
                    ->middleware('permission:manage_boards')->name('task-board.webhooks.store');
                Route::put('task-board/webhooks/{webhook}', [TaskBoardWebhookController::class, 'update'])
                    ->middleware('permission:manage_boards')->name('task-board.webhooks.update');
                Route::delete('task-board/webhooks/{webhook}', [TaskBoardWebhookController::class, 'destroy'])
                    ->middleware('permission:manage_boards')->name('task-board.webhooks.destroy');
                Route::post('task-board/webhooks/{webhook}/test', [TaskBoardWebhookController::class, 'test'])
                    ->middleware(['permission:manage_boards', 'throttle:6,1'])->name('task-board.webhooks.test');
                Route::get('task-board/webhooks/{webhook}/deliveries', [TaskBoardWebhookController::class, 'deliveries'])
                    ->middleware('permission:view_tasks')->name('task-board.webhooks.deliveries');
                Route::post('task-board/webhook-deliveries/{delivery}/replay', [TaskBoardWebhookController::class, 'replay'])
                    ->middleware(['permission:manage_boards', 'throttle:30,1'])->name('task-board.webhook-deliveries.replay');

                // Roles list for the @-mention popover (board-scoped permission)
                Route::get('task-board/mention-roles', [MentionRoleController::class, 'index'])
                    ->middleware('permission:view_tasks')->name('task-board.mention-roles');

                // Cross-board inbox (per-user mention/assigned/watching/reminders feed)
                Route::get('task-board/inbox', [InboxController::class, 'index'])
                    ->middleware('permission:view_tasks')->name('task-board.inbox');

                // Approval gates on column moves
                Route::get('task-approval-requests', [TaskApprovalController::class, 'index'])
                    ->middleware('permission:view_tasks')->name('task-approval-requests.index');
                Route::post('tasks/{task}/approval-requests', [TaskApprovalController::class, 'store'])
                    ->middleware(['permission:edit_tasks', 'throttle:30,1', 'board.access:editor'])->name('tasks.approval-requests.store');
                // Decision authorisation lives in the controller (board admin
                // OR column-listed approver). Middleware gates basic access.
                Route::post('task-approval-requests/{approvalRequest}/decision', [TaskApprovalController::class, 'decide'])
                    ->middleware(['throttle:60,1', 'board.access:viewer'])->name('task-approval-requests.decide');
                Route::post('task-approval-requests/{approvalRequest}/cancel', [TaskApprovalController::class, 'cancel'])
                    ->middleware('board.access:viewer')->name('task-approval-requests.cancel');

                // Per-user reminders / snooze — board viewer access is enough,
                // the reminder itself is per-user so no further gating.
                Route::get('tasks/{task}/reminders', [TaskReminderController::class, 'index'])
                    ->middleware('board.access:viewer')->name('tasks.reminders.index');
                Route::post('tasks/{task}/reminders', [TaskReminderController::class, 'store'])
                    ->middleware(['throttle:30,1', 'board.access:viewer'])->name('tasks.reminders.store');
                Route::post('tasks/{task}/reminders/snooze', [TaskReminderController::class, 'snooze'])
                    ->middleware(['throttle:60,1', 'board.access:viewer'])->name('tasks.reminders.snooze');
                Route::delete('task-reminders/{reminder}', [TaskReminderController::class, 'destroy'])->name('task-reminders.destroy');

                // Personal flags: star (favourite), mute (suppress notifications)
                Route::post('tasks/{task}/flags/{flag}', [TaskPersonalFlagController::class, 'store'])
                    ->middleware('board.access:viewer')
                    ->whereIn('flag', ['star', 'mute'])->name('tasks.flags.store');
                Route::delete('tasks/{task}/flags/{flag}', [TaskPersonalFlagController::class, 'destroy'])
                    ->middleware('board.access:viewer')
                    ->whereIn('flag', ['star', 'mute'])->name('tasks.flags.destroy');
                Route::post('tasks/{task}/subtasks', [TaskController::class, 'storeSubtask'])
                    ->middleware(['permission:create_tasks', 'board.access:editor'])->name('tasks.subtasks.store');

                // Comments
                Route::get('tasks/{task}/comments', [TaskCommentController::class, 'index'])
                    ->middleware('board.access:viewer')->name('tasks.comments.index');
                Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store'])
                    ->middleware('board.access:viewer')->name('tasks.comments.store');
                Route::put('task-comments/{comment}', [TaskCommentController::class, 'update'])
                    ->middleware('board.access:viewer')->name('task-comments.update');
                Route::delete('task-comments/{comment}', [TaskCommentController::class, 'destroy'])
                    ->middleware('board.access:viewer')->name('task-comments.destroy');
                Route::post('task-comments/{comment}/reactions', [CommentReactionController::class, 'toggle'])
                    ->middleware(['throttle:60,1', 'board.access:viewer'])->name('task-comments.reactions.toggle');
                Route::post('tasks/{task}/reactions', [TaskReactionController::class, 'toggle'])
                    ->middleware(['throttle:60,1', 'board.access:viewer'])->name('tasks.reactions.toggle');

                // Task templates
                Route::get('task-templates', [TaskTemplateController::class, 'index'])->name('task-templates.index');
                Route::post('task-templates', [TaskTemplateController::class, 'store'])->name('task-templates.store');
                Route::put('task-templates/{taskTemplate}', [TaskTemplateController::class, 'update'])->name('task-templates.update');
                Route::delete('task-templates/{taskTemplate}', [TaskTemplateController::class, 'destroy'])->name('task-templates.destroy');
                Route::post('task-templates/{taskTemplate}/spawn', [TaskTemplateController::class, 'spawn'])
                    ->middleware('throttle:30,1')->name('task-templates.spawn');

                // Task sharing — owner generates / revokes the public token.
                Route::post('tasks/{task}/share', [TaskShareController::class, 'create'])
                    ->middleware(['throttle:20,1', 'board.access:editor'])->name('tasks.share.create');
                Route::delete('tasks/{task}/share', [TaskShareController::class, 'revoke'])
                    ->middleware('board.access:editor')->name('tasks.share.revoke');

                // Board insights (cycle time / throughput / WIP / aging)
                Route::get('boards/{board}/insights', [BoardInsightsController::class, 'show'])
                    ->middleware(['throttle:30,1', 'board.access:viewer'])->name('boards.insights');

                // Automation rules
                Route::get('automation-rules', [AutomationRuleController::class, 'index'])->name('automation-rules.index');
                Route::post('automation-rules', [AutomationRuleController::class, 'store'])->name('automation-rules.store');
                Route::put('automation-rules/{automationRule}', [AutomationRuleController::class, 'update'])->name('automation-rules.update');
                Route::post('automation-rules/{automationRule}/toggle', [AutomationRuleController::class, 'toggle'])->name('automation-rules.toggle');
                Route::delete('automation-rules/{automationRule}', [AutomationRuleController::class, 'destroy'])->name('automation-rules.destroy');

                // Attachments
                Route::get('tasks/{task}/attachments', [TaskAttachmentController::class, 'index'])
                    ->middleware('board.access:viewer')->name('tasks.attachments.index');
                Route::post('tasks/{task}/attachments', [TaskAttachmentController::class, 'store'])
                    ->middleware(['throttle:30,1', 'board.access:editor'])->name('tasks.attachments.store');
                Route::get('task-attachments/{attachment}/download', [TaskAttachmentController::class, 'download'])
                    ->middleware('signed')->name('tasks.attachments.download');
                Route::delete('task-attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])
                    ->middleware('board.access:editor')->name('task-attachments.destroy');

                // Activity feed (task timeline)
                Route::get('tasks/{task}/activity', [TaskActivityController::class, 'index'])
                    ->middleware('board.access:viewer')->name('tasks.activity');

                // Bulk actions — atomic, tenant-scoped on the server side
                Route::post('tasks/bulk', [TaskBulkController::class, 'bulk'])
                    ->middleware('throttle:30,1')->name('tasks.bulk');

                // Checklists + items
                Route::get('tasks/{task}/checklists', [TaskChecklistController::class, 'index'])
                    ->middleware('board.access:viewer')->name('tasks.checklists.index');
                Route::post('tasks/{task}/checklists', [TaskChecklistController::class, 'store'])
                    ->middleware('board.access:editor')->name('tasks.checklists.store');
                Route::put('task-checklists/{checklist}', [TaskChecklistController::class, 'update'])
                    ->middleware('board.access:editor')->name('task-checklists.update');
                Route::delete('task-checklists/{checklist}', [TaskChecklistController::class, 'destroy'])
                    ->middleware('board.access:editor')->name('task-checklists.destroy');
                Route::post('task-checklists/{checklist}/items', [TaskChecklistController::class, 'storeItem'])
                    ->middleware('board.access:editor')->name('task-checklists.items.store');
                Route::put('task-checklist-items/{item}', [TaskChecklistController::class, 'updateItem'])
                    ->middleware('board.access:editor')->name('task-checklist-items.update');
                Route::delete('task-checklist-items/{item}', [TaskChecklistController::class, 'destroyItem'])
                    ->middleware('board.access:editor')->name('task-checklist-items.destroy');

                // Personal iCal subscription token
                Route::get('me/ical/token', [TaskBoardICalController::class, 'token'])->name('me.ical.token');
                Route::post('me/ical/token/rotate', [TaskBoardICalController::class, 'rotate'])
                    ->middleware('throttle:6,1')->name('me.ical.token.rotate');

                // Time tracking on tasks
                Route::get('me/task-timer', [TaskTimerController::class, 'current'])->name('me.task-timer');
                Route::post('me/task-timer/stop', [TaskTimerController::class, 'stop'])->name('me.task-timer.stop');
                Route::post('tasks/{task}/timer/start', [TaskTimerController::class, 'start'])
                    ->middleware('board.access:viewer')->name('tasks.timer.start');
                Route::post('tasks/{task}/time-entries', [TaskTimerController::class, 'manualLog'])
                    ->middleware('board.access:viewer')->name('tasks.time-entries.store');
                Route::get('tasks/{task}/time-entries', [TaskTimerController::class, 'index'])
                    ->middleware('board.access:viewer')->name('tasks.time-entries.index');
                Route::delete('task-time-entries/{taskTimeEntry}', [TaskTimerController::class, 'destroy'])
                    ->middleware('board.access:viewer')->name('task-time-entries.destroy');

                // Saved board views (filter presets)
                Route::get('task-board-views', [TaskBoardViewController::class, 'index'])->name('task-board-views.index');
                Route::post('task-board-views', [TaskBoardViewController::class, 'store'])->name('task-board-views.store');
                Route::put('task-board-views/{taskBoardView}', [TaskBoardViewController::class, 'update'])->name('task-board-views.update');
                Route::delete('task-board-views/{taskBoardView}', [TaskBoardViewController::class, 'destroy'])->name('task-board-views.destroy');

                // Dependencies (blocks / duplicates / relates_to)
                // GitHub PR links per task
                Route::get('tasks/{task}/github-links', [TaskGithubLinkController::class, 'index'])
                    ->middleware('board.access:viewer')->name('tasks.github-links.index');
                Route::post('tasks/{task}/github-links', [TaskGithubLinkController::class, 'store'])
                    ->middleware(['board.access:editor', 'throttle:30,1'])->name('tasks.github-links.store');
                Route::delete('task-github-links/{githubLink}', [TaskGithubLinkController::class, 'destroy'])
                    ->name('task-github-links.destroy');
                Route::post('task-github-links/{githubLink}/refresh', [TaskGithubLinkController::class, 'refresh'])
                    ->middleware('throttle:30,1')->name('task-github-links.refresh');

                Route::get('tasks/{task}/dependencies', [TaskDependencyController::class, 'index'])
                    ->middleware('board.access:viewer')->name('tasks.dependencies.index');
                Route::post('tasks/{task}/dependencies', [TaskDependencyController::class, 'store'])
                    ->middleware('board.access:editor')->name('tasks.dependencies.store');
                Route::delete('task-dependencies/{taskDependency}', [TaskDependencyController::class, 'destroy'])
                    ->middleware('board.access:editor')->name('task-dependencies.destroy');

                // Recurrences
                Route::get('task-recurrences', [TaskRecurrenceController::class, 'index'])->middleware('permission:manage_recurrences')->name('task-recurrences.index');
                Route::post('task-recurrences', [TaskRecurrenceController::class, 'store'])->middleware('permission:manage_recurrences')->name('task-recurrences.store');
                Route::put('task-recurrences/{taskRecurrence}', [TaskRecurrenceController::class, 'update'])
                    ->middleware('board.access:editor')->name('task-recurrences.update');
                Route::delete('task-recurrences/{taskRecurrence}', [TaskRecurrenceController::class, 'destroy'])
                    ->middleware('board.access:editor')->name('task-recurrences.destroy');

                // Sprints — list / CRUD / start / complete / membership / burndown
                // (sprints are board-scoped; the middleware reads board_id
                // from {sprint} or from the request body on create)
                Route::get('sprints', [SprintController::class, 'index'])
                    ->middleware('board.access:viewer')->name('sprints.index');
                Route::get('sprints/active', [SprintController::class, 'active'])
                    ->middleware('board.access:viewer')->name('sprints.active');
                Route::get('sprints/{sprint}', [SprintController::class, 'show'])
                    ->middleware('board.access:viewer')->name('sprints.show');
                Route::post('sprints', [SprintController::class, 'store'])
                    ->middleware('board.access:editor')->name('sprints.store');
                Route::put('sprints/{sprint}', [SprintController::class, 'update'])
                    ->middleware('board.access:editor')->name('sprints.update');
                Route::delete('sprints/{sprint}', [SprintController::class, 'destroy'])
                    ->middleware('board.access:editor')->name('sprints.destroy');
                Route::post('sprints/{sprint}/start', [SprintController::class, 'start'])
                    ->middleware(['throttle:10,1', 'board.access:editor'])->name('sprints.start');
                Route::post('sprints/{sprint}/complete', [SprintController::class, 'complete'])
                    ->middleware(['throttle:10,1', 'board.access:editor'])->name('sprints.complete');
                Route::post('sprints/{sprint}/tasks', [SprintController::class, 'addTasks'])
                    ->middleware('board.access:editor')->name('sprints.tasks.add');
                Route::delete('sprints/{sprint}/tasks/{task}', [SprintController::class, 'removeTask'])
                    ->middleware('board.access:editor')->name('sprints.tasks.remove');
                Route::get('sprints/{sprint}/burndown', [SprintController::class, 'burndown'])
                    ->middleware('board.access:viewer')->name('sprints.burndown');

                // Task types (per-company catalogue)
                Route::get('task-types', [TaskTypeController::class, 'index'])->name('task-types.index');
                Route::post('task-types', [TaskTypeController::class, 'store'])->name('task-types.store');
                Route::put('task-types/{taskType}', [TaskTypeController::class, 'update'])->name('task-types.update');
                Route::delete('task-types/{taskType}', [TaskTypeController::class, 'destroy'])->name('task-types.destroy');

                // Tags (board-scoped or company-wide)
                Route::get('tags', [TagController::class, 'index'])->name('tags.index');
                Route::post('tags', [TagController::class, 'store'])->name('tags.store');
                Route::put('tags/{tag}', [TagController::class, 'update'])->name('tags.update');
                Route::delete('tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');

                // Versions
                Route::get('versions', [VersionController::class, 'index'])->name('versions.index');
                Route::post('versions', [VersionController::class, 'store'])->name('versions.store');
                Route::put('versions/{version}', [VersionController::class, 'update'])->name('versions.update');
                Route::delete('versions/{version}', [VersionController::class, 'destroy'])->name('versions.destroy');
            });
        });

        // ──────────────────────────────────
        // Super admin
        // ──────────────────────────────────
        Route::middleware(['permission:super_admin', 'admin.ip'])->prefix('admin')->name('admin.')->group(function (): void {
            Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
            Route::apiResource('users', AdminUserController::class);
            Route::apiResource('roles', AdminRoleController::class);
            Route::post('tenants/{tenant}/suspend', [AdminTenantController::class, 'suspend'])->name('tenants.suspend');
            Route::post('tenants/{tenant}/activate', [AdminTenantController::class, 'activate'])->name('tenants.activate');
            Route::post('tenants/{tenant}/cancel', [AdminTenantController::class, 'cancel'])->name('tenants.cancel');
            Route::post('tenants/{tenant}/impersonate', [AdminTenantController::class, 'impersonate'])->name('tenants.impersonate');
            Route::apiResource('tenants', AdminTenantController::class);
            Route::apiResource('plans', PlanController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::apiResource('subscriptions', AdminSubscriptionController::class);
            Route::apiResource('feature-flags', AdminFeatureFlagController::class);
            Route::apiResource('email-templates', AdminEmailTemplateController::class);
            Route::apiResource('integrations', AdminIntegrationController::class);
            Route::get('metrics', [AdminMetricsController::class, 'index'])->name('metrics');
            Route::get('usage', [AdminUsageController::class, 'index'])->name('usage');
            Route::get('platform-settings', [AdminPlatformSettingsController::class, 'index'])->name('platform-settings.index');
            Route::put('platform-settings', [AdminPlatformSettingsController::class, 'update'])->name('platform-settings.update');
            Route::get('activity-log', [AdminActivityLogController::class, 'index'])->name('activity-log');
            Route::get('api-log', [AdminApiLogController::class, 'index'])->name('api-log');
            Route::get('audit-log', [AdminAuditLogController::class, 'index'])->name('audit-log');
        });
    });
});
