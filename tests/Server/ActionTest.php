<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Server;

use Innmind\ScalewaySdk\{
    Server\Action,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class ActionTest extends TestCase
{
    /**
     * @dataProvider actions
     */
    public function testInterface($name, $value)
    {
        $this->assertInstanceOf(Action::class, Action::$name());
        $this->assertSame(Action::$name(), Action::$name());
        $this->assertInstanceOf(Action::class, Action::of($value));
        $this->assertSame($value, Action::of($value)->toString());
        $this->assertSame($value, Action::$name()->toString());
    }

    public function testThrowWhenUnknownAction()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('foo');

        Action::of('foo');
    }

    public static function actions(): array
    {
        return [
            ['powerOn', 'poweron'],
            ['powerOff', 'poweroff'],
            ['stopInPlace', 'stop_in_place'],
            ['reboot', 'reboot'],
            ['terminate', 'terminate'],
            ['backup', 'backup'],
        ];
    }
}
