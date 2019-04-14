<?php
declare(strict_types = 1);

namespace Tests\Innmind\ScalewaySdk\Tokens;

use Innmind\ScalewaySdk\Tokens\NewToken;
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait,
};

class NewTokenTest extends TestCase
{
    use TestTrait;

    public function testPermanent()
    {
        $this
            ->forAll(Generator\string(), Generator\string(), Generator\string())
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
            ->forAll(Generator\string(), Generator\string())
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
            ->forAll(Generator\string(), Generator\string(), Generator\string())
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
            ->forAll(Generator\string(), Generator\string())
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
