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
    Message\Request\Request,
    Message\Method,
    ProtocolVersion,
    Headers,
    Header\ContentType,
    Header\LinkValue,
};
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Set;
use function Innmind\Immutable\first;

final class Http implements Servers
{
    private Transport $fulfill;
    private Region $region;
    private Token\Id $token;

    public function __construct(
        Transport $fulfill,
        Region $region,
        Token\Id $token
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
        string ...$tags
    ): Server {
        /** @psalm-suppress InvalidArgument */
        $response = ($this->fulfill)(new Request(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/servers"),
            Method::post(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
                ContentType::of('application', 'json'),
            ),
            Stream::ofContent(Json::encode([
                'name' => $name->toString(),
                'organization' => $organization->toString(),
                'image' => $image->toString(),
                'tags' => $tags,
                'dynamic_ip_required' => false,
                'enable_ipv6' => true,
                'public_ip' => $ip->toString(),
            ])),
        ));

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
            $response = ($this->fulfill)(new Request(
                $url,
                Method::get(),
                new ProtocolVersion(2, 0),
                Headers::of(
                    new AuthToken($this->token),
                ),
            ));

            /** @var array{servers: list<array{id: string, organization: string, name: string, image: array{id: string}, public_ip: array{id: string}, state: string, allowed_actions?: list<string>, tags: list<string>, volumes: list<array{id: string}>}>} */
            $body = Json::decode($response->body()->toString());
            $servers = \array_merge($servers, $body['servers']);
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

        /** @var Set<Server> */
        $set = Set::of(Server::class);

        foreach ($servers as $server) {
            $set = ($set)($this->decode($server));
        }

        return $set;
    }

    public function get(Server\Id $id): Server
    {
        $response = ($this->fulfill)(new Request(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/servers/{$id->toString()}"),
            Method::get(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
            ),
        ));

        /** @var array{server: array{id: string, organization: string, name: string, image: array{id: string}, public_ip: array{id: string}, state: string, allowed_actions?: list<string>, tags: list<string>, volumes: list<array{id: string}>}} */
        $body = Json::decode($response->body()->toString());

        return $this->decode($body['server']);
    }

    public function remove(Server\Id $id): void
    {
        ($this->fulfill)(new Request(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/servers/{$id->toString()}"),
            Method::delete(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
            ),
        ));
    }

    public function execute(Server\Id $id, Server\Action $action): void
    {
        /** @psalm-suppress InvalidArgument */
        ($this->fulfill)(new Request(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/servers/{$id->toString()}/action"),
            Method::post(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
                ContentType::of('application', 'json'),
            ),
            Stream::ofContent(Json::encode([
                'action' => $action->toString(),
            ])),
        ));
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
            Set::of(Server\Action::class),
        );
        /** @var Set<Volume\Id> */
        $volumes = \array_reduce(
            $server['volumes'],
            static function(Set $volumes, array $volume): Set {
                /** @var array{id: string} $volume */
                return ($volumes)(new Volume\Id($volume['id']));
            },
            Set::of(Volume\Id::class),
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
