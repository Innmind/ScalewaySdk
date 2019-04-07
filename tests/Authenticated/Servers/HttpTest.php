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
            Servers::class,
            new Http(
                $this->createMock(Transport::class),
                Region::paris1(),
                new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
            )
        );
    }

    public function testCreate()
    {
        $servers = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/servers' &&
                    (string) $request->method() === 'POST' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20' &&
                    (string) $request->body() === Json::encode([
                        'name' => 'foobar',
                        'organization' => '000a115d-2852-4b0a-9ce8-47f1134ba95a',
                        'image' => '9956c6a6-607c-4d42-92bc-5f51f7087ae4',
                        'tags' => ['foo', 'bar'],
                    ]);
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<JSON
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
JSON
            ));

        $server = $servers->create(
            'foobar',
            new Organization\Id('000a115d-2852-4b0a-9ce8-47f1134ba95a'),
            new Image\Id('9956c6a6-607c-4d42-92bc-5f51f7087ae4'),
            'foo',
            'bar'
        );

        $this->assertInstanceOf(Server::class, $server);
        $this->assertSame('3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c', (string) $server->id());
        $this->assertSame('000a115d-2852-4b0a-9ce8-47f1134ba95a', (string) $server->organization());
        $this->assertSame('foobar', $server->name());
        $this->assertSame('9956c6a6-607c-4d42-92bc-5f51f7087ae4', (string) $server->image());
        $this->assertSame('95be217a-c32f-41c1-b62d-97827adfc9e5', (string) $server->ip());
        $this->assertSame(Server\State::stopped(), $server->state());
        $this->assertSame(['foo', 'bar'], $server->tags()->toPrimitive());
        $this->assertSame('d9257116-6919-49b4-a420-dcfdff51fcb1', (string) $server->volumes()->current());
    }

    public function testList()
    {
        $servers = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->at(0))
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/servers' &&
                    (string) $request->method() === 'GET' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->any())
            ->method('headers')
            ->willReturn(Headers::of(
                new Link(
                    new LinkValue(Url::fromString('/servers?page=2&per_page=50'), 'next')
                )
            ));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<JSON
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
JSON
            ));
        $http
            ->expects($this->at(1))
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/servers?page=2&per_page=50' &&
                    (string) $request->method() === 'GET' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<JSON
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
JSON
            ));

        $servers = $servers->list();

        $this->assertInstanceOf(SetInterface::class, $servers);
        $this->assertSame(Server::class, (string) $servers->type());
        $this->assertCount(2, $servers);
    }

    public function testGet()
    {
        $servers = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/servers/3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c' &&
                    (string) $request->method() === 'GET' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<JSON
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
JSON
            ));

        $server = $servers->get(new Server\Id('3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c'));

        $this->assertInstanceOf(Server::class, $server);
        $this->assertSame('3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c', (string) $server->id());
        $this->assertSame('000a115d-2852-4b0a-9ce8-47f1134ba95a', (string) $server->organization());
        $this->assertSame('foobar', $server->name());
        $this->assertSame('9956c6a6-607c-4d42-92bc-5f51f7087ae4', (string) $server->image());
        $this->assertSame('95be217a-c32f-41c1-b62d-97827adfc9e5', (string) $server->ip());
        $this->assertSame(Server\State::stopped(), $server->state());
        $this->assertSame(['foo', 'bar'], $server->tags()->toPrimitive());
        $this->assertSame('d9257116-6919-49b4-a420-dcfdff51fcb1', (string) $server->volumes()->current());
        $this->assertSame(
            [Server\Action::backup()],
            $server->allowedActions()->toPrimitive()
        );
    }

    public function testRemove()
    {
        $servers = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/servers/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd' &&
                    (string) $request->method() === 'DELETE' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }));

        $this->assertNull($servers->remove(new Server\Id('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd')));
    }

    public function testExecute()
    {
        $servers = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/servers/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd' &&
                    (string) $request->method() === 'POST' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20' &&
                    (string) $request->body() === '{"action":"poweroff"}';
            }));

        $this->assertNull($servers->execute(
            new Server\Id('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd'),
            Server\Action::powerOff()
        ));
    }
}
