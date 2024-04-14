<?php

namespace Tests\Unit;

use App\Models\PasswordGenerator;
use PHPUnit\Framework\TestCase;

class PasswordGeneratorTest extends TestCase
{
    public function test_get_password()
    {
        $password = new PasswordGenerator;
        $this->assertNotEmpty($password->getPassword());
    }
}
