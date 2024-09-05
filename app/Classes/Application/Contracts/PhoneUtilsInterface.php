<?php

namespace App\Classes\Application\Contracts;

interface PhoneUtilsInterface
{
    public function formatPhoneNumber(string $countryCode, string $phoneNumber): string;
    public function splitPhoneNumber(string $phoneNumber):array;
}
