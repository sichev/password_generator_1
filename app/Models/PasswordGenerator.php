<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordGenerator extends Model
{
    protected $table = 'password_generator';

    private int $length = 8;
    private string $charactersSet = '';

    public function setLength(int $length): self
    {
        $this->length = $length;
        return $this;
    }

    public function useUpperCase(bool $upperCase = true): self {
        $set = config('app.passwordSets.upperCase');
        $this->processSet($set, $upperCase);
        return $this;
    }

    public function useLowerCase(bool $lowerCase = true): self {
        $set = config('app.passwordSets.lowerCase');
        $this->processSet($set, $lowerCase);
        return $this;
    }

    public function useNumerics(bool $numerics = true): self {
        $set = config('app.passwordSets.numbers');
        $this->processSet($set, $numerics);
        return $this;
    }

    public function getSet(): string
    {
        return $this->charactersSet;
    }

    public function getPassword(): string
    {
        if (strlen($this->charactersSet) < $this->length) {
            throw new \Exception("Password set is too short");
        }

        $passIsPassed = false;
        $startTime = microtime(true);
        while (!$passIsPassed) {
            $availableSet = $this->charactersSet;

            $pass = "";
            for ($i = 0; $i < $this->length; $i++) {
                $usedChar = $this->getRandomCharFromString($availableSet);
                $pass .= $usedChar;
                $availableSet = $this->removeCharFromSet($usedChar, $availableSet);
            }

            if (!$this->isPasswordAlreadyUsed($pass)) {
                $this->storePassword($pass);
                $passIsPassed = true;
            }

            $time = microtime(true) - $startTime;
            if ($time > 25) {
                throw new \Exception("Cannot generate a unique password");
            }
        }
        return $pass;
    }

    public function isPasswordAlreadyUsed(string $password): bool
    {
        foreach (self::all(['pass_hash']) as $hashedPassword) {
            if (password_verify($password, $hashedPassword->pass_hash)) {
                return true;
            }
        }

        return false;
    }

    private function storePassword(string $password): self
    {
        $this->pass_hash = password_hash($password, PASSWORD_DEFAULT);
        $this->save();
        return $this;
    }

    private function processSet(string $set, bool $isNeedToAdd)
    {
        $this->removeSet($set);
        if ($isNeedToAdd)
            $this->addSet($set);
        return $this;
    }

    private function removeCharFromSet(string $char, string $set): string
    {
        $set = str_split($set);
        $set = array_diff($set, [$char]);
        return implode('', $set);
    }

    private function removeSet(string $set): self
    {
        for ($i = 0; $i <= strlen($set)-1; $i++) {
            $this->charactersSet = $this->removeCharFromSet($set[$i], $this->charactersSet);
        }
        return $this;
    }

    private function addSet(string $set): self
    {
        $this->charactersSet .= $set;
        return $this;
    }

    private function getRandomCharFromString(string $string): string
    {
        return $string[mt_rand(0, strlen($string) - 1)];
    }
}
