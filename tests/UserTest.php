<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\{
    User,
    User\Id,
    User\SshKey,
    Organization,
};
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set as DataSet,
};

class UserTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this
            ->forAll(DataSet\Strings::any(), DataSet\Strings::any(), DataSet\Strings::any(), DataSet\Strings::any())
            ->then(function($email, $firstname, $lastname, $fullname): void {
                $user = new User(
                    $id = new User\Id('5bea0358-db40-429e-bd82-914686a7e7b9'),
                    $email,
                    $firstname,
                    $lastname,
                    $fullname,
                    $keys = Set::of(SshKey::class),
                    $organizations = Set::of(Organization\Id::class)
                );

                $this->assertSame($id, $user->id());
                $this->assertSame($email, $user->email());
                $this->assertSame($firstname, $user->firstname());
                $this->assertSame($lastname, $user->lastname());
                $this->assertSame($fullname, $user->fullname());
                $this->assertSame($keys, $user->sshKeys());
                $this->assertSame($organizations, $user->organizations());
            });
    }
}
