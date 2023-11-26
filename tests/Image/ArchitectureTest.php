<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Image;

use Innmind\ScalewaySdk\{
    Image\Architecture,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class ArchitectureTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testInterface($name, $value)
    {
        $this->assertInstanceOf(Architecture::class, Architecture::$name());
        $this->assertSame(Architecture::$name(), Architecture::$name());
        $this->assertSame($value, Architecture::$name()->toString());
        $this->assertInstanceOf(Architecture::class, Architecture::of($value));
        $this->assertSame($value, Architecture::of($value)->toString());
    }

    public function testThrowWhenUnknownValue()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('foo');

        Architecture::of('foo');
    }

    public static function cases(): array
    {
        return [
            ['arm', 'arm'],
            ['arm64', 'arm64'],
            ['x86_64', 'x86_64'],
        ];
    }
}
