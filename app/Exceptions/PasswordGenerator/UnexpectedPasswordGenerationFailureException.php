<?php

namespace App\Exceptions\PasswordGenerator;

class UnexpectedPasswordGenerationFailureException extends \Exception
{
    public function __construct()
    {
        parent::__construct("Unexpected password generation failure");
    }
}
