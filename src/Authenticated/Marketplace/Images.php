<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated\Marketplace;

use Innmind\ScalewaySdk\Marketplace\Image;
use Innmind\Immutable\Set;

interface Images
{
    /**
     * @return Set<Image>
     */
    public function list(): Set;
    public function get(Image\Id $id): Image;
}
