<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\{
    Marketplace\Image\Version\LocalImage,
    Exception\ImageCannotBeDetermined,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class ChooseImage
{
    private $images;

    public function __construct(Marketplace\Image ...$images)
    {
        $this->images = Set::of(Marketplace\Image::class, ...$images);
    }

    public function __invoke(
        Region $region,
        Marketplace\Image\Name $image,
        Marketplace\Product\Server\Name $server
    ): Image\Id {
        $ids = $this
            ->images
            ->filter(static function(Marketplace\Image $marketplace) use ($image): bool {
                return (string) $marketplace->name() === (string) $image;
            })
            ->reduce(
                Set::of(LocalImage::class),
                static function(SetInterface $localImages, Marketplace\Image $image): SetInterface {
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
                            return (string) $type === (string) $server;
                        })
                        ->size() > 0;
            });

        if ($ids->size() !== 1) {
            throw new ImageCannotBeDetermined;
        }

        return $ids->current()->id();
    }
}
