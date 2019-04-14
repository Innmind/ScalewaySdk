<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated\Marketplace;

use Innmind\ScalewaySdk\Marketplace\Image;
use Innmind\Immutable\SetInterface;

interface Images
{
    /**
     * @return SetInterface<Image>
     */
    public function list(): SetInterface;
    public function get(Image\Id $id): Image;
}
