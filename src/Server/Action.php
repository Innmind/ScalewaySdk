<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Server;

use Innmind\ScalewaySdk\Exception\DomainException;

final class Action
{
    private const POWERON = 'poweron';
    private const POWEROFF = 'poweroff';
    private const STOP_IN_PLACE = 'stop_in_place';
    private const REBOOT = 'reboot';
    private const TERMINATE = 'terminate';
    private const BACKUP = 'backup';

    private static ?self $powerOn = null;
    private static ?self $powerOff = null;
    private static ?self $stopInPlace = null;
    private static ?self $reboot = null;
    private static ?self $terminate = null;
    private static ?self $backup = null;

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function of(string $value): self
    {
        switch ($value) {
            case self::POWERON:
                return self::powerOn();

            case self::POWEROFF:
                return self::powerOff();

            case self::STOP_IN_PLACE:
                return self::stopInPlace();

            case self::REBOOT:
                return self::reboot();

            case self::TERMINATE:
                return self::terminate();

            case self::BACKUP:
                return self::backup();
        }

        throw new DomainException($value);
    }

    public static function powerOn(): self
    {
        return self::$powerOn ??= new self(self::POWERON);
    }

    public static function powerOff(): self
    {
        return self::$powerOff ??= new self(self::POWEROFF);
    }

    public static function stopInPlace(): self
    {
        return self::$stopInPlace ??= new self(self::STOP_IN_PLACE);
    }

    public static function reboot(): self
    {
        return self::$reboot ??= new self(self::REBOOT);
    }

    public static function terminate(): self
    {
        return self::$terminate ??= new self(self::TERMINATE);
    }

    public static function backup(): self
    {
        return self::$backup ??= new self(self::BACKUP);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
