<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated;

use Innmind\ScalewaySdk\User;

interface Users
{
    public function get(User\Id $id): User;
    public function updateSshKeys(User\Id $id, User\SshKey ...$keys): void;
}
