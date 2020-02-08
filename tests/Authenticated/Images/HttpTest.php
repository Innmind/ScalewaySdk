<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Authenticated\Images;

use Innmind\ScalewaySdk\{
    Authenticated\Images\Http,
    Authenticated\Images,
    Token,
    Image,
    Region,
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
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Images::class,
            new Http(
                $this->createMock(Transport::class),
                Region::paris1(),
                new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
            )
        );
    }

    public function testList()
    {
        $images = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->at(0))
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return $request->url()->toString() === 'https://cp-par1.scaleway.com/images' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->any())
            ->method('headers')
            ->willReturn(Headers::of(
                new Link(
                    new LinkValue(Url::of('/images?page=2&per_page=50'), 'next')
                )
            ));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
{
    "images": [
        {
            "arch": "arm",
            "creation_date": "2014-05-22T12:56:56.984011+00:00",
            "extra_volumes": [],
            "from_image": null,
            "from_server": null,
            "id": "98bf3ac2-a1f5-471d-8c8f-1b706ab57ef0",
            "modification_date": "2014-05-22T12:56:56.984011+00:00",
            "name": "my_image",
            "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
            "public": false,
            "root_volume": {
                "id": "f0361e7b-cbe4-4882-a999-945192b7171b",
                "name": "vol-0-1"
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
                return $request->url()->toString() === 'https://cp-par1.scaleway.com/images?page=2&per_page=50' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->any())
            ->method('headers')
            ->willReturn(Headers::of(
                new Link(
                    new LinkValue(Url::of('/images?page=2&per_page=50'), 'last')
                )
            ));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
{
    "images": [
        {
            "arch": "arm",
            "creation_date": "2014-05-22T12:57:22.514299+00:00",
            "extra_volumes": [],
            "from_image": null,
            "from_server": null,
            "id": "1f73d975-35fc-4365-9ead-8dab7e54152f",
            "modification_date": "2014-05-22T12:57:22.514299+00:00",
            "name": "my_image_1",
            "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
            "public": false,
            "root_volume": {
                "id": "f0361e7b-cbe4-4882-a999-945192b7171b",
                "name": "vol-0-2"
            }
        }
    ]
}
JSON
            ));

        $all = $images->list();

        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(Image::class, (string) $all->type());
        $this->assertCount(2, $all);
    }

    public function testGet()
    {
        $images = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return $request->url()->toString() === 'https://cp-par1.scaleway.com/images/25d37e4e-9674-450c-a8ac-96ec3be9a643' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
{
    "image": {
        "arch": "arm",
        "creation_date": "2014-05-22T12:57:22.514299+00:00",
        "extra_volumes": [],
        "from_image": null,
        "from_server": null,
        "id": "1f73d975-35fc-4365-9ead-8dab7e54152f",
        "modification_date": "2014-05-22T12:57:22.514299+00:00",
        "name": "my_image_1",
        "organization": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
        "public": false,
        "root_volume": {
            "id": "f0361e7b-cbe4-4882-a999-945192b7171b",
            "name": "vol-0-2"
        }
    }
}
JSON
            ));

        $image = $images->get(new Image\Id('25d37e4e-9674-450c-a8ac-96ec3be9a643'));

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame('1f73d975-35fc-4365-9ead-8dab7e54152f', (string) $image->id());
        $this->assertSame('000a115d-2852-4b0a-9ce8-47f1134ba95a', (string) $image->organization());
        $this->assertSame('my_image_1', (string) $image->name());
        $this->assertSame('arm', (string) $image->architecture());
        $this->assertFalse($image->public());
    }
}
