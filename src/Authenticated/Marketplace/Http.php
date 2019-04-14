<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated\Marketplace;

use Innmind\ScalewaySdk\{
    Authenticated\Marketplace,
    Token,
};
use Innmind\HttpTransport\Transport;
use Innmind\TimeContinuum\TimeContinuumInterface;

final class Http implements Marketplace
{
    private $transport;
    private $clock;
    private $token;
    private $images;

    public function __construct(
        Transport $transport,
        TimeContinuumInterface $clock,
        Token\Id $token
    ) {
        $this->transport = $transport;
        $this->clock = $clock;
        $this->token = $token;
    }

    public function images(): Images
    {
        return $this->images ?? $this->images = new Images\Http(
            $this->transport,
            $this->clock,
            $this->token
        );
    }
}
