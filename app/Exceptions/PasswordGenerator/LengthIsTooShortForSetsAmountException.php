<?php

namespace App\Exceptions\PasswordGenerator;

class LengthIsTooShortForSetsAmountException extends \Exception
{
    public function __construct(int $setsAmount, int $length    )
    {
        $message = sprintf(
            "Password length (%d) too small to use current amount of sets (%d)",
            $length,
            $setsAmount,
        ) ;

        parent::__construct($message);
    }
}
