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
use Innmind\IP\{
    IPv4,
    IPv6,
    Exception\AddressNotMatchingIPv4Format,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class Http implements IPs
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

    public function create(Organization\Id $organization): IP
    {
        $response = ($this->fulfill)(new Request(
            Url::fromString("https://cp-{$this->region}.scaleway.com/ips"),
            Method::post(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
                new ContentType(
                    new ContentTypeValue('application', 'json')
                )
            ),
            new StringStream(Json::encode([
                'organization' => (string) $organization,
            ]))
        ));

        $ip = Json::decode((string) $response->body())['ip'];

        return $this->decode($ip);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): SetInterface
    {
        $url = Url::fromString("https://cp-{$this->region}.scaleway.com/ips");
        $ips = [];

        do {
            $response = ($this->fulfill)(new Request(
                $url,
                Method::get(),
                new ProtocolVersion(2, 0),
                Headers::of(
                    new AuthToken($this->token)
                )
            ));

            $ips = \array_merge(
                $ips,
                Json::decode((string) $response->body())['ips']
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

        $set = Set::of(IP::class);

        foreach ($ips as $ip) {
            $set = $set->add($this->decode($ip));
        }

        return $set;
    }

    public function get(IP\Id $id): IP
    {
        $response = ($this->fulfill)(new Request(
            Url::fromString("https://cp-{$this->region}.scaleway.com/ips/$id"),
            Method::get(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token)
            )
        ));

        $ip = Json::decode((string) $response->body())['ip'];

        return $this->decode($ip);
    }

    public function remove(IP\Id $id): void
    {
        ($this->fulfill)(new Request(
            Url::fromString("https://cp-{$this->region}.scaleway.com/ips/$id"),
            Method::delete(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token)
            )
        ));
    }

    public function attach(IP\Id $id, Server\Id $server): IP
    {
        $response = ($this->fulfill)(new Request(
            Url::fromString("https://cp-{$this->region}.scaleway.com/ips/$id"),
            Method::patch(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
                new ContentType(
                    new ContentTypeValue('application', 'json')
                )
            ),
            new StringStream(Json::encode([
                'server' => (string) $server,
            ]))
        ));

        $ip = Json::decode((string) $response->body())['ip'];

        return $this->decode($ip);
    }

    private function decode(array $ip): IP
    {
        try {
            $address = new IPv4($ip['address']);
        } catch (AddressNotMatchingIPv4Format $e) {
            $address = new IPv6($ip['address']);
        }

        return new IP(
            new IP\Id($ip['id']),
            $address,
            new Organization\Id($ip['organization']),
            \is_array($ip['server']) ? new Server\Id($ip['server']['id']) : null
        );
    }
}
