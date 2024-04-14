<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordGenerator extends Model
{
    private int $length = 8;
    private bool $useNumerics = false;
    private bool $useUpperCase = false;
    private bool $useLowerCase = false;

    public function setLength(int $length): self
    {
        $this->length = $length;
        return $this;
    }

    public function useUpperCase(bool $upperCase = true): self {
        $this->useUpperCase = $upperCase;
        return $this;
    }

    public function useLowerCase(bool $lowerCase = true): self {
        $this->useLowerCase = $lowerCase;
        return $this;
    }

    public function useNumerics(bool $numerics = true): self {
        $this->useNumerics = $numerics;
        return $this;
    }

    public function getPassword(): string
    {
        $set = $this->prepareSet();
        $setLength = strlen($set) - 1;
        if ($setLength <= 0) {
            throw new \Exception("Password set is empty");
        }

        $pass = "";
        for ($i = 0; $i < $this->length; $i++) {
            $pass .= $set[mt_rand(0, $setLength)];
        }
        return $pass;
    }

    private function prepareSet(): string
    {
        $set = "";
        if ($this->useUpperCase) $set .= config('app.passwordSets.upperCase');
        if ($this->useLowerCase) $set .= config('app.passwordSets.lowerCase');
        if ($this->useNumerics) $set .= config('app.passwordSets.numbers');
        return $set;
    }
}
