<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Volume;

use Innmind\ScalewaySdk\Exception\DomainException;

final class Type
{
    private const LSSD = 'l_ssd';
    private const BSSD = 'b_ssd';

    private static ?self $lssd = null;
    private static ?self $bssd = null;

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function of(string $value): self
    {
        switch ($value) {
            case self::LSSD:
                return self::lssd();
            case self::BSSD:
                return self::bssd();
        }

        throw new DomainException($value);
    }

    public static function lssd(): self
    {
        return self::$lssd ??= new self(self::LSSD);
    }

    public static function bssd(): self
    {
        return self::$bssd ??= new self(self::BSSD);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
