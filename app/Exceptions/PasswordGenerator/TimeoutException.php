<?php

namespace App\Exceptions\PasswordGenerator;

class TimeoutException extends \Exception
{
    protected $message = 'Timeout while trying to generate a unique password';
}
