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
use Innmind\TimeContinuum\Clock;
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method,
    ProtocolVersion,
    Headers,
    Header\LinkValue,
};
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\Immutable\Set;
use function Innmind\Immutable\first;

final class Http implements Images
{
    private Transport $fulfill;
    private Clock $clock;
    private Token\Id $token;

    public function __construct(
        Transport $fulfill,
        Clock $clock,
        Token\Id $token
    ) {
        $this->fulfill = $fulfill;
        $this->clock = $clock;
        $this->token = $token;
    }

    public function list(): Set
    {
        $url = Url::of('https://api-marketplace.scaleway.com/images');
        /** @var list<array{id: string, name: string, logo: string, categories: list<string>, valid_until: string|null, organization: array{id: string}, current_public_version: string, versions: list<array{id: string, local_images: list<array{id: string, arch: string, zone: string, compatible_commercial_types: list<string>}>}>}> */
        $images = [];

        do {
            $response = ($this->fulfill)(new Request(
                $url,
                Method::get(),
                new ProtocolVersion(2, 0),
                Headers::of(
                    new AuthToken($this->token),
                ),
            ));

            /** @var array{images: list<array{id: string, name: string, logo: string, categories: list<string>, valid_until: string|null, organization: array{id: string}, current_public_version: string, versions: list<array{id: string, local_images: list<array{id: string, arch: string, zone: string, compatible_commercial_types: list<string>}>}>}>} */
            $body = Json::decode($response->body()->toString());
            $images = \array_merge($images, $body['images']);
            $next = null;

            if ($response->headers()->contains('Link')) {
                /**
                 * @psalm-suppress ArgumentTypeCoercion
                 * @var Set<LinkValue>
                 */
                $next = $response
                    ->headers()
                    ->get('Link')
                    ->values()
                    ->filter(static function(LinkValue $link): bool {
                        return $link->relationship() === 'next';
                    });

                if ($next->size() === 1) {
                    $next = $url
                        ->withPath(first($next)->url()->path())
                        ->withQuery(first($next)->url()->query());
                    $url = $next;
                }
            }
        } while ($next instanceof Url);

        /** @var Set<Marketplace\Image> */
        $set = Set::of(Marketplace\Image::class);

        foreach ($images as $image) {
            $set = ($set)($this->decode($image));
        }

        return $set;
    }

    public function get(Marketplace\Image\Id $id): Marketplace\Image
    {
        $response = ($this->fulfill)(new Request(
            Url::of("https://api-marketplace.scaleway.com/images/{$id->toString()}"),
            Method::get(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
            ),
        ));

        /** @var array{image: array{id: string, name: string, logo: string, categories: list<string>, valid_until: string|null, organization: array{id: string}, current_public_version: string, versions: list<array{id: string, local_images: list<array{id: string, arch: string, zone: string, compatible_commercial_types: list<string>}>}>}} */
        $body = Json::decode($response->body()->toString());

        return $this->decode($body['image']);
    }

    /**
     * @param array{id: string, name: string, logo: string, categories: list<string>, valid_until: string|null, organization: array{id: string}, current_public_version: string, versions: list<array{id: string, local_images: list<array{id: string, arch: string, zone: string, compatible_commercial_types: list<string>}>}>} $image
     */
    private function decode(array $image): Marketplace\Image
    {
        /** @var Set<Marketplace\Image\Version> */
        $versions = \array_reduce(
            $image['versions'],
            static function(Set $versions, array $version): Set {
                /** @var array{id: string, local_images: list<array{id: string, arch: string, zone: string, compatible_commercial_types: list<string>}>} $version */
                return ($versions)(new Marketplace\Image\Version(
                    new Marketplace\Image\Version\Id($version['id']),
                    ...\array_map(static function(array $image) {
                        return new Marketplace\Image\Version\LocalImage(
                            new Image\Id($image['id']),
                            Image\Architecture::of($image['arch']),
                            Region::of($image['zone']),
                            ...\array_map(static function(string $name) {
                                return new Marketplace\Product\Server\Name($name);
                            }, $image['compatible_commercial_types']),
                        );
                    }, $version['local_images']),
                ));
            },
            Set::of(Marketplace\Image\Version::class),
        );
        $currentPublicVersion = first($versions->filter(
            static fn($version): bool => $version->id()->toString() === $image['current_public_version'],
        ));

        /** @var Set<Marketplace\Image\Category> */
        $categories = \array_reduce(
            $image['categories'],
            static function(Set $categories, string $category): Set {
                return $categories->add(Marketplace\Image\Category::of($category));
            },
            Set::of(Marketplace\Image\Category::class),
        );

        return new Marketplace\Image(
            new Marketplace\Image\Id($image['id']),
            new Organization\Id($image['organization']['id']),
            $currentPublicVersion,
            $versions,
            new Marketplace\Image\Name($image['name']),
            $categories,
            Url::of($image['logo']),
            \is_string($image['valid_until']) ? $this->clock->at($image['valid_until']) : null,
        );
    }
}
