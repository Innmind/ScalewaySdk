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
            Images::class,
            new Http(
                $this->createMock(Transport::class),
                Region::paris1(),
                new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
            ),
        );
    }

    public function testList()
    {
        $images = new Http(
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
                        'https://cp-par1.scaleway.com/images',
                        $request->url()->toString(),
                    ),
                    2 => $this->assertSame(
                        'https://cp-par1.scaleway.com/images?page=2&per_page=50',
                        $request->url()->toString(),
                    ),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => Either::right(new Success($request, Response::of(
                        StatusCode::ok,
                        $request->protocolVersion(),
                        Headers::of(new Link(
                            new LinkValue(Url::of('/images?page=2&per_page=50'), 'next'),
                        )),
                        Content::ofString(<<<JSON
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
                        JSON),
                    ))),
                    2 => Either::right(new Success($request, Response::of(
                        StatusCode::ok,
                        $request->protocolVersion(),
                        null,
                        Content::ofString(<<<JSON
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
                        JSON),
                    ))),
                };
            });

        $all = $images->list();

        $this->assertInstanceOf(Set::class, $all);
        $this->assertCount(2, $all);
    }

    public function testGet()
    {
        $images = new Http(
            $http = $this->createMock(Transport::class),
            Region::paris1(),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->willReturnCallback(function($request) {
                $this->assertSame(
                    'https://cp-par1.scaleway.com/images/25d37e4e-9674-450c-a8ac-96ec3be9a643',
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
                    JSON),
                )));
            });

        $image = $images->get(new Image\Id('25d37e4e-9674-450c-a8ac-96ec3be9a643'));

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame('1f73d975-35fc-4365-9ead-8dab7e54152f', $image->id()->toString());
        $this->assertSame('000a115d-2852-4b0a-9ce8-47f1134ba95a', $image->organization()->toString());
        $this->assertSame('my_image_1', $image->name()->toString());
        $this->assertSame('arm', $image->architecture()->toString());
        $this->assertFalse($image->public());
    }
}
