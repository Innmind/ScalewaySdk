<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Marketplace\Image;

use Innmind\ScalewaySdk\Exception\DomainException;

final class Category
{
    private const INSTANT_APP = 'instantapp';
    private const DISTRIBUTION = 'distribution';
    private const MACHINE_LEARNING = 'Machine Learning';

    private static ?self $instantApp = null;
    private static ?self $distribution = null;
    private static ?self $machineLearning = null;

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function of(string $value): self
    {
        switch ($value) {
            case self::INSTANT_APP:
                return self::instantApp();

            case self::DISTRIBUTION:
                return self::distribution();

            case self::MACHINE_LEARNING:
                return self::machineLearning();
        }

        throw new DomainException($value);
    }

    public static function instantApp(): self
    {
        return self::$instantApp ??= new self(self::INSTANT_APP);
    }

    public static function distribution(): self
    {
        return self::$distribution ??= new self(self::DISTRIBUTION);
    }

    public static function machineLearning(): self
    {
        return self::$machineLearning ??= new self(self::MACHINE_LEARNING);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
