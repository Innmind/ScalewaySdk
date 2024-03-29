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
    Request,
    Method,
    ProtocolVersion,
    Headers,
    Header\ContentType,
};
use Innmind\Filesystem\File\Content;
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\Immutable\Set;

final class Http implements Users
{
    private Transport $fulfill;
    private Token\Id $token;

    public function __construct(
        Transport $fulfill,
        Token\Id $token,
    ) {
        $this->fulfill = $fulfill;
        $this->token = $token;
    }

    public function get(User\Id $id): User
    {
        $response = ($this->fulfill)(Request::of(
            Url::of("https://account.scaleway.com/users/{$id->toString()}"),
            Method::get,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
            ),
        ))->match(
            static fn($success) => $success->response(),
            static fn() => throw new \RuntimeException,
        );

        /** @var array{user: array{id: string, email: string, firstname: string, lastname: string, fullname: string, ssh_public_keys?: list<array{key: string, description: string|null}>, organizations?: list<array{id: string}>}} */
        $body = Json::decode($response->body()->toString());
        $user = $body['user'];
        /** @var Set<User\SshKey> */
        $keys = Set::of();
        /** @var Set<Organization\Id> */
        $organizations = Set::of();

        foreach ($user['ssh_public_keys'] ?? [] as $key) {
            $keys = ($keys)(new User\SshKey(
                $key['key'],
                $key['description'],
            ));
        }

        foreach ($user['organizations'] ?? [] as $organization) {
            $organizations = ($organizations)(new Organization\Id(
                $organization['id'],
            ));
        }

        return new User(
            new User\Id($user['id']),
            $user['email'],
            $user['firstname'],
            $user['lastname'],
            $user['fullname'],
            $keys,
            $organizations,
        );
    }

    public function updateSshKeys(User\Id $id, User\SshKey ...$keys): void
    {
        /** @psalm-suppress InvalidArgument */
        ($this->fulfill)(Request::of(
            Url::of("https://account.scaleway.com/users/{$id->toString()}"),
            Method::patch,
            ProtocolVersion::v20,
            Headers::of(
                AuthToken::of($this->token),
                ContentType::of('application', 'json'),
            ),
            Content::ofString(Json::encode([
                'ssh_public_keys' => \array_map(static function($key): array {
                    return [
                        'key' => $key->key(),
                        'description' => $key->hasDescription() ? $key->description() : null,
                    ];
                }, $keys),
            ])),
        ))->match(
            static fn() => null,
            static fn() => throw new \RuntimeException,
        );
    }
}
