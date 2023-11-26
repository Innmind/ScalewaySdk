<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated\Volumes;

use Innmind\ScalewaySdk\{
    Authenticated\Volumes,
    Region,
    Token,
    Volume,
    Organization,
    Server,
    Http\Header\AuthToken,
};
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Request,
    Method,
    ProtocolVersion,
    Headers,
    Header\ContentType,
    Header\Link,
    Header\LinkValue,
};
use Innmind\Filesystem\File\Content;
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\Immutable\{
    Set,
    Predicate\Instance,
};

final class Http implements Volumes
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

    public function create(
        Volume\Name $name,
        Organization\Id $organization,
        Volume\Size $size,
        Volume\Type $type,
    ): Volume {
        /** @psalm-suppress InvalidArgument */
        $response = ($this->fulfill)(Request::of(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/volumes"),
            Method::post,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
                ContentType::of('application', 'json'),
            ),
            Content::ofString(Json::encode([
                'name' => $name->toString(),
                'organization' => $organization->toString(),
                'size' => $size->toInt(),
                'type' => $type->toString(),
            ])),
        ))->match(
            static fn($success) => $success->response(),
            static fn() => throw new \RuntimeException,
        );

        /** @var array{volume: array{id: string, name: string, organization: string, size: int, volume_type: string, server: ?array{id: string}}} */
        $body = Json::decode($response->body()->toString());

        return $this->decode($body['volume']);
    }

    public function list(): Set
    {
        $url = Url::of("https://cp-{$this->region->toString()}.scaleway.com/volumes");
        /** @var list<array{id: string, name: string, organization: string, size: int, volume_type: string, server: ?array{id: string}}> */
        $volumes = [];

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

            /** @var array{volumes: list<array{id: string, name: string, organization: string, size: int, volume_type: string, server: ?array{id: string}}>} */
            $body = Json::decode($response->body()->toString());
            $volumes = \array_merge(
                $volumes,
                $body['volumes'],
            );

            $url = $response
                ->headers()
                ->find(Link::class)
                ->flatMap(
                    static fn($header) => $header
                        ->values()
                        ->keep(Instance::of(LinkValue::class))
                        ->find(static fn($link) => $link->relationship() === 'next'),
                )
                ->match(
                    static fn($link) => $url
                        ->withPath($link->url()->path())
                        ->withQuery($link->url()->query()),
                    static fn() => null,
                );
        } while ($url instanceof Url);

        /** @var Set<Volume> */
        return Set::of(...$volumes)->map($this->decode(...));
    }

    public function get(Volume\Id $id): Volume
    {
        $response = ($this->fulfill)(Request::of(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/volumes/{$id->toString()}"),
            Method::get,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
            ),
        ))->match(
            static fn($success) => $success->response(),
            static fn() => throw new \RuntimeException,
        );

        /** @var array{volume: array{id: string, name: string, organization: string, size: int, volume_type: string, server: ?array{id: string}}} */
        $body = Json::decode($response->body()->toString());

        return $this->decode($body['volume']);
    }

    public function remove(Volume\Id $id): void
    {
        ($this->fulfill)(Request::of(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/volumes/{$id->toString()}"),
            Method::delete,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
            ),
        ));
    }

    /**
     * @param array{id: string, name: string, organization: string, size: int, volume_type: string, server: ?array{id: string}} $volume
     */
    private function decode(array $volume): Volume
    {
        return new Volume(
            new Volume\Id($volume['id']),
            new Volume\Name($volume['name']),
            new Organization\Id($volume['organization']),
            Volume\Size::of($volume['size']),
            Volume\Type::of($volume['volume_type']),
            \is_array($volume['server']) ? new Server\Id($volume['server']['id']) : null,
        );
    }
}
