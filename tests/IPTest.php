<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\{
    IP,
    Organization,
    Server,
};
use Innmind\IP\IP as Address;
use PHPUnit\Framework\TestCase;

class IPTest extends TestCase
{
    public function testIPattachedToAServer()
    {
        $ip = new IP(
            $id = new IP\Id('8f7ad8e0-c9e3-4705-afb4-bf4ce11e6f90'),
            $address = $this->createMock(Address::class),
            $organization = new Organization\Id('2ac913be-006c-4b76-b60e-86ec822bcdda'),
            $server = new Server\Id('37462fcb-8744-4567-98a4-a239edf5758d')
        );

        $this->assertSame($id, $ip->id());
        $this->assertSame($address, $ip->address());
        $this->assertSame($organization, $ip->organization());
        $this->assertTrue($ip->attachedToAServer());
        $this->assertSame($server, $ip->server());
    }
}
