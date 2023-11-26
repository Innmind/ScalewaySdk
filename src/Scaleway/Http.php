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
use Innmind\TimeContinuum\Clock;
use function Innmind\HttpTransport\bootstrap as http;

final class Http implements Scaleway
{
    private Transport $transport;
    private Clock $clock;
    private ?Tokens $tokens = null;
    private ?Authenticated $authenticated = null;

    public function __construct(
        Transport $transport,
        Clock $clock,
    ) {
        $this->transport = http()['throw_on_error']($transport);
        $this->clock = $clock;
    }

    public function tokens(): Tokens
    {
        return $this->tokens ??= new Tokens\Http(
            $this->transport,
            $this->clock,
        );
    }

    public function authenticated(Token\Id $token): Authenticated
    {
        return $this->authenticated ??= new Authenticated\Http(
            $this->transport,
            $this->clock,
            $token,
        );
    }
}
