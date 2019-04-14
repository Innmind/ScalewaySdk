# Scaleway SDK (unofficial)

| `develop` |
|-----------|
| [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/ScalewaySdk/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/ScalewaySdk/?branch=develop) |
| [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/ScalewaySdk/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/ScalewaySdk/?branch=develop) |
| [![Build Status](https://scrutinizer-ci.com/g/Innmind/ScalewaySdk/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/ScalewaySdk/build-status/develop) |

This is a sdk for the [Scaleway](https://scaleway.com/) API.

## Installation

```sh
composer require innmind/scaleway-sdk
```

## Usage

```php
use Innmind\OperatingSystem\Factory;
use function Innmind\ScalewaySdk\bootstrap;
use Innmind\ScalewaySdk\{
    Tokens\NewToken,
    Region,
    Server,
    Marketplace,
    ChooseImage,
};

$os = Factory::build();
$sdk = bootstrap($os->remote()->http(), $os->clock());
$token = $sdk
    ->tokens()
    ->create(NewToken::temporary(
        'foo@example.com',
        'some secret password',
        '2FACOD' // is 2FA enabled on your account
    ));
$organization = $sdk
    ->authenticated($token->id())
    ->users()
    ->get($token->user())
    ->organizations()
    ->current();
$servers = $sdk
    ->authenticated($token->id())
    ->servers(Region::paris1());
$chooseImage = new ChooseImage(
    ...$sdk
        ->authenticated($token->id())
        ->marketplace()
        ->images()
        ->list()
);
$server = $servers->create(
    new Server\Name('my-server'),
    $organization,
    $chooseImage(
        Region::paris1(),
        new Marketplace\Image\Name('CentOS 7.6'),
        new Marketplace\Product\Server\Name('GP1-XS')
    )
);
$servers->execute(
    $server->id(),
    Server\Action::powerOn()
);
```

This example creates a new CentOS machine. To review all the capabilities of this SDK take a look at the interfaces in the [`Authenticated` directory](src/Authenticated).
