<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Enums;

enum EngagementType: string
{
    case Project = 'project';
    case Retainer = 'retainer';
    case Consulting = 'consulting';
    case Campaign = 'campaign';
    case Implementation = 'implementation';
    case Maintenance = 'maintenance';
    case Internal = 'internal';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Project => 'Project',
            self::Retainer => 'Retainer',
            self::Consulting => 'Consulting',
            self::Campaign => 'Campaign',
            self::Implementation => 'Implementation',
            self::Maintenance => 'Maintenance',
            self::Internal => 'Internal',
            self::Other => 'Other',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Project => 'مشروع',
            self::Retainer => 'تعاقد دائم',
            self::Consulting => 'استشارات',
            self::Campaign => 'حملة',
            self::Implementation => 'تنفيذ',
            self::Maintenance => 'صيانة',
            self::Internal => 'داخلي',
            self::Other => 'أخرى',
        };
    }
}
