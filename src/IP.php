<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

use Innmind\IP\IP as Address;

final class IP
{
    private IP\Id $id;
    private Address $address;
    private Organization\Id $organization;
    private ?Server\Id $server;

    public function __construct(
        IP\Id $id,
        Address $address,
        Organization\Id $organization,
        ?Server\Id $server,
    ) {
        $this->id = $id;
        $this->address = $address;
        $this->organization = $organization;
        $this->server = $server;
    }

    public function id(): IP\Id
    {
        return $this->id;
    }

    public function address(): Address
    {
        return $this->address;
    }

    public function organization(): Organization\Id
    {
        return $this->organization;
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
