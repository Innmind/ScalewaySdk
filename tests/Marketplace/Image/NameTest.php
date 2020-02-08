<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Marketplace\Image;

use Innmind\ScalewaySdk\Marketplace\Image\Name;
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait,
};

class NameTest extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this
            ->forAll(Generator\string())
            ->then(function($string): void {
                $this->assertSame(
                    $string,
                    (new Name($string))->toString()
                );
            });
    }
}
