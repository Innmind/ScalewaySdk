<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Server;

use Innmind\ScalewaySdk\{
    Server\State,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class StateTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testInterface($name, $value)
    {
        $this->assertInstanceOf(State::class, State::$name());
        $this->assertSame(State::$name(), State::$name());
        $this->assertSame($value, State::$name()->toString());
        $this->assertInstanceOf(State::class, State::of($value));
        $this->assertSame($value, State::of($value)->toString());
    }

    public function testThrowWhenUnknownValue()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('foo');

        State::of('foo');
    }

    public function cases(): array
    {
        return [
            ['running', 'running'],
            ['stopping', 'stopping'],
            ['stoppedInPlace', 'stopped in place'],
            ['stopped', 'stopped'],
            ['starting', 'starting'],
        ];
    }
}
