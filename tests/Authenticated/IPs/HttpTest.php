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
use Innmind\HttpTransport\{
    Transport,
    Success,
};
use Innmind\Http\{
    Response,
    Response\StatusCode,
    Headers,
    Header\Link,
    Header\LinkValue,
};
use Innmind\Filesystem\File\Content;
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\Immutable\{
    Set,
    Either,
};
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
            ->willReturnCallback(function($request) {
                $this->assertSame(
                    'https://cp-par1.scaleway.com/ips',
                    $request->url()->toString(),
                );
                $this->assertSame(
                    'POST',
                    $request->method()->toString(),
                );
                $this->assertSame(
                    'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20',
                    $request->headers()->get('x-auth-token')->match(
                        static fn($header) => $header->toString(),
                        static fn() => null,
                    ),
                );
                $this->assertSame(
                    Json::encode([
                        'organization' => '000a115d-2852-4b0a-9ce8-47f1134ba95a',
                    ]),
                    $request->body()->toString(),
                );

                return Either::right(new Success($request, Response::of(
                    StatusCode::ok,
                    $request->protocolVersion(),
                    null,
                    Content::ofString(<<<JSON
                    {
                        "ip": {
                            "id": "c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd",
                            "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
                            "server": null,
                            "address": "::1"
                        }
                    }
                    JSON),
                )));
            });

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
            ->expects($matcher = $this->exactly(2))
            ->method('__invoke')
            ->willReturnCallback(function($request) use ($matcher) {
                $this->assertSame(
                    'GET',
                    $request->method()->toString(),
                );
                $this->assertSame(
                    'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20',
                    $request->headers()->get('x-auth-token')->match(
                        static fn($header) => $header->toString(),
                        static fn() => null,
                    ),
                );

                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertSame(
                        'https://cp-par1.scaleway.com/ips',
                        $request->url()->toString(),
                    ),
                    2 => $this->assertSame(
                        'https://cp-par1.scaleway.com/ips?page=2&per_page=50',
                        $request->url()->toString(),
                    ),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => Either::right(new Success($request, Response::of(
                        StatusCode::ok,
                        $request->protocolVersion(),
                        Headers::of(new Link(
                            new LinkValue(Url::of('/ips?page=2&per_page=50'), 'next'),
                        )),
                        Content::ofString(<<<JSON
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
                        JSON),
                    ))),
                    2 => Either::right(new Success($request, Response::of(
                        StatusCode::ok,
                        $request->protocolVersion(),
                        null,
                        Content::ofString(<<<JSON
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
                        JSON),
                    ))),
                };
            });

        $ips = $ips->list();

        $this->assertInstanceOf(Set::class, $ips);
        $this->assertCount(2, $ips);
        $ips = $ips->toList();
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
            ->willReturnCallback(function($request) {
                $this->assertSame(
                    'https://cp-par1.scaleway.com/ips/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd',
                    $request->url()->toString(),
                );
                $this->assertSame(
                    'GET',
                    $request->method()->toString(),
                );
                $this->assertSame(
                    'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20',
                    $request->headers()->get('x-auth-token')->match(
                        static fn($header) => $header->toString(),
                        static fn() => null,
                    ),
                );

                return Either::right(new Success($request, Response::of(
                    StatusCode::ok,
                    $request->protocolVersion(),
                    null,
                    Content::ofString(<<<JSON
                    {
                        "ip": {
                            "id": "c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd",
                            "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
                            "server": null,
                            "size": 10000000000,
                            "address": "127.0.0.1"
                        }
                    }
                    JSON),
                )));
            });

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
            ->willReturnCallback(function($request) {
                $this->assertSame(
                    'https://cp-par1.scaleway.com/ips/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd',
                    $request->url()->toString(),
                );
                $this->assertSame(
                    'DELETE',
                    $request->method()->toString(),
                );
                $this->assertSame(
                    'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20',
                    $request->headers()->get('x-auth-token')->match(
                        static fn($header) => $header->toString(),
                        static fn() => null,
                    ),
                );

                return Either::right(new Success($request, Response::of(
                    StatusCode::ok,
                    $request->protocolVersion(),
                )));
            });

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
            ->willReturnCallback(function($request) {
                $this->assertSame(
                    'https://cp-par1.scaleway.com/ips/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd',
                    $request->url()->toString(),
                );
                $this->assertSame(
                    'PATCH',
                    $request->method()->toString(),
                );
                $this->assertSame(
                    'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20',
                    $request->headers()->get('x-auth-token')->match(
                        static fn($header) => $header->toString(),
                        static fn() => null,
                    ),
                );
                $this->assertSame(
                    Json::encode([
                        'server' => '49083202-b911-4a4a-b367-6f36c7f1ac4f',
                    ]),
                    $request->body()->toString(),
                );

                return Either::right(new Success($request, Response::of(
                    StatusCode::ok,
                    $request->protocolVersion(),
                    null,
                    Content::ofString(<<<JSON
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
                    JSON),
                )));
            });

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
