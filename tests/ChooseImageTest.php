<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\{
    ChooseImage,
    Marketplace,
    Image,
    Organization,
    Region,
    Exception\ImageCannotBeDetermined,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class ChooseImageTest extends TestCase
{
    private $image;
    private $expected;

    public function setUp(): void
    {
        $this->image = new Marketplace\Image(
            new Marketplace\Image\Id('e4ae6a9e-88a1-4a44-a418-263f03b197fa'),
            new Organization\Id('ac5be507-17d8-453e-8974-071bcfd09fc6'),
            $currentPublicVersion = new Marketplace\Image\Version(
                new Marketplace\Image\Version\Id('d24fae12-9b64-4241-b614-03fb643130ef'),
                new Marketplace\Image\Version\LocalImage(
                    $this->expected = new Image\Id('9cf52e89-b8bf-43ec-b02a-3de82f3f851c'),
                    Image\Architecture::arm(),
                    Region::paris1(),
                    new Marketplace\Product\Server\Name('C1')
                )
            ),
            Set::of(Marketplace\Image\Version::class, $currentPublicVersion),
            new Marketplace\Image\Name('Ubuntu'),
            Set::of(Marketplace\Image\Category::class),
            $this->createMock(UrlInterface::class),
            null
        );
    }

    public function testThrowWhenImageNameNotFound()
    {
        $chooseImage = new ChooseImage($this->image);

        $this->expectException(ImageCannotBeDetermined::class);

        $chooseImage(
            Region::paris1(),
            'Debian',
            'C1'
        );
    }

    public function testThrowWhenCommercialTypeNotFound()
    {
        $chooseImage = new ChooseImage($this->image);

        $this->expectException(ImageCannotBeDetermined::class);

        $chooseImage(
            Region::paris1(),
            'Ubuntu',
            'C2'
        );
    }

    public function testThrowWhenNotInExpectedRegion()
    {
        $chooseImage = new ChooseImage($this->image);

        $this->expectException(ImageCannotBeDetermined::class);

        $chooseImage(
            Region::amsterdam1(),
            'Ubuntu',
            'C1'
        );
    }

    public function testChooseImage()
    {
        $chooseImage = new ChooseImage($this->image);

        $this->assertSame($this->expected, $chooseImage(
            Region::paris1(),
            'Ubuntu',
            'C1'
        ));
    }
}
