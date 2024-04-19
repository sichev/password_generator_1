<?php

namespace Tests\Unit;

use App\Exceptions\PasswordGenerator\CharactersAmountIsTooSmallForPasswordLengthException;
use App\Exceptions\PasswordGenerator\TimeoutException;
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

        $password = PasswordGenerator::factory()->make()->setLength($passLength)->useNumbers()->getPassword();
        $this->assertNotEmpty($password);
        $this->assertEquals($passLength, strlen($password));
    }

    public function test_sets_for_correct_set_and_unset()
    {
        $generator = PasswordGenerator::factory()->make();
        $this->assertEquals(PHPUnitUtil::getParam($generator, 'usedSets'), []);

        $generator->useNumbers();
        $this->assertEquals(PHPUnitUtil::getParam($generator, 'usedSets'), [PasswordGenerator::SET_NUMERICS]);
        $generator->useNumbers(false);
        $this->assertEquals(PHPUnitUtil::getParam($generator, 'usedSets'), []);

        $generator->useLowerCase();
        $this->assertEquals(PHPUnitUtil::getParam($generator, 'usedSets'), [PasswordGenerator::SET_LOWERCASE]);
        $generator->useLowerCase(false);
        $this->assertEquals(PHPUnitUtil::getParam($generator, 'usedSets'), []);

        $generator->useUpperCase();
        $this->assertEquals(PHPUnitUtil::getParam($generator, 'usedSets'), [PasswordGenerator::SET_UPPERCASE]);
        $generator->useUpperCase(false);
        $this->assertEquals(PHPUnitUtil::getParam($generator, 'usedSets'), []);
    }

    public function test_get_numeric_password()
    {
        $password = PasswordGenerator::factory()->make()->setLength(10)->useNumbers()->getPassword();
        $set = PasswordGenerator::SETS[PasswordGenerator::SET_NUMERICS];
        for ($i = 0; $i < strlen($password)-1; $i++) {
            $this->assertContains($password[$i], str_split($set));
        }

        for ($i = 0; $i < strlen($set)-1; $i++) {
            $this->assertContains($set[$i], str_split($password));
        }
    }

    public function test_get_lowercase_password()
    {
        $password = PasswordGenerator::factory()->make()->setLength(26)->useLowerCase()->getPassword();
        $set = PasswordGenerator::SETS[PasswordGenerator::SET_LOWERCASE];
        for ($i = 0; $i < strlen($password)-1; $i++) {
            $this->assertContains($password[$i], str_split($set));
        }

        for ($i = 0; $i < strlen($set)-1; $i++) {
            $this->assertContains($set[$i], str_split($password));
        }
    }

    public function test_get_uppercase_password()
    {
        $password = PasswordGenerator::factory()->make()->setLength(26)->useUpperCase()->getPassword();
        $set = PasswordGenerator::SETS[PasswordGenerator::SET_UPPERCASE];
        for ($i = 0; $i < strlen($password)-1; $i++) {
            $this->assertContains($password[$i], str_split($set));
        }

        for ($i = 0; $i < strlen($set)-1; $i++) {
            $this->assertContains($set[$i], str_split($password));
        }
    }

    public function test_password_excess_length()
    {
        $this->expectException(CharactersAmountIsTooSmallForPasswordLengthException::class);
        $password = PasswordGenerator::factory()->make()
            ->setLength(11)
            ->useNumbers()
            ->getPassword();
    }


    public function test_password_for_unique_characters()
    {
        $password = PasswordGenerator::factory()->make()
            ->setLength(10 + 26 + 26)
            ->useNumbers()
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

        $generator = PasswordGenerator::factory()->make();
        $this->assertFalse(PHPUnitUtil::callMethod($generator, 'isPasswordAlreadyUsed', [$password]));
        PHPUnitUtil::callMethod($generator, 'storePassword', [$password]);
        $this->assertTrue(PHPUnitUtil::callMethod($generator, 'isPasswordAlreadyUsed', [$password]));

        $generator2 = PasswordGenerator::factory()->make();
        $this->assertFalse(PHPUnitUtil::callMethod($generator2, 'isPasswordAlreadyUsed', [$password2]));
        PHPUnitUtil::callMethod($generator2, 'storePassword', [$password2]);
        $this->assertTrue(PHPUnitUtil::callMethod($generator2, 'isPasswordAlreadyUsed', [$password2]));

        for($i = 1; $i <= 10; $i++)
            PasswordGenerator::factory()->make()->setLength(1)->useNumbers()->getPassword();

        $this->assertDatabaseCount(PasswordGenerator::class, 12);
    }

    public function test_unique_exception_runtime_limit()
    {
        PasswordGenerator::truncate();
        for($i = 1; $i <= 10; $i++)
            PasswordGenerator::factory()->make()->setLength(1)->useNumbers()->getPassword();
        $this->assertDatabaseCount(PasswordGenerator::class, 10);

        $this->expectException(TimeoutException::class);
        PasswordGenerator::factory()->make()->setLength(1)->useNumbers()->getPassword();
    }

    public function test_password_contains_selected_characters()
    {
        $password = PasswordGenerator::factory()->make()->setLength(3)->useUpperCase()->getPassword();
        $this->assertMatchesRegularExpression('/[A-Z]/', $password);
        $this->assertMatchesRegularExpression('/[^a-z]/', $password);
        $this->assertMatchesRegularExpression('/[^0-9]/', $password);

        $password = PasswordGenerator::factory()->make()->setLength(3)->useLowerCase()->getPassword();
        $this->assertMatchesRegularExpression('/[^A-Z]/', $password);
        $this->assertMatchesRegularExpression('/[a-z]/', $password);
        $this->assertMatchesRegularExpression('/[^0-9]/', $password);

        $password = PasswordGenerator::factory()->make()->setLength(3)->useNumbers()->getPassword();
        $this->assertMatchesRegularExpression('/[^A-Z]/', $password);
        $this->assertMatchesRegularExpression('/[^a-z]/', $password);
        $this->assertMatchesRegularExpression('/[0-9]/', $password);

        $password = PasswordGenerator::factory()->make()->setLength(3)->useNumbers()->useUpperCase()->getPassword();
        $this->assertMatchesRegularExpression('/[A-Z]/', $password);
        $this->assertMatchesRegularExpression('/[^a-z]/', $password);
        $this->assertMatchesRegularExpression('/[0-9]/', $password);

        $password = PasswordGenerator::factory()->make()->setLength(3)->useNumbers()->useLowerCase()->getPassword();
        $this->assertMatchesRegularExpression('/[^A-Z]/', $password);
        $this->assertMatchesRegularExpression('/[a-z]/', $password);
        $this->assertMatchesRegularExpression('/[0-9]/', $password);

        $password = PasswordGenerator::factory()->make()->setLength(3)->useUpperCase()->useLowerCase()->getPassword();
        $this->assertMatchesRegularExpression('/[A-Z]/', $password);
        $this->assertMatchesRegularExpression('/[a-z]/', $password);
        $this->assertMatchesRegularExpression('/[^0-9]/', $password);

        $password = PasswordGenerator::factory()->make()->setLength(3)->useUpperCase()->useLowerCase()->useNumbers()->getPassword();
        $this->assertMatchesRegularExpression('/[A-Z]/', $password);
        $this->assertMatchesRegularExpression('/[a-z]/', $password);
        $this->assertMatchesRegularExpression('/[0-9]/', $password);
    }
}
