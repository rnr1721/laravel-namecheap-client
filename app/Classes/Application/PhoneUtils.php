<?php

namespace App\Classes\Application;

use App\Classes\Application\Contracts\PhoneUtilsInterface;

class PhoneUtils implements PhoneUtilsInterface
{
    public function formatPhoneNumber(string $countryCode, string $phoneNumber): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phoneNumber);
        $countryCode = ltrim($countryCode, '+');
        return '+' . $countryCode . '.' . $digits;
    }

    public function splitPhoneNumber(string $phoneNumber):array
    {
        $parts = explode('.', $phoneNumber, 2);
        return [
            'countryCode' => ltrim($parts[0], '+'),
            'number' => $parts[1] ?? '',
        ];
    }

}
