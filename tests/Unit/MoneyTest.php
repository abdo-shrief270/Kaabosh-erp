<?php

declare(strict_types=1);

use App\Support\Money;

describe('Money::of normalization', function (): void {

    it('returns 0.00 for null and empty string', function (): void {
        expect(Money::of(null))->toBe('0.00');
        expect(Money::of(''))->toBe('0.00');
    });

    it('normalizes integers and numeric strings', function (): void {
        expect(Money::of(100))->toBe('100.00');
        expect(Money::of('42'))->toBe('42.00');
        expect(Money::of('42.5'))->toBe('42.50');
        expect(Money::of('42.555'))->toBe('42.56');
        expect(Money::of('-10.005'))->toBe('-10.01');
    });

    it('normalizes floats without drift', function (): void {
        expect(Money::of(0.1 + 0.2))->toBe('0.30');
        expect(Money::of(33.33))->toBe('33.33');
    });

    it('throws on non-numeric input', function (): void {
        expect(fn () => Money::of('abc'))->toThrow(InvalidArgumentException::class);
    });

    it('throws on non-finite floats', function (): void {
        expect(fn () => Money::of(INF))->toThrow(InvalidArgumentException::class);
        expect(fn () => Money::of(NAN))->toThrow(InvalidArgumentException::class);
    });
});

describe('Money arithmetic', function (): void {

    it('adds and subtracts without float drift', function (): void {
        expect(Money::add('0.1', '0.2'))->toBe('0.30');
        expect(Money::add('10.10', '20.20'))->toBe('30.30');
        expect(Money::sub('50.00', '25.75'))->toBe('24.25');
        expect(Money::sub('10', '25'))->toBe('-15.00');
    });

    it('multiplies with half-away-from-zero rounding at scale 2', function (): void {
        expect(Money::mul('10', '3.3'))->toBe('33.00');
        expect(Money::mul('0.25', '0.5'))->toBe('0.13');
        expect(Money::mul('2', '-1.5'))->toBe('-3.00');
    });

    it('divides and guards against zero divisor', function (): void {
        expect(Money::div('100', '3'))->toBe('33.33');
        expect(Money::div('100', '8'))->toBe('12.50');
        expect(fn () => Money::div('10', '0'))->toThrow(InvalidArgumentException::class);
    });

    it('computes percentages precisely', function (): void {
        expect(Money::percent('100.00', '33.33'))->toBe('33.33');
        expect(Money::percent('1000.00', '14'))->toBe('140.00');
        expect(Money::percent('0', '50'))->toBe('0.00');
        expect(Money::percent('500.00', '0'))->toBe('0.00');
    });

    it('sums an iterable of mixed-type numerics', function (): void {
        expect(Money::sum([1, 2, 3, '4.50']))->toBe('10.50');
        expect(Money::sum([]))->toBe('0.00');
        expect(Money::sum([0.1, 0.2, 0.3]))->toBe('0.60');
    });
});

describe('Money comparisons and predicates', function (): void {

    it('compares at 2 decimal scale', function (): void {
        expect(Money::cmp('10.00', '10.00'))->toBe(0);
        expect(Money::cmp('10.01', '10.00'))->toBe(1);
        expect(Money::cmp('9.99', '10.00'))->toBe(-1);
        // Third decimal is below scale and must not affect cmp
        expect(Money::cmp('10.001', '10.00'))->toBe(0);
    });

    it('identifies zero, positive, and negative values', function (): void {
        expect(Money::isZero('0'))->toBeTrue();
        expect(Money::isZero('0.00'))->toBeTrue();
        expect(Money::isZero('-0.001'))->toBeTrue();
        expect(Money::isNegative('-0.01'))->toBeTrue();
        expect(Money::isNegative('0'))->toBeFalse();
        expect(Money::isPositive('0.01'))->toBeTrue();
    });

    it('returns the larger value and absolute value', function (): void {
        expect(Money::max('10.50', '10.49'))->toBe('10.50');
        expect(Money::max('-5', '-10'))->toBe('-5.00');
        expect(Money::abs('-42.50'))->toBe('42.50');
        expect(Money::abs('42.50'))->toBe('42.50');
    });
});

describe('Money precision invariants', function (): void {

    it('preserves sum invariants for weighted profit splits', function (): void {
        // Three investors owning 33.33 / 33.33 / 33.34 share net profit of 100.00
        $shares = [
            Money::percent('100.00', '33.33'),
            Money::percent('100.00', '33.33'),
            Money::percent('100.00', '33.34'),
        ];

        expect(Money::sum($shares))->toBe('100.00');
    });

    it('applies 14% VAT to the cent across many lines', function (): void {
        // 10 lines of 12.34 each, 14% VAT per line
        $lineVats = array_fill(0, 10, Money::percent('12.34', '14'));
        // 12.34 * 14 / 100 = 1.7276 -> 1.73 per line
        expect($lineVats[0])->toBe('1.73');
        expect(Money::sum($lineVats))->toBe('17.30');
    });

    it('never drifts across 1000 sequential additions', function (): void {
        $total = Money::zero();
        for ($i = 0; $i < 1000; $i++) {
            $total = Money::add($total, '0.01');
        }
        expect($total)->toBe('10.00');
    });
});
