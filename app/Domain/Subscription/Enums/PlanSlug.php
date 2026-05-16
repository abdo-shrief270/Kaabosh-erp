<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Enums;

enum PlanSlug: string
{
    case FreeTrial = 'free_trial';
    case Starter = 'starter';
    case Professional = 'professional';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::FreeTrial => 'Free Trial',
            self::Starter => 'Starter',
            self::Professional => 'Professional',
            self::Enterprise => 'Enterprise',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::FreeTrial => 'تجربة مجانية',
            self::Starter => 'أساسي',
            self::Professional => 'احترافي',
            self::Enterprise => 'مؤسسات',
        };
    }
}
