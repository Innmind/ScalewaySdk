<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\User;

final class SshKey
{
    private string $key;
    private ?string $description;

    public function __construct(string $key, string $description = null)
    {
        $this->key = $key;
        $this->description = $description;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function hasDescription(): bool
    {
        return \is_string($this->description);
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function description(): string
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->description;
    }
}
