<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated;

use Innmind\ScalewaySdk\{
    IP,
    Organization,
    Server,
};
use Innmind\Immutable\Set;

interface IPs
{
    public function create(Organization\Id $organization): IP;

    /**
     * @return Set<IP>
     */
    public function list(): Set;
    public function get(IP\Id $id): IP;
    public function remove(IP\Id $id): void;
    public function attach(IP\Id $id, Server\Id $server): IP;
}
