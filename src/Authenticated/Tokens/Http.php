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
    Message\Request\Request,
    Message\Method,
    ProtocolVersion,
    Headers,
    Header\LinkValue,
};
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\Immutable\Set;
use function Innmind\Immutable\first;

final class Http implements Tokens
{
    private Transport $fulfill;
    private Clock $clock;
    private Token\Id $token;

    public function __construct(
        Transport $fulfill,
        Clock $clock,
        Token\Id $token
    ) {
        $this->fulfill = $fulfill;
        $this->clock = $clock;
        $this->token = $token;
    }

    /**
     * {@inheritdoc}
     */
    public function list(): Set
    {
        $url = Url::of('https://account.scaleway.com/tokens');
        $tokens = [];

        do {
            $response = ($this->fulfill)(new Request(
                $url,
                Method::get(),
                new ProtocolVersion(2, 0),
                Headers::of(
                    new AuthToken($this->token)
                )
            ));

            $tokens = \array_merge(
                $tokens,
                Json::decode($response->body()->toString())['tokens']
            );
            $next = null;

            if ($response->headers()->contains('Link')) {
                $next = $response
                    ->headers()
                    ->get('Link')
                    ->values()
                    ->filter(static function(LinkValue $link): bool {
                        return $link->relationship() === 'next';
                    });

                if ($next->size() === 1) {
                    $next = $url
                        ->withPath(first($next)->url()->path())
                        ->withQuery(first($next)->url()->query());
                    $url = $next;
                }
            }
        } while ($next instanceof Url);

        $set = Set::of(Token::class);

        foreach ($tokens as $token) {
            $set = ($set)($this->decode($token));
        }

        return $set;
    }

    public function get(Token\Id $id): Token
    {
        $response = ($this->fulfill)(new Request(
            Url::of("https://account.scaleway.com/tokens/{$id->toString()}"),
            Method::get(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token)
            )
        ));

        $token = Json::decode($response->body()->toString())['token'];

        return $this->decode($token);
    }

    public function remove(Token\Id $id): void
    {
        ($this->fulfill)(new Request(
            Url::of("https://account.scaleway.com/tokens/{$id->toString()}"),
            Method::delete(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token)
            )
        ));
    }

    private function decode(array $token): Token
    {
        return new Token(
            new Token\Id($token['id']),
            new User\Id($token['user_id']),
            $this->clock->at($token['creation_date']),
            \is_string($token['expires']) ? $this->clock->at($token['expires']) : null
        );
    }
}
