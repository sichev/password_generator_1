<?php

namespace App\Models;

use App\Interfaces\PasswordGeneratorInterface;
use Illuminate\Database\Eloquent\Model;

class PasswordGenerator extends Model implements PasswordGeneratorInterface
{
    protected $table = 'password_generator';

    const int SET_RANDOM = 0;
    const int SET_NUMERICS = 1;
    const int SET_LOWERCASE = 2;
    const int SET_UPPERCASE = 3;
    const array SETS = [
        self::SET_NUMERICS => '1234567890',
        self::SET_LOWERCASE => 'qwertyuiopasdfghjklzxcvbnm',
        self::SET_UPPERCASE => 'QWERTYUIOPASDFGHJKLZXCVBNM',
    ];

    private int $length = 0;
    /** @var array<int> */
    private array $usedSets = [];
    /** @var array<int,string>  */
    private array $availableCharacters = [];
    private string $passwordMask = '';

    public function setLength(int $length): self
    {
        $this->length = $length;
        return $this;
    }

    public function useUpperCase(bool $isActive = true): self {
        $this->processSet($isActive, self::SET_UPPERCASE);
        return $this;
    }

    public function useLowerCase(bool $isActive = true): self {
        $this->processSet($isActive, self::SET_LOWERCASE);
        return $this;
    }

    public function useNumbers(bool $isActive = true): self {
        $this->processSet($isActive, self::SET_NUMERICS);
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function getPassword(): string
    {
        $this->verifySetSettings();
        $passIsPassed = false;
        $startTime = microtime(true);
        while (!$passIsPassed) {
            $time = microtime(true) - $startTime;
            if ($time > 25) {
                throw new \Exception("Cannot generate a unique password");
            }

            $this->updatePasswordMask();
            $this->resetAvailableCharacters();
            $pass = "";

            for ($i = 0; $i < $this->length; $i++) {
                $setType = (int)$this->passwordMask[$i];
                $usedChar = $this->getNextCharacter($setType);
                $pass .= $usedChar;
            }

            if (!$this->isPasswordAlreadyUsed($pass)) {
                $this->storePassword($pass);
                $passIsPassed = true;
            }
        }

        return $pass;
    }

    private function isPasswordAlreadyUsed(string $password): bool
    {
        foreach (self::all(['pass_hash']) as $hashedPassword) {
            if (password_verify($password, $hashedPassword->pass_hash ?? null)) {
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

    private function processSet(bool $isNeedToAdd, int $setType): self
    {
        $this->removeSet($setType);
        if ($isNeedToAdd)
            $this->addSet($setType);
        return $this;
    }

    private function getNextCharacter(int $initialSetType): string
    {
        $setType = $initialSetType;
        if (empty($this->availableCharacters[$setType]))
            $setType = self::SET_RANDOM;

        while ($setType === self::SET_RANDOM) {
            $newSetTypeKey = array_rand($this->usedSets);
            if (empty($this->availableCharacters[$this->usedSets[$newSetTypeKey]])) {
                unset($this->usedSets[$newSetTypeKey]);
                continue;
            }
            $setType = $this->usedSets[$newSetTypeKey];
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

    private function removeSet(int $setType): self
    {
        $this->usedSets = array_diff($this->usedSets, [$setType]);
        unset($this->availableCharacters[$setType]);

        return $this;
    }

    private function addSet(int $setType): self
    {
        $this->usedSets[] = $setType;
        $this->resetAvailableCharacters();

        return $this;
    }

    private function resetAvailableCharacters(): void
    {
        foreach ($this->usedSets as $set) {
            $this->availableCharacters[$set] = self::SETS[$set];
        }
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
