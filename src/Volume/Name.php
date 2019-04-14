<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Volume;

use Innmind\ScalewaySdk\Exception\DomainException;
use Innmind\Immutable\Str;

final class Name
{
    private $value;

    public function __construct(string $value)
    {
        if (!Str::of($value)->matches('~^[a-zA-Z0-9\-]+$~')) {
            throw new DomainException($value);
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
