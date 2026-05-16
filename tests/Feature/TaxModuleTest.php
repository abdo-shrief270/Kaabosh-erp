<?php

declare(strict_types=1);

use App\Domain\Tax\Enums\TaxAdjustmentType;
use App\Domain\Tax\Enums\TaxReturnStatus;
use App\Domain\Tax\Enums\WhtCertificateStatus;

describe('TaxReturnStatus transitions', function (): void {

    it('can calculate only from Draft', function (): void {
        expect(TaxReturnStatus::Draft->canCalculate())->toBeTrue();
        expect(TaxReturnStatus::Calculated->canCalculate())->toBeFalse();
        expect(TaxReturnStatus::Filed->canCalculate())->toBeFalse();
        expect(TaxReturnStatus::Paid->canCalculate())->toBeFalse();
    });

    it('can file only from Calculated', function (): void {
        expect(TaxReturnStatus::Calculated->canFile())->toBeTrue();
        expect(TaxReturnStatus::Draft->canFile())->toBeFalse();
        expect(TaxReturnStatus::Filed->canFile())->toBeFalse();
        expect(TaxReturnStatus::Paid->canFile())->toBeFalse();
    });

    it('can pay only from Filed', function (): void {
        expect(TaxReturnStatus::Filed->canPay())->toBeTrue();
        expect(TaxReturnStatus::Draft->canPay())->toBeFalse();
        expect(TaxReturnStatus::Calculated->canPay())->toBeFalse();
        expect(TaxReturnStatus::Paid->canPay())->toBeFalse();
    });

});

describe('TaxAdjustmentType labels', function (): void {

    it('has non-empty labels for all cases', function (): void {
        foreach (TaxAdjustmentType::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
            expect($case->labelAr())->toBeString()->not->toBeEmpty();
        }
    });

});

describe('WhtCertificateStatus labels', function (): void {

    it('has non-empty labels for all cases', function (): void {
        foreach (WhtCertificateStatus::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
            expect($case->labelAr())->toBeString()->not->toBeEmpty();
        }
    });

});

describe('Corporate tax calculation', function (): void {

    it('calculates corporate tax with non-deductible adjustments', function (): void {
        // Revenue 1,000,000 - Expenses 700,000 = Accounting profit 300,000
        $revenue = '1000000.00';
        $expenses = '700000.00';
        $accountingProfit = bcsub($revenue, $expenses, 2);

        expect($accountingProfit)->toBe('300000.00');

        // Add non-deductible expense 50,000 → Taxable income 350,000
        $nonDeductible = '50000.00';
        $taxableIncome = bcadd($accountingProfit, $nonDeductible, 2);

        expect($taxableIncome)->toBe('350000.00');

        // Corporate tax rate 22.5%
        $taxAmount = bcmul($taxableIncome, '0.225', 2);

        expect($taxAmount)->toBe('78750.00');
    });

});

describe('VAT net calculation', function (): void {

    it('calculates net VAT as output minus input', function (): void {
        $outputVat = '140000.00';
        $inputVat = '84000.00';

        $netVat = bcsub($outputVat, $inputVat, 2);

        expect($netVat)->toBe('56000.00');
    });

});

describe('WHT certificate amount', function (): void {

    it('calculates total WHT from bills with WHT rates', function (): void {
        // 3 bills, each with amount 100,000 and WHT rate 3%
        $bills = [
            ['amount' => '100000.00', 'wht_rate' => '0.03'],
            ['amount' => '100000.00', 'wht_rate' => '0.03'],
            ['amount' => '100000.00', 'wht_rate' => '0.03'],
        ];

        $totalWht = '0.00';
        foreach ($bills as $bill) {
            $wht = bcmul($bill['amount'], $bill['wht_rate'], 2);
            $totalWht = bcadd($totalWht, $wht, 2);
        }

        expect($totalWht)->toBe('9000.00');
    });

});

describe('Tax loss carryforward', function (): void {

    it('reduces taxable income and tracks remaining carryforward', function (): void {
        $taxableIncome = '100000.00';
        $carryforward = '150000.00';

        // Apply carryforward: can only offset up to taxable income
        $appliedCarryforward = min($taxableIncome, $carryforward);
        // Use bccomp for proper string comparison
        if (bccomp($carryforward, $taxableIncome, 2) <= 0) {
            $appliedCarryforward = $carryforward;
        } else {
            $appliedCarryforward = $taxableIncome;
        }

        $adjustedTaxable = bcsub($taxableIncome, $appliedCarryforward, 2);
        $remainingCarryforward = bcsub($carryforward, $appliedCarryforward, 2);

        expect($adjustedTaxable)->toBe('0.00');
        expect($remainingCarryforward)->toBe('50000.00');
    });

});
