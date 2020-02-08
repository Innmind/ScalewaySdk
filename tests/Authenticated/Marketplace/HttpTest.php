<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Authenticated\Marketplace;

use Innmind\ScalewaySdk\{
    Authenticated\Marketplace\Http,
    Authenticated\Marketplace\Images,
    Authenticated\Marketplace,
    Token,
};
use Innmind\TimeContinuum\Clock;
use Innmind\HttpTransport\Transport;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    public function testInterface()
    {
        $marketplace = new Http(
            $this->createMock(Transport::class),
            $this->createMock(Clock::class),
            new Token\Id('c378c728-8fe9-4e23-a07f-a2f49dff5c46')
        );

        $this->assertInstanceOf(Marketplace::class, $marketplace);
        $this->assertInstanceOf(Images\Http::class, $marketplace->images());
        $this->assertSame($marketplace->images(), $marketplace->images());
    }
}
