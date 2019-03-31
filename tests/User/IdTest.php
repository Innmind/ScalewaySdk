<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\User;

use Innmind\ScalewaySdk\{
    User\Id,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class IdTest extends TestCase
{
    public function testInterface()
    {
        $id = new Id('5bea0358-db40-429e-bd82-914686a7e7b9');

        $this->assertSame('5bea0358-db40-429e-bd82-914686a7e7b9', (string) $id);
    }

    public function testThrowWhenNotAUuid()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('foo');

        new Id('foo');
    }
}