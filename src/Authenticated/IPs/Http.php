<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated\IPs;

use Innmind\ScalewaySdk\{
    Authenticated\IPs,
    Region,
    Token,
    IP,
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
use Innmind\IP\{
    IPv4,
    IPv6,
};
use Innmind\Immutable\{
    Set,
    Predicate\Instance,
};

final class Http implements IPs
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

    public function create(Organization\Id $organization): IP
    {
        /** @psalm-suppress InvalidArgument */
        $response = ($this->fulfill)(Request::of(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/ips"),
            Method::post,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
                ContentType::of('application', 'json'),
            ),
            Content::ofString(Json::encode([
                'organization' => $organization->toString(),
            ])),
        ))->match(
            static fn($success) => $success->response(),
            static fn() => throw new \RuntimeException,
        );

        /** @var array{ip: array{address: string, id: string, organization: string, server: ?array{id: string}}} */
        $body = Json::decode($response->body()->toString());

        return $this->decode($body['ip']);
    }

    public function list(): Set
    {
        $url = Url::of("https://cp-{$this->region->toString()}.scaleway.com/ips");
        /** @var list<array{address: string, id: string, organization: string, server: ?array{id: string}}> */
        $ips = [];

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

            /** @var array{ips: list<array{address: string, id: string, organization: string, server: ?array{id: string}}>} */
            $body = Json::decode($response->body()->toString());
            $ips = \array_merge($ips, $body['ips']);

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

        /** @var Set<IP> */
        return Set::of(...$ips)->map($this->decode(...));
    }

    public function get(IP\Id $id): IP
    {
        $response = ($this->fulfill)(Request::of(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/ips/{$id->toString()}"),
            Method::get,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
            ),
        ))->match(
            static fn($success) => $success->response(),
            static fn() => throw new \RuntimeException,
        );

        /** @var array{ip: array{address: string, id: string, organization: string, server: ?array{id: string}}} */
        $body = Json::decode($response->body()->toString());

        return $this->decode($body['ip']);
    }

    public function remove(IP\Id $id): void
    {
        ($this->fulfill)(Request::of(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/ips/{$id->toString()}"),
            Method::delete,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
            ),
        ))->match(
            static fn($success) => $success->response(),
            static fn() => throw new \RuntimeException,
        );
    }

    public function attach(IP\Id $id, Server\Id $server): IP
    {
        /** @psalm-suppress InvalidArgument */
        $response = ($this->fulfill)(Request::of(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/ips/{$id->toString()}"),
            Method::patch,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
                ContentType::of('application', 'json'),
            ),
            Content::ofString(Json::encode([
                'server' => $server->toString(),
            ])),
        ))->match(
            static fn($success) => $success->response(),
            static fn() => throw new \RuntimeException,
        );

        /** @var array{ip: array{address: string, id: string, organization: string, server: ?array{id: string}}} */
        $body = Json::decode($response->body()->toString());
        $ip = $body['ip'];

        return $this->decode($ip);
    }

    /**
     * @param array{address: string, id: string, organization: string, server: ?array{id: string}} $ip
     */
    private function decode(array $ip): IP
    {
        $address = IPv4::maybe($ip['address'])
            ->otherwise(static fn() => IPv6::maybe($ip['address']))
            ->match(
                static fn($ip) => $ip,
                static fn() => throw new \RuntimeException,
            );

        return new IP(
            new IP\Id($ip['id']),
            $address,
            new Organization\Id($ip['organization']),
            \is_array($ip['server']) ? new Server\Id($ip['server']['id']) : null,
        );
    }
}
