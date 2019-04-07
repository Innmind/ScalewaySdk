<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\{
    Volume,
    Organization,
    Server,
};
use PHPUnit\Framework\TestCase;

class VolumeTest extends TestCase
{
    public function testVolumeAttachedToAServer()
    {
        $volume = new Volume(
            $id = new Volume\Id('228f4993-ca23-4c1c-b2eb-aa8a94e093dd'),
            'foobar',
            $organization = new Organization\Id('228f4993-ca23-4c1c-b2eb-aa8a94e093dd'),
            $size = Volume\Size::of(42),
            $type = Volume\Type::lssd(),
            $server = new Server\Id('228f4993-ca23-4c1c-b2eb-aa8a94e093dd')
        );

        $this->assertSame($id, $volume->id());
        $this->assertSame('foobar', $volume->name());
        $this->assertSame($organization, $volume->organization());
        $this->assertSame($size, $volume->size());
        $this->assertSame($type, $volume->type());
        $this->assertTrue($volume->attachedToAServer());
        $this->assertSame($server, $volume->server());
    }

    public function testStandaloneVolume()
    {
        $volume = new Volume(
            $id = new Volume\Id('228f4993-ca23-4c1c-b2eb-aa8a94e093dd'),
            'foobar',
            $organization = new Organization\Id('228f4993-ca23-4c1c-b2eb-aa8a94e093dd'),
            $size = Volume\Size::of(42),
            $type = Volume\Type::lssd(),
            null
        );

        $this->assertSame($id, $volume->id());
        $this->assertSame('foobar', $volume->name());
        $this->assertSame($organization, $volume->organization());
        $this->assertSame($size, $volume->size());
        $this->assertSame($type, $volume->type());
        $this->assertFalse($volume->attachedToAServer());

        $this->expectException(\TypeError::class);

        $volume->server();
    }
}
