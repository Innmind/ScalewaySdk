<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Marketplace;

use Innmind\ScalewaySdk\Organization;
use Innmind\TimeContinuum\PointInTime;
use Innmind\Url\Url;
use Innmind\Immutable\Set;

final class Image
{
    private Image\Id $id;
    private Organization\Id $organization;
    private Image\Version $currentPublicVersion;
    /** @var Set<Image\Version> */
    private Set $versions;
    private Image\Name $name;
    /** @var Set<Image\Category> */
    private Set $categories;
    private Url $logo;
    private ?PointInTime $expiresAt;

    /**
     * @param Set<Image\Version> $versions
     * @param Set<Image\Category> $categories
     */
    public function __construct(
        Image\Id $id,
        Organization\Id $organization,
        Image\Version $currentPublicVersion,
        Set $versions,
        Image\Name $name,
        Set $categories,
        Url $logo,
        ?PointInTime $expiresAt,
    ) {
        $this->id = $id;
        $this->organization = $organization;
        $this->currentPublicVersion = $currentPublicVersion;
        $this->versions = $versions;
        $this->name = $name;
        $this->categories = $categories;
        $this->logo = $logo;
        $this->expiresAt = $expiresAt;
    }

    public function id(): Image\Id
    {
        return $this->id;
    }

    public function organization(): Organization\Id
    {
        return $this->organization;
    }

    public function currentPublicVersion(): Image\Version
    {
        return $this->currentPublicVersion;
    }

    /**
     * @return Set<Image\Version>
     */
    public function versions(): Set
    {
        return $this->versions;
    }

    public function name(): Image\Name
    {
        return $this->name;
    }

    /**
     * @return Set<Image\Category>
     */
    public function categories(): Set
    {
        return $this->categories;
    }

    public function logo(): Url
    {
        return $this->logo;
    }

    public function expires(): bool
    {
        return $this->expiresAt instanceof PointInTime;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function expiresAt(): PointInTime
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->expiresAt;
    }
}
