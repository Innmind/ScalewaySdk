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
    Headers\Headers,
    Header\Link,
    Header\LinkValue,
};
use Innmind\Url\Url;
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Json\Json;
use Innmind\Immutable\SetInterface;
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
                new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
            )
        );
    }

    public function testCreate()
    {
        $ips = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/ips' &&
                    (string) $request->method() === 'POST' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20' &&
                    (string) $request->body() === Json::encode([
                        'organization' => '000a115d-2852-4b0a-9ce8-47f1134ba95a',
                    ]);
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<JSON
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
        $this->assertSame('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd', (string) $ip->id());
        $this->assertSame('000a115d-2852-4b0a-9ce8-47f1134ba95a', (string) $ip->organization());
        $this->assertSame('::1', (string) $ip->address());
        $this->assertFalse($ip->attachedToAServer());
    }

    public function testAll()
    {
        $ips = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->at(0))
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/ips' &&
                    (string) $request->method() === 'GET' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->any())
            ->method('headers')
            ->willReturn(Headers::of(
                new Link(
                    new LinkValue(Url::fromString('/ips?page=2&per_page=50'), 'next')
                )
            ));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<JSON
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
        $http
            ->expects($this->at(1))
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/ips?page=2&per_page=50' &&
                    (string) $request->method() === 'GET' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<JSON
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

        $ips = $ips->all();

        $this->assertInstanceOf(SetInterface::class, $ips);
        $this->assertSame(IP::class, (string) $ips->type());
        $this->assertCount(2, $ips);
        $this->assertFalse($ips->current()->attachedToAServer());
        $ips->next();
        $this->assertTrue($ips->current()->attachedToAServer());
    }

    public function testGet()
    {
        $ips = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/ips/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd' &&
                    (string) $request->method() === 'GET' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<JSON
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
        $this->assertSame('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd', (string) $ip->id());
        $this->assertSame('000a115d-2852-4b0a-9ce8-47f1134ba95a', (string) $ip->organization());
        $this->assertSame('127.0.0.1', (string) $ip->address());
    }

    public function testRemove()
    {
        $ips = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/ips/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd' &&
                    (string) $request->method() === 'DELETE' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }));

        $this->assertNull($ips->remove(new IP\Id('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd')));
    }

    public function testAttach()
    {
        $ips = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/ips/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd' &&
                    (string) $request->method() === 'PATCH' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20' &&
                    (string) $request->body() === Json::encode([
                        'server' => '49083202-b911-4a4a-b367-6f36c7f1ac4f',
                    ]);
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<JSON
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
            new Server\Id('49083202-b911-4a4a-b367-6f36c7f1ac4f')
        );

        $this->assertInstanceOf(IP::class, $ip);
        $this->assertSame('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd', (string) $ip->id());
        $this->assertSame('000a115d-2852-4b0a-9ce8-47f1134ba95a', (string) $ip->organization());
        $this->assertSame('::1', (string) $ip->address());
        $this->assertTrue($ip->attachedToAServer());
        $this->assertSame('49083202-b911-4a4a-b367-6f36c7f1ac4f', (string) $ip->server());
    }
}
