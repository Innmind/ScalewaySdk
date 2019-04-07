<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Authenticated\Volumes;

use Innmind\ScalewaySdk\{
    Authenticated\Volumes\Http,
    Authenticated\Volumes,
    Region,
    Token,
    Volume,
    Organization,
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
            Volumes::class,
            new Http(
                $this->createMock(Transport::class),
                Region::paris1(),
                new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
            )
        );
    }

    public function testCreate()
    {
        $volumes = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/volumes' &&
                    (string) $request->method() === 'POST' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20' &&
                    (string) $request->body() === Json::encode([
                        'name' => 'foobar',
                        'organization' => '000a115d-2852-4b0a-9ce8-47f1134ba95a',
                        'size' => 10000000000,
                        'type' => 'l_ssd',
                    ]);
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<JSON
{
    "volume": {
        "export_uri": null,
        "id": "c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd",
        "name": "foobar",
        "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
        "server": null,
        "size": 10000000000,
        "volume_type": "l_ssd"
    }
}
JSON
            ));

        $volume = $volumes->create(
            'foobar',
            new Organization\Id('000a115d-2852-4b0a-9ce8-47f1134ba95a'),
            Volume\Size::of(10000000000),
            Volume\Type::lssd()
        );

        $this->assertInstanceOf(Volume::class, $volume);
        $this->assertSame('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd', (string) $volume->id());
        $this->assertSame('foobar', $volume->name());
        $this->assertSame('000a115d-2852-4b0a-9ce8-47f1134ba95a', (string) $volume->organization());
        $this->assertSame(10000000000, $volume->size()->toInt());
        $this->assertSame('l_ssd', (string) $volume->type());
    }

    public function testList()
    {
        $volumes = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->at(0))
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/volumes' &&
                    (string) $request->method() === 'GET' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->any())
            ->method('headers')
            ->willReturn(Headers::of(
                new Link(
                    new LinkValue(Url::fromString('/volumes?page=2&per_page=50'), 'next')
                )
            ));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<JSON
{
    "volumes": [
        {
            "export_uri": null,
            "id": "f929fe39-63f8-4be8-a80e-1e9c8ae22a76",
            "name": "volume-0-1",
            "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
            "server": null,
            "size": 10000000000,
            "volume_type": "l_ssd"
        }
    ]
}
JSON
            ));
        $http
            ->expects($this->at(1))
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/volumes?page=2&per_page=50' &&
                    (string) $request->method() === 'GET' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<JSON
{
    "volumes": [
        {
            "export_uri": null,
            "id": "0facb6b5-b117-441a-81c1-f28b1d723779",
            "name": "volume-0-2",
            "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
            "server": {
                "id": "a61434eb-5f70-42d8-9915-45e8aa3201c7",
                "name": "foobar"
            },
            "size": 20000000000,
            "volume_type": "l_ssd"
        }
    ]
}
JSON
            ));

        $volumes = $volumes->list();

        $this->assertInstanceOf(SetInterface::class, $volumes);
        $this->assertSame(Volume::class, (string) $volumes->type());
        $this->assertCount(2, $volumes);
        $this->assertFalse($volumes->current()->attachedToAServer());
        $volumes->next();
        $this->assertTrue($volumes->current()->attachedToAServer());
    }

    public function testGet()
    {
        $volumes = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/volumes/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd' &&
                    (string) $request->method() === 'GET' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<JSON
{
    "volume": {
        "export_uri": null,
        "id": "c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd",
        "name": "foobar",
        "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
        "server": null,
        "size": 10000000000,
        "volume_type": "l_ssd"
    }
}
JSON
            ));

        $volume = $volumes->get(new Volume\Id('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd'));

        $this->assertInstanceOf(Volume::class, $volume);
        $this->assertSame('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd', (string) $volume->id());
        $this->assertSame('foobar', $volume->name());
        $this->assertSame('000a115d-2852-4b0a-9ce8-47f1134ba95a', (string) $volume->organization());
        $this->assertSame(10000000000, $volume->size()->toInt());
        $this->assertSame('l_ssd', (string) $volume->type());
    }

    public function testRemove()
    {
        $volumes = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://cp-par1.scaleway.com/volumes/c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd' &&
                    (string) $request->method() === 'DELETE' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }));

        $this->assertNull($volumes->remove(new Volume\Id('c675f420-cfeb-48ff-ba2a-9d2a4dbe3fcd')));
    }
}
