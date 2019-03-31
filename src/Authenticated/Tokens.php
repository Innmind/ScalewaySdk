<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated;

use Innmind\ScalewaySdk\Token;
use Innmind\Immutable\SetInterface;

interface Tokens
{
    /**
     * @return SetInterface<Token>
     */
    public function list(): SetInterface;
    public function get(Token\Id $id): Token;
    public function remove(Token\Id $id): void;
}