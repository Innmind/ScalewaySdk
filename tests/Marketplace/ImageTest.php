<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Marketplace;

use Innmind\ScalewaySdk\{
    Marketplace\Image,
    Organization,
};
use Innmind\TimeContinuum\PointInTime;
use Innmind\Url\Url;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    public function testInterface()
    {
        $image = new Image(
            $id = new Image\Id('e4ae6a9e-88a1-4a44-a418-263f03b197fa'),
            $organization = new Organization\Id('ac5be507-17d8-453e-8974-071bcfd09fc6'),
            $currentPublicVersion = new Image\Version(
                new Image\Version\Id('d24fae12-9b64-4241-b614-03fb643130ef')
            ),
            $versions = Set::of(Image\Version::class, $currentPublicVersion),
            $name = new Image\Name('foo'),
            $categories = Set::of(Image\Category::class),
            $logo = Url::of('http://example.com'),
            $expiresAt = $this->createMock(PointInTime::class)
        );

        $this->assertSame($id, $image->id());
        $this->assertSame($organization, $image->organization());
        $this->assertSame($currentPublicVersion, $image->currentPublicVersion());
        $this->assertSame($versions, $image->versions());
        $this->assertSame($name, $image->name());
        $this->assertSame($categories, $image->categories());
        $this->assertSame($logo, $image->logo());
        $this->assertTrue($image->expires());
        $this->assertSame($expiresAt, $image->expiresAt());
    }
}
