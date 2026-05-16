<?php

declare(strict_types=1);

namespace App\Domain\Notification\Enums;

enum NotificationType: string
{
    case Welcome = 'welcome';
    case SetupReminder = 'setup_reminder';
    case InvoiceSent = 'invoice_sent';
    case PaymentReceived = 'payment_received';
    case TrialExpiring = 'trial_expiring';
    case SubscriptionRenewed = 'subscription_renewed';
    case TeamInvite = 'team_invite';
    case SystemAlert = 'system_alert';
    case DocumentShared = 'document_shared';
    case NewMessage = 'new_message';
    case ClientInvite = 'client_invite';
    case InvoiceDispute = 'invoice_dispute';
    case ApprovalRequired = 'approval_required';
    case ApprovalDecided  = 'approval_decided';
    // Task board
    case TaskAssigned = 'task_assigned';
    case TaskMentioned = 'task_mentioned';
    case TaskCommented = 'task_commented';
    case TaskMoved = 'task_moved';
    case TaskDue = 'task_due';
    case TaskReminder = 'task_reminder';

    public function label(): string
    {
        return match ($this) {
            self::Welcome => 'Welcome',
            self::SetupReminder => 'Setup Reminder',
            self::InvoiceSent => 'Invoice Sent',
            self::PaymentReceived => 'Payment Received',
            self::TrialExpiring => 'Trial Expiring',
            self::SubscriptionRenewed => 'Subscription Renewed',
            self::TeamInvite => 'Team Invite',
            self::SystemAlert => 'System Alert',
            self::DocumentShared => 'Document Shared',
            self::NewMessage => 'New Message',
            self::ClientInvite => 'Client Invite',
            self::InvoiceDispute => 'Invoice Dispute',
            self::ApprovalRequired => 'Approval Required',
            self::ApprovalDecided  => 'Approval Decided',
            self::TaskAssigned => 'Task Assigned',
            self::TaskMentioned => 'Mentioned in Task',
            self::TaskCommented => 'New Comment on Task',
            self::TaskMoved => 'Task Moved',
            self::TaskDue => 'Task Due',
            self::TaskReminder => 'Task Reminder',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Welcome => 'مرحباً',
            self::SetupReminder => 'تذكير بالإعداد',
            self::InvoiceSent => 'تم إرسال الفاتورة',
            self::PaymentReceived => 'تم استلام الدفعة',
            self::TrialExpiring => 'انتهاء الفترة التجريبية',
            self::SubscriptionRenewed => 'تم تجديد الاشتراك',
            self::TeamInvite => 'دعوة فريق',
            self::SystemAlert => 'تنبيه النظام',
            self::DocumentShared => 'تمت مشاركة مستند',
            self::NewMessage => 'رسالة جديدة',
            self::ClientInvite => 'دعوة عميل',
            self::InvoiceDispute => 'نزاع على فاتورة',
            self::ApprovalRequired => 'مطلوب اعتماد',
            self::ApprovalDecided  => 'تمّ البتّ في الاعتماد',
            self::TaskAssigned => 'تم تعيين مهمة',
            self::TaskMentioned => 'تم ذكرك في مهمة',
            self::TaskCommented => 'تعليق جديد على مهمة',
            self::TaskMoved => 'تم نقل المهمة',
            self::TaskDue => 'موعد استحقاق مهمة',
            self::TaskReminder => 'تذكير بمهمة',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Welcome => 'bell',
            self::SetupReminder => 'settings',
            self::InvoiceSent => 'file-text',
            self::PaymentReceived => 'credit-card',
            self::TrialExpiring => 'clock',
            self::SubscriptionRenewed => 'refresh',
            self::TeamInvite => 'user-plus',
            self::SystemAlert => 'alert-triangle',
            self::DocumentShared => 'share-2',
            self::NewMessage => 'mail',
            self::ClientInvite => 'user-check',
            self::InvoiceDispute => 'alert-circle',
            self::ApprovalRequired => 'shield-alert',
            self::ApprovalDecided  => 'check-circle',
            self::TaskAssigned => 'user-plus',
            self::TaskMentioned => 'at-sign',
            self::TaskCommented => 'message-square',
            self::TaskMoved => 'arrow-right',
            self::TaskDue => 'clock',
            self::TaskReminder => 'bell',
        };
    }
}
