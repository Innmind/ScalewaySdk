<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated\Marketplace;

use Innmind\ScalewaySdk\{
    Authenticated\Marketplace,
    Token,
};
use Innmind\HttpTransport\Transport;
use Innmind\TimeContinuum\Clock;

final class Http implements Marketplace
{
    private Transport $transport;
    private Clock $clock;
    private Token\Id $token;
    private ?Images $images = null;

    public function __construct(
        Transport $transport,
        Clock $clock,
        Token\Id $token,
    ) {
        $this->transport = $transport;
        $this->clock = $clock;
        $this->token = $token;
    }

    public function images(): Images
    {
        return $this->images ??= new Images\Http(
            $this->transport,
            $this->clock,
            $this->token,
        );
    }
}
