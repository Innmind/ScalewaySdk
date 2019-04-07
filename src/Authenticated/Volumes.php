<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated;

use Innmind\ScalewaySdk\{
    Volume,
    Organization,
};

interface Volumes
{
    public function create(
        string $name,
        Organization\Id $organization,
        Volume\Size $size,
        Volume\Type $type
    ): Volume;
}
