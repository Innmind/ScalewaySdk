<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated\Users;

use Innmind\ScalewaySdk\{
    Authenticated\Users,
    Token,
    User,
    Http\Header\AuthToken,
};
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method\Method,
    ProtocolVersion\ProtocolVersion,
    Headers\Headers,
    Header\ContentType,
    Header\ContentTypeValue,
};
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Json\Json;
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\Set;

final class Http implements Users
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

    public function get(User\Id $id): User
    {
        $response = ($this->fulfill)(new Request(
            Url::fromString("https://account.scaleway.com/users/$id"),
            Method::get(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token)
            )
        ));

        $user = Json::decode((string) $response->body())['user'];
        $keys = Set::of(User\SshKey::class);

        foreach ($user['ssh_public_keys'] ?? [] as $key) {
            $keys = $keys->add(new User\SshKey(
                $key['key'],
                $key['description']
            ));
        }

        return new User(
            new User\Id($user['id']),
            $user['email'],
            $user['firstname'],
            $user['lastname'],
            $user['fullname'],
            $keys
        );
    }

    public function updateSshKeys(User\Id $id, User\SshKey ...$keys): void
    {
        ($this->fulfill)(new Request(
            Url::fromString("https://account.scaleway.com/users/$id"),
            Method::patch(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
                new ContentType(
                    new ContentTypeValue('application', 'json')
                )
            ),
            new StringStream(Json::encode([
                'ssh_public_keys' => \array_map(static function($key): array {
                    return [
                        'key' => $key->key(),
                        'description' => $key->hasDescription() ? $key->description() : null,
                    ];
                }, $keys),
            ]))
        ));
    }
}
