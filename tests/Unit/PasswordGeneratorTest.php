<?php

namespace Tests\Unit;

use App\Models\PasswordGenerator;
use PHPUnit\Framework\TestCase;

class PasswordGeneratorTest extends TestCase
{
    public function test_get_password()
    {
        $passLength = mt_rand(1, 16);

        $password = (new PasswordGenerator)->setLength($passLength)->getPassword();
        $this->assertNotEmpty($password);
        $this->assertEquals($passLength, strlen($password));
    }

    public function test_get_numeric_password()
    {
        $password = (new PasswordGenerator)->setLength(300)->getPassword();
        $set = '1234567890';
        for ($i = 0; $i < strlen($password)-1; $i++) {
            $this->assertContains($password[$i], str_split($set));
        }
    }
}
