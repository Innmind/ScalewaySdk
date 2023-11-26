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
    Request,
    Method,
    ProtocolVersion,
    Headers,
    Header\Link,
    Header\LinkValue,
};
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\Immutable\{
    Set,
    Maybe,
    Predicate\Instance,
};

final class Http implements Images
{
    private Transport $fulfill;
    private Clock $clock;
    private Token\Id $token;

    public function __construct(
        Transport $fulfill,
        Clock $clock,
        Token\Id $token,
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
            $response = ($this->fulfill)(Request::of(
                $url,
                Method::get,
                ProtocolVersion::v20,
                Headers::of(
                    AuthToken::of($this->token),
                ),
            ))->match(
                static fn($success) => $success->response(),
                static fn() => throw new \RuntimeException,
            );

            /** @var array{images: list<array{id: string, name: string, logo: string, categories: list<string>, valid_until: string|null, organization: array{id: string}, current_public_version: string, versions: list<array{id: string, local_images: list<array{id: string, arch: string, zone: string, compatible_commercial_types: list<string>}>}>}>} */
            $body = Json::decode($response->body()->toString());
            $images = \array_merge($images, $body['images']);

            $url = $response
                ->headers()
                ->find(Link::class)
                ->flatMap(
                    static fn($header) => $header
                        ->values()
                        ->keep(Instance::of(LinkValue::class))
                        ->find(static fn($link) => $link->relationship() === 'next'),
                )
                ->map(
                    static fn($link) => $url
                        ->withPath($link->url()->path())
                        ->withQuery($link->url()->query()),
                )
                ->match(
                    static fn($next) => $next,
                    static fn() => null,
                );
        } while ($url instanceof Url);

        /** @var Set<Marketplace\Image> */
        return Set::of(...$images)->map($this->decode(...));
    }

    public function get(Marketplace\Image\Id $id): Marketplace\Image
    {
        $response = ($this->fulfill)(Request::of(
            Url::of("https://api-marketplace.scaleway.com/images/{$id->toString()}"),
            Method::get,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
            ),
        ))->match(
            static fn($success) => $success->response(),
            static fn() => throw new \RuntimeException,
        );

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
            Set::of(),
        );
        $currentPublicVersion = $versions
            ->filter(
                static fn($version): bool => $version->id()->toString() === $image['current_public_version'],
            )
            ->match(
                static fn($version) => $version,
                static fn() => throw new \RuntimeException,
            );

        /** @var Set<Marketplace\Image\Category> */
        $categories = \array_reduce(
            $image['categories'],
            static function(Set $categories, string $category): Set {
                return $categories->add(Marketplace\Image\Category::of($category));
            },
            Set::of(),
        );

        return new Marketplace\Image(
            new Marketplace\Image\Id($image['id']),
            new Organization\Id($image['organization']['id']),
            $currentPublicVersion,
            $versions,
            new Marketplace\Image\Name($image['name']),
            $categories,
            Url::of($image['logo']),
            Maybe::of($image['valid_until'])
                ->flatMap($this->clock->at(...))
                ->match(
                    static fn($point) => $point,
                    static fn() => null,
                ),
        );
    }
}
