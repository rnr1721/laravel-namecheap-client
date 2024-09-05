<?php

namespace App\Classes\Application;

use App\Classes\Application\Contracts\DomainServiceInterface;
use App\Classes\Application\Contracts\PhoneUtilsInterface;
use App\Classes\Application\Exceptions\NamecheapDomainException;
use App\Classes\Countries\Contracts\CountriesInterface;
use App\Classes\NamecheapWrapper\Contracts\ApiWrapperFactoryServiceInterface;
use Exception;
use Illuminate\Support\Facades\Log;

class DomainService implements DomainServiceInterface
{
    private ApiWrapperFactoryServiceInterface $apiFactory;
    private PhoneUtilsInterface $phoneUtils;
    private CountriesInterface $countries;

    public function __construct(ApiWrapperFactoryServiceInterface $apiFactory, PhoneUtilsInterface $phoneUtils, CountriesInterface $countries)
    {
        $this->apiFactory = $apiFactory;
        $this->phoneUtils = $phoneUtils;
        $this->countries = $countries;
    }

    public function getAll(string $userName, string $apiKey, ?string $searchQuery, ?int $currentPage = 1, ?int $pageSize = 10): array
    {
        $api = $this->apiFactory->getNewInstance($userName, $apiKey, $userName);

        $responseRaw = $api->getDomains()->getList(
            $searchQuery,
            null,
            $currentPage,
            $pageSize
        );
        $response = json_decode($responseRaw, true);

        $paging = [
            'TotalItems' => 0,
            'CurrentPage' => 1,
            'PageSize' => $pageSize
        ];
        $domainsList = [];

        if (isset($response['ApiResponse']['CommandResponse']['DomainGetListResult']['Domain'])) {
            $domainsList = $response['ApiResponse']['CommandResponse']['DomainGetListResult']['Domain'];
            if (isset($domainsList['_Name'])) {
                $domainsList = [$domainsList];
            }
        }

        if (isset($response['ApiResponse']['CommandResponse']['Paging'])) {
            $paging = array_merge($paging, $response['ApiResponse']['CommandResponse']['Paging']);
        }

        $paging['TotalItems'] = (int)$paging['TotalItems'];
        $paging['CurrentPage'] = (int)$paging['CurrentPage'];
        $paging['PageSize'] = (int)$paging['PageSize'];

        return [
            'domains' => $domainsList,
            'paging' => $paging
        ];
    }

