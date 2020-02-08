<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Marketplace\Image\Version;

use Innmind\ScalewaySdk\Exception\DomainException;
use Ramsey\Uuid\Uuid;

final class Id
{
    private string $value;

    public function __construct(string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new DomainException($value);
        }

        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
