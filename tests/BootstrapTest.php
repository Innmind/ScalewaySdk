<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk;

use function Innmind\ScalewaySdk\bootstrap;
use Innmind\ScalewaySdk\Scaleway;
use Innmind\HttpTransport\{
    Transport,
    ThrowOnErrorTransport,
};
use Innmind\TimeContinuum\Clock;
use Innmind\ObjectGraph\{
    Assert\Stack,
    Graph,
    Visualize
};
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testInvokation()
    {
        $sdk = bootstrap(
            $http = $this->createMock(Transport::class),
            $this->createMock(Clock::class),
        );

        $this->assertInstanceOf(Scaleway\Http::class, $sdk);
        $stack = Stack::of(
            ThrowOnErrorTransport::class,
            \get_class($http),
        );

        $this->assertTrue($stack((new Graph)($sdk)));
    }
}
