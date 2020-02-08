<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Volume;

use Innmind\ScalewaySdk\Exception\DomainException;

final class Size
{
    private int $value;

    private function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function of(int $value): self
    {
        if ($value < 0) {
            throw new DomainException;
        }

        return new self($value);
    }

    public static function of25Go(): self
    {
        return new self(25_000_000_000);
    }

    public static function of50Go(): self
    {
        return new self(50_000_000_000);
    }

    public static function of75Go(): self
    {
        return new self(75_000_000_000);
    }

    public static function of100Go(): self
    {
        return new self(100_000_000_000);
    }

    public static function of125Go(): self
    {
        return new self(125_000_000_000);
    }

    public static function of150Go(): self
    {
        return new self(150_000_000_000);
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
