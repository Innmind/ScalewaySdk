<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Marketplace;

use Innmind\ScalewaySdk\Organization;
use Innmind\TimeContinuum\PointInTimeInterface;
use Innmind\Url\UrlInterface;
use Innmind\Immutable\SetInterface;
use function Innmind\Immutable\assertSet;

final class Image
{
    private $id;
    private $organization;
    private $currentPublicVersion;
    private $versions;
    private $name;
    private $categories;
    private $logo;
    private $expiresAt;

    public function __construct(
        Image\Id $id,
        Organization\Id $organization,
        Image\Version $currentPublicVersion,
        SetInterface $versions,
        string $name,
        SetInterface $categories,
        UrlInterface $logo,
        ?PointInTimeInterface $expiresAt
    ) {
        assertSet(Image\Version::class, $versions, 4);
        assertSet(Image\Category::class, $categories, 6);

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
     * @return SetInterface<Image\Version>
     */
    public function versions(): SetInterface
    {
        return $this->versions;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return SetInterface<Image\Category>
     */
    public function categories(): SetInterface
    {
        return $this->categories;
    }

    public function logo(): UrlInterface
    {
        return $this->logo;
    }

    public function expires(): bool
    {
        return $this->expiresAt instanceof PointInTimeInterface;
    }

    public function expiresAt(): PointInTimeInterface
    {
        return $this->expiresAt;
    }
}
