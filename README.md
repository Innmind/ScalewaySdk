# Scaleway SDK (unofficial)

[![Build Status](https://github.com/Innmind/ScalewaySdk/workflows/CI/badge.svg?branch=master)](https://github.com/Innmind/ScalewaySdk/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/Innmind/ScalewaySdk/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/ScalewaySdk)
[![Type Coverage](https://shepherd.dev/github/Innmind/ScalewaySdk/coverage.svg)](https://shepherd.dev/github/Innmind/ScalewaySdk)

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
use function Innmind\Immutable\{
    first,
    unwrap,
};

$os = Factory::build();
$sdk = bootstrap($os->remote()->http(), $os->clock());
$token = $sdk
    ->tokens()
    ->create(NewToken::temporary(
        'foo@example.com',
        'some secret password',
        '2FACOD', // if 2FA enabled on your account
    ));
$organizations = $sdk
    ->authenticated($token->id())
    ->users()
    ->get($token->user())
    ->organizations();
$organization = first($organizations);
$servers = $sdk
    ->authenticated($token->id())
    ->servers(Region::paris1());
$chooseImage = new ChooseImage(
    ...unwrap($sdk
        ->authenticated($token->id())
        ->marketplace()
        ->images()
        ->list()),
);
$server = $servers->create(
    new Server\Name('my-server'),
    $organization,
    $chooseImage(
        Region::paris1(),
        new Marketplace\Image\Name('CentOS 7.6'),
        new Marketplace\Product\Server\Name('GP1-XS'),
    ),
    $servers
        ->authenticated($token->id())
        ->ips(Region::paris1())
        ->create($organization),
);
$servers->execute(
    $server->id(),
    Server\Action::powerOn(),
);
```

This example creates a new CentOS machine. To review all the capabilities of this SDK take a look at the interfaces in the [`Authenticated` directory](src/Authenticated).
