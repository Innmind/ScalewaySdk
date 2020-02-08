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
            Url::of("https://cp-{$this->region}.scaleway.com/volumes"),
            Method::post(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
                ContentType::of('application', 'json'),
            ),
            Stream::ofContent(Json::encode([
                'name' => (string) $name,
                'organization' => (string) $organization,
                'size' => $size->toInt(),
                'type' => (string) $type,
            ]))
        ));

        $volume = Json::decode($response->body()->toString())['volume'];

        return $this->decode($volume);
    }

    /**
     * {@inheritdoc}
     */
    public function list(): Set
    {
        $url = Url::of("https://cp-{$this->region}.scaleway.com/volumes");
        $volumes = [];

        do {
            $response = ($this->fulfill)(new Request(
                $url,
                Method::get(),
                new ProtocolVersion(2, 0),
                Headers::of(
                    new AuthToken($this->token)
                )
            ));

            $volumes = \array_merge(
                $volumes,
                Json::decode($response->body()->toString())['volumes']
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

        $set = Set::of(Volume::class);

        foreach ($volumes as $volume) {
            $set = ($set)($this->decode($volume));
        }

        return $set;
    }

    public function get(Volume\Id $id): Volume
    {
        $response = ($this->fulfill)(new Request(
            Url::of("https://cp-{$this->region}.scaleway.com/volumes/$id"),
            Method::get(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token)
            )
        ));

        $volume = Json::decode($response->body()->toString())['volume'];

        return $this->decode($volume);
    }

    public function remove(Volume\Id $id): void
    {
        ($this->fulfill)(new Request(
            Url::of("https://cp-{$this->region}.scaleway.com/volumes/$id"),
            Method::delete(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token)
            )
        ));
    }

    private function decode(array $volume): Volume
    {
        return new Volume(
            new Volume\Id($volume['id']),
            new Volume\Name($volume['name']),
            new Organization\Id($volume['organization']),
            Volume\Size::of($volume['size']),
            Volume\Type::of($volume['volume_type']),
            \is_array($volume['server']) ? new Server\Id($volume['server']['id']) : null
        );
    }
}
