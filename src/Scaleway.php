<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

interface Scaleway
{
    public function tokens(): Tokens;
    public function authenticated(Token\Id $token): Authenticated;
}
