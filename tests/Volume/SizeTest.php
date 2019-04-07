<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Volume;

use Innmind\ScalewaySdk\{
    Volume\Size,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait,
};

class SizeTest extends TestCase
{
    use TestTrait;

    public function testOf()
    {
        $this
            ->forAll(Generator\pos())
            ->then(function($value): void {
                $this->assertInstanceOf(Size::class, Size::of($value));
                $this->assertSame($value, Size::of($value)->toInt());
            });
    }

    public function testThrowWhenNegativeValue()
    {
        $this
            ->forAll(Generator\neg())
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

    public function constructors(): array
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
