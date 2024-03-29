<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

final class Image
{
    private Image\Id $id;
    private Organization\Id $organization;
    private Image\Name $name;
    private Image\Architecture $architecture;
    private bool $public;

    public function __construct(
        Image\Id $id,
        Organization\Id $organization,
        Image\Name $name,
        Image\Architecture $architecture,
        bool $public,
    ) {
        $this->id = $id;
        $this->organization = $organization;
        $this->name = $name;
        $this->architecture = $architecture;
        $this->public = $public;
    }

    public function id(): Image\Id
    {
        return $this->id;
    }

    public function organization(): Organization\Id
    {
        return $this->organization;
    }

    public function name(): Image\Name
    {
        return $this->name;
    }

    public function architecture(): Image\Architecture
    {
        return $this->architecture;
    }

    public function public(): bool
    {
        return $this->public;
    }
}
