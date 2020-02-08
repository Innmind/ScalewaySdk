<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

use Innmind\HttpTransport\Transport;
use Innmind\TimeContinuum\Clock;

function bootstrap(
    Transport $transport,
    Clock $clock
): Scaleway {
    return new Scaleway\Http(
        $transport,
        $clock,
    );
}
