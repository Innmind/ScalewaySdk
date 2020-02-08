<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\{
    Token,
    User,
};
use Innmind\TimeContinuum\PointInTime;
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
{
    public function testTemporaryToken()
    {
        $token = new Token(
            $id = new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
            $user = new User\Id('5bea0358-db40-429e-bd82-914686a7e7b9'),
            $createdAt = $this->createMock(PointInTime::class),
            $expiresAt = $this->createMock(PointInTime::class)
        );

        $this->assertSame($id, $token->id());
        $this->assertSame($user, $token->user());
        $this->assertSame($createdAt, $token->createdAt());
        $this->assertTrue($token->expires());
        $this->assertSame($expiresAt, $token->expiresAt());
    }

    public function testPermanentToken()
    {
        $token = new Token(
            $id = new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
            $user = new User\Id('5bea0358-db40-429e-bd82-914686a7e7b9'),
            $createdAt = $this->createMock(PointInTime::class)
        );

        $this->assertSame($id, $token->id());
        $this->assertSame($user, $token->user());
        $this->assertSame($createdAt, $token->createdAt());
        $this->assertFalse($token->expires());

        $this->expectException(\TypeError::class);

        $token->expiresAt();
    }
}
