<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Authenticated\IPs;

use Innmind\ScalewaySdk\{
    Authenticated\IPs\Http,
    Authenticated\IPs,
    Region,
    Token,
    IP,
    Organization,
    Server,
};
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Response,
    Headers,
    Header\Link,
    Header\LinkValue,
};
use Innmind\Url\Url;
use Innmind\Stream\Readable\Stream;
use Innmind\Json\Json;
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            IPs::class,
            new Http(
                $this->createMock(Transport::class),
                Region::paris1(),
                new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
            ),
        );
    }

    public function testCreate()
    {
        $ips = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return $request->url()->toString() === 'https://cp-par1.scaleway.com/ips' &&
                    $request->method()->toString() === 'POST' &&
                    $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20' &&
                    $request->body()->toString() === Json::encode([
                        'organization' => '000a115d-2852-4b0a-9ce8-47f1134ba95a',
                    ]);
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
{
    "ip": {
        "id": "c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd",
        "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
        "server": null,
        "address": "::1"
    }
}
JSON
            ));

        $ip = $ips->create(new Organization\Id('000a115d-2852-4b0a-9ce8-47f1134ba95a'));

        $this->assertInstanceOf(IP::class, $ip);
        $this->assertSame('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd', $ip->id()->toString());
        $this->assertSame('000a115d-2852-4b0a-9ce8-47f1134ba95a', $ip->organization()->toString());
        $this->assertSame('::1', $ip->address()->toString());
        $this->assertFalse($ip->attachedToAServer());
    }

    public function testList()
    {
        $ips = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive(
                [$this->callback(static function($request): bool {
                    return $request->url()->toString() === 'https://cp-par1.scaleway.com/ips' &&
                        $request->method()->toString() === 'GET' &&
                        $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
                })],
                [$this->callback(static function($request): bool {
                    return $request->url()->toString() === 'https://cp-par1.scaleway.com/ips?page=2&per_page=50' &&
                        $request->method()->toString() === 'GET' &&
                        $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
                })],
            )
            ->will($this->onConsecutiveCalls(
                $response1 = $this->createMock(Response::class),
                $response2 = $this->createMock(Response::class),
            ));
        $response1
            ->expects($this->any())
            ->method('headers')
            ->willReturn(Headers::of(
                new Link(
                    new LinkValue(Url::of('/ips?page=2&per_page=50'), 'next'),
                ),
            ));
        $response1
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
{
    "ips": [
        {
            "id": "f929fe39-63f8-4be8-a80e-1e9c8ae22a76",
            "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
            "server": null,
            "address": "::1"
        }
    ]
}
JSON
            ));
        $response2
            ->expects($this->once())
            ->method('headers')
            ->willReturn(Headers::of());
        $response2
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
{
    "ips": [
        {
            "id": "0facb6b5-b117-441a-81c1-f28b1d723779",
            "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
            "server": {
                "id": "a61434eb-5f70-42d8-9915-45e8aa3201c7",
                "name": "foobar"
            },
            "address": "::2"
        }
    ]
}
JSON
            ));

        $ips = $ips->list();

        $this->assertInstanceOf(Set::class, $ips);
        $this->assertSame(IP::class, (string) $ips->type());
        $this->assertCount(2, $ips);
        $ips = unwrap($ips);
        $this->assertFalse(\current($ips)->attachedToAServer());
        \next($ips);
        $this->assertTrue(\current($ips)->attachedToAServer());
    }

    public function testGet()
    {
        $ips = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return $request->url()->toString() === 'https://cp-par1.scaleway.com/ips/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
{
    "ip": {
        "id": "c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd",
        "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
        "server": null,
        "size": 10000000000,
        "address": "127.0.0.1"
    }
}
JSON
            ));

        $ip = $ips->get(new IP\Id('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd'));

        $this->assertInstanceOf(IP::class, $ip);
        $this->assertSame('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd', $ip->id()->toString());
        $this->assertSame('000a115d-2852-4b0a-9ce8-47f1134ba95a', $ip->organization()->toString());
        $this->assertSame('127.0.0.1', $ip->address()->toString());
    }

    public function testRemove()
    {
        $ips = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return $request->url()->toString() === 'https://cp-par1.scaleway.com/ips/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd' &&
                    $request->method()->toString() === 'DELETE' &&
                    $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }));

        $this->assertNull($ips->remove(new IP\Id('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd')));
    }

    public function testAttach()
    {
        $ips = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return $request->url()->toString() === 'https://cp-par1.scaleway.com/ips/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd' &&
                    $request->method()->toString() === 'PATCH' &&
                    $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20' &&
                    $request->body()->toString() === Json::encode([
                        'server' => '49083202-b911-4a4a-b367-6f36c7f1ac4f',
                    ]);
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
{
    "ip": {
        "id": "c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd",
        "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
        "server": {
            "id": "49083202-b911-4a4a-b367-6f36c7f1ac4f",
            "name": "watev"
        },
        "address": "::1"
    }
}
JSON
            ));

        $ip = $ips->attach(
            new IP\Id('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd'),
            new Server\Id('49083202-b911-4a4a-b367-6f36c7f1ac4f'),
        );

        $this->assertInstanceOf(IP::class, $ip);
        $this->assertSame('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd', $ip->id()->toString());
        $this->assertSame('000a115d-2852-4b0a-9ce8-47f1134ba95a', $ip->organization()->toString());
        $this->assertSame('::1', $ip->address()->toString());
        $this->assertTrue($ip->attachedToAServer());
        $this->assertSame('49083202-b911-4a4a-b367-6f36c7f1ac4f', $ip->server()->toString());
    }
}
