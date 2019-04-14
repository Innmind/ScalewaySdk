<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

final class Region
{
    private const PARIS1 = 'par1';
    private const AMSTERDAM1 = 'ams1';

    private static $paris1;
    private static $amsterdam1;

    private $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function paris1(): self
    {
        return self::$paris1 ?? self::$paris1 = new self(self::PARIS1);
    }

    public static function amsterdam1(): self
    {
        return self::$amsterdam1 ?? self::$amsterdam1 = new self(self::AMSTERDAM1);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
