<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated\Servers;

use Innmind\ScalewaySdk\{
    Authenticated\Servers,
    Region,
    Token,
    Server,
    Volume,
    Organization,
    Image,
    IP,
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

final class Http implements Servers
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
        Server\Name $name,
        Organization\Id $organization,
        Image\Id $image,
        IP\Id $ip,
        string ...$tags,
    ): Server {
        $response = ($this->fulfill)(Request::of(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/servers"),
            Method::post,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
                ContentType::of('application', 'json'),
            ),
            Content::ofString(Json::encode([
                'name' => $name->toString(),
                'organization' => $organization->toString(),
                'image' => $image->toString(),
                'tags' => $tags,
                'dynamic_ip_required' => false,
                'enable_ipv6' => true,
                'public_ip' => $ip->toString(),
            ])),
        ))->match(
            static fn($success) => $success->response(),
            static fn() => throw new \RuntimeException,
        );

        /** @var array{server: array{id: string, organization: string, name: string, image: array{id: string}, public_ip: array{id: string}, state: string, allowed_actions?: list<string>, tags: list<string>, volumes: list<array{id: string}>}} */
        $body = Json::decode($response->body()->toString());

        return $this->decode($body['server']);
    }

    public function list(): Set
    {
        $url = Url::of("https://cp-{$this->region->toString()}.scaleway.com/servers");
        /** @var list<array{id: string, organization: string, name: string, image: array{id: string}, public_ip: array{id: string}, state: string, allowed_actions?: list<string>, tags: list<string>, volumes: list<array{id: string}>}> */
        $servers = [];

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

            /** @var array{servers: list<array{id: string, organization: string, name: string, image: array{id: string}, public_ip: array{id: string}, state: string, allowed_actions?: list<string>, tags: list<string>, volumes: list<array{id: string}>}>} */
            $body = Json::decode($response->body()->toString());
            $servers = \array_merge($servers, $body['servers']);

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

        /** @var Set<Server> */
        return Set::of(...$servers)->map($this->decode(...));
    }

    public function get(Server\Id $id): Server
    {
        $response = ($this->fulfill)(Request::of(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/servers/{$id->toString()}"),
            Method::get,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
            ),
        ))->match(
            static fn($success) => $success->response(),
            static fn() => throw new \RuntimeException,
        );

        /** @var array{server: array{id: string, organization: string, name: string, image: array{id: string}, public_ip: array{id: string}, state: string, allowed_actions?: list<string>, tags: list<string>, volumes: list<array{id: string}>}} */
        $body = Json::decode($response->body()->toString());

        return $this->decode($body['server']);
    }

    public function remove(Server\Id $id): void
    {
        ($this->fulfill)(Request::of(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/servers/{$id->toString()}"),
            Method::delete,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
            ),
        ))->match(
            static fn() => null,
            static fn() => throw new \RuntimeException,
        );
    }

    public function execute(Server\Id $id, Server\Action $action): void
    {
        ($this->fulfill)(Request::of(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/servers/{$id->toString()}/action"),
            Method::post,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
                ContentType::of('application', 'json'),
            ),
            Content::ofString(Json::encode([
                'action' => $action->toString(),
            ])),
        ))->match(
            static fn() => null,
            static fn() => throw new \RuntimeException,
        );
    }

    /**
     * @param array{id: string, organization: string, name: string, image: array{id: string}, public_ip: array{id: string}, state: string, allowed_actions?: list<string>, tags: list<string>, volumes: list<array{id: string}>} $server
     */
    private function decode(array $server): Server
    {
        /** @var Set<Server\Action> */
        $actions = \array_reduce(
            $server['allowed_actions'] ?? [],
            static function(Set $allowed, string $action): Set {
                return ($allowed)(Server\Action::of($action));
            },
            Set::of(),
        );
        /** @var Set<Volume\Id> */
        $volumes = \array_reduce(
            $server['volumes'],
            static function(Set $volumes, array $volume): Set {
                /** @var array{id: string} $volume */
                return ($volumes)(new Volume\Id($volume['id']));
            },
            Set::of(),
        );

        return new Server(
            new Server\Id($server['id']),
            new Organization\Id($server['organization']),
            new Server\Name($server['name']),
            new Image\Id($server['image']['id']),
            new IP\Id($server['public_ip']['id']),
            Server\State::of($server['state']),
            $actions,
            Set::strings(...$server['tags']),
            $volumes,
        );
    }
}
