<?php

namespace App\Filament\Pages;

use App\Classes\Countries\Contracts\CountriesInterface;
use App\Classes\NamecheapWrapper\Contracts\ApiWrapperFactoryServiceInterface;
use App\Models\NamecheapAccount;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class EditDomainContacts extends Page implements HasForms
{
    use InteractsWithForms;

    protected CountriesInterface $countries;
    protected ApiWrapperFactoryServiceInterface $apiFactory;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.edit-domain-contacts';
    protected static bool $shouldRegisterNavigation = false;

    public ?NamecheapAccount $account = null;

    public $accountId;
    public ?string $domain = null;
    public $contacts = [];

    public function __construct()
    {
        $this->apiFactory = app(ApiWrapperFactoryServiceInterface::class);
        $this->countries = app(CountriesInterface::class);;
    }

    public function mount($accountId = null, $domain = null): void
    {
        if (!auth()->check()) {
            Log::warning('Unauthorized access attempt to EditDomainContacts page');
            redirect('/admin');
        }

        if ($accountId === null || $domain === null) {
            Log::error('Invalid accountId or domain', ['accountId' => $accountId, 'domain' => $domain]);
            abort(404);
        }

        try {
            $this->account = NamecheapAccount::findOrFail($accountId);
            $this->accountId = $accountId;
            $this->domain = $domain;
            $this->loadDomainContacts();
        } catch (ModelNotFoundException $e) {
            Log::error('Account not found', ['accountId' => $accountId, 'error' => $e->getMessage()]);
            abort(404);
        }
    }

    public function form(Form $form): Form
    {

        return $form
            ->schema([
                TextInput::make('RegistrantFirstName')
                    ->label(__('First Name'))
                    ->required(),
                TextInput::make('RegistrantLastName')
                    ->label(__('Last Name'))
                    ->required(),
                TextInput::make('RegistrantAddress1')
                    ->label(__('Address'))
                    ->required(),
                TextInput::make('RegistrantCity')
                    ->label(__('City'))
                    ->required(),
                TextInput::make('RegistrantStateProvince')
                    ->label(__('State/Province'))
                    ->required(),
                TextInput::make('RegistrantPostalCode')
                    ->label(__('Postal Code'))
                    ->required(),
                Select::make('RegistrantCountry')
                    ->label(__('Country'))
                    ->options($this->countries->getCountriesList())
                    ->required(),
                Select::make('RegistrantPhoneCountryCode')
                    ->label(__('Country Code'))
                    ->options($this->countries->getCountryCodes())
                    ->required(),
                TextInput::make('RegistrantPhone')
                    ->label(__('Phone Number'))
                    ->required(),
                TextInput::make('RegistrantEmailAddress')
                    ->label(__('Email'))
                    ->required()
                    ->email(),
                TextInput::make('RegistrantOrganizationName')
                    ->label(__('Organization'))
                    ->nullable(),
            ])
            ->statePath('contacts');
    }

    public function loadDomainContacts(): void
    {
        $api = $this->apiFactory->getNewInstanceFromModel($this->account);
        $response = $api->getDomains()->getContacts($this->domain);

        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to decode API response', ['error' => json_last_error_msg()]);
            Notification::make()
                ->title(__('Failed to load domain contacts'))
                ->danger()
                ->body(__('Invalid API response format'))
                ->send();
            return;
        }

        if (isset($decodedResponse['ApiResponse']['CommandResponse']['DomainContactsResult']['Registrant'])) {
            $registrant = $decodedResponse['ApiResponse']['CommandResponse']['DomainContactsResult']['Registrant'];

            $phoneparts = $this->splitPhoneNumber($registrant['Phone']);

            $matchingCountryCode = $this->findMatchingCountryCode($phoneparts['countryCode'], $this->countries->getCountryCodes());

            $matchingCountry = $this->findMatchingCountry($registrant['Country'], $this->countries->getCountriesList());

            $this->contacts = [
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

            $this->form->fill($this->contacts);

            Notification::make()
                ->title(__('Contacts loaded successfully'))
                ->success()
                ->send();
        } else {
            Log::error('Failed to load domain contacts', ['decodedResponse' => $decodedResponse]);
            Notification::make()
                ->title(__('Failed to load domain contacts'))
                ->danger()
                ->body(__('Invalid API response structure'))
                ->send();
        }
    }


    public function save(): void
    {
        $data = $this->form->getState();

        $formattedPhone = $this->formatPhoneNumber(
            $data['RegistrantPhoneCountryCode'],
            $data['RegistrantPhone']
        );

        $domainInfo = [
            'DomainName' => $this->domain
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
            $api = $this->apiFactory->getNewInstanceFromModel($this->account);
            $response = $api->getDomains()->setContacts($domainInfo, $contactInfo);

            // Parse the JSON response
            $responseData = json_decode($response, true);

            if (
                isset($responseData['ApiResponse']['CommandResponse']['DomainSetContactResult']['_IsSuccess'])
                && $responseData['ApiResponse']['CommandResponse']['DomainSetContactResult']['_IsSuccess'] === 'true'
            ) {
                Notification::make()
                    ->title(__('Domain contacts updated successfully'))
                    ->success()
                    ->send();
            } else {
                Log::error('Failed to update domain contacts', ['response' => $response]);
                Notification::make()
                    ->title(__('Failed to update domain contacts'))
                    ->danger()
                    ->body(__('Unexpected API response structure'))
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Exception while updating domain contacts', ['error' => $e->getMessage()]);
            Notification::make()
                ->title(__('Failed to update domain contacts'))
                ->danger()
                ->body(__('Error: ') . $e->getMessage())
                ->send();
        }
    }

    private function formatPhoneNumber($countryCode, $phoneNumber)
    {
        $countryCode = ltrim($countryCode, '+');
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        return '+' . $countryCode . '.' . $phoneNumber;
    }

    private function splitPhoneNumber($phoneNumber)
    {
        $parts = explode('.', $phoneNumber, 2);
        return [
            'countryCode' => ltrim($parts[0], '+'),
            'number' => $parts[1] ?? '',
        ];
    }

    private function findMatchingCountryCode($apiCountryCode, $availableCountryCodes)
    {
        $apiCountryCode = ltrim($apiCountryCode, '+');

        foreach ($availableCountryCodes as $code => $label) {
            if (strpos($label, $apiCountryCode) !== false) {
                return $code;
            }
        }

        foreach ($availableCountryCodes as $code => $label) {
            if (strpos($label, substr($apiCountryCode, 0, 2)) !== false) {
                return $code;
            }
        }

        return $apiCountryCode;
    }


    private function findMatchingCountry($apiCountry, $availableCountries)
    {

        $apiCountry = strtoupper($apiCountry);

        foreach ($availableCountries as $code => $name) {
            if (strtoupper($code) === $apiCountry) {
                return $code;
            }
        }

        foreach ($availableCountries as $code => $name) {
            if (stripos($name, $apiCountry) !== false) {
                return $code;
            }
        }

        return $apiCountry;
    }
}
