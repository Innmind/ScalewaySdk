<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\User;

final class SshKey
{
    private $key;
    private $description;

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

    public function description(): string
    {
        return $this->description;
    }
}
