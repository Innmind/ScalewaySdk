<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\User;

use Innmind\ScalewaySdk\User\SshKey;
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait,
};

class Test extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this
            ->forAll(Generator\string(), Generator\string())
            ->then(function($key, $description): void {
                $sshKey = new SshKey($key, $description);

                $this->assertSame($key, $sshKey->key());
                $this->assertTrue($sshKey->hasDescription());
                $this->assertSame($description, $sshKey->description());
            });
    }

    public function testDescriptionIsNullable()
    {
        $this
            ->forAll(Generator\string())
            ->then(function($key): void {
                $sshKey = new SshKey($key);

                $this->assertFalse($sshKey->hasDescription());

                try {
                    $sshKey->description();
                    $this->fail('it should throw');
                } catch (\TypeError $e) {
                    // pass
                }
            });
    }
}
