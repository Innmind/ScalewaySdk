<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Marketplace\Image;

use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class Version
{
    private $id;
    private $localImages;

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
     * @return SetInterface<Version\LocalImage>
     */
    public function localImages(): SetInterface
    {
        return $this->localImages;
    }
}
