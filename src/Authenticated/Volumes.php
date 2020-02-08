<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated;

use Innmind\ScalewaySdk\{
    Volume,
    Organization,
};
use Innmind\Immutable\Set;

interface Volumes
{
    public function create(
        Volume\Name $name,
        Organization\Id $organization,
        Volume\Size $size,
        Volume\Type $type
    ): Volume;

    /**
     * @return Set<Volume>
     */
    public function list(): Set;
    public function get(Volume\Id $id): Volume;
    public function remove(Volume\Id $id): void;
}
