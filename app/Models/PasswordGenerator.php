<?php

namespace App\Models;

use App\Exceptions\PasswordGenerator\LengthIsTooShortForSetsAmountException;
use App\Exceptions\PasswordGenerator\CharactersAmountIsTooSmallForPasswordLengthException;
use App\Exceptions\PasswordGenerator\TimeoutException;
use App\Exceptions\PasswordGenerator\UnexpectedEndOfAvailableSetsException;
use App\Exceptions\PasswordGenerator\UnexpectedPasswordGenerationFailureException;
use App\Interfaces\PasswordGeneratorInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordGenerator extends Model implements PasswordGeneratorInterface
{
    use HasFactory;

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
    private string $resultPassword = '';

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
     * Get a password string
     *
     * @return string
     *
     * @throws CharactersAmountIsTooSmallForPasswordLengthException
     * @throws LengthIsTooShortForSetsAmountException
     * @throws TimeoutException
     * @throws UnexpectedPasswordGenerationFailureException
     * @throws UnexpectedEndOfAvailableSetsException
     */
    public function getPassword(): string
    {
        $this->verifySetSettings();
        $startTime = microtime(true);

        while (true) {
            $time = microtime(true) - $startTime;
            if ($time > 25) {
                throw new TimeoutException();
            }

            $this->updatePasswordMask();
            $this->resetAvailableCharacters();

            for ($i = 0; $i < $this->length; $i++) {
                $setType = (int) $this->passwordMask[$i];
                $usedChar = $this->getNextCharacter($setType);
                $this->resultPassword .= $usedChar;
            }

            if (!$this->isPasswordAlreadyUsed($this->resultPassword)) {
                $this->storePassword($this->resultPassword);
                return $this->resultPassword;
            }
        }

        throw new UnexpectedPasswordGenerationFailureException();
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

    private function storePassword(string $password): void
    {
        $this->pass_hash = password_hash($password, PASSWORD_DEFAULT);
        $this->save();
    }

    private function processSet(bool $isNeedToAdd, int $setType): void
    {
        $this->removeSet($setType);
        if ($isNeedToAdd)
            $this->addSet($setType);
    }

    /**
     * @throws UnexpectedEndOfAvailableSetsException
     */
    private function getNextCharacter(int $setType): string
    {
        if (empty($this->availableCharacters[$setType]))
            $setType = self::SET_RANDOM;

        while ($setType === self::SET_RANDOM) {
            $newSetTypeKey = array_rand($this->usedSets);
            if (!strlen($this->availableCharacters[$this->usedSets[$newSetTypeKey]])) {
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

    private function removeSet(int $setType): void
    {
        $this->usedSets = array_diff($this->usedSets, [$setType]);
        unset($this->availableCharacters[$setType]);
    }

    private function addSet(int $setType): void
    {
        $this->usedSets[] = $setType;
        $this->resetAvailableCharacters();
    }

    private function resetAvailableCharacters(): void
    {
        foreach ($this->usedSets as $set) {
            $this->availableCharacters[$set] = self::SETS[$set];
        }
        $this->resultPassword = '';
    }


    private function getRandomCharFromString(string $string): string
    {
        return $string[$this->getRandomCharacterIndexFromString($string)];
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
        return str_repeat(self::SET_RANDOM, $length);
    }

    private function getRandomPasswordMaskIndex(string $mask): int
    {
        $index = $this->getRandomCharacterIndexFromString($mask);
        return $this->getNextAvailableRandomIndex($mask, $index);
    }

    private function getRandomCharacterIndexFromString(string $string): int
    {
        return mt_rand(0, strlen($string) - 1);
    }

    private function getNextAvailableRandomIndex(string $mask, int $currentIndex): int
    {
        if ($this->isRandomIndexInMask($mask, $currentIndex))
            return $currentIndex;

        $newIndex = $this->getNextIndexInMask($mask, $currentIndex);
        while (!$this->isRandomIndexInMask($mask, $newIndex))
            $this->getNextAvailableRandomIndex($mask, $newIndex);

        return $newIndex;
    }

    private function getNextIndexInMask(string $mask, int $currentIndex): int
    {
        return $this->isLastInList($mask, $currentIndex) ? 0 : $currentIndex + 1;
    }

    private function isLastInList(string $mask, int $currentIndex): bool
    {
        return $currentIndex === strlen($mask) -1;
    }

    private function isRandomIndexInMask(string $mask, int $index): bool
    {
        return $mask[$index] === (string) self::SET_RANDOM;
    }

    /**
     * @throws CharactersAmountIsTooSmallForPasswordLengthException
     * @throws LengthIsTooShortForSetsAmountException
     */
    private function verifySetSettings(): void
    {
        $length = $this->length;
        $charsAmount = strlen(implode('', $this->availableCharacters));
        $setsAmount = count($this->usedSets);

        if ($charsAmount < $length)
            throw new CharactersAmountIsTooSmallForPasswordLengthException($charsAmount, $length);

        if ($setsAmount > $length)
            throw new LengthIsTooShortForSetsAmountException($setsAmount, $length);
    }
}
