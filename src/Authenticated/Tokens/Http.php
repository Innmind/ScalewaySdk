<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated\Tokens;

use Innmind\ScalewaySdk\{
    Authenticated\Tokens,
    Token,
    User,
};
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method\Method,
    ProtocolVersion\ProtocolVersion,
    Headers\Headers,
    Header\Header,
    Header\Value\Value,
    Header\LinkValue,
};
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Json\Json;
use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class Http implements Tokens
{
    private $fulfill;
    private $clock;
    private $token;

    public function __construct(
        Transport $fulfill,
        TimeContinuumInterface $clock,
        Token\Id $token
    ) {
        $this->fulfill = $fulfill;
        $this->clock = $clock;
        $this->token = $token;
    }

    /**
     * {@inheritdoc}
     */
    public function list(): SetInterface
    {
        $url = Url::fromString('https://account.scaleway.com/tokens');
        $tokens = [];

        do {
            $response = ($this->fulfill)(new Request(
                $url,
                Method::get(),
                new ProtocolVersion(2, 0),
                Headers::of(
                    new Header(
                        'X-Auth-Token',
                        new Value((string) $this->token)
                    )
                )
            ));

            $tokens = \array_merge(
                $tokens,
                Json::decode((string) $response->body())['tokens']
            );
            $next = null;

            if ($response->headers()->has('Link')) {
                $next = $response
                    ->headers()
                    ->get('Link')
                    ->values()
                    ->filter(static function(LinkValue $link): bool {
                        return $link->relationship() === 'next';
                    });

                if ($next->size() === 1) {
                    $next = $url
                        ->withPath($next->current()->url()->path())
                        ->withQuery($next->current()->url()->query());
                    $url = $next;
                }
            }
        } while ($next instanceof UrlInterface);

        $set = Set::of(Token::class);

        foreach ($tokens as $token) {
            $set = $set->add($this->decode($token));
        }

        return $set;
    }

    public function get(Token\Id $id): Token
    {
        $response = ($this->fulfill)(new Request(
            Url::fromString("https://account.scaleway.com/tokens/$id"),
            Method::get(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new Header(
                    'X-Auth-Token',
                    new Value((string) $this->token)
                )
            )
        ));

        $token = Json::decode((string) $response->body())['token'];

        return $this->decode($token);
    }

    public function remove(Token\Id $id): void
    {
        ($this->fulfill)(new Request(
            Url::fromString("https://account.scaleway.com/tokens/$id"),
            Method::delete(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new Header(
                    'X-Auth-Token',
                    new Value((string) $this->token)
                )
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
