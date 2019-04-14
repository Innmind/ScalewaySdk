<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Http\Header;

use Innmind\ScalewaySdk\Token\Id;
use Innmind\Http\{
    Header\Header,
    Header\Value\Value,
};

final class AuthToken extends Header
{
    public function __construct(Id $id)
    {
        parent::__construct('X-Auth-Token', new Value((string) $id));
    }
}
