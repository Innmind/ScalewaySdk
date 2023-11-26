<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Marketplace\Image;

use Innmind\ScalewaySdk\{
    Marketplace\Image\Version,
    Image,
    Region,
};
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    public function testInterface()
    {
        $version = new Version(
            $id = new Version\Id('cdd886bb-dadd-4084-8e5a-e50a4215c6d2'),
            $image = new Version\LocalImage(
                new Image\Id('eb8f7471-5850-4e4f-886a-220efc09ce3d'),
                Image\Architecture::arm(),
                Region::paris1(),
            ),
        );

        $this->assertSame($id, $version->id());
        $this->assertInstanceOf(Set::class, $version->localImages());
        $this->assertSame(Version\LocalImage::class, (string) $version->localImages()->type());
        $this->assertSame([$image], unwrap($version->localImages()));
    }
}
