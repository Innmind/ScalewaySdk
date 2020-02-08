<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Server;

use Innmind\ScalewaySdk\Exception\DomainException;

final class State
{
    private const RUNNING = 'running';
    private const STOPPING = 'stopping';
    private const STOPPED_IN_PLACE = 'stopped in place';
    private const STOPPED = 'stopped';
    private const STARTING = 'starting';

    private static ?self $running = null;
    private static ?self $stopping = null;
    private static ?self $stoppedInPlace = null;
    private static ?self $stopped = null;
    private static ?self $starting = null;

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function of(string $value): self
    {
        switch ($value) {
            case self::RUNNING:
                return self::running();

            case self::STOPPING:
                return self::stopping();

            case self::STOPPED_IN_PLACE:
                return self::stoppedInPlace();

            case self::STOPPED:
                return self::stopped();

            case self::STARTING:
                return self::starting();
        }

        throw new DomainException($value);
    }

    public static function running(): self
    {
        return self::$running ??= new self(self::RUNNING);
    }

    public static function stopping(): self
    {
        return self::$stopping ??= new self(self::STOPPING);
    }

    public static function stoppedInPlace(): self
    {
        return self::$stoppedInPlace ??= new self(self::STOPPED_IN_PLACE);
    }

    public static function stopped(): self
    {
        return self::$stopped ??= new self(self::STOPPED);
    }

    public static function starting(): self
    {
        return self::$starting ??= new self(self::STARTING);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
