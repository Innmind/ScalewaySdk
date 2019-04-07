<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Volume;

use Innmind\ScalewaySdk\{
    Volume\Type,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    public function testLssd()
    {
        $this->assertInstanceOf(Type::class, Type::lssd());
        $this->assertSame('l_ssd', (string) Type::lssd());
        $this->assertInstanceOf(Type::class, Type::of('l_ssd'));
        $this->assertSame('l_ssd', (string) Type::of('l_ssd'));
    }

    public function testThrowWhenUnknownType()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('foo');

        Type::of('foo');
    }
}
