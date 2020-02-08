<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\Token\Id;
use Innmind\TimeContinuum\PointInTime;

final class Token
{
    private Id $id;
    private User\Id $user;
    private PointInTime $createdAt;
    private ?PointInTime $expiresAt;

    public function __construct(
        Id $id,
        User\Id $user,
        PointInTime $createdAt,
        PointInTime $expiresAt = null
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

    public function createdAt(): PointInTime
    {
        return $this->createdAt;
    }

    public function expires(): bool
    {
        return $this->expiresAt instanceof PointInTime;
    }

    public function expiresAt(): PointInTime
    {
        return $this->expiresAt;
    }
}
