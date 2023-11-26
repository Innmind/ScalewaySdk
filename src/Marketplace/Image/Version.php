<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Marketplace\Image;

use Innmind\Immutable\Set;

final class Version
{
    private Version\Id $id;
    /** @var Set<Version\LocalImage> */
    private Set $localImages;

    /**
     * @no-named-arguments
     */
    public function __construct(Version\Id $id, Version\LocalImage ...$localImages)
    {
        $this->id = $id;
        $this->localImages = Set::of(...$localImages);
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
