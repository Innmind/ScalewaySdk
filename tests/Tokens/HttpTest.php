<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Tokens;

use Innmind\ScalewaySdk\{
    Tokens\Http,
    Tokens,
    Tokens\NewToken,
    Token,
};
use Innmind\HttpTransport\Transport;
use Innmind\TimeContinuum\{
    TimeContinuum\Earth,
    Timezone\Earth\UTC,
    Format\ISO8601,
};
use Innmind\Json\Json;
use Innmind\Http\Message\Response;
use Innmind\Filesystem\Stream\StringStream;
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait,
};

class HttpTest extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Tokens::class,
            new Http(
                $this->createMock(Transport::class),
                new Earth(new UTC)
            )
        );
    }

    public function testCreatePermanentToken()
    {
        $this
            ->forAll(Generator\string(), Generator\string(), Generator\string())
            ->then(function($email, $password, $twofa): void {
                $tokens = new Http(
                    $http = $this->createMock(Transport::class),
                    new Earth(new UTC)
                );
                $http
                    ->expects($this->once())
                    ->method('__invoke')
                    ->with($this->callback(static function($request) use ($email, $password, $twofa): bool {
                        return (string) $request->url() === 'https://account.scaleway.com/tokens' &&
                            (string) $request->method() === 'POST' &&
                            (string) $request->headers()->get('content-type') === 'Content-Type: application/json' &&
                            (string) $request->body() === Json::encode([
                                'email' => $email,
                                'password' => $password,
                                'expires' => false,
                                '2FA_token' => $twofa,
                            ]);
                    }))
                    ->willReturn($response = $this->createMock(Response::class));
                $response
                    ->expects($this->once())
                    ->method('body')
                    ->willReturn(new StringStream(<<<JSON
{
  "token": {
    "creation_date": "2014-05-22T08:05:57.556385+00:00",
    "expires": null,
    "id": "9de8f869-c58e-4aa3-9208-2d4eaff5fa20",
    "inherits_user_perms": true,
    "permissions": [],
    "roles": {
      "organization": null,
      "role": null
    },
    "user_id": "5bea0358-db40-429e-bd82-914686a7e7b9"
  }
}
JSON
                    ));

                $token = $tokens->create(NewToken::permanent($email, $password, $twofa));

                $this->assertInstanceOf(Token::class, $token);
                $this->assertSame('9de8f869-c58e-4aa3-9208-2d4eaff5fa20', (string) $token->id());
                $this->assertSame('5bea0358-db40-429e-bd82-914686a7e7b9', (string) $token->user());
                $this->assertSame('2014-05-22T08:05:57+00:00', $token->createdAt()->format(new ISO8601));
                $this->assertFalse($token->expires());
            });
        $this
            ->forAll(Generator\string(), Generator\string())
            ->then(function($email, $password): void {
                $tokens = new Http(
                    $http = $this->createMock(Transport::class),
                    new Earth(new UTC)
                );
                $http
                    ->expects($this->once())
                    ->method('__invoke')
                    ->with($this->callback(static function($request) use ($email, $password): bool {
                        return (string) $request->url() === 'https://account.scaleway.com/tokens' &&
                            (string) $request->method() === 'POST' &&
                            (string) $request->headers()->get('content-type') === 'Content-Type: application/json' &&
                            (string) $request->body() === Json::encode([
                                'email' => $email,
                                'password' => $password,
                                'expires' => false,
                            ]);
                    }))
                    ->willReturn($response = $this->createMock(Response::class));
                $response
                    ->expects($this->once())
                    ->method('body')
                    ->willReturn(new StringStream(<<<JSON
{
  "token": {
    "creation_date": "2014-05-22T08:05:57.556385+00:00",
    "expires": null,
    "id": "9de8f869-c58e-4aa3-9208-2d4eaff5fa20",
    "inherits_user_perms": true,
    "permissions": [],
    "roles": {
      "organization": null,
      "role": null
    },
    "user_id": "5bea0358-db40-429e-bd82-914686a7e7b9"
  }
}
JSON
                    ));

                $token = $tokens->create(NewToken::permanent($email, $password));

                $this->assertInstanceOf(Token::class, $token);
                $this->assertSame('9de8f869-c58e-4aa3-9208-2d4eaff5fa20', (string) $token->id());
                $this->assertSame('5bea0358-db40-429e-bd82-914686a7e7b9', (string) $token->user());
                $this->assertSame('2014-05-22T08:05:57+00:00', $token->createdAt()->format(new ISO8601));
                $this->assertFalse($token->expires());
            });
    }

    public function testCreateTemporaryToken()
    {
        $this
            ->forAll(Generator\string(), Generator\string(), Generator\string())
            ->then(function($email, $password, $twofa): void {
                $tokens = new Http(
                    $http = $this->createMock(Transport::class),
                    new Earth(new UTC)
                );
                $http
                    ->expects($this->once())
                    ->method('__invoke')
                    ->with($this->callback(static function($request) use ($email, $password, $twofa): bool {
                        return (string) $request->url() === 'https://account.scaleway.com/tokens' &&
                            (string) $request->method() === 'POST' &&
                            (string) $request->headers()->get('content-type') === 'Content-Type: application/json' &&
                            (string) $request->body() === Json::encode([
                                'email' => $email,
                                'password' => $password,
                                'expires' => true,
                                '2FA_token' => $twofa,
                            ]);
                    }))
                    ->willReturn($response = $this->createMock(Response::class));
                $response
                    ->expects($this->once())
                    ->method('body')
                    ->willReturn(new StringStream(<<<JSON
{
  "token": {
    "creation_date": "2014-05-22T08:05:57.556385+00:00",
    "expires": "2014-05-22T09:05:57.556385+00:00",
    "id": "9de8f869-c58e-4aa3-9208-2d4eaff5fa20",
    "inherits_user_perms": true,
    "permissions": [],
    "roles": {
      "organization": null,
      "role": null
    },
    "user_id": "5bea0358-db40-429e-bd82-914686a7e7b9"
  }
}
JSON
                    ));

                $token = $tokens->create(NewToken::temporary($email, $password, $twofa));

                $this->assertInstanceOf(Token::class, $token);
                $this->assertSame('9de8f869-c58e-4aa3-9208-2d4eaff5fa20', (string) $token->id());
                $this->assertSame('5bea0358-db40-429e-bd82-914686a7e7b9', (string) $token->user());
                $this->assertSame('2014-05-22T08:05:57+00:00', $token->createdAt()->format(new ISO8601));
                $this->assertTrue($token->expires());
                $this->assertSame('2014-05-22T09:05:57+00:00', $token->expiresAt()->format(new ISO8601));
            });
        $this
            ->forAll(Generator\string(), Generator\string())
            ->then(function($email, $password): void {
                $tokens = new Http(
                    $http = $this->createMock(Transport::class),
                    new Earth(new UTC)
                );
                $http
                    ->expects($this->once())
                    ->method('__invoke')
                    ->with($this->callback(static function($request) use ($email, $password): bool {
                        return (string) $request->url() === 'https://account.scaleway.com/tokens' &&
                            (string) $request->method() === 'POST' &&
                            (string) $request->headers()->get('content-type') === 'Content-Type: application/json' &&
                            (string) $request->body() === Json::encode([
                                'email' => $email,
                                'password' => $password,
                                'expires' => true,
                            ]);
                    }))
                    ->willReturn($response = $this->createMock(Response::class));
                $response
                    ->expects($this->once())
                    ->method('body')
                    ->willReturn(new StringStream(<<<JSON
{
  "token": {
    "creation_date": "2014-05-22T08:05:57.556385+00:00",
    "expires": "2014-05-22T09:05:57.556385+00:00",
    "id": "9de8f869-c58e-4aa3-9208-2d4eaff5fa20",
    "inherits_user_perms": true,
    "permissions": [],
    "roles": {
      "organization": null,
      "role": null
    },
    "user_id": "5bea0358-db40-429e-bd82-914686a7e7b9"
  }
}
JSON
                    ));

                $token = $tokens->create(NewToken::temporary($email, $password));

                $this->assertInstanceOf(Token::class, $token);
                $this->assertSame('9de8f869-c58e-4aa3-9208-2d4eaff5fa20', (string) $token->id());
                $this->assertSame('5bea0358-db40-429e-bd82-914686a7e7b9', (string) $token->user());
                $this->assertSame('2014-05-22T08:05:57+00:00', $token->createdAt()->format(new ISO8601));
                $this->assertTrue($token->expires());
                $this->assertSame('2014-05-22T09:05:57+00:00', $token->expiresAt()->format(new ISO8601));
            });
    }
}