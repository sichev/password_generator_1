<?php

namespace App\Models;

class PasswordGenerator
{
    private int $length = 8;

    public function setLength(int $length): self
    {
        $this->length = $length;
        return $this;
    }

    public function getPassword(): string
    {
        $pass = "";
        for ($i = 0; $i < $this->length; $i++) {
            $pass .= mt_rand(0, 1);
        }
        return $pass;
    }
}
