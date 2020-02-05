<?php
declare(strict_types = 1);

namespace Innmind\ScalewaySdk\Tokens;

final class NewToken
{
    private string $email;
    private string $password;
    private bool $expires;
    private ?string $twoFaToken;

    private function __construct(
        string $email,
        string $password,
        ?string $twoFaToken,
        bool $expires
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->expires = $expires;
        $this->twoFaToken = $twoFaToken;
    }

    public static function permanent(
        string $email,
        string $password,
        string $twoFaToken = null
    ): self {
        return new self($email, $password, $twoFaToken, false);
    }

    public static function temporary(
        string $email,
        string $password,
        string $twoFaToken = null
    ): self {
        return new self($email, $password, $twoFaToken, true);
    }

    public function email(): string
    {
        return $this->email;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function expires(): bool
    {
        return $this->expires;
    }

    public function hasTwoFaToken(): bool
    {
        return \is_string($this->twoFaToken);
    }

    public function twoFaToken(): string
    {
        return $this->twoFaToken;
    }
}
