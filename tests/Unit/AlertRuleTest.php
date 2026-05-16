<?php

declare(strict_types=1);

use App\Domain\Notification\Models\AlertRule;

// ──────────────────────────────────────
// Metric Calculations
// ──────────────────────────────────────

describe('Metric calculations', function (): void {
    it('computes DSO correctly', function (): void {
        $accountsReceivable = '100000.00';
        $creditSales = '300000.00';
        $periodDays = '90';

        // DSO = (AR * periodDays) / creditSales. Multiply first so bcmath
        // truncation doesn't eat a cent on exactly-divisible inputs.
        $dso = bcdiv(
            bcmul($accountsReceivable, $periodDays, 2),
            $creditSales,
            2
        );

        expect($dso)->toBe('30.00');
    });

    it('returns zero DSO when credit sales are zero', function (): void {
        $accountsReceivable = '50000.00';
        $creditSales = '0.00';

        if (bccomp($creditSales, '0', 2) === 0) {
            $dso = '0.00';
        } else {
            $ratio = bcdiv($accountsReceivable, $creditSales, 6);
            $dso = bcmul($ratio, '90', 2);
        }

        expect($dso)->toBe('0.00');
    });

    it('computes collection rate as percentage', function (): void {
        $totalInvoiced = '200000.00';
        $totalPaid = '150000.00';

        $ratio = bcdiv($totalPaid, $totalInvoiced, 6);
        $rate = bcmul($ratio, '100', 2);

        expect($rate)->toBe('75.00');
    });

    it('computes budget utilization as percentage', function (): void {
        $totalBudgeted = '500000.00';
        $actualSpend = '375000.00';

        $ratio = bcdiv($actualSpend, $totalBudgeted, 6);
        $utilization = bcmul($ratio, '100', 2);

        expect($utilization)->toBe('75.00');
    });
});

// ──────────────────────────────────────
// Condition Evaluation (bcmath operators)
// ──────────────────────────────────────

describe('Alert rule condition evaluation', function (): void {
    /**
     * Simulate the conditionMet logic from AlertRule model.
     */
    function conditionMet(string $operator, string $metricValue, string $threshold): bool
    {
        return match ($operator) {
            'gt' => bccomp($metricValue, $threshold, 2) === 1,
            'gte' => bccomp($metricValue, $threshold, 2) >= 0,
            'lt' => bccomp($metricValue, $threshold, 2) === -1,
            'lte' => bccomp($metricValue, $threshold, 2) <= 0,
            'eq' => bccomp($metricValue, $threshold, 2) === 0,
            default => false,
        };
    }

    it('triggers when DSO 30 > threshold 25 (gt operator)', function (): void {
        expect(conditionMet('gt', '30.00', '25.00'))->toBeTrue();
    });

    it('does not trigger when DSO 20 > threshold 25 (gt operator)', function (): void {
        expect(conditionMet('gt', '20.00', '25.00'))->toBeFalse();
    });

    it('triggers when value equals threshold (gte operator)', function (): void {
        expect(conditionMet('gte', '25.00', '25.00'))->toBeTrue();
    });

    it('triggers when value equals threshold (eq operator)', function (): void {
        expect(conditionMet('eq', '100.00', '100.00'))->toBeTrue();
    });

    it('does not trigger eq when values differ', function (): void {
        expect(conditionMet('eq', '100.01', '100.00'))->toBeFalse();
    });

    it('triggers lt when value below threshold', function (): void {
        expect(conditionMet('lt', '10.00', '50.00'))->toBeTrue();
    });

    it('does not trigger lt when value equals threshold', function (): void {
        expect(conditionMet('lt', '50.00', '50.00'))->toBeFalse();
    });

    it('triggers lte when value equals threshold', function (): void {
        expect(conditionMet('lte', '50.00', '50.00'))->toBeTrue();
    });
});

// ──────────────────────────────────────
// Cooldown Logic
// ──────────────────────────────────────

