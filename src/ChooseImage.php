<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\{
    Marketplace\Image\Version\LocalImage,
    Exception\ImageCannotBeDetermined,
};
use Innmind\Immutable\Set;
use function Innmind\Immutable\first;

final class ChooseImage
{
    /** @var Set<Marketplace\Image> */
    private Set $images;

    public function __construct(Marketplace\Image ...$images)
    {
        /** @var Set<Marketplace\Image> */
        $this->images = Set::of(Marketplace\Image::class, ...$images);
    }

    public function __invoke(
        Region $region,
        Marketplace\Image\Name $image,
        Marketplace\Product\Server\Name $server
    ): Image\Id {
        /** @var Set<LocalImage> */
        $ids = $this
            ->images
            ->filter(static function(Marketplace\Image $marketplace) use ($image): bool {
                return $marketplace->name()->toString() === $image->toString();
            })
            ->reduce(
                Set::of(LocalImage::class),
                static function(Set $localImages, Marketplace\Image $image): Set {
                    return $localImages->merge(
                        $image->currentPublicVersion()->localImages()
                    );
                }
            )
            ->filter(static function(LocalImage $localImage) use ($region, $server): bool {
                return $localImage->region() === $region &&
                    $localImage
                        ->compatibleCommercialTypes()
                        ->filter(static function($type) use ($server): bool {
                            return $type->toString() === $server->toString();
                        })
                        ->size() > 0;
            });

        if ($ids->size() !== 1) {
            throw new ImageCannotBeDetermined;
        }

        return first($ids)->id();
    }
}
