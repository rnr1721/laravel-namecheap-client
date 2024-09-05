<?php

namespace App\Classes\Countries;

use App\Classes\Countries\Contracts\CountriesInterface;

class Countries implements CountriesInterface
{

    protected ?array $countryList = null;
    protected ?array $countryCodes = null;
    protected array $countries = [];

    public function __construct()
    {
        $this->populateCountries();
    }

    public function getCountriesList(): array
    {
        if ($this->countryList) {
            return $this->countryList;
        }
        $result = [];
        foreach ($this->countries as $countryCode => $countryData) {
            $result[strtoupper($countryCode)] = $countryData['name'];
        }
        $this->countryList = $result;
        return $result;
    }
    public function getCountryCodes(): array
    {
        if ($this->countryCodes) {
            return $this->countryCodes;
        }
        $result = [];
        foreach ($this->countries as $countryData) {
            $result[$countryData['code']] = $countryData['name'] . ' (' . $countryData['code'] . ')';
        }
        $this->countryCodes = $result;
        return $result;
    }

    public function findMatchingCountryCode($countryCode, $availableCountryCodes): string
    {
        $countryCode = ltrim($countryCode, '+');

        foreach ($availableCountryCodes as $code => $label) {
            if (strpos($label, $countryCode) !== false) {
                return $code;
            }
        }

        foreach ($availableCountryCodes as $code => $label) {
            if (strpos($label, substr($countryCode, 0, 2)) !== false) {
                return $code;
            }
        }

        return $countryCode;
    }

    public function findMatchingCountry($country, $availableCountries): string
    {

        $country = strtoupper($country);

        foreach ($availableCountries as $code => $name) {
            if (strtoupper($code) === $country) {
                return $code;
            }
        }

        foreach ($availableCountries as $code => $name) {
            if (stripos($name, $country) !== false) {
                return $code;
            }
        }

        return $country;
    }

    private function populateCountries(): void
    {
        $this->countries = [
            'ua' => [
                'code' => '+380',
                'name' => 'Ukraine'
            ],
            'us' => [
                'code' => '+1',
                'name' => 'United states'
            ],
            'uk' => [
                'code' => '+44',
                'name' => 'United Kingdom'
            ],
            'fr' => [
                'code' => '+33',
                'name' => 'France'
            ],
            'de' => [
                'code' => '+49',
                'name' => 'Germany'
            ],
        ];
    }
}
