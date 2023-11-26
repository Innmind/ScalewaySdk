<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Tokens;

use Innmind\ScalewaySdk\{
    Tokens,
    Token,
    User,
};
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Request,
    Method,
    ProtocolVersion,
    Headers,
    Header\ContentType,
};
use Innmind\Filesystem\File\Content;
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\TimeContinuum\Clock;
use Innmind\Immutable\Maybe;

final class Http implements Tokens
{
    private Transport $fulfill;
    private Clock $clock;

    public function __construct(Transport $fulfill, Clock $clock)
    {
        $this->fulfill = $fulfill;
        $this->clock = $clock;
    }

    public function create(NewToken $token): Token
    {
        $payload = [
            'email' => $token->email(),
            'password' => $token->password(),
            'expires' => $token->expires(),
        ];

        if ($token->hasTwoFaToken()) {
            $payload['2FA_token'] = $token->twoFaToken();
        }

        /** @psalm-suppress InvalidArgument */
        $response = ($this->fulfill)(Request::of(
            Url::of('https://account.scaleway.com/tokens'),
            Method::post,
            ProtocolVersion::v20,
            Headers::of(
                ContentType::of('application', 'json'),
            ),
            Content::ofString(Json::encode($payload)),
        ))->match(
            static fn($success) => $success->response(),
            static fn() => throw new \RuntimeException,
        );

        /** @var array{token: array{id: string, user_id: string, creation_date: string, expires: string|null}} */
        $body = Json::decode($response->body()->toString());
        $data = $body['token'];

        return new Token(
            new Token\Id($data['id']),
            new User\Id($data['user_id']),
            $this->clock->at($data['creation_date'])->match(
                static fn($point) => $point,
                static fn() => throw new \LogicException,
            ),
            Maybe::of($data['expires'])
                ->flatMap($this->clock->at(...))
                ->match(
                    static fn($point) => $point,
                    static fn() => null,
                ),
        );
    }
}
