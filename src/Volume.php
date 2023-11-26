<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

final class Volume
{
    private Volume\Id $id;
    private Volume\Name $name;
    private Organization\Id $organization;
    private Volume\Size $size;
    private Volume\Type $type;
    private ?Server\Id $server;

    public function __construct(
        Volume\Id $id,
        Volume\Name $name,
        Organization\Id $organization,
        Volume\Size $size,
        Volume\Type $type,
        ?Server\Id $server,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->organization = $organization;
        $this->size = $size;
        $this->type = $type;
        $this->server = $server;
    }

    public function id(): Volume\Id
    {
        return $this->id;
    }

    public function name(): Volume\Name
    {
        return $this->name;
    }

    public function organization(): Organization\Id
    {
        return $this->organization;
    }

    public function size(): Volume\Size
    {
        return $this->size;
    }

    public function type(): Volume\Type
    {
        return $this->type;
    }

    public function attachedToAServer(): bool
    {
        return $this->server instanceof Server\Id;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function server(): Server\Id
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->server;
    }
}
