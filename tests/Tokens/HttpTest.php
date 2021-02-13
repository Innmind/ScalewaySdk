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
use Innmind\TimeContinuum\Earth\{
    Clock,
    Timezone\UTC,
    Format\ISO8601,
};
use Innmind\Json\Json;
use Innmind\Http\Message\Response;
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class HttpTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Tokens::class,
            new Http(
                $this->createMock(Transport::class),
                new Clock(new UTC)
            )
        );
    }

    public function testCreatePermanentToken()
    {
        $this
            ->forAll($this->string(), $this->string(), $this->string())
            ->then(function($email, $password, $twofa): void {
                $tokens = new Http(
                    $http = $this->createMock(Transport::class),
                    new Clock(new UTC)
                );
                $http
                    ->expects($this->once())
                    ->method('__invoke')
                    ->with($this->callback(static function($request) use ($email, $password, $twofa): bool {
                        return $request->url()->toString() === 'https://account.scaleway.com/tokens' &&
                            $request->method()->toString() === 'POST' &&
                            $request->headers()->get('content-type')->toString() === 'Content-Type: application/json' &&
                            $request->body()->toString() === Json::encode([
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
                    ->willReturn(Stream::ofContent(<<<JSON
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
                $this->assertSame('9de8f869-c58e-4aa3-9208-2d4eaff5fa20', $token->id()->toString());
                $this->assertSame('5bea0358-db40-429e-bd82-914686a7e7b9', $token->user()->toString());
                $this->assertSame('2014-05-22T08:05:57+00:00', $token->createdAt()->format(new ISO8601));
                $this->assertFalse($token->expires());
            });
        $this
            ->forAll($this->string(), $this->string())
            ->then(function($email, $password): void {
                $tokens = new Http(
                    $http = $this->createMock(Transport::class),
                    new Clock(new UTC)
                );
                $http
                    ->expects($this->once())
                    ->method('__invoke')
                    ->with($this->callback(static function($request) use ($email, $password): bool {
                        return $request->url()->toString() === 'https://account.scaleway.com/tokens' &&
                            $request->method()->toString() === 'POST' &&
                            $request->headers()->get('content-type')->toString() === 'Content-Type: application/json' &&
                            $request->body()->toString() === Json::encode([
                                'email' => $email,
                                'password' => $password,
                                'expires' => false,
                            ]);
                    }))
                    ->willReturn($response = $this->createMock(Response::class));
                $response
                    ->expects($this->once())
                    ->method('body')
                    ->willReturn(Stream::ofContent(<<<JSON
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
                $this->assertSame('9de8f869-c58e-4aa3-9208-2d4eaff5fa20', $token->id()->toString());
                $this->assertSame('5bea0358-db40-429e-bd82-914686a7e7b9', $token->user()->toString());
                $this->assertSame('2014-05-22T08:05:57+00:00', $token->createdAt()->format(new ISO8601));
                $this->assertFalse($token->expires());
            });
    }

    public function testCreateTemporaryToken()
    {
        $this
            ->forAll($this->string(), $this->string(), $this->string())
            ->then(function($email, $password, $twofa): void {
                $tokens = new Http(
                    $http = $this->createMock(Transport::class),
                    new Clock(new UTC)
                );
                $http
                    ->expects($this->once())
                    ->method('__invoke')
                    ->with($this->callback(static function($request) use ($email, $password, $twofa): bool {
                        return $request->url()->toString() === 'https://account.scaleway.com/tokens' &&
                            $request->method()->toString() === 'POST' &&
                            $request->headers()->get('content-type')->toString() === 'Content-Type: application/json' &&
                            $request->body()->toString() === Json::encode([
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
                    ->willReturn(Stream::ofContent(<<<JSON
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
                $this->assertSame('9de8f869-c58e-4aa3-9208-2d4eaff5fa20', $token->id()->toString());
                $this->assertSame('5bea0358-db40-429e-bd82-914686a7e7b9', $token->user()->toString());
                $this->assertSame('2014-05-22T08:05:57+00:00', $token->createdAt()->format(new ISO8601));
                $this->assertTrue($token->expires());
                $this->assertSame('2014-05-22T09:05:57+00:00', $token->expiresAt()->format(new ISO8601));
            });
        $this
            ->forAll($this->string(), $this->string())
            ->then(function($email, $password): void {
                $tokens = new Http(
                    $http = $this->createMock(Transport::class),
                    new Clock(new UTC)
                );
                $http
                    ->expects($this->once())
                    ->method('__invoke')
                    ->with($this->callback(static function($request) use ($email, $password): bool {
                        return $request->url()->toString() === 'https://account.scaleway.com/tokens' &&
                            $request->method()->toString() === 'POST' &&
                            $request->headers()->get('content-type')->toString() === 'Content-Type: application/json' &&
                            $request->body()->toString() === Json::encode([
                                'email' => $email,
                                'password' => $password,
                                'expires' => true,
                            ]);
                    }))
                    ->willReturn($response = $this->createMock(Response::class));
                $response
                    ->expects($this->once())
                    ->method('body')
                    ->willReturn(Stream::ofContent(<<<JSON
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
                $this->assertSame('9de8f869-c58e-4aa3-9208-2d4eaff5fa20', $token->id()->toString());
                $this->assertSame('5bea0358-db40-429e-bd82-914686a7e7b9', $token->user()->toString());
                $this->assertSame('2014-05-22T08:05:57+00:00', $token->createdAt()->format(new ISO8601));
                $this->assertTrue($token->expires());
                $this->assertSame('2014-05-22T09:05:57+00:00', $token->expiresAt()->format(new ISO8601));
            });
    }

    private function string(): Set
    {
        return Set\Decorate::immutable(
            static fn($chars) => \implode('', $chars),
            Set\Sequence::of(
                Set\Decorate::immutable(
                    static fn($ord) => \chr($ord),
                    Set\Integers::between(33, 126), // ascii
                ),
            ),
        );
    }
}
