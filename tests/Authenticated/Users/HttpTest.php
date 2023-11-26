<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Authenticated\Users;

use Innmind\ScalewaySdk\{
    Authenticated\Users\Http,
    Authenticated\Users,
    Token,
    User,
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
use Innmind\Json\Json;
use Innmind\Immutable\Either;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Users::class,
            new Http(
                $this->createMock(Transport::class),
                new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
            ),
        );
    }

    public function testGet()
    {
        $users = new Http(
            $http = $this->createMock(Transport::class),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->willReturnCallback(function($request) {
                $this->assertSame(
                    'https://account.scaleway.com/users/25d37e4e-9674-450c-a8ac-96ec3be9a643',
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
                        "user": {
                            "email": "jsnow@got.com",
                            "firstname": "John",
                            "fullname": "John Snow",
                            "id": "25d37e4e-9674-450c-a8ac-96ec3be9a643",
                            "lastname": "Snow",
                            "organizations": [
                                {
                                    "id": "000a115d-2852-4b0a-9ce8-47f1134ba95a",
                                    "name": "watev"
                                }
                            ],
                            "roles": null,
                            "ssh_public_keys": [
                                {
                                    "key": "foo",
                                    "description": "bar"
                                }
                            ]
                        }
                    }
                    JSON),
                )));
            });

        $user = $users->get(new User\Id('25d37e4e-9674-450c-a8ac-96ec3be9a643'));

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('25d37e4e-9674-450c-a8ac-96ec3be9a643', $user->id()->toString());
        $this->assertSame('jsnow@got.com', $user->email());
        $this->assertSame('John', $user->firstname());
        $this->assertSame('Snow', $user->lastname());
        $this->assertSame('John Snow', $user->fullname());
        $this->assertCount(1, $user->sshKeys());
        $this->assertCount(1, $user->organizations());
    }

    public function testUpdateSshKeys()
    {
        $users = new Http(
            $http = $this->createMock(Transport::class),
            new Token\Id('9de8f869-c58e-4aa3-9208-2d4eaff5fa20'),
        );
        $http
            ->expects($this->once())
            ->method('__invoke')
            ->willReturnCallback(function($request) {
                $this->assertSame(
                    'https://account.scaleway.com/users/25d37e4e-9674-450c-a8ac-96ec3be9a643',
                    $request->url()->toString(),
                );
                $this->assertSame(
                    'PATCH',
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
                        'ssh_public_keys' => [
                            [
                                'key' => 'foo',
                                'description' => 'bar',
                            ],
                            [
                                'key' => 'baz',
                                'description' => null,
                            ],
                        ],
                    ]),
                    $request->body()->toString(),
                );

                return Either::right(new Success($request, Response::of(
                    StatusCode::ok,
                    $request->protocolVersion(),
                )));
            });

        $this->assertNull($users->updateSshKeys(
            new User\Id('25d37e4e-9674-450c-a8ac-96ec3be9a643'),
            new User\SshKey('foo', 'bar'),
            new User\SshKey('baz'),
        ));
    }
}
