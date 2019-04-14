<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated\Marketplace\Images;

use Innmind\ScalewaySdk\{
    Authenticated\Marketplace\Images,
    Token,
    Marketplace,
    Image,
    Organization,
    Region,
    Http\Header\AuthToken,
};
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method\Method,
    ProtocolVersion\ProtocolVersion,
    Headers\Headers,
    Header\LinkValue,
};
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Json\Json;
use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class Http implements Images
{
    private $fulfill;
    private $clock;
    private $token;

    public function __construct(
        Transport $fulfill,
        TimeContinuumInterface $clock,
        Token\Id $token
    ) {
        $this->fulfill = $fulfill;
        $this->clock = $clock;
        $this->token = $token;
    }

    /**
     * {@inheritdoc}
     */
    public function list(): SetInterface
    {
        $url = Url::fromString("https://api-marketplace.scaleway.com/images");
        $images = [];

        do {
            $response = ($this->fulfill)(new Request(
                $url,
                Method::get(),
                new ProtocolVersion(2, 0),
                Headers::of(
                    new AuthToken($this->token)
                )
            ));

            $images = \array_merge(
                $images,
                Json::decode((string) $response->body())['images']
            );
            $next = null;

            if ($response->headers()->has('Link')) {
                $next = $response
                    ->headers()
                    ->get('Link')
                    ->values()
                    ->filter(static function(LinkValue $link): bool {
                        return $link->relationship() === 'next';
                    });

                if ($next->size() === 1) {
                    $next = $url
                        ->withPath($next->current()->url()->path())
                        ->withQuery($next->current()->url()->query());
                    $url = $next;
                }
            }
        } while ($next instanceof UrlInterface);

        $set = Set::of(Marketplace\Image::class);

        foreach ($images as $image) {
            $set = $set->add($this->decode($image));
        }

        return $set;
    }

    public function get(Marketplace\Image\Id $id): Marketplace\Image
    {
        $response = ($this->fulfill)(new Request(
            Url::fromString("https://api-marketplace.scaleway.com/images/$id"),
            Method::get(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token)
            )
        ));

        $image = Json::decode((string) $response->body())['image'];

        return $this->decode($image);
    }

    private function decode(array $image): Marketplace\Image
    {
        $versions = \array_reduce(
            $image['versions'],
            static function(SetInterface $versions, array $version): SetInterface {
                return $versions->add(new Marketplace\Image\Version(
                    new Marketplace\Image\Version\Id($version['id']),
                    ...\array_map(static function(array $image) {
                        return new Marketplace\Image\Version\LocalImage(
                            new Image\Id($image['id']),
                            Image\Architecture::of($image['arch']),
                            Region::of($image['zone']),
                            ...$image['compatible_commercial_types']
                        );
                    }, $version['local_images'])
                ));
            },
            Set::of(Marketplace\Image\Version::class)
        );
        $currentPublicVersion = $versions
            ->filter(static function($version) use ($image): bool {
                return (string) $version->id() === $image['current_public_version'];
            })
            ->current();

        return new Marketplace\Image(
            new Marketplace\Image\Id($image['id']),
            new Organization\Id($image['organization']['id']),
            $currentPublicVersion,
            $versions,
            $image['name'],
            \array_reduce(
                $image['categories'],
                static function(SetInterface $categories, string $category): SetInterface {
                    return $categories->add(Marketplace\Image\Category::of($category));
                },
                Set::of(Marketplace\Image\Category::class)
            ),
            Url::fromString($image['logo']),
            \is_string($image['valid_until']) ? $this->clock->at($image['valid_until']) : null
        );
    }
}
