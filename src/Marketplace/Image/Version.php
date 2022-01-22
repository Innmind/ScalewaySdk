<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Marketplace\Image;

use Innmind\Immutable\Set;

final class Version
{
    private Version\Id $id;
    /** @var Set<Version\LocalImage> */
    private Set $localImages;

    public function __construct(Version\Id $id, Version\LocalImage ...$localImages)
    {
        $this->id = $id;
        $this->localImages = Set::of(Version\LocalImage::class, ...$localImages);
    }

    public function id(): Version\Id
    {
        return $this->id;
    }

    /**
     * @return Set<Version\LocalImage>
     */
    public function localImages(): Set
    {
        return $this->localImages;
    }
}
