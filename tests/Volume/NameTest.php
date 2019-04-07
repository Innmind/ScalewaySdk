<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Volume;

use Innmind\ScalewaySdk\{
    Volume\Name,
    Exception\DomainException,
};
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
            ->minimumEvaluationRatio(0.01)
            ->forAll(Generator\string(), Generator\pos())
            ->when(static function($string): bool {
                return (bool) preg_match('~^[a-zA-Z]+$~', $string);
            })
            ->then(function($string, $int): void {
                $this->assertSame(
                    "$string-$int",
                    (string) new Name("$string-$int")
                );
            });
    }

    public function testThrowWhenInvalidString()
    {
        $this
            ->minimumEvaluationRatio(0.01)
            ->forAll(Generator\string())
            ->when(static function($string): bool {
                return !((bool) preg_match('~^[a-zA-Z0-9\-]+$~', $string));
            })
            ->then(function($string): void {
                $this->expectException(DomainException::class);
                $this->expectExceptionMessage($string);

                new Name($string);
            });
    }
}