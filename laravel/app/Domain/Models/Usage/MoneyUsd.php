<?php

declare(strict_types=1);

namespace App\Domain\Models\Usage;

use InvalidArgumentException;

readonly class MoneyUsd
{
    private const SCALE = 5;
    private string $estimated_cost_usd;

    public function __construct(string $estimated_cost_usd) 
    {
        $estimated_cost_usd = trim($estimated_cost_usd);

        if ($estimated_cost_usd === '') {
            throw new InvalidArgumentException('USD金額が空です。');
        }

        if (!preg_match('/^\d+(\.\d+)?$/', $estimated_cost_usd)) {
            throw new InvalidArgumentException("USD金額が不正です: {$estimated_cost_usd}");
        }

        $normalized = $this->normalizeScale($estimated_cost_usd);

        // ★ VO生成前の生値チェックなので this は使わない
        if (\bccomp($normalized, '0', self::SCALE) < 0) {
            throw new InvalidArgumentException("USD金額は0以上である必要があります: {$estimated_cost_usd}");
        }

        $this->estimated_cost_usd = $normalized;
    }

    public static function zero(): self
    {
        return new self('0');
    }

    /**
     * Compare this money with numeric-string.
     * Returns: -1 if <, 0 if ==, 1 if >
     */
    public function compare(string $otherNumericString): int
    {
        $other = $this->normalizeScale($otherNumericString);
        return \bccomp($this->estimated_cost_usd, $other, self::SCALE);
    }

    public function isGreaterThan(self $other): bool
    {
        return \bccomp($this->estimated_cost_usd, $other->estimated_cost_usd, self::SCALE) === 1;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        $c = \bccomp($this->estimated_cost_usd, $other->estimated_cost_usd, self::SCALE);
        return $c === 1 || $c === 0;
    }

    public function isZero(): bool
    {
        return \bccomp($this->estimated_cost_usd, '0', self::SCALE) === 0;
    }

    public function getValue(): string
    {
        return $this->estimated_cost_usd;
    }

    public function __toString(): string
    {
        return $this->estimated_cost_usd;
    }

    private function normalizeScale(string $value): string
    {
        if (str_contains($value, '.')) {
            [$int, $frac] = explode('.', $value, 2);
            $frac = substr($frac . str_repeat('0', self::SCALE), 0, self::SCALE);
            $int = ltrim($int, '0');
            $int = $int === '' ? '0' : $int;
            return $int . '.' . $frac;
        }

        $int = ltrim($value, '0');
        $int = $int === '' ? '0' : $int;
        return $int . '.' . str_repeat('0', self::SCALE);
    }
}