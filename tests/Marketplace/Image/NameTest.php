<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Marketplace\Image;

use Innmind\ScalewaySdk\Marketplace\Image\Name;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class NameTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function($string): void {
                $this->assertSame(
                    $string,
                    (new Name($string))->toString(),
                );
            });
    }
}
