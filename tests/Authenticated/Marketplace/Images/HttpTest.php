<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Authenticated\Marketplace\Images;

use Innmind\ScalewaySdk\{
    Authenticated\Marketplace\Images\Http,
    Authenticated\Marketplace\Images,
    Token,
    Marketplace\Image,
    Image\Architecture,
    Region,
};
use Innmind\TimeContinuum\Clock;
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
use function Innmind\Immutable\first;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Images::class,
            new Http(
                $this->createMock(Transport::class),
                $this->createMock(Clock::class),
                new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
            ),
        );
    }

    public function testList()
    {
        $images = new Http(
            $http = $this->createMock(Transport::class),
            $this->createMock(Clock::class),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive(
                [$this->callback(static function($request): bool {
                    return $request->url()->toString() === 'https://api-marketplace.scaleway.com/images' &&
                        $request->method()->toString() === 'GET' &&
                        $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
                })],
                [$this->callback(static function($request): bool {
                    return $request->url()->toString() === 'https://api-marketplace.scaleway.com/images?page=2&per_page=50' &&
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
                    new LinkValue(Url::of('/images?page=2&per_page=50'), 'next'),
                ),
            ));
        $response1
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
{
    "images": [
        {
            "valid_until": null,
            "description": "Etherpad is a highly customizable Open Source online editor.",
            "creation_date": "2016-03-07T21:01:06.696775+00:00",
            "logo": "https://marketplace-logos.s3.nl-ams.scw.cloud/etherpad.png",
            "id": "53e87f7e-b173-4e88-b9ae-81c2ab7f168e",
            "categories": [
                "instantapp"
            ],
            "name": "Etherpad",
            "modification_date": "2019-03-26T14:00:49.232680+00:00",
            "versions": [
                {
                    "creation_date": "2016-03-07T21:54:43.305997+00:00",
                    "modification_date": "2016-03-07T21:54:43.305997+00:00",
                    "id": "6fc832a1-6a31-4ac4-9dc5-27a9875e2a32",
                    "local_images": [
                        {
                            "compatible_commercial_types": [
                                "C1"
                            ],
                            "arch": "arm",
                            "id": "f085beb3-b99c-493e-a882-87a2f6d48bf8",
                            "zone": "par1"
                        },
                        {
                            "compatible_commercial_types": [],
                            "arch": "arm",
                            "id": "7cb6244a-7d24-4459-b5eb-ce0053c30633",
                            "zone": "ams1"
                        }
                    ],
                    "name": "2015-09-18"
                }
            ],
            "current_public_version": "6fc832a1-6a31-4ac4-9dc5-27a9875e2a32",
            "organization": {
                "id": "6d6b64e5-6bad-4cc6-b7ef-2030884c3e11",
                "name": "mtouron@ocs.online.net"
            }
        }
    ]
}
JSON
            ));
        $response2
            ->expects($this->any())
            ->method('headers')
            ->willReturn(Headers::of(
                new Link(
                    new LinkValue(Url::of('/images?page=2&per_page=50'), 'last'),
                ),
            ));
        $response2
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
{
    "images": [
        {
            "valid_until": null,
            "description": "Arch Linux is an independently developed Linux distribution versatile enough to suit any role.",
            "creation_date": "2016-03-07T20:55:32.213089+00:00",
            "logo": "https://marketplace-logos.s3.nl-ams.scw.cloud/archlinux.png",
            "id": "8f60c5dd-e659-48da-97e3-fb7de42195f5",
            "categories": [
                "distribution"
            ],
            "name": "Arch Linux",
            "modification_date": "2019-03-26T14:00:49.327070+00:00",
            "versions": [
                {
                    "creation_date": "2018-04-20T15:59:04.594929+00:00",
                    "modification_date": "2018-04-20T15:59:04.594929+00:00",
                    "id": "f7696517-bc49-448b-9869-f2c84e7c2a96",
                    "local_images": [
                        {
                            "compatible_commercial_types": [
                                "GP1-XS",
                                "DEV1-L",
                                "RENDER-S",
                                "GP1-XL",
                                "C2S",
                                "X64-15GB",
                                "DEV1-XL",
                                "C2L",
                                "C2M",
                                "VC1S",
                                "START1-S",
                                "X64-30GB",
                                "GP1-L",
                                "GP1-M",
                                "GP1-S",
                                "START1-L",
                                "START1-M",
                                "VC1L",
                                "VC1M",
                                "X64-120GB",
                                "X64-60GB"
                            ],
                            "arch": "x86_64",
                            "id": "f21defd0-9fd9-4fb2-a29a-22844a6be3cd",
                            "zone": "par1"
                        },
                        {
                            "compatible_commercial_types": [
                                "X64-120GB",
                                "C2M",
                                "START1-S",
                                "VC1S",
                                "C2L",
                                "X64-15GB",
                                "C2S",
                                "X64-30GB",
                                "START1-L",
                                "START1-M",
                                "X64-60GB",
                                "VC1L",
                                "VC1M"
                            ],
                            "arch": "x86_64",
                            "id": "3c904f73-080e-4c6f-8b28-8426cfdcb3c7",
                            "zone": "ams1"
                        }
                    ],
                    "name": "2018-04-20T15:59:04.593811"
                }
            ],
            "current_public_version": "f7696517-bc49-448b-9869-f2c84e7c2a96",
            "organization": {
                "id": "6d6b64e5-6bad-4cc6-b7ef-2030884c3e11",
                "name": "mtouron@ocs.online.net"
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
            $this->createMock(Clock::class),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return $request->url()->toString() === 'https://api-marketplace.scaleway.com/images/25d37e4e-9674-450c-a8ac-96ec3be9a643' &&
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
        "valid_until": null,
        "description": "Arch Linux is an independently developed Linux distribution versatile enough to suit any role.",
        "creation_date": "2016-03-07T20:55:32.213089+00:00",
        "logo": "https://marketplace-logos.s3.nl-ams.scw.cloud/archlinux.png",
        "id": "8f60c5dd-e659-48da-97e3-fb7de42195f5",
        "categories": [
            "distribution"
        ],
        "name": "Arch Linux",
        "modification_date": "2019-03-26T14:00:49.327070+00:00",
        "versions": [
            {
                "creation_date": "2018-04-20T15:59:04.594929+00:00",
                "modification_date": "2018-04-20T15:59:04.594929+00:00",
                "id": "f7696517-bc49-448b-9869-f2c84e7c2a96",
                "local_images": [
                    {
                        "compatible_commercial_types": [
                            "GP1-XS",
                            "DEV1-L",
                            "RENDER-S",
                            "GP1-XL",
                            "C2S",
                            "X64-15GB",
                            "DEV1-XL",
                            "C2L",
                            "C2M",
                            "VC1S",
                            "START1-S",
                            "X64-30GB",
                            "GP1-L",
                            "GP1-M",
                            "GP1-S",
                            "START1-L",
                            "START1-M",
                            "VC1L",
                            "VC1M",
                            "X64-120GB",
                            "X64-60GB"
                        ],
                        "arch": "x86_64",
                        "id": "f21defd0-9fd9-4fb2-a29a-22844a6be3cd",
                        "zone": "par1"
                    },
                    {
                        "compatible_commercial_types": [
                            "X64-120GB",
                            "C2M",
                            "START1-S",
                            "VC1S",
                            "C2L",
                            "X64-15GB",
                            "C2S",
                            "X64-30GB",
                            "START1-L",
                            "START1-M",
                            "X64-60GB",
                            "VC1L",
                            "VC1M"
                        ],
                        "arch": "x86_64",
                        "id": "3c904f73-080e-4c6f-8b28-8426cfdcb3c7",
                        "zone": "ams1"
                    }
                ],
                "name": "2018-04-20T15:59:04.593811"
            }
        ],
        "current_public_version": "f7696517-bc49-448b-9869-f2c84e7c2a96",
        "organization": {
            "id": "6d6b64e5-6bad-4cc6-b7ef-2030884c3e11",
            "name": "mtouron@ocs.online.net"
        }
    }
}
JSON
            ));

        $image = $images->get(new Image\Id('25d37e4e-9674-450c-a8ac-96ec3be9a643'));

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame('8f60c5dd-e659-48da-97e3-fb7de42195f5', $image->id()->toString());
        $this->assertSame('6d6b64e5-6bad-4cc6-b7ef-2030884c3e11', $image->organization()->toString());
        $this->assertSame('Arch Linux', $image->name()->toString());
        $this->assertSame('f7696517-bc49-448b-9869-f2c84e7c2a96', $image->currentPublicVersion()->id()->toString());
        $this->assertFalse($image->expires());
        $this->assertSame(
            'https://marketplace-logos.s3.nl-ams.scw.cloud/archlinux.png',
            $image->logo()->toString(),
        );
        $this->assertCount(1, $image->categories());
        $this->assertCount(1, $image->versions());
        $this->assertCount(2, first($image->versions())->localImages());
        $this->assertCount(21, first(first($image->versions())->localImages())->compatibleCommercialTypes());
        $this->assertSame(
            'f21defd0-9fd9-4fb2-a29a-22844a6be3cd',
            first(first($image->versions())->localImages())->id()->toString(),
        );
        $this->assertSame(
            Architecture::x86_64(),
            first(first($image->versions())->localImages())->architecture(),
        );
        $this->assertSame(
            Region::paris1(),
            first(first($image->versions())->localImages())->region(),
        );
    }
}
