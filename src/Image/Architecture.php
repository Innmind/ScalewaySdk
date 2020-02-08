<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Image;

use Innmind\ScalewaySdk\Exception\DomainException;

final class Architecture
{
    private const ARM = 'arm';
    private const ARM64 = 'arm64';
    private const X86_64 = 'x86_64';

    private static ?self $arm = null;
    private static ?self $arm64 = null;
    private static ?self $x86_64 = null;

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function of(string $value): self
    {
        switch ($value) {
            case self::ARM:
                return self::arm();

            case self::ARM64:
                return self::arm64();

            case self::X86_64:
                return self::x86_64();
        }

        throw new DomainException($value);
    }

    public static function arm(): self
    {
        return self::$arm ??= new self(self::ARM);
    }

    public static function arm64(): self
    {
        return self::$arm64 ??= new self(self::ARM64);
    }

    public static function x86_64(): self
    {
        return self::$x86_64 ??= new self(self::X86_64);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
