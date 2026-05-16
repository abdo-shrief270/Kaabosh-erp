<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\NotificationType;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\TaskBoard\Models\TaskReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Fires due task reminders into the in-app notification stream. Runs
 * every five minutes via the scheduler; idempotent because each reminder
 * has its own `sent_at` flag that we set before dispatching so a partial
 * run can't double-send.
 */
class TaskBoardFireRemindersCommand extends Command
{
    protected $signature = 'task-board:fire-reminders {--dry-run : Don\'t actually send}';

    protected $description = 'Send due task reminders to their owners';

    public function handle(NotificationService $notifications): int
    {
        $now = now();
        $dry = (bool) $this->option('dry-run');
        $sent = 0;

        TaskReminder::query()
            ->whereNull('sent_at')
            ->where('remind_at', '<=', $now)
            ->with(['task:id,reference,title', 'user:id,name'])
            ->chunkById(200, function ($reminders) use (&$sent, $notifications, $dry, $now) {
                foreach ($reminders as $r) {
                    /** @var TaskReminder $r */
                    if (! $r->task || ! $r->user) {
                        // Task or user gone — clear the reminder so we don't loop.
                        if (! $dry) {
                            $r->forceFill(['sent_at' => $now])->save();
                        }
                        continue;
                    }

                    if (! $dry) {
                        // Mark sent FIRST so a crash mid-dispatch can't replay.
                        $r->forceFill(['sent_at' => $now])->save();

                        $title = 'Reminder: '.$r->task->reference;
                        $body = $r->note ?: Str::limit($r->task->title, 120);
                        $notifications->send(
                            userId: (int) $r->user_id,
                            type: NotificationType::TaskReminder,
                            titleAr: 'تذكير: '.$r->task->reference,
                            titleEn: $title,
                            bodyAr: $body,
                            bodyEn: $body,
                            actionUrl: rtrim((string) config('app.frontend_url', config('app.url')), '/').'/tasks/'.$r->task_id,
                            data: ['task_id' => $r->task_id, 'reminder_id' => $r->id],
                            channel: NotificationChannel::InApp,
                        );
                    }
                    $sent++;
                }
            });

        $this->info(($dry ? '[dry-run] ' : '').'Fired '.$sent.' reminder(s).');

        return self::SUCCESS;
    }
}
