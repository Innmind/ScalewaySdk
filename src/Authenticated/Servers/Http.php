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
            ]))
        ));

        $server = Json::decode($response->body()->toString())['server'];

        return $this->decode($server);
    }

    /**
     * {@inheritdoc}
     */
    public function list(): Set
    {
        $url = Url::of("https://cp-{$this->region->toString()}.scaleway.com/servers");
        $servers = [];

        do {
            $response = ($this->fulfill)(new Request(
                $url,
                Method::get(),
                new ProtocolVersion(2, 0),
                Headers::of(
                    new AuthToken($this->token)
                )
            ));

            $servers = \array_merge(
                $servers,
                Json::decode($response->body()->toString())['servers']
            );
            $next = null;

            if ($response->headers()->contains('Link')) {
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
                new AuthToken($this->token)
            )
        ));

        $server = Json::decode($response->body()->toString())['server'];

        return $this->decode($server);
    }

    public function remove(Server\Id $id): void
    {
        ($this->fulfill)(new Request(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/servers/{$id->toString()}"),
            Method::delete(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token)
            )
        ));
    }

    public function execute(Server\Id $id, Server\Action $action): void
    {
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
            ]))
        ));
    }

    private function decode(array $server): Server
    {
        return new Server(
            new Server\Id($server['id']),
            new Organization\Id($server['organization']),
            new Server\Name($server['name']),
            new Image\Id($server['image']['id']),
            new IP\Id($server['public_ip']['id']),
            Server\State::of($server['state']),
            \array_reduce(
                $server['allowed_actions'] ?? [],
                static function(Set $allowed, string $action): Set {
                    return ($allowed)(Server\Action::of($action));
                },
                Set::of(Server\Action::class)
            ),
            Set::of('string', ...$server['tags']),
            \array_reduce(
                $server['volumes'],
                static function(Set $volumes, array $volume): Set {
                    return ($volumes)(new Volume\Id($volume['id']));
                },
                Set::of(Volume\Id::class)
            )
        );
    }
}
