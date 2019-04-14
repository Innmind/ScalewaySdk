<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Marketplace\Image;

use Innmind\ScalewaySdk\{
    Marketplace\Image\Category,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testInterface($name, $value)
    {
        $this->assertInstanceOf(Category::class, Category::$name());
        $this->assertSame(Category::$name(), Category::$name());
        $this->assertSame($value, (string) Category::$name());
        $this->assertInstanceOf(Category::class, Category::of($value));
        $this->assertSame(Category::$name(), Category::of($value));
    }

    public function testThrowWhenUnknownValue()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('foo');

        Category::of('foo');
    }

    public function cases(): array
    {
        return [
            ['instantApp', 'instantapp'],
            ['distribution', 'distribution'],
            ['machineLearning', 'Machine Learning'],
        ];
    }
}
