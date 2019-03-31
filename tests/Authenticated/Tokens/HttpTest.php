<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Authenticated\Tokens;

use Innmind\ScalewaySdk\{
    Authenticated\Tokens\Http,
    Authenticated\Tokens,
    Token,
};
use Innmind\TimeContinuum\{
    TimeContinuum\Earth,
    Timezone\Earth\UTC,
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
use Innmind\Immutable\SetInterface;
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait,
};

class HttpTest extends TestCase
{
    use TestTrait;

    public function testList()
    {
        $tokens = new Http(
            $http = $this->createMock(Transport::class),
            new Earth(new UTC),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->at(0))
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://account.scaleway.com/tokens' &&
                    (string) $request->method() === 'GET' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->any())
            ->method('headers')
            ->willReturn(Headers::of(
                new Link(
                    new LinkValue(Url::fromString('/tokens?page=2&per_page=50'), 'next')
                )
            ));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<JSON
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
        $http
            ->expects($this->at(1))
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://account.scaleway.com/tokens?page=2&per_page=50' &&
                    (string) $request->method() === 'GET' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->any())
            ->method('headers')
            ->willReturn(Headers::of(
                new Link(
                    new LinkValue(Url::fromString('/tokens?page=2&per_page=50'), 'last')
                )
            ));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<JSON
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

        $this->assertInstanceOf(SetInterface::class, $all);
        $this->assertSame(Token::class, (string) $all->type());
        $this->assertCount(2, $all);
    }

    public function testGet()
    {
        $tokens = new Http(
            $http = $this->createMock(Transport::class),
            new Earth(new UTC),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20')
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'https://account.scaleway.com/tokens/25d37e4e-9674-450c-a8ac-96ec3be9a643' &&
                    (string) $request->method() === 'GET' &&
                    (string) $request->headers()->get('x-auth-token') === 'X-Auth-Token: 9de8f869-c58e-4aa3-9208-2d4eaff5fa20';
            }))
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream(<<<JSON
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
        $this->assertSame('25d37e4e-9674-450c-a8ac-96ec3be9a643', (string) $token->id());
    }
}
