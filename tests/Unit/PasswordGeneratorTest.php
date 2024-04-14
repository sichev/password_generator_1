<?php

namespace Tests\Unit;

use App\Models\PasswordGenerator;
use Tests\TestCase;
use Tests\CreatesApplication;

class PasswordGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        $this->createApplication();
    }

    public function test_get_password()
    {
        $passLength = mt_rand(1, 16);

        $password = (new PasswordGenerator)->setLength($passLength)->useNumerics()->getPassword();
        $this->assertNotEmpty($password);
        $this->assertEquals($passLength, strlen($password));
    }

    public function test_get_numeric_password()
    {
        $password = (new PasswordGenerator)->setLength(300)->useNumerics()->getPassword();
        $set = config('app.passwordSets.numbers');
        for ($i = 0; $i < strlen($password)-1; $i++) {
            $this->assertContains($password[$i], str_split($set));
        }

        for ($i = 0; $i < strlen($set)-1; $i++) {
            $this->assertContains($set[$i], str_split($password));
        }
    }

    public function test_get_lowercase_password()
    {
        $password = (new PasswordGenerator)->setLength(300)->useLowerCase()->getPassword();
        $set = config('app.passwordSets.lowerCase');
        for ($i = 0; $i < strlen($password)-1; $i++) {
            $this->assertContains($password[$i], str_split($set));
        }

        for ($i = 0; $i < strlen($set)-1; $i++) {
            $this->assertContains($set[$i], str_split($password));
        }
    }

    public function test_get_uppercase_password()
    {
        $password = (new PasswordGenerator)->setLength(300)->useUpperCase()->getPassword();
        $set = config('app.passwordSets.upperCase');
        for ($i = 0; $i < strlen($password)-1; $i++) {
            $this->assertContains($password[$i], str_split($set));
        }

        for ($i = 0; $i < strlen($set)-1; $i++) {
            $this->assertContains($set[$i], str_split($password));
        }
    }
}
