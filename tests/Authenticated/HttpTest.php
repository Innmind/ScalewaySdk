<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Authenticated;

use Innmind\ScalewaySdk\{
    Authenticated,
    Region,
    Token,
};
use Innmind\HttpTransport\Transport;
use Innmind\TimeContinuum\TimeContinuumInterface;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    public function testInterface()
    {
        $authenticated = new Authenticated\Http(
            $this->createMock(Transport::class),
            $this->createMock(TimeContinuumInterface::class),
            new Token\Id('989389f8-ea22-42e8-a194-1c5571051e2c')
        );

        $this->assertInstanceOf(Authenticated::class, $authenticated);
        $this->assertInstanceOf(Authenticated\Images\Http::class, $authenticated->images(Region::paris1()));
        $this->assertInstanceOf(Authenticated\IPs\Http::class, $authenticated->ips(Region::paris1()));
        $this->assertInstanceOf(Authenticated\Servers\Http::class, $authenticated->servers(Region::paris1()));
        $this->assertInstanceOf(Authenticated\Tokens\Http::class, $authenticated->tokens());
        $this->assertInstanceOf(Authenticated\Users\Http::class, $authenticated->users());
        $this->assertInstanceOf(Authenticated\Volumes\Http::class, $authenticated->volumes(Region::paris1()));
    }
}
