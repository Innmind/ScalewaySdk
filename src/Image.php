<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

final class Image
{
    private $id;
    private $organization;
    private $name;
    private $architecture;
    private $public;

    public function __construct(
        Image\Id $id,
        Organization\Id $organization,
        string $name,
        Image\Architecture $architecture,
        bool $public
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

    public function name(): string
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
