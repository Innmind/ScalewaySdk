<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\{
    Image,
    Organization,
};
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    public function testPublic()
    {
        $image = new Image(
            $id = new Image\Id('8ba71ed0-1469-4574-be80-a0a476d9f355'),
            $organization = new Organization\Id('2f71895b-d649-47bd-a1a9-a145652e0f3e'),
            'foo',
            $architecture = Image\Architecture::x86_64(),
            true
        );

        $this->assertSame($id, $image->id());
        $this->assertSame($organization, $image->organization());
        $this->assertSame('foo', $image->name());
        $this->assertSame($architecture, $image->architecture());
        $this->assertTrue($image->public());
    }

    public function testPrivate()
    {
        $image = new Image(
            $id = new Image\Id('8ba71ed0-1469-4574-be80-a0a476d9f355'),
            $organization = new Organization\Id('2f71895b-d649-47bd-a1a9-a145652e0f3e'),
            'foo',
            $architecture = Image\Architecture::x86_64(),
            false
        );

        $this->assertSame($id, $image->id());
        $this->assertSame($organization, $image->organization());
        $this->assertSame('foo', $image->name());
        $this->assertSame($architecture, $image->architecture());
        $this->assertFalse($image->public());
    }
}
