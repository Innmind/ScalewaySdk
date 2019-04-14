<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\Tokens\NewToken;

interface Tokens
{
    public function create(NewToken $token): Token;
}
