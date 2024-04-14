<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordGenerator
{
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

        $availableSet = $this->charactersSet;

        $pass = "";
        for ($i = 0; $i < $this->length; $i++) {
            $usedChar = $this->getRandomCharFromString($availableSet);
            $pass .= $usedChar;
            $availableSet = $this->removeCharFromSet($usedChar, $availableSet);
        }
        return $pass;
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
