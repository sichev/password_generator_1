<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordGenerator extends Model
{
    protected $table = 'password_generator';

    const SET_RANDOM = 0;
    const SET_NUMERICS = 1;
    const SET_LOWERCASE = 2;
    const SET_UPPERCASE = 3;

    private int $length = 0;
    /** @var array<int>} */
    private array $usedSets = [];
    /** @var array<int,string>  */
    private array $availableCharacters = [];
    private string $charactersSet = '';
    private $passwordMask = '';

    public function setLength(int $length): self
    {
        $this->length = $length;
        return $this;
    }

    public function useUpperCase(bool $upperCase = true): self {
        $set = config('app.passwordSets.upperCase');
        $this->processSet($set, $upperCase, self::SET_UPPERCASE);
        return $this;
    }

    public function useLowerCase(bool $lowerCase = true): self {
        $set = config('app.passwordSets.lowerCase');
        $this->processSet($set, $lowerCase, self::SET_LOWERCASE);
        return $this;
    }

    public function useNumerics(bool $numerics = true): self {
        $set = config('app.passwordSets.numbers');
        $this->processSet($set, $numerics, self::SET_NUMERICS);
        return $this;
    }

    public function getPassword(): string
    {
        $this->verifySetSettings();
        $passIsPassed = false;
        $startTime = microtime(true);
        while (!$passIsPassed) {
            $this->updatePasswordMask();
            $pass = "";
            for ($i = 0; $i < $this->length; $i++) {
                $setType = (int) $this->passwordMask[$i];
                $usedChar = $this->getNextCharacter($setType);
                $pass .= $usedChar;
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

    private function processSet(string $setContent, bool $isNeedToAdd, int $setType): self
    {
        $this->removeSet($setContent, $setType);
        if ($isNeedToAdd)
            $this->addSet($setContent, $setType);
        return $this;
    }

    private function getNextCharacter(int $initialSetType): string
    {
        $setType = $initialSetType;
        if (empty($this->availableCharacters[$setType]))
            $setType = self::SET_RANDOM;

        if ($setType === self::SET_RANDOM) {
            $pool = $this->usedSets;
            while ($setType === self::SET_RANDOM) {
                $newSetTypeKey = array_rand($pool);
                if (empty($this->availableCharacters[$pool[$newSetTypeKey]])) {
                    unset($pool[$newSetTypeKey]);
                    continue;
                }
                $setType = $pool[$newSetTypeKey];
            }
        }

        $charactersSet = $this->availableCharacters[$setType];
        $nextCharacter = $this->getRandomCharFromString($charactersSet);
        $charactersSet = $this->removeCharFromSet($nextCharacter, $charactersSet);
        $this->availableCharacters[$setType] = $charactersSet;

        return $nextCharacter;
    }


    private function removeCharFromSet(string $char, string $set): string
    {
        $set = str_split($set);
        $set = array_diff($set, [$char]);
        return implode('', $set);
    }

    private function removeSet(string $setContent, int $setType): self
    {
        // Old approach
//        for ($i = 0; $i <= strlen($setContent)-1; $i++) {
//            $this->charactersSet = $this->removeCharFromSet($setContent[$i], $this->charactersSet);
//        }

        // New approach
        $this->usedSets = array_diff($this->usedSets, [$setType]);
        unset($this->availableCharacters[$setType]);

        return $this;
    }

    private function addSet(string $setContent, int $setType): self
    {
        // Old approach
//        $this->charactersSet .= $setContent;

        // New approach
        $this->usedSets[] = $setType;
        $this->availableCharacters[$setType] = $setContent;

        return $this;
    }

    private function getRandomCharFromString(string $string): string
    {
        return $string[mt_rand(0, strlen($string) - 1)];
    }

    private function updatePasswordMask(): void
    {
        $mask = $this->getEmptyMask($this->length);
        foreach ($this->usedSets as $setType) {
            $index = $this->getRandomPasswordMaskIndex($mask);
            $mask[$index] = $setType;
        }

        $this->passwordMask = $mask;
    }

    private function getEmptyMask(int $length): string
    {
        $mask = '';

        for ($i = 0; $i < $length; $i++) {
            $mask .= self::SET_RANDOM;
        }

        return $mask;
    }

    private function getRandomPasswordMaskIndex(string $mask): int
    {
        $index = mt_rand(0, strlen($mask) - 1);
        return $this->getNextAvailableIndex($mask, $index);
    }

    private function getNextAvailableIndex(string $mask, int $currentIndex): int
    {
        if ($this->isAvailableMaskIndex($mask, $currentIndex))
            return $currentIndex;

        $newIndex = $this->isLastInList($mask, $currentIndex) ? 0 : $currentIndex + 1;
        while (!$this->isAvailableMaskIndex($mask, $newIndex))
            $this->getNextAvailableIndex($mask, $newIndex);

        return $newIndex;
    }

    private function isLastInList(string $mask, int $currentIndex): bool
    {
        return $currentIndex === strlen($mask) -1;
    }

    private function isAvailableMaskIndex(string $mask, int $index):bool
    {
        return $mask[$index] === (string) self::SET_RANDOM;
    }

    private function verifySetSettings(): void
    {
        if (strlen(implode($this->availableCharacters)) < $this->length)
            throw new \Exception("Password set is too short");

        if (count($this->usedSets) > $this->length)
            throw new \Exception("Password length exceed amount of sets");
    }
}
