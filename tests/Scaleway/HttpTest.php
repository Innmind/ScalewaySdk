<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Scaleway;

use Innmind\ScalewaySdk\{
    Scaleway\Http,
    Scaleway,
    Tokens,
    Token,
    Authenticated,
};
use Innmind\HttpTransport\Transport;
use Innmind\TimeContinuum\TimeContinuumInterface;
use PHPUnit\Framework\TestCase;

class ScalewayTest extends TestCase
{
    public function testInterface()
    {
        $scaleway = new Http(
            $this->createMock(Transport::class),
            $this->createMock(TimeContinuumInterface::class)
        );

        $this->assertInstanceOf(Scaleway::class, $scaleway);
        $this->assertInstanceOf(Tokens\Http::class, $scaleway->tokens());
        $this->assertInstanceOf(
            Authenticated\Http::class,
            $scaleway->authenticated(new Token\Id('4c11230f-c14a-4874-8cbd-d9c969b86631'))
        );
    }
}