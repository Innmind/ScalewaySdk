<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Http\Header;

use Innmind\ScalewaySdk\{
    Http\Header\AuthToken,
    Token\Id,
};
use Innmind\Http\Header;
use PHPUnit\Framework\TestCase;

class AuthTokenTest extends TestCase
{
    public function testInterface()
    {
        $header = AuthToken::of(new Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'));

        $this->assertInstanceOf(Header::class, $header);
        $this->assertSame('X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20', $header->toString());
    }
}
