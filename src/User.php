<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk;

use Innmind\ScalewaySdk\{
    User\Id,
    User\SshKey,
    Organization,
};
use Innmind\Immutable\Set;
use function Innmind\Immutable\assertSet;

final class User
{
    private Id $id;
    private string $email;
    private string $firstname;
    private string $lastname;
    private string $fullname;
    /** @var Set<SshKey> */
    private Set $sshKeys;
    /** @var Set<Organization\Id> */
    private Set $organizations;

    /**
     * @param Set<SshKey> $sshKeys
     * @param Set<Organization\Id> $organizations
     */
    public function __construct(
        Id $id,
        string $email,
        string $firstname,
        string $lastname,
        string $fullname,
        Set $sshKeys,
        Set $organizations,
    ) {
        assertSet(SshKey::class, $sshKeys, 6);
        assertSet(Organization\Id::class, $organizations, 7);

        $this->id = $id;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->fullname = $fullname;
        $this->sshKeys = $sshKeys;
        $this->organizations = $organizations;
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
     * @return Set<SshKey>
     */
    public function sshKeys(): Set
    {
        return $this->sshKeys;
    }

    /**
     * @return Set<Organization\Id>
     */
    public function organizations(): Set
    {
        return $this->organizations;
    }
}
