<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Token;

use Innmind\ScalewaySdk\Exception\DomainException;
use Ramsey\Uuid\Uuid;

final class Id
{
    private $value;

    public function __construct(string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new DomainException($value);
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
