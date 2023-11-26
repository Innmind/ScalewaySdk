<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Authenticated\Tokens;

use Innmind\ScalewaySdk\{
    Authenticated\Tokens\Http,
    Authenticated\Tokens,
    Token,
};
use Innmind\TimeContinuum\Earth\{
    Clock,
    Timezone\UTC,
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
            Tokens::class,
            new Http(
                $this->createMock(Transport::class),
                new Clock,
                new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
            ),
        );
    }

    public function testList()
    {
        $tokens = new Http(
            $http = $this->createMock(Transport::class),
            new Clock(new UTC),
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
                        'https://account.scaleway.com/tokens',
                        $request->url()->toString(),
                    ),
                    2 => $this->assertSame(
                        'https://account.scaleway.com/tokens?page=2&per_page=50',
                        $request->url()->toString(),
                    ),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => Either::right(new Success($request, Response::of(
                        StatusCode::ok,
                        $request->protocolVersion(),
                        Headers::of(new Link(
                            new LinkValue(Url::of('/tokens?page=2&per_page=50'), 'next'),
                        )),
                        Content::ofString(<<<JSON
                        {
                            "tokens": [
                                {
                                    "creation_date": "2014-03-13T10:53:11.456319+00:00",
                                    "expires": null,
                                    "id": "25d37e4e-9674-450c-a8ac-96ec3be9a643",
                                    "inherits_user_perms": true,
                                    "permissions": [],
                                    "roles": {
                                        "organization": null,
                                        "role": null
                                    },
                                    "user_id": "8d77785c-abd5-40d0-908f-f13a97e24869"
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
                            "tokens": [
                                {
                                    "creation_date": "2014-05-19T18:05:47.304433+00:00",
                                    "expires": "2014-05-20T14:05:06.393875+00:00",
                                    "id": "654c95b0-2cf5-41a3-b3cc-733ffba4b4b7",
                                    "inherits_user_perms": true,
                                    "permissions": [],
                                    "roles": {
                                        "organization": null,
                                        "role": null
                                    },
                                    "user_id": "8d77785c-abd5-40d0-908f-f13a97e24869"
                                }
                            ]
                        }
                        JSON),
                    ))),
                };
            });

        $all = $tokens->list();

        $this->assertCount(2, $all);
    }

    public function testGet()
    {
        $tokens = new Http(
            $http = $this->createMock(Transport::class),
            new Clock(new UTC),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->willReturnCallback(function($request) {
                $this->assertSame(
                    'https://account.scaleway.com/tokens/25d37e4e-9674-450c-a8ac-96ec3be9a643',
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
                        "token": {
                            "creation_date": "2014-03-13T10:53:11.456319+00:00",
                            "expires": null,
                            "id": "25d37e4e-9674-450c-a8ac-96ec3be9a643",
                            "inherits_user_perms": true,
                            "permissions": [],
                            "roles": {
                                "organization": null,
                                "role": null
                            },
                            "user_id": "8d77785c-abd5-40d0-908f-f13a97e24869"
                        }
                    }
                    JSON),
                )));
            });

        $token = $tokens->get(new Token\Id('25d37e4e-9674-450c-a8ac-96ec3be9a643'));

        $this->assertInstanceOf(Token::class, $token);
        $this->assertSame('25d37e4e-9674-450c-a8ac-96ec3be9a643', $token->id()->toString());
    }

    public function testRemove()
    {
        $tokens = new Http(
            $http = $this->createMock(Transport::class),
            new Clock(new UTC),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->willReturnCallback(function($request) {
                $this->assertSame(
                    'https://account.scaleway.com/tokens/25d37e4e-9674-450c-a8ac-96ec3be9a643',
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

        $this->assertNull($tokens->remove(new Token\Id('25d37e4e-9674-450c-a8ac-96ec3be9a643')));
    }
}
