<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Token;

use Innmind\ScalewaySdk\{
    Token\Id,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class IdTest extends TestCase
{
    public function testInterface()
    {
        $id = new Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20');

        $this->assertSame('9de8f869-c58e-4aa3-9208-2d4eaff5fa20', (string) $id);
    }

    public function testThrowWhenNotAUuid()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('foo');

        new Id('foo');
    }
}
