<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated\Tokens;

use Innmind\ScalewaySdk\{
    Authenticated\Tokens,
    Token,
    User,
    Http\Header\AuthToken,
};
use Innmind\TimeContinuum\Clock;
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Request,
    Method,
    ProtocolVersion,
    Headers,
    Header\Link,
    Header\LinkValue,
};
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\Immutable\{
    Set,
    Maybe,
    Predicate\Instance,
};

final class Http implements Tokens
{
    private Transport $fulfill;
    private Clock $clock;
    private Token\Id $token;

    public function __construct(
        Transport $fulfill,
        Clock $clock,
        Token\Id $token,
    ) {
        $this->fulfill = $fulfill;
        $this->clock = $clock;
        $this->token = $token;
    }

    public function list(): Set
    {
        $url = Url::of('https://account.scaleway.com/tokens');
        /** @var list<array{id: string, user_id: string, creation_date: string, expires: string|null}> */
        $tokens = [];

        do {
            $response = ($this->fulfill)(Request::of(
                $url,
                Method::get,
                ProtocolVersion::v20,
                Headers::of(
                    AuthToken::of($this->token),
                ),
            ))->match(
                static fn($success) => $success->response(),
                static fn() => throw new \RuntimeException,
            );

            /** @var array{tokens: list<array{id: string, user_id: string, creation_date: string, expires: string|null}>} */
            $body = Json::decode($response->body()->toString());
            $tokens = \array_merge($tokens, $body['tokens']);

            $url = $response
                ->headers()
                ->find(Link::class)
                ->flatMap(
                    static fn($header) => $header
                        ->values()
                        ->keep(Instance::of(LinkValue::class))
                        ->find(static fn($link) => $link->relationship() === 'next'),
                )
                ->map(
                    static fn($link) => $url
                        ->withPath($link->url()->path())
                        ->withQuery($link->url()->query()),
                )
                ->match(
                    static fn($next) => $next,
                    static fn() => null,
                );
        } while ($url instanceof Url);

        /** @var Set<Token> */
        return Set::of(...$tokens)->map($this->decode(...));
    }

    public function get(Token\Id $id): Token
    {
        $response = ($this->fulfill)(Request::of(
            Url::of("https://account.scaleway.com/tokens/{$id->toString()}"),
            Method::get,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
            ),
        ))->match(
            static fn($success) => $success->response(),
            static fn() => throw new \RuntimeException,
        );

        /** @var array{token: array{id: string, user_id: string, creation_date: string, expires: string|null}} */
        $body = Json::decode($response->body()->toString());

        return $this->decode($body['token']);
    }

    public function remove(Token\Id $id): void
    {
        ($this->fulfill)(Request::of(
            Url::of("https://account.scaleway.com/tokens/{$id->toString()}"),
            Method::delete,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
            ),
        ))->match(
            static fn() => null,
            static fn() => throw new \RuntimeException,
        );
    }

    /**
     * @param array{id: string, user_id: string, creation_date: string, expires: string|null} $token
     */
    private function decode(array $token): Token
    {
        return new Token(
            new Token\Id($token['id']),
            new User\Id($token['user_id']),
            $this->clock->at($token['creation_date'])->match(
                static fn($point) => $point,
                static fn() => throw new \RuntimeException,
            ),
            Maybe::of($token['expires'])
                ->flatMap($this->clock->at(...))
                ->match(
                    static fn($point) => $point,
                    static fn() => null,
                ),
        );
    }
}
