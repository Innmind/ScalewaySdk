<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Authenticated;

use Innmind\ScalewaySdk\{
    Server,
    Organization,
    Image,
    IP,
};
use Innmind\Immutable\Set;

interface Servers
{
    public function create(
        Server\Name $name,
        Organization\Id $organization,
        Image\Id $image,
        IP\Id $ip,
        string ...$tags,
    ): Server;

    /**
     * @return Set<Server>
     */
    public function list(): Set;
    public function get(Server\Id $id): Server;
    public function remove(Server\Id $id): void;
    public function execute(Server\Id $id, Server\Action $action): void;
}
