<?php

namespace App\Models;

class PasswordGenerator
{


    public function getPassword(int $length = 8): string
    {
        $pass = "";
        for ($i = 0; $i < $length; $i++) {
            $pass .= mt_rand(0, 9);
        }
        return $pass;
    }
}
