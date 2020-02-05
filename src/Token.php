<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\Token\Id;
use Innmind\TimeContinuum\PointInTimeInterface;

final class Token
{
    private Id $id;
    private User\Id $user;
    private PointInTimeInterface $createdAt;
    private ?PointInTimeInterface $expiresAt;

    public function __construct(
        Id $id,
        User\Id $user,
        PointInTimeInterface $createdAt,
        PointInTimeInterface $expiresAt = null
    ) {
        $this->id = $id;
        $this->user = $user;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function user(): User\Id
    {
        return $this->user;
    }

    public function createdAt(): PointInTimeInterface
    {
        return $this->createdAt;
    }

    public function expires(): bool
    {
        return $this->expiresAt instanceof PointInTimeInterface;
    }

    public function expiresAt(): PointInTimeInterface
    {
        return $this->expiresAt;
    }
}
