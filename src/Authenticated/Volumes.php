<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated;

use Innmind\ScalewaySdk\{
    Volume,
    Organization,
};
use Innmind\Immutable\SetInterface;

interface Volumes
{
    public function create(
        string $name,
        Organization\Id $organization,
        Volume\Size $size,
        Volume\Type $type
    ): Volume;

    /**
     * @return SetInterface<Volume>
     */
    public function all(): SetInterface;
    public function get(Volume\Id $id): Volume;
    public function delete(Volume\Id $id): void;
}
