<?php

namespace App\Exceptions\PasswordGenerator;

class CharactersAmountIsTooSmallForPasswordLengthException extends \Exception
{
    public function __construct(int $charactersAmount, int $passwordLength)
    {
        $message = sprintf(
            "Password length (%d) too big for available characters variations (%d)",
            $passwordLength,
            $charactersAmount,
        );

        parent::__construct($message);
    }
}