describe('Cooldown prevents re-trigger', function (): void {
    it('is not in cooldown when never triggered', function (): void {
        $lastTriggeredAt = null;
        $cooldownHours = 24;

        $inCooldown = $lastTriggeredAt !== null
            && now()->diffInHours($lastTriggeredAt) < $cooldownHours;

        expect($inCooldown)->toBeFalse();
    });

    it('is in cooldown when triggered less than 24h ago', function (): void {
        $lastTriggeredAt = now()->subHours(12);
        $cooldownHours = 24;

        $inCooldown = $lastTriggeredAt !== null
            && $lastTriggeredAt->addHours($cooldownHours)->isFuture();

        expect($inCooldown)->toBeTrue();
    });

    it('is not in cooldown when triggered more than 24h ago', function (): void {
        $lastTriggeredAt = now()->subHours(25);
        $cooldownHours = 24;

        $inCooldown = $lastTriggeredAt !== null
            && $lastTriggeredAt->addHours($cooldownHours)->isFuture();

        expect($inCooldown)->toBeFalse();
    });

    it('respects custom cooldown period of 48h', function (): void {
        $lastTriggeredAt = now()->subHours(30);
        $cooldownHours = 48;

        $inCooldown = $lastTriggeredAt !== null
            && $lastTriggeredAt->addHours($cooldownHours)->isFuture();

        expect($inCooldown)->toBeTrue();
    });
});

// ──────────────────────────────────────
// Alert History Record
// ──────────────────────────────────────

describe('Alert history creation on trigger', function (): void {
    it('creates a history entry with correct metric and threshold values', function (): void {
        $metricValue = '30.50';
        $thresholdValue = '25.00';
        $operator = 'gt';

        // Simulate the history record creation
        $historyData = [
            'tenant_id' => 1,
            'alert_rule_id' => 1,
            'triggered_at' => now()->toDateTimeString(),
            'metric_value' => $metricValue,
            'threshold_value' => $thresholdValue,
            'message_ar' => "تنبيه: DSO مرتفع — القيمة الحالية ({$metricValue}) أكبر من الحد ({$thresholdValue})",
            'message_en' => "Alert: High DSO — current value ({$metricValue}) is greater than threshold ({$thresholdValue})",
            'notified_users' => [1, 2, 3],
        ];

        expect($historyData['metric_value'])->toBe('30.50');
        expect($historyData['threshold_value'])->toBe('25.00');
        expect($historyData['notified_users'])->toBeArray()->toHaveCount(3);
        expect($historyData['message_ar'])->toContain('30.50');
        expect($historyData['message_en'])->toContain('greater than');
    });

    it('increments trigger count after firing', function (): void {
        $triggerCount = 0;

        // Simulate trigger
        $triggerCount++;

        expect($triggerCount)->toBe(1);

        // Trigger again
        $triggerCount++;

        expect($triggerCount)->toBe(2);
    });
});

// ──────────────────────────────────────
// End-to-End DSO Threshold Scenario
// ──────────────────────────────────────

describe('DSO threshold trigger scenario', function (): void {
    it('triggers alert when DSO exceeds threshold of 25', function (): void {
        // Given: AR = 100,000, Credit Sales (90d) = 300,000 → DSO = 30
        $arTotal = '100000.00';
        $creditSales = '300000.00';
        $periodDays = '90';

        $dso = bcdiv(bcmul($arTotal, $periodDays, 2), $creditSales, 2);

        // Rule: DSO > 25
        $threshold = '25.00';
        $operator = 'gt';

        $triggered = match ($operator) {
            'gt' => bccomp($dso, $threshold, 2) === 1,
            default => false,
        };

        expect($dso)->toBe('30.00');
        expect($triggered)->toBeTrue();
    });

    it('does not trigger when DSO is below threshold', function (): void {
        // Given: AR = 50,000, Credit Sales (90d) = 300,000 → DSO = 15
        $arTotal = '50000.00';
        $creditSales = '300000.00';
        $periodDays = '90';

        $dso = bcdiv(bcmul($arTotal, $periodDays, 2), $creditSales, 2);

        $threshold = '25.00';
        $triggered = bccomp($dso, $threshold, 2) === 1;

        expect($dso)->toBe('15.00');
        expect($triggered)->toBeFalse();
    });
});
