<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

final class Region
{
    private $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function paris1(): self
    {
        return new self('par1');
    }

    public static function amsterdam1(): self
    {
        return new self('ams1');
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
