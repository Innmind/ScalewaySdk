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
use function Innmind\Immutable\{
    unwrap,
    first,
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
            ->with($this->callback(static function($request): bool {
                return $request->url()->toString() === 'https://cp-par1.scaleway.com/servers' &&
                    $request->method()->toString() === 'POST' &&
                    $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20' &&
                    $request->body()->toString() === Json::encode([
                        'name' => 'foobar',
                        'organization' => '000a115d-2852-4b0a-9ce8-47f1134ba95a',
                        'image' => '9956c6a6-607c-4d42-92bc-5f51f7087ae4',
                        'tags' => ['foo', 'bar'],
                        'dynamic_ip_required' => false,
                        'enable_ipv6' => true,
                        'public_ip' => '95be217a-c32f-41c1-b62d-97827adfc9e5',
                    ]);
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
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
        $this->assertSame(['foo', 'bar'], unwrap($server->tags()));
        $this->assertSame('d9257116-6919-49b4-a420-dcfdff51fcb1', first($server->volumes())->toString());
    }

    public function testList()
    {
        $servers = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive(
                [$this->callback(static function($request): bool {
                    return $request->url()->toString() === 'https://cp-par1.scaleway.com/servers' &&
                        $request->method()->toString() === 'GET' &&
                        $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
                })],
                [$this->callback(static function($request): bool {
                    return $request->url()->toString() === 'https://cp-par1.scaleway.com/servers?page=2&per_page=50' &&
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
                    new LinkValue(Url::of('/servers?page=2&per_page=50'), 'next'),
                ),
            ));
        $response1
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
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
        $response2
            ->expects($this->once())
            ->method('headers')
            ->willReturn(Headers::of());
        $response2
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
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

        $this->assertInstanceOf(Set::class, $servers);
        $this->assertSame(Server::class, (string) $servers->type());
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
            ->with($this->callback(static function($request): bool {
                return $request->url()->toString() === 'https://cp-par1.scaleway.com/servers/3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
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
        $this->assertSame('3cb18e2d-f4f7-48f7-b452-59b88ae8fc8c', $server->id()->toString());
        $this->assertSame('000a115d-2852-4b0a-9ce8-47f1134ba95a', $server->organization()->toString());
        $this->assertSame('foobar', $server->name()->toString());
        $this->assertSame('9956c6a6-607c-4d42-92bc-5f51f7087ae4', $server->image()->toString());
        $this->assertSame('95be217a-c32f-41c1-b62d-97827adfc9e5', $server->ip()->toString());
        $this->assertSame(Server\State::stopped(), $server->state());
        $this->assertSame(['foo', 'bar'], unwrap($server->tags()));
        $this->assertSame('d9257116-6919-49b4-a420-dcfdff51fcb1', first($server->volumes())->toString());
        $this->assertSame(
            [Server\Action::backup()],
            unwrap($server->allowedActions()),
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
            ->with($this->callback(static function($request): bool {
                return $request->url()->toString() === 'https://cp-par1.scaleway.com/servers/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd' &&
                    $request->method()->toString() === 'DELETE' &&
                    $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }));

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
            ->with($this->callback(static function($request): bool {
                return $request->url()->toString() === 'https://cp-par1.scaleway.com/servers/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd/action' &&
                    $request->method()->toString() === 'POST' &&
                    $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20' &&
                    $request->body()->toString() === '{"action":"poweroff"}';
            }));

        $this->assertNull($servers->execute(
            new Server\Id('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd'),
            Server\Action::powerOff(),
        ));
    }
}
