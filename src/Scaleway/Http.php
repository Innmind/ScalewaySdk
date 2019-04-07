<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Scaleway;

use Innmind\ScalewaySdk\{
    Scaleway,
    Tokens,
    Token,
    Authenticated,
};
use Innmind\HttpTransport\Transport;
use Innmind\TimeContinuum\TimeContinuumInterface;

final class Http implements Scaleway
{
    private $transport;
    private $clock;
    private $tokens;
    private $authenticated;

    public function __construct(
        Transport $transport,
        TimeContinuumInterface $clock
    ) {
        $this->transport = $transport;
        $this->clock = $clock;
    }

    public function tokens(): Tokens
    {
        return $this->tokens ?? $this->tokens = new Tokens\Http(
            $this->transport,
            $this->clock
        );
    }

    public function authenticated(Token\Id $token): Authenticated
    {
        return $this->authenticated ?? $this->authenticated = new Authenticated\Http(
            $this->transport,
            $this->clock,
            $token
        );
    }
}
