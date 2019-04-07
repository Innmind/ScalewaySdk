<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Server;

use Innmind\ScalewaySdk\Server\Action;
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
        $this->assertSame($value, (string) Action::of($value));
        $this->assertSame($value, (string) Action::$name());
    }

    public function actions(): array
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
