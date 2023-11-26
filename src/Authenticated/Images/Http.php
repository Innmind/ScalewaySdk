<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated\Images;

use Innmind\ScalewaySdk\{
    Authenticated\Images,
    Token,
    Image,
    Organization,
    Region,
    Http\Header\AuthToken,
};
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
    Predicate\Instance,
};

final class Http implements Images
{
    private Transport $fulfill;
    private Region $region;
    private Token\Id $token;

    public function __construct(
        Transport $fulfill,
        Region $region,
        Token\Id $token,
    ) {
        $this->fulfill = $fulfill;
        $this->region = $region;
        $this->token = $token;
    }

    public function list(): Set
    {
        $url = Url::of("https://cp-{$this->region->toString()}.scaleway.com/images");
        /** @var list<array{id: string, organization: string, name: string, arch: string, public: bool}> */
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

            /** @var array{images: list<array{id: string, organization: string, name: string, arch: string, public: bool}>} */
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

        /** @var Set<Image> */
        return Set::of(...$images)->map($this->decode(...));
    }

    public function get(Image\Id $id): Image
    {
        $response = ($this->fulfill)(Request::of(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/images/{$id->toString()}"),
            Method::get,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
            ),
        ))->match(
            static fn($success) => $success->response(),
            static fn() => throw new \RuntimeException,
        );

        /** @var array{image: array{id: string, organization: string, name: string, arch: string, public: bool}} */
        $body = Json::decode($response->body()->toString());

        return $this->decode($body['image']);
    }

    /**
     * @param array{id: string, organization: string, name: string, arch: string, public: bool} $image
     */
    private function decode(array $image): Image
    {
        return new Image(
            new Image\Id($image['id']),
            new Organization\Id($image['organization']),
            new Image\Name($image['name']),
            Image\Architecture::of($image['arch']),
            $image['public'],
        );
    }
}
