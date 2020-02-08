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
    Message\Request\Request,
    Message\Method,
    ProtocolVersion,
    Headers,
    Header\ContentType,
};
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\Stream\Readable\Stream;
use Innmind\TimeContinuum\Clock;

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

        $response = ($this->fulfill)(new Request(
            Url::of('https://account.scaleway.com/tokens'),
            Method::post(),
            new ProtocolVersion(2, 0),
            Headers::of(
                ContentType::of('application', 'json'),
            ),
            Stream::ofContent(Json::encode($payload)),
        ));

        $data = Json::decode($response->body()->toString())['token'];

        return new Token(
            new Token\Id($data['id']),
            new User\Id($data['user_id']),
            $this->clock->at($data['creation_date']),
            \is_string($data['expires']) ? $this->clock->at($data['expires']) : null
        );
    }
}
