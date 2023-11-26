<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Authenticated\Servers;

use Innmind\ScalewaySdk\{
    Authenticated\Servers\Http,
    Authenticated\Servers,
    Region,
    Token,
    Server,
    Organization,
    Image,
    IP,
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
            Servers::class,
            new Http(
                $this->createMock(Transport::class),
                Region::paris1(),
                new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
            ),
        );
    }

    public function testCreate()
    {
        $servers = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->willReturnCallback(function($request) {
                $this->assertSame(
                    'https://cp-par1.scaleway.com/servers',
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
                        'name' => 'foobar',
                        'organization' => '000a115d-2852-4b0a-9ce8-47f1134ba95a',
                        'image' => '9956c6a6-607c-4d42-92bc-5f51f7087ae4',
                        'tags' => ['foo', 'bar'],
                        'dynamic_ip_required' => false,
                        'enable_ipv6' => true,
                        'public_ip' => '95be217a-c32f-41c1-b62d-97827adfc9e5',
                    ]),
                    $request->body()->toString(),
                );

                return Either::right(new Success($request, Response::of(
                    StatusCode::ok,
                    $request->protocolVersion(),
                    null,
                    Content::ofString(<<<JSON
                    {
                        "server": {
                            "bootscript": null,
                            "dynamic_ip_required": true,
                            "id": "3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c",
                            "image": {
                                "id": "9956c6a6-607c-4d42-92bc-5f51f7087ae4",
                                "name": "ubuntu working"
                            },
                            "name": "foobar",
                            "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
                            "private_ip": null,
                            "public_ip": {
                                "id": "95be217a-c32f-41c1-b62d-97827adfc9e5"
                            },
                            "enable_ipv6": true,
                            "state": "stopped",
                            "ipv6": null,
                            "commercial_type": "START1-S",
                            "arch": "x86_64",
                            "boot_type": "local",
                            "tags": [
                                "foo",
                                "bar"
                            ],
                            "volumes": {
                                "0": {
                                    "export_uri": null,
                                    "id": "d9257116-6919-49b4-a420-dcfdff51fcb1",
                                    "name": "vol simple snapshot",
                                    "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
                                    "server": {
                                        "id": "3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c",
                                        "name": "foobar"
                                    },
                                    "size": 10000000000,
                                    "volume_type": "l_ssd"
                                }
                            }
                        }
                    }
                    JSON),
                )));
            });

        $server = $servers->create(
            new Server\Name('foobar'),
            new Organization\Id('000a115d-2852-4b0a-9ce8-47f1134ba95a'),
            new Image\Id('9956c6a6-607c-4d42-92bc-5f51f7087ae4'),
            new IP\Id('95be217a-c32f-41c1-b62d-97827adfc9e5'),
            'foo',
            'bar',
        );

        $this->assertInstanceOf(Server::class, $server);
        $this->assertSame('3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c', $server->id()->toString());
        $this->assertSame('000a115d-2852-4b0a-9ce8-47f1134ba95a', $server->organization()->toString());
        $this->assertSame('foobar', $server->name()->toString());
        $this->assertSame('9956c6a6-607c-4d42-92bc-5f51f7087ae4', $server->image()->toString());
        $this->assertSame('95be217a-c32f-41c1-b62d-97827adfc9e5', $server->ip()->toString());
        $this->assertSame(Server\State::stopped(), $server->state());
        $this->assertSame(['foo', 'bar'], $server->tags()->toList());
        $this->assertSame('d9257116-6919-49b4-a420-dcfdff51fcb1', $server->volumes()->match(
            static fn($volume) => $volume->toString(),
            static fn() => null,
        ));
    }

    public function testList()
    {
        $servers = new Http(
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
                        'https://cp-par1.scaleway.com/servers',
                        $request->url()->toString(),
                    ),
                    2 => $this->assertSame(
                        'https://cp-par1.scaleway.com/servers?page=2&per_page=50',
                        $request->url()->toString(),
                    ),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => Either::right(new Success($request, Response::of(
                        StatusCode::ok,
                        $request->protocolVersion(),
                        Headers::of(new Link(
                            new LinkValue(Url::of('/servers?page=2&per_page=50'), 'next'),
                        )),
                        Content::ofString(<<<JSON
                        {
                            "servers": [
                                {
                                    "bootscript": null,
                                    "dynamic_ip_required": true,
                                    "id": "3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c",
                                    "image": {
                                        "id": "9956c6a6-607c-4d42-92bc-5f51f7087ae4",
                                        "name": "ubuntu working"
                                    },
                                    "name": "foobar",
                                    "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
                                    "private_ip": null,
                                    "public_ip": {
                                        "id": "95be217a-c32f-41c1-b62d-97827adfc9e5"
                                    },
                                    "enable_ipv6": true,
                                    "state": "stopped",
                                    "ipv6": null,
                                    "commercial_type": "START1-S",
                                    "arch": "x86_64",
                                    "boot_type": "local",
                                    "tags": [
                                        "foo",
                                        "bar"
                                    ],
                                    "volumes": {
                                        "0": {
                                            "export_uri": null,
                                            "id": "d9257116-6919-49b4-a420-dcfdff51fcb1",
                                            "name": "vol simple snapshot",
                                            "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
                                            "server": {
                                                "id": "3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c",
                                                "name": "foobar"
                                            },
                                            "size": 10000000000,
                                            "volume_type": "l_ssd"
                                        }
                                    }
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
                            "servers": [
                                {
                                    "bootscript": null,
                                    "dynamic_ip_required": true,
                                    "id": "3cb18e2d-f4f7-48f7-b452-59b88ae8fc8d",
                                    "image": {
                                        "id": "9956c6a6-607c-4d42-92bc-5f51f7087ae4",
                                        "name": "ubuntu working"
                                    },
                                    "name": "foobar",
                                    "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
                                    "private_ip": null,
                                    "public_ip": {
                                        "id": "95be217a-c32f-41c1-b62d-97827adfc9e5"
                                    },
                                    "enable_ipv6": true,
                                    "state": "stopped",
                                    "ipv6": null,
                                    "commercial_type": "START1-S",
                                    "arch": "x86_64",
                                    "boot_type": "local",
                                    "tags": [
                                        "foo",
                                        "bar"
                                    ],
                                    "volumes": {
                                        "0": {
                                            "export_uri": null,
                                            "id": "d9257116-6919-49b4-a420-dcfdff51fcb1",
                                            "name": "vol simple snapshot",
                                            "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
                                            "server": {
                                                "id": "3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c",
                                                "name": "foobar"
                                            },
                                            "size": 10000000000,
                                            "volume_type": "l_ssd"
                                        }
                                    }
                                }
                            ]
                        }
                        JSON),
                    ))),
                };
            });

        $servers = $servers->list();

        $this->assertInstanceOf(Set::class, $servers);
        $this->assertCount(2, $servers);
    }

    public function testGet()
    {
        $servers = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->willReturnCallback(function($request) {
                $this->assertSame(
                    'https://cp-par1.scaleway.com/servers/3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c',
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
                        "server": {
                            "bootscript": null,
                            "dynamic_ip_required": true,
                            "id": "3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c",
                            "image": {
                                "id": "9956c6a6-607c-4d42-92bc-5f51f7087ae4",
                                "name": "ubuntu working"
                            },
                            "name": "foobar",
                            "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
                            "private_ip": null,
                            "public_ip": {
                                "id": "95be217a-c32f-41c1-b62d-97827adfc9e5"
                            },
                            "enable_ipv6": true,
                            "state": "stopped",
                            "ipv6": null,
                            "commercial_type": "START1-S",
                            "arch": "x86_64",
                            "boot_type": "local",
                            "tags": [
                                "foo",
                                "bar"
                            ],
                            "volumes": {
                                "0": {
                                    "export_uri": null,
                                    "id": "d9257116-6919-49b4-a420-dcfdff51fcb1",
                                    "name": "vol simple snapshot",
                                    "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
                                    "server": {
                                        "id": "3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c",
                                        "name": "foobar"
                                    },
                                    "size": 10000000000,
                                    "volume_type": "l_ssd"
                                }
                            },
                            "allowed_actions": [
                                "backup"
                            ]
                        }
                    }
                    JSON),
                )));
            });

        $server = $servers->get(new Server\Id('3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c'));

        $this->assertInstanceOf(Server::class, $server);
        $this->assertSame('3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c', $server->id()->toString());
        $this->assertSame('000a115d-2852-4b0a-9ce8-47f1134ba95a', $server->organization()->toString());
        $this->assertSame('foobar', $server->name()->toString());
        $this->assertSame('9956c6a6-607c-4d42-92bc-5f51f7087ae4', $server->image()->toString());
        $this->assertSame('95be217a-c32f-41c1-b62d-97827adfc9e5', $server->ip()->toString());
        $this->assertSame(Server\State::stopped(), $server->state());
        $this->assertSame(['foo', 'bar'], $server->tags()->toList());
        $this->assertSame('d9257116-6919-49b4-a420-dcfdff51fcb1', $server->volumes()->match(
            static fn($volume) => $volume->toString(),
            static fn() => null,
        ));
        $this->assertSame(
            [Server\Action::backup()],
            $server->allowedActions()->toList(),
        );
    }

    public function testRemove()
    {
        $servers = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->willReturnCallback(function($request) {
                $this->assertSame(
                    'https://cp-par1.scaleway.com/servers/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd',
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

        $this->assertNull($servers->remove(new Server\Id('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd')));
    }

    public function testExecute()
    {
        $servers = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->willReturnCallback(function($request) {
                $this->assertSame(
                    'https://cp-par1.scaleway.com/servers/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd/action',
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
                    '{"action":"poweroff"}',
                    $request->body()->toString(),
                );

                return Either::right(new Success($request, Response::of(
                    StatusCode::ok,
                    $request->protocolVersion(),
                )));
            });

        $this->assertNull($servers->execute(
            new Server\Id('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd'),
            Server\Action::powerOff(),
        ));
    }
}
