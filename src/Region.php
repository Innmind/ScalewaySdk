<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\Exception\DomainException;

final class Region
{
    private const PARIS1 = 'par1';
    private const AMSTERDAM1 = 'ams1';

    private static ?self $paris1 = null;
    private static ?self $amsterdam1 = null;

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function of(string $value): self
    {
        switch ($value) {
            case self::PARIS1:
                return self::paris1();

            case self::AMSTERDAM1:
                return self::amsterdam1();
        }

        throw new DomainException($value);
    }

    public static function paris1(): self
    {
        return self::$paris1 ??= new self(self::PARIS1);
    }

    public static function amsterdam1(): self
    {
        return self::$amsterdam1 ??= new self(self::AMSTERDAM1);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
