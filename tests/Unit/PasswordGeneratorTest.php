<?php

namespace Tests\Unit;

use App\Models\PasswordGenerator;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\PHPUnitUtil;
use Tests\TestCase;

class PasswordGeneratorTest extends TestCase
{
    use DatabaseMigrations;
    protected function setUp(): void
    {
        parent::setUp();
        $this->createApplication();
    }

    public function test_get_password()
    {
        $passLength = mt_rand(3, 5);

        $password = (new PasswordGenerator)->setLength($passLength)->useNumerics()->getPassword();
        $this->assertNotEmpty($password);
        $this->assertEquals($passLength, strlen($password));
    }

    public function test_sets_for_correct_set_and_unset()
    {
        $generator = new PasswordGenerator();
        $this->assertEquals($generator->getSet(), '');

        $generator->useNumerics();
        $this->assertEquals($generator->getSet(), config('app.passwordSets.numbers'));
        $generator->useNumerics(false);
        $this->assertEquals($generator->getSet(), '');

        $generator->useLowerCase();
        $this->assertEquals($generator->getSet(), config('app.passwordSets.lowerCase'));
        $generator->useLowerCase(false);
        $this->assertEquals($generator->getSet(), '');

        $generator->useUpperCase();
        $this->assertEquals($generator->getSet(), config('app.passwordSets.upperCase'));
        $generator->useUpperCase(false);
        $this->assertEquals($generator->getSet(), '');
    }

    public function test_get_numeric_password()
    {
        $password = (new PasswordGenerator)->setLength(10)->useNumerics()->getPassword();
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
        $password = (new PasswordGenerator)->setLength(26)->useLowerCase()->getPassword();
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
        $password = (new PasswordGenerator)->setLength(26)->useUpperCase()->getPassword();
        $set = config('app.passwordSets.upperCase');
        for ($i = 0; $i < strlen($password)-1; $i++) {
            $this->assertContains($password[$i], str_split($set));
        }

        for ($i = 0; $i < strlen($set)-1; $i++) {
            $this->assertContains($set[$i], str_split($password));
        }
    }

    public function test_password_excess_length()
    {
        $this->expectExceptionMessage("Password set is too short");
        $password = (new PasswordGenerator)
            ->setLength(11)
            ->useNumerics()
            ->getPassword();
    }


    public function test_password_for_unique_characters()
    {
        $password = (new PasswordGenerator)
            ->setLength(10 + 26 + 26)
            ->useNumerics()
            ->useLowerCase()
            ->useUpperCase()
            ->getPassword();

        $usedCharacters = [];
        for ($i = 0; $i < strlen($password); $i++) {
            $this->assertNotContains($password[$i], $usedCharacters);
            $usedCharacters[] = $password[$i];
        }
    }

    public function test_that_passwords_are_non_repeat()
    {
        PasswordGenerator::truncate();

        $password = 'password';
        $password2 = 'password2';

        $generator = new PasswordGenerator();
        $this->assertFalse($generator->isPasswordAlreadyUsed($password));
        PHPUnitUtil::callMethod($generator, 'storePassword', [$password]);
        $this->assertTrue($generator->isPasswordAlreadyUsed($password));

        $generator2 = new PasswordGenerator();
        $this->assertFalse($generator2->isPasswordAlreadyUsed($password2));
        PHPUnitUtil::callMethod($generator2, 'storePassword', [$password2]);
        $this->assertTrue($generator2->isPasswordAlreadyUsed($password2));

        for($i = 1; $i <= 10; $i++)
            (new PasswordGenerator)->setLength(1)->useNumerics()->getPassword();

        $this->assertDatabaseCount(PasswordGenerator::class, 12);
    }

    public function test_unique_exception_runtime_limit()
    {
        PasswordGenerator::truncate();
        for($i = 1; $i <= 10; $i++)
            (new PasswordGenerator)->setLength(1)->useNumerics()->getPassword();
        $this->assertDatabaseCount(PasswordGenerator::class, 10);

        $this->expectExceptionMessage("Cannot generate a unique password");
        (new PasswordGenerator)->setLength(1)->useNumerics()->getPassword();
    }
}
