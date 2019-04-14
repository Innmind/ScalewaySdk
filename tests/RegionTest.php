<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\{
    Region,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class RegionTest extends TestCase
{
    public function testParis1()
    {
        $this->assertInstanceOf(Region::class, Region::paris1());
        $this->assertSame(Region::paris1(), Region::paris1());
        $this->assertSame(Region::paris1(), Region::of('par1'));
        $this->assertSame('par1', (string) Region::paris1());
    }

    public function testAmsterdam()
    {
        $this->assertInstanceOf(Region::class, Region::amsterdam1());
        $this->assertSame(Region::amsterdam1(), Region::amsterdam1());
        $this->assertSame(Region::amsterdam1(), Region::of('ams1'));
        $this->assertSame('ams1', (string) Region::amsterdam1());
    }

    public function testThrowWhenInvalidValue()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('foo');

        Region::of('foo');
    }
}
