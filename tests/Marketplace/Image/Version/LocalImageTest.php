<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Marketplace\Image\Version;

use Innmind\ScalewaySdk\{
    Marketplace\Image\Version\LocalImage,
    Image,
    Region,
};
use Innmind\Immutable\SetInterface;
use PHPUnit\Framework\TestCase;

class LocalImageTest extends TestCase
{
    public function testInterface()
    {
        $image = new LocalImage(
            $id = new Image\Id('eb8f7471-5850-4e4f-886a-220efc09ce3d'),
            $architecture = Image\Architecture::arm(),
            $region = Region::paris1(),
            'foo',
            'bar'
        );

        $this->assertSame($id, $image->id());
        $this->assertSame($architecture, $image->architecture());
        $this->assertSame($region, $image->region());
        $this->assertInstanceOf(SetInterface::class, $image->compatibleCommercialTypes());
        $this->assertSame('string', (string) $image->compatibleCommercialTypes()->type());
        $this->assertSame(['foo', 'bar'], $image->compatibleCommercialTypes()->toPrimitive());
    }
}
