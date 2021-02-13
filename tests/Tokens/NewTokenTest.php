<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Tokens;

use Innmind\ScalewaySdk\Tokens\NewToken;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class NewTokenTest extends TestCase
{
    use BlackBox;

    public function testPermanent()
    {
        $this
            ->forAll(Set\Strings::any(), Set\Strings::any(), Set\Strings::any())
            ->then(function($email, $password, $twofa): void {
                $token = NewToken::permanent($email, $password, $twofa);

                $this->assertInstanceOf(NewToken::class, $token);
                $this->assertSame($email, $token->email());
                $this->assertSame($password, $token->password());
                $this->assertFalse($token->expires());
                $this->assertTrue($token->hasTwoFaToken());
                $this->assertSame($twofa, $token->twoFaToken());
            });
        $this
            ->forAll(Set\Strings::any(), Set\Strings::any())
            ->then(function($email, $password): void {
                $token = NewToken::permanent($email, $password);

                $this->assertInstanceOf(NewToken::class, $token);
                $this->assertSame($email, $token->email());
                $this->assertSame($password, $token->password());
                $this->assertFalse($token->expires());
                $this->assertFalse($token->hasTwoFaToken());
            });
    }

    public function testTemporary()
    {
        $this
            ->forAll(Set\Strings::any(), Set\Strings::any(), Set\Strings::any())
            ->then(function($email, $password, $twofa): void {
                $token = NewToken::temporary($email, $password, $twofa);

                $this->assertInstanceOf(NewToken::class, $token);
                $this->assertSame($email, $token->email());
                $this->assertSame($password, $token->password());
                $this->assertTrue($token->expires());
                $this->assertTrue($token->hasTwoFaToken());
                $this->assertSame($twofa, $token->twoFaToken());
            });
        $this
            ->forAll(Set\Strings::any(), Set\Strings::any())
            ->then(function($email, $password): void {
                $token = NewToken::temporary($email, $password);

                $this->assertInstanceOf(NewToken::class, $token);
                $this->assertSame($email, $token->email());
                $this->assertSame($password, $token->password());
                $this->assertTrue($token->expires());
                $this->assertFalse($token->hasTwoFaToken());
            });
    }
}
