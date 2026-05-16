<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Arbitrary-precision money arithmetic wrapper around bcmath.
 *
 * All public operations accept mixed numeric input (int, float, numeric-string,
 * null) and always return a normalized 2-decimal numeric string so values can
 * be written straight back to decimal(x, 2) columns without precision loss.
 *
 * Intermediate multiplication/division uses scale 6 to avoid cascading
 * rounding errors before the final half-away-from-zero round to 2 decimals.
 */
final class Money
{
    public const SCALE = 2;

    private const INTERNAL_SCALE = 6;

    public static function zero(): string
    {
        return '0.00';
    }

    /**
     * Coerce any numeric-ish value to a normalized 2-decimal string.
     */
    public static function of(mixed $value): string
    {
        if ($value === null || $value === '') {
            return self::zero();
        }

        if (is_int($value)) {
            return self::round((string) $value);
        }

        if (is_float($value)) {
            if (! is_finite($value)) {
                throw new InvalidArgumentException('Money value must be finite.');
            }

            return self::round(sprintf('%.'.self::INTERNAL_SCALE.'F', $value));
        }

        if (is_string($value) && is_numeric($value)) {
            return self::round($value);
        }

        throw new InvalidArgumentException('Money value must be numeric, got '.get_debug_type($value));
    }

    public static function add(mixed $a, mixed $b): string
    {
        return bcadd(self::of($a), self::of($b), self::SCALE);
    }

    public static function sub(mixed $a, mixed $b): string
    {
        return bcsub(self::of($a), self::of($b), self::SCALE);
    }

    /**
     * Multiply two values and return a 2-decimal result, using scale 6 internally.
     */
    public static function mul(mixed $a, mixed $b): string
    {
        $product = bcmul(self::of($a), self::of($b), self::INTERNAL_SCALE);

        return self::round($product);
    }

    public static function div(mixed $a, mixed $b): string
    {
        $divisor = self::of($b);
        if (bccomp($divisor, '0', self::INTERNAL_SCALE) === 0) {
            throw new InvalidArgumentException('Division by zero in Money::div.');
        }

        return self::round(bcdiv(self::of($a), $divisor, self::INTERNAL_SCALE));
    }

    /**
     * Apply a percentage to an amount: amount * (percentage / 100).
     * Kept as a single operation to preserve precision across the divide.
     */
    public static function percent(mixed $amount, mixed $percentage): string
    {
        $raw = bcdiv(
            bcmul(self::of($amount), self::of($percentage), self::INTERNAL_SCALE),
            '100',
            self::INTERNAL_SCALE,
        );

        return self::round($raw);
    }

    public static function cmp(mixed $a, mixed $b): int
    {
        return bccomp(self::of($a), self::of($b), self::SCALE);
    }

    public static function max(mixed $a, mixed $b): string
    {
        return self::cmp($a, $b) >= 0 ? self::of($a) : self::of($b);
    }

    public static function abs(mixed $a): string
    {
        $value = self::of($a);

        return bccomp($value, '0', self::SCALE) < 0 ? bcmul($value, '-1', self::SCALE) : $value;
    }

    public static function isZero(mixed $a): bool
    {
        return self::cmp($a, '0') === 0;
    }

    public static function isNegative(mixed $a): bool
    {
        return self::cmp($a, '0') < 0;
    }

    public static function isPositive(mixed $a): bool
    {
        return self::cmp($a, '0') > 0;
    }

    /**
     * Sum a list of values at scale 2.
     *
     * @param  iterable<mixed>  $values
     */
    public static function sum(iterable $values): string
    {
        $total = self::zero();
        foreach ($values as $value) {
            $total = self::add($total, $value);
        }

        return $total;
    }

    /**
     * Half-away-from-zero round of a numeric string to 2 decimals, matching
     * PHP's default round() behavior without going through float.
     */
    private static function round(string $value): string
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Non-numeric value passed to Money::round: {$value}");
        }

        $adjustment = '0.'.str_repeat('0', self::SCALE).'5';

        if (bccomp($value, '0', self::INTERNAL_SCALE) < 0) {
            return bcsub($value, $adjustment, self::SCALE);
        }

        return bcadd($value, $adjustment, self::SCALE);
    }
}
