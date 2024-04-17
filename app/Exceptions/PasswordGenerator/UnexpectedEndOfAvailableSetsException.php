<?php

namespace App\Exceptions\PasswordGenerator;

class UnexpectedEndOfAvailableSetsException extends \Exception
{
    public function __construct(array $availableSets, string $currentPassword, array $currentSetsIndexes, ?int $selectedSetIndex)
    {
        parent::__construct(sprintf(
            "Unexpected end of available sets. \nSets: %s. \nCurrent password: %s. \nCurrent sets indexes: %s. \n Selected set index: %s.",
            json_encode($availableSets),
            $currentPassword,
            json_encode($currentSetsIndexes),
            json_encode($selectedSetIndex),
        ));
    }
}
