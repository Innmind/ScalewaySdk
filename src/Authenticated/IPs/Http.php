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
use Innmind\IP\{
    IPv4,
    IPv6,
    Exception\AddressNotMatchingIPv4Format,
};
use Innmind\Immutable\Set;
use function Innmind\Immutable\first;

final class Http implements IPs
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

    public function create(Organization\Id $organization): IP
    {
        /** @psalm-suppress InvalidArgument */
        $response = ($this->fulfill)(new Request(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/ips"),
            Method::post(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
                ContentType::of('application', 'json'),
            ),
            Stream::ofContent(Json::encode([
                'organization' => $organization->toString(),
            ])),
        ));

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
            $response = ($this->fulfill)(new Request(
                $url,
                Method::get(),
                new ProtocolVersion(2, 0),
                Headers::of(
                    new AuthToken($this->token),
                ),
            ));

            /** @var array{ips: list<array{address: string, id: string, organization: string, server: ?array{id: string}}>} */
            $body = Json::decode($response->body()->toString());
            $ips = \array_merge($ips, $body['ips']);
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

        /** @var Set<IP> */
        $set = Set::of(IP::class);

        foreach ($ips as $ip) {
            $set = ($set)($this->decode($ip));
        }

        return $set;
    }

    public function get(IP\Id $id): IP
    {
        $response = ($this->fulfill)(new Request(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/ips/{$id->toString()}"),
            Method::get(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
            ),
        ));

        /** @var array{ip: array{address: string, id: string, organization: string, server: ?array{id: string}}} */
        $body = Json::decode($response->body()->toString());

        return $this->decode($body['ip']);
    }

    public function remove(IP\Id $id): void
    {
        ($this->fulfill)(new Request(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/ips/{$id->toString()}"),
            Method::delete(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
            ),
        ));
    }

    public function attach(IP\Id $id, Server\Id $server): IP
    {
        /** @psalm-suppress InvalidArgument */
        $response = ($this->fulfill)(new Request(
            Url::of("https://cp-{$this->region->toString()}.scaleway.com/ips/{$id->toString()}"),
            Method::patch(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
                ContentType::of('application', 'json'),
            ),
            Stream::ofContent(Json::encode([
                'server' => $server->toString(),
            ]))
        ));

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
        try {
            $address = IPv4::of($ip['address']);
        } catch (AddressNotMatchingIPv4Format $e) {
            $address = IPv6::of($ip['address']);
        }

        return new IP(
            new IP\Id($ip['id']),
            $address,
            new Organization\Id($ip['organization']),
            \is_array($ip['server']) ? new Server\Id($ip['server']['id']) : null
        );
    }
}
