<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\{
    Server,
    Organization,
    Image,
    IP,
    Volume,
};
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase
{
    public function testInterface()
    {
        $server = new Server(
            $id = new Server\Id('039aafa0-e8d6-40d5-9db5-7f2b6ed443d7'),
            $organization = new Organization\Id('32798ad1-7d52-4c3d-ba9d-6a93bfbd2283'),
            'foo',
            $image = new Image\Id('7e0d1343-c2b4-4a72-85a7-7ef6f63a28e7'),
            $ip = new IP\Id('6fb83d24-6a2a-4c76-8304-7b9212b40865'),
            $state = Server\State::running(),
            $tags = Set::of('string'),
            $volumes = Set::of(Volume\Id::class)
        );

        $this->assertSame($id, $server->id());
        $this->assertSame($organization, $server->organization());
        $this->assertSame('foo', $server->name());
        $this->assertSame($image, $server->image());
        $this->assertSame($ip, $server->ip());
        $this->assertSame($state, $server->state());
        $this->assertSame($tags, $server->tags());
        $this->assertSame($volumes, $server->volumes());
    }
}
