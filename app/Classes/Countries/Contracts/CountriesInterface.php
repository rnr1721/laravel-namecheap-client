<?php

namespace App\Classes\Countries\Contracts;

interface CountriesInterface
{
    public function getCountriesList(): array;
    public function getCountryCodes(): array;
}
