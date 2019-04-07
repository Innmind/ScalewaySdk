<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

use Innmind\HttpTransport\Transport;
use Innmind\TimeContinuum\TimeContinuumInterface;
use function Innmind\HttpTransport\bootstrap as http;

function bootstrap(
    Transport $transport,
    TimeContinuumInterface $clock
): Scaleway {
    return new Scaleway\Http(
        http()['throw_on_error']($transport),
        $clock
    );
}
