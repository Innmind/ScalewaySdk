<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated;

use Innmind\ScalewaySdk\Token;
use Innmind\Immutable\Set;

interface Tokens
{
    /**
     * @return Set<Token>
     */
    public function list(): Set;
    public function get(Token\Id $id): Token;
    public function remove(Token\Id $id): void;
}
