<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\{
    Marketplace\Image\Version\LocalImage,
    Exception\ImageCannotBeDetermined,
};
use Innmind\Immutable\Set;

final class ChooseImage
{
    /** @var Set<Marketplace\Image> */
    private Set $images;

    /**
     * @no-named-arguments
     */
    public function __construct(Marketplace\Image ...$images)
    {
        $this->images = Set::of(...$images);
    }

    public function __invoke(
        Region $region,
        Marketplace\Image\Name $image,
        Marketplace\Product\Server\Name $server,
    ): Image\Id {
        return $this
            ->images
            ->filter(static function(Marketplace\Image $marketplace) use ($image): bool {
                return $marketplace->name()->toString() === $image->toString();
            })
            ->flatMap(
                static fn($image) => $image
                    ->currentPublicVersion()
                    ->localImages(),
            )
            ->filter(static function(LocalImage $localImage) use ($region, $server): bool {
                return $localImage->region() === $region &&
                    !$localImage
                        ->compatibleCommercialTypes()
                        ->filter(static function($type) use ($server): bool {
                            return $type->toString() === $server->toString();
                        })
                        ->empty();
            })
            ->match(
                static fn($image) => $image->id(),
                static fn() => throw new ImageCannotBeDetermined,
            );
    }
}