    public function isAvailable(string $userName, string $apiKey, string $domainName): array
    {
        $instance = $this->apiFactory->getNewInstance($userName, $apiKey, $userName);
        $response = $instance->getDomains()->check($domainName);
        if (is_string($response)) {
            $response = json_decode($response, true);
        }
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new NamecheapDomainException('Failed to process API response.');
        }
        if (!isset($response['ApiResponse']) || !isset($response['ApiResponse']['CommandResponse'])) {
            throw new NamecheapDomainException('Unexpected API response structure.');
        }
        $commandResponse = $response['ApiResponse']['CommandResponse'];
        if (!isset($commandResponse['DomainCheckResult'])) {
            throw new NamecheapDomainException('Unable to check domain availability.');
        }
        $checkResult = $commandResponse['DomainCheckResult'];
        if (!isset($checkResult['_Available'])) {
            throw new NamecheapDomainException('Unable to determine domain availability.');
        }
        if ($checkResult['_Available'] === 'true') {
            $result = [];
            if (isset($checkResult['_IsPremiumName']) && $checkResult['_IsPremiumName'] === 'true') {
                $result['premium_info'] = [
                    'registrationPrice' => $checkResult['_PremiumRegistrationPrice'] ?? 'N/A',
                    'renewalPrice' => $checkResult['_PremiumRenewalPrice'] ?? 'N/A',
                    'restorePrice' => $checkResult['_PremiumRestorePrice'] ?? 'N/A',
                    'transferPrice' => $checkResult['_PremiumTransferPrice'] ?? 'N/A',
                ];
            }
            return $result;
        }
        throw new NamecheapDomainException('Unable to check domain availability.');
    }

    public function purchaseDomain(string $userName, string $apiKey, array $data): array
    {
        $formattedPhone = $this->phoneUtils->formatPhoneNumber($data['countryCode'], $data['phoneNumber']);
        $domainInfo = [
            'domainName' => $data['domainName'],
            'years' => $data['years'],
        ];
        $contactInfo = [
            'registrantFirstName' => $data['registrantFirstName'],
            'registrantLastName' => $data['registrantLastName'],
            'registrantAddress1' => $data['registrantAddress1'],
            'registrantCity' => $data['registrantCity'],
            'registrantStateProvince' => $data['registrantStateProvince'],
            'registrantPostalCode' => $data['registrantPostalCode'],
            'registrantCountry' => $data['registrantCountry'],
            'registrantPhone' => $formattedPhone,
            'registrantEmailAddress' => $data['registrantEmailAddress'],
            // Repeat some data for tech, admin, and auxBilling
            'techFirstName' => $data['registrantFirstName'],
            'techLastName' => $data['registrantLastName'],
            'techAddress1' => $data['registrantAddress1'],
            'techCity' => $data['registrantCity'],
            'techStateProvince' => $data['registrantStateProvince'],
            'techPostalCode' => $data['registrantPostalCode'],
            'techCountry' => $data['registrantCountry'],
            'techPhone' => $formattedPhone,
            'techEmailAddress' => $data['registrantEmailAddress'],
            'adminFirstName' => $data['registrantFirstName'],
            'adminLastName' => $data['registrantLastName'],
            'adminAddress1' => $data['registrantAddress1'],
            'adminCity' => $data['registrantCity'],
            'adminStateProvince' => $data['registrantStateProvince'],
            'adminPostalCode' => $data['registrantPostalCode'],
            'adminCountry' => $data['registrantCountry'],
            'adminPhone' => $formattedPhone,
            'adminEmailAddress' => $data['registrantEmailAddress'],
            'auxBillingFirstName' => $data['registrantFirstName'],
            'auxBillingLastName' => $data['registrantLastName'],
            'auxBillingAddress1' => $data['registrantAddress1'],
            'auxBillingCity' => $data['registrantCity'],
            'auxBillingStateProvince' => $data['registrantStateProvince'],
            'auxBillingPostalCode' => $data['registrantPostalCode'],
            'auxBillingCountry' => $data['registrantCountry'],
            'auxBillingPhone' => $formattedPhone,
            'auxBillingEmailAddress' => $data['registrantEmailAddress'],
        ];
        $api = $this->apiFactory->getNewInstance($userName, $apiKey, $userName);
        try {
            $result = $api->getDomains()->create($domainInfo, $contactInfo);
            if (is_string($result)) {
                $result = json_decode($result, true);
            }
            if (isset($result['ApiResponse'])) {
                $apiResponse = $result['ApiResponse'];
                if ($apiResponse['_Status'] === 'OK') {
                    return $apiResponse['CommandResponse']['DomainCreateResult'];
                } else {
                    $errorMessage = $apiResponse['Errors']['Error']['__text'] ?? 'Unknown error';
                    throw new NamecheapDomainException("Domain registration failed: $errorMessage");
                }
            } else {
                throw new NamecheapDomainException('Invalid API response');
            }
        } catch (\Exception $e) {
            throw new NamecheapDomainException($e->getMessage());
        }
    }

    public function getDomainContacts(string $userName, string $apiKey, string $domain): array
    {
        $api = $this->apiFactory->getNewInstance($userName, $apiKey, $userName);
        $response = $api->getDomains()->getContacts($domain);

        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorMsg = 'Failed to decode API response';
            Log::error($errorMsg, ['error' => json_last_error_msg()]);
            throw new NamecheapDomainException($errorMsg . ': ' . json_last_error_msg());
        }

        if (isset($decodedResponse['ApiResponse']['CommandResponse']['DomainContactsResult']['Registrant'])) {
            $registrant = $decodedResponse['ApiResponse']['CommandResponse']['DomainContactsResult']['Registrant'];

            $phoneparts = $this->phoneUtils->splitPhoneNumber($registrant['Phone']);

            $matchingCountryCode = $this->countries->findMatchingCountryCode($phoneparts['countryCode'], $this->countries->getCountryCodes());

            $matchingCountry = $this->countries->findMatchingCountry($registrant['Country'], $this->countries->getCountriesList());

            $contacts = [
                'RegistrantFirstName' => $registrant['FirstName'],
                'RegistrantLastName' => $registrant['LastName'],
                'RegistrantAddress1' => $registrant['Address1'],
                'RegistrantCity' => $registrant['City'],
                'RegistrantStateProvince' => $registrant['StateProvince'],
                'RegistrantPostalCode' => $registrant['PostalCode'],
                'RegistrantCountry' => $matchingCountry,
                'RegistrantPhoneCountryCode' => $matchingCountryCode,
                'RegistrantPhone' => $phoneparts['number'],
                'RegistrantEmailAddress' => $registrant['EmailAddress'],
                'RegistrantOrganizationName' => $registrant['OrganizationName'] ?? null,
            ];

            return $contacts;
        } else {
            Log::error('Failed to load domain contacts', ['decodedResponse' => $decodedResponse]);
            throw new NamecheapDomainException('Failed to load domain contacts');
        }
    }

    public function setDomainContacts(string $userName, string $apiKey, string $domain, array $data): array
    {

        $formattedPhone = $this->phoneUtils->formatPhoneNumber(
            $data['RegistrantPhoneCountryCode'],
            $data['RegistrantPhone']
        );

        $domainInfo = [
            'DomainName' => $domain
        ];

        $contactInfo = [
            'registrantFirstName' => $data['RegistrantFirstName'],
            'registrantLastName' => $data['RegistrantLastName'],
            'registrantAddress1' => $data['RegistrantAddress1'],
            'registrantCity' => $data['RegistrantCity'],
            'registrantStateProvince' => $data['RegistrantStateProvince'],
            'registrantPostalCode' => $data['RegistrantPostalCode'],
            'registrantCountry' => $data['RegistrantCountry'],
            'registrantPhone' => $formattedPhone,
            'registrantEmailAddress' => $data['RegistrantEmailAddress'],
            'registrantOrganizationName' => $data['RegistrantOrganizationName'] ?? '',
        ];

        // Copy Registrant data to Tech, Admin, and AuxBilling with lowercase keys
        foreach (['tech', 'admin', 'auxBilling'] as $type) {
            foreach ($contactInfo as $key => $value) {
                $newKey = str_replace('registrant', $type, $key);
                $contactInfo[$newKey] = $value;
            }
        }

        try {
            $api = $this->apiFactory->getNewInstance($userName, $apiKey, $userName);
            $response = $api->getDomains()->setContacts($domainInfo, $contactInfo);

            // Parse the JSON response
            $responseData = json_decode($response, true);

            if (
                isset($responseData['ApiResponse']['CommandResponse']['DomainSetContactResult']['_IsSuccess'])
                && $responseData['ApiResponse']['CommandResponse']['DomainSetContactResult']['_IsSuccess'] === 'true'
            ) {
                return $responseData['ApiResponse']['CommandResponse']['DomainSetContactResult'];
            } else {
                throw new NamecheapDomainException('Failed to update domain contacts');
                Log::error('Failed to update domain contacts', ['response' => $response]);
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
