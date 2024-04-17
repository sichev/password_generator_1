<?php

namespace App\Interfaces;

interface PasswordGeneratorInterface
{

    const int SET_RANDOM = 0;
    const int SET_NUMERICS = 1;
    const int SET_LOWERCASE = 2;
    const int SET_UPPERCASE = 3;
    const array SETS = [];

    public function getPassword(): string;
    public function useNumbers(bool $isActive = true): PasswordGeneratorInterface;
    public function useLowerCase(bool $isActive = true): PasswordGeneratorInterface;
    public function useUpperCase(bool $isActive = true): PasswordGeneratorInterface;
    public function setLength(int $length): PasswordGeneratorInterface;

}
