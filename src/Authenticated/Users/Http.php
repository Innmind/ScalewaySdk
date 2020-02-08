<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated\Users;

use Innmind\ScalewaySdk\{
    Authenticated\Users,
    Token,
    User,
    Organization,
    Http\Header\AuthToken,
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
use Innmind\Immutable\Set;

final class Http implements Users
{
    private Transport $fulfill;
    private Token\Id $token;

    public function __construct(
        Transport $fulfill,
        Token\Id $token
    ) {
        $this->fulfill = $fulfill;
        $this->token = $token;
    }

    public function get(User\Id $id): User
    {
        $response = ($this->fulfill)(new Request(
            Url::of("https://account.scaleway.com/users/$id"),
            Method::get(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token)
            )
        ));

        $user = Json::decode($response->body()->toString())['user'];
        $keys = Set::of(User\SshKey::class);
        $organizations = Set::of(Organization\Id::class);

        foreach ($user['ssh_public_keys'] ?? [] as $key) {
            $keys = ($keys)(new User\SshKey(
                $key['key'],
                $key['description']
            ));
        }

        foreach ($user['organizations'] ?? [] as $organization) {
            $organizations = ($organizations)(new Organization\Id(
                $organization['id']
            ));
        }

        return new User(
            new User\Id($user['id']),
            $user['email'],
            $user['firstname'],
            $user['lastname'],
            $user['fullname'],
            $keys,
            $organizations
        );
    }

    public function updateSshKeys(User\Id $id, User\SshKey ...$keys): void
    {
        ($this->fulfill)(new Request(
            Url::of("https://account.scaleway.com/users/$id"),
            Method::patch(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new AuthToken($this->token),
                ContentType::of('application', 'json'),
            ),
            Stream::ofContent(Json::encode([
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
