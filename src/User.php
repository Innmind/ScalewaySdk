<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\User\{
    Id,
    SshKey,
};
use Innmind\Immutable\SetInterface;
use function Innmind\Immutable\assertSet;

final class User
{
    private $id;
    private $email;
    private $firstname;
    private $lastname;
    private $fullname;
    private $sshKeys;

    public function __construct(
        Id $id,
        string $email,
        string $firstname,
        string $lastname,
        string $fullname,
        SetInterface $sshKeys
    ) {
        assertSet(SshKey::class, $sshKeys, 6);

        $this->id = $id;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->fullname = $fullname;
        $this->sshKeys = $sshKeys;
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function firstname(): string
    {
        return $this->firstname;
    }

    public function lastname(): string
    {
        return $this->lastname;
    }

    public function fullname(): string
    {
        return $this->fullname;
    }

    /**
     * @return SetInterface<SshKey>
     */
    public function sshKeys(): SetInterface
    {
        return $this->sshKeys;
    }
}
