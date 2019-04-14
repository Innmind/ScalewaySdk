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
    Message\Method\Method,
    ProtocolVersion\ProtocolVersion,
    Headers\Headers,
    Header\ContentType,
    Header\ContentTypeValue,
    Header\LinkValue,
};
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Json\Json;
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class Http implements Servers
{
    private $fulfill;
    private $region;
    private $token;

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
        string ...$tags
    ): Server {
        $response = ($this->fulfill)(new Request(
            Url::fromString("https://cp-{$this->region}.scaleway.com/servers"),
            Method::post(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
                new ContentType(
                    new ContentTypeValue('application', 'json')
                )
            ),
            new StringStream(Json::encode([
                'name' => (string) $name,
                'organization' => (string) $organization,
                'image' => (string) $image,
                'tags' => $tags,
            ]))
        ));

        $server = Json::decode((string) $response->body())['server'];

        return $this->decode($server);
    }

    /**
     * {@inheritdoc}
     */
    public function list(): SetInterface
    {
        $url = Url::fromString("https://cp-{$this->region}.scaleway.com/servers");
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
                Json::decode((string) $response->body())['servers']
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

        $set = Set::of(Server::class);

        foreach ($servers as $server) {
            $set = $set->add($this->decode($server));
        }

        return $set;
    }

    public function get(Server\Id $id): Server
    {
        $response = ($this->fulfill)(new Request(
            Url::fromString("https://cp-{$this->region}.scaleway.com/servers/$id"),
            Method::get(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token)
            )
        ));

        $server = Json::decode((string) $response->body())['server'];

        return $this->decode($server);
    }

    public function remove(Server\Id $id): void
    {
        ($this->fulfill)(new Request(
            Url::fromString("https://cp-{$this->region}.scaleway.com/servers/$id"),
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
            Url::fromString("https://cp-{$this->region}.scaleway.com/servers/$id/action"),
            Method::post(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
                new ContentType(
                    new ContentTypeValue('application', 'json')
                )
            ),
            new StringStream(Json::encode([
                'action' => (string) $action,
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
                static function(SetInterface $allowed, string $action): SetInterface {
                    return $allowed->add(Server\Action::of($action));
                },
                Set::of(Server\Action::class)
            ),
            Set::of('string', ...$server['tags']),
            \array_reduce(
                $server['volumes'],
                static function(SetInterface $volumes, array $volume): SetInterface {
                    return $volumes->add(new Volume\Id($volume['id']));
                },
                Set::of(Volume\Id::class)
            )
        );
    }
}
