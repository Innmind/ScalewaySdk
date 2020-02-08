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

final class Http implements Volumes
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
        Volume\Name $name,
        Organization\Id $organization,
        Volume\Size $size,
        Volume\Type $type
    ): Volume {
        $response = ($this->fulfill)(new Request(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/volumes"),
            Method::post(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
                ContentType::of('application', 'json'),
            ),
            Stream::ofContent(Json::encode([
                'name' => $name->toString(),
                'organization' => $organization->toString(),
                'size' => $size->toInt(),
                'type' => $type->toString(),
            ])),
        ));

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
            $response = ($this->fulfill)(new Request(
                $url,
                Method::get(),
                new ProtocolVersion(2, 0),
                Headers::of(
                    new AuthToken($this->token),
                ),
            ));

            /** @var array{volumes: list<array{id: string, name: string, organization: string, size: int, volume_type: string, server: ?array{id: string}}>} */
            $body = Json::decode($response->body()->toString());
            $volumes = \array_merge(
                $volumes,
                $body['volumes'],
            );
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

        /** @var Set<Volume> */
        $set = Set::of(Volume::class);

        foreach ($volumes as $volume) {
            $set = ($set)($this->decode($volume));
        }

        return $set;
    }

    public function get(Volume\Id $id): Volume
    {
        $response = ($this->fulfill)(new Request(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/volumes/{$id->toString()}"),
            Method::get(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
            ),
        ));

        /** @var array{volume: array{id: string, name: string, organization: string, size: int, volume_type: string, server: ?array{id: string}}} */
        $body = Json::decode($response->body()->toString());

        return $this->decode($body['volume']);
    }

    public function remove(Volume\Id $id): void
    {
        ($this->fulfill)(new Request(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/volumes/{$id->toString()}"),
            Method::delete(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
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
