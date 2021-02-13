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
            Tokens::class,
            new Http(
                $this->createMock(Transport::class),
                new Clock,
                new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
            )
        );
    }

    public function testList()
    {
        $tokens = new Http(
            $http = $this->createMock(Transport::class),
            new Clock(new UTC),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive(
                [$this->callback(static function($request): bool {
                    return $request->url()->toString() === 'https://account.scaleway.com/tokens' &&
                        $request->method()->toString() === 'GET' &&
                        $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
                })],
                [$this->callback(static function($request): bool {
                    return $request->url()->toString() === 'https://account.scaleway.com/tokens?page=2&per_page=50' &&
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
                    new LinkValue(Url::of('/tokens?page=2&per_page=50'), 'next')
                )
            ));
        $response1
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
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
JSON
            ));
        $response2
            ->expects($this->any())
            ->method('headers')
            ->willReturn(Headers::of(
                new Link(
                    new LinkValue(Url::of('/tokens?page=2&per_page=50'), 'last')
                )
            ));
        $response2
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
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
JSON
            ));

        $all = $tokens->list();

        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(Token::class, (string) $all->type());
        $this->assertCount(2, $all);
    }

    public function testGet()
    {
        $tokens = new Http(
            $http = $this->createMock(Transport::class),
            new Clock(new UTC),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return $request->url()->toString() === 'https://account.scaleway.com/tokens/25d37e4e-9674-450c-a8ac-96ec3be9a643' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent(<<<JSON
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
JSON
            ));

        $token = $tokens->get(new Token\Id('25d37e4e-9674-450c-a8ac-96ec3be9a643'));

        $this->assertInstanceOf(Token::class, $token);
        $this->assertSame('25d37e4e-9674-450c-a8ac-96ec3be9a643', $token->id()->toString());
    }

    public function testRemove()
    {
        $tokens = new Http(
            $http = $this->createMock(Transport::class),
            new Clock(new UTC),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return $request->url()->toString() === 'https://account.scaleway.com/tokens/25d37e4e-9674-450c-a8ac-96ec3be9a643' &&
                    $request->method()->toString() === 'DELETE' &&
                    $request->headers()->get('x-auth-token')->toString() === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }));

        $this->assertNull($tokens->remove(new Token\Id('25d37e4e-9674-450c-a8ac-96ec3be9a643')));
    }
}
