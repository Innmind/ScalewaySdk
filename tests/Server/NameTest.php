<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Server;

use Innmind\ScalewaySdk\{
    Server\Name,
    Exception\DomainException,
};
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
            ->forAll(
                Set\Decorate::immutable(
                    static fn($chars) => \implode('', $chars),
                    Set\Sequence::of(
                        Set\Decorate::immutable(
                            static fn($ord) => \chr($ord),
                            new Set\Either(
                                Set\Integers::between(65, 90), // A-Z
                                Set\Integers::between(97, 122), // a-z
                                Set\Elements::of(46), // .
                            ),
                        ),
                        Set\Integers::between(1, 50),
                    ),
                ),
                Set\Integers::above(0),
            )
            ->then(function($string, $int): void {
                $this->assertSame(
                    "$string-$int.",
                    (new Name("$string-$int."))->toString(),
                );
            });
    }

    public function testThrowWhenInvalidString()
    {
        $this
            ->forAll(Set\Unicode::strings()->filter(static function($string): bool {
                return !((bool) preg_match('~^[a-zA-Z0-9\-\.]+$~', $string));
            }))
            ->then(function($string): void {
                $this->expectException(DomainException::class);
                $this->expectExceptionMessage($string);

                new Name($string);
            });
    }
}
