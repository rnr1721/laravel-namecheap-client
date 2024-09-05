<?php

namespace App\Classes\Countries\Contracts;

interface CountriesInterface
{
    public function findMatchingCountryCode($countryCode, $availableCountryCodes): string;
    public function findMatchingCountry($country, $availableCountries): string;
    public function getCountriesList(): array;
    public function getCountryCodes(): array;
}
