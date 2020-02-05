<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated;

use Innmind\ScalewaySdk\{
    Authenticated,
    Region,
    Token,
};
use Innmind\HttpTransport\Transport;
use Innmind\TimeContinuum\TimeContinuumInterface;

final class Http implements Authenticated
{
    private Transport $transport;
    private TimeContinuumInterface $clock;
    private Token\Id $token;

    public function __construct(
        Transport $transport,
        TimeContinuumInterface $clock,
        Token\Id $token
    ) {
        $this->transport = $transport;
        $this->clock = $clock;
        $this->token = $token;
    }

    public function images(Region $region): Images
    {
        return new Images\Http(
            $this->transport,
            $region,
            $this->token
        );
    }

    public function ips(Region $region): IPs
    {
        return new IPs\Http(
            $this->transport,
            $region,
            $this->token
        );
    }

    public function servers(Region $region): Servers
    {
        return new Servers\Http(
            $this->transport,
            $region,
            $this->token
        );
    }

    public function tokens(): Tokens
    {
        return new Tokens\Http(
            $this->transport,
            $this->clock,
            $this->token
        );
    }

    public function users(): Users
    {
        return new Users\Http(
            $this->transport,
            $this->token
        );
    }

    public function volumes(Region $region): Volumes
    {
        return new Volumes\Http(
            $this->transport,
            $region,
            $this->token
        );
    }

    public function marketplace(): Marketplace
    {
        return new Marketplace\Http(
            $this->transport,
            $this->clock,
            $this->token
        );
    }
}
