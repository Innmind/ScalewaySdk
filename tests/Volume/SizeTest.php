<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Volume;

use Innmind\ScalewaySdk\{
    Volume\Size,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class SizeTest extends TestCase
{
    use BlackBox;

    public function testOf()
    {
        $this
            ->forAll(Set\Integers::above(0))
            ->then(function($value): void {
                $this->assertInstanceOf(Size::class, Size::of($value));
                $this->assertSame($value, Size::of($value)->toInt());
            });
    }

    public function testThrowWhenNegativeValue()
    {
        $this
            ->forAll(Set\Integers::below(-1))
            ->then(function($value): void {
                $this->expectException(DomainException::class);

                Size::of($value);
            });
    }

    /**
     * @dataProvider constructors
     */
    public function testNamedConstructors($name, $value)
    {
        $this->assertInstanceOf(Size::class, Size::$name());
        $this->assertSame($value, Size::$name()->toInt());
    }

    public static function constructors(): array
    {
        return [
            ['of25Go', 25000000000],
            ['of50Go', 50000000000],
            ['of75Go', 75000000000],
            ['of100Go', 100000000000],
            ['of125Go', 125000000000],
            ['of150Go', 150000000000],
        ];
    }
}
