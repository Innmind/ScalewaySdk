<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Volume;

use Innmind\ScalewaySdk\Exception\DomainException;

final class Type
{
    private const LSSD = 'l_ssd';

    private static $lssd;

    private $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function of(string $value): self
    {
        switch ($value) {
            case self::LSSD:
                return self::lssd();
        }

        throw new DomainException($value);
    }

    public static function lssd(): self
    {
        return self::$lssd ?? self::$lssd = new self(self::LSSD);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
