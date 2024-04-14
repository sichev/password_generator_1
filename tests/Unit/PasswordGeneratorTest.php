<?php

namespace Tests\Unit;

use App\Models\PasswordGenerator;
use PHPUnit\Framework\TestCase;

class PasswordGeneratorTest extends TestCase
{
    public function test_get_password()
    {
        $passLength = mt_rand(1, 16);

        $password = (new PasswordGenerator)->getPassword($passLength);
        $this->assertNotEmpty($password);
        $this->assertEquals($passLength, strlen($password));
    }
}
