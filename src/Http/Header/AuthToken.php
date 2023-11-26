<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Http\Header;

use Innmind\ScalewaySdk\Token\Id;
use Innmind\Http\{
    Header\Header,
    Header\Value\Value,
};

final class AuthToken
{
    public static function of(Id $id): Header
    {
        return new Header('X-Auth-Token', new Value($id->toString()));
    }
}
