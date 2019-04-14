<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Marketplace\Image\Version;

use Innmind\ScalewaySdk\{
    Image,
    Region,
    Marketplace\Product\Server\Name,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class LocalImage
{
    private $id;
    private $architecture;
    private $region;
    private $compatibleCommercialTypes;

    public function __construct(
        Image\Id $id,
        Image\Architecture $architecture,
        Region $region,
        Name ...$compatibleCommercialTypes
    ) {
        $this->id = $id;
        $this->architecture = $architecture;
        $this->region = $region;
        $this->compatibleCommercialTypes = Set::of(Name::class, ...$compatibleCommercialTypes);
    }

    public function id(): Image\Id
    {
        return $this->id;
    }

    public function architecture(): Image\Architecture
    {
        return $this->architecture;
    }

    public function region(): Region
    {
        return $this->region;
    }

    /**
     * @return SetInterface<string>
     */
    public function compatibleCommercialTypes(): SetInterface
    {
        return $this->compatibleCommercialTypes;
    }
}
