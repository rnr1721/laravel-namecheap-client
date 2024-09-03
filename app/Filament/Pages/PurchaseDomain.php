<?php
namespace App\Filament\Pages;

use App\Classes\Countries\Contracts\CountriesInterface;
use App\Classes\NamecheapWrapper\Contracts\ApiWrapperFactoryServiceInterface;
use App\Models\NamecheapAccount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
class PurchaseDomain extends Page
{
    public ?NamecheapAccount $account = null;
    public $accountId;
    public $domainName;
    public $domainChecked = false;
    public $domainAvailable = false;
    public $domainStatus = null;
    public $domainMessage = '';
    public $premiumInfo = null;
    // Form data
    public $years = 1;
    public $registrantFirstName;
    public $registrantLastName;
    public $registrantAddress1;
    public $registrantCity;
    public $registrantStateProvince;
    public $registrantPostalCode;
    public $registrantCountry;
    public $registrantPhone;
    public $registrantEmailAddress;
    public $countryCode;
    public $phoneNumber;
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.purchase-domain';
    public function mount($accountId = null): void
    {
        if ($accountId === null) {
            abort(404);
        }
        try {
            $this->account = NamecheapAccount::findOrFail($accountId);
            $this->accountId = $accountId;
            $this->form->fill();
        } catch (ModelNotFoundException $e) {
            abort(404);
        }
    }
    protected function getFormSchema(): array
    {

        $countries = app(CountriesInterface::class);

        return [
            TextInput::make('domainName')
                ->label(_('Domain Name'))->readOnly()
                ->required(),
                Select::make('years')
                ->label('Years')
                ->options([
                    '1' => '1 year',
                    '2' => '2 years',
                    '3' => '3 years',
                    '4' => '4 years',
                    '5' => '5 years'
                ])
                ->required()
                ->default(1),
            TextInput::make('registrantFirstName')
                ->label(_('First Name'))
                ->required(),
            TextInput::make('registrantLastName')
                ->label(_('Last Name'))
                ->required(),
            TextInput::make('registrantAddress1')
                ->label(_('Address'))
                ->required(),
            TextInput::make('registrantCity')
                ->label(_('City'))
                ->required(),
            TextInput::make('registrantStateProvince')
                ->label(_('State/Province'))
                ->required(),
            TextInput::make('registrantPostalCode')
                ->label(_('Postal Code'))
                ->required(),
                Select::make('registrantCountry')
                ->label(_('Country'))
                ->options($countries->getCountriesList())
                ->required(),
            Select::make('countryCode')
                ->label(_('Country Code'))
                ->options($countries->getCountryCodes())
                ->required(),
            TextInput::make('phoneNumber')
                ->label(_('Phone Number'))
                ->required(),
            TextInput::make('registrantEmailAddress')
                ->label(_('Email'))
                ->required()
                ->email(),
        ];
    }
    protected function getFormStatePath(): string
    {
        return 'data';
    }
    protected function getForms(): array
    {
        return [
            'form' => $this->makeForm()
                ->schema($this->getFormSchema())
        ];
    }

    public function rules(): array
    {
        return [
            'years' => ['required', 'integer', 'min:1', 'max:10'],
            'registrantFirstName' => ['required', 'string', 'max:255'],
            'registrantLastName' => ['required', 'string', 'max:255'],
            'registrantAddress1' => ['required', 'string', 'max:255'],
            'registrantCity' => ['required', 'string', 'max:255'],
            'registrantStateProvince' => ['required', 'string', 'max:255'],
            'registrantPostalCode' => ['required', 'string', 'max:20'],
            'registrantCountry' => ['required', 'string', 'max:2'],
            'registrantPhone' => ['required', 'string', 'max:20'],
            'registrantEmailAddress' => ['required', 'email', 'max:255'],
        ];
    }
    public function checkDomainAvailability()
    {
        $apiFactory = app(ApiWrapperFactoryServiceInterface::class);
        $instance = $apiFactory->getNewInstanceFromModel($this->account);
        $response = $instance->getDomains()->check($this->domainName);
        if (is_string($response)) {
            $response = json_decode($response, true);
        }
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->handleError('Failed to process API response.');
            return;
        }
        if (!isset($response['ApiResponse']) || !isset($response['ApiResponse']['CommandResponse'])) {
            $this->handleError('Unexpected API response structure.');
            return;
        }
        $commandResponse = $response['ApiResponse']['CommandResponse'];
        if (!isset($commandResponse['DomainCheckResult'])) {
            $this->handleError('Unable to check domain availability.');
            return;
        }
        $checkResult = $commandResponse['DomainCheckResult'];
        if (!isset($checkResult['_Available'])) {
            $this->handleError('Unable to determine domain availability.');
            return;
        }
        if ($checkResult['_Available'] === 'true') {
            $this->domainStatus = 'available';
            $this->domainMessage = 'Domain is available for registration.';
            if (isset($checkResult['_IsPremiumName']) && $checkResult['_IsPremiumName'] === 'true') {
                $this->premiumInfo = [
                    'registrationPrice' => $checkResult['_PremiumRegistrationPrice'] ?? 'N/A',
                    'renewalPrice' => $checkResult['_PremiumRenewalPrice'] ?? 'N/A',
                    'restorePrice' => $checkResult['_PremiumRestorePrice'] ?? 'N/A',
                    'transferPrice' => $checkResult['_PremiumTransferPrice'] ?? 'N/A',
                ];
                $this->domainMessage .= ' This is a premium domain.';
            }
            Notification::make()
                ->success()
                ->title('Domain is available')
                ->body($this->domainMessage)
                ->send();
        } else {
            $this->domainStatus = 'unavailable';
            $this->domainMessage = 'Domain is not available for registration.';
            Notification::make()
                ->warning()
                ->title('Domain is not available')
                ->body($this->domainMessage)
                ->send();
        }
    }
    private function handleError($message)
    {
        $this->domainStatus = 'error';
        $this->domainMessage = $message;
        Notification::make()
            ->danger()
            ->title('Error checking domain')
            ->body($this->domainMessage)
            ->send();
    }
    public function purchaseDomain()
    {
        $data = $this->form->getState();
        $formattedPhone = $this->formatPhoneNumber($data['countryCode'], $data['phoneNumber']);
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
        $apiFactory = app(ApiWrapperFactoryServiceInterface::class);
        $instance = $apiFactory->getNewInstanceFromModel($this->account);
        try {
            $result = $instance->getDomains()->create($domainInfo, $contactInfo);
            if (is_string($result)) {
                $result = json_decode($result, true);
            }
            if (isset($result['ApiResponse'])) {
                $apiResponse = $result['ApiResponse'];
                if ($apiResponse['_Status'] === 'OK') {
                    $domainCreateResult = $apiResponse['CommandResponse']['DomainCreateResult'];
                    $domain = $domainCreateResult['_Domain'];
                    $chargedAmount = $domainCreateResult['_ChargedAmount'];
                    Notification::make()
                        ->success()
                        ->title('Domain purchased successfully!')
                        ->body("Domain {$domain} has been registered for {$data['years']} year(s). Charged amount: {$chargedAmount}")
                        ->send();
                    $this->resetForm();
                } else {
                    $errorMessage = $apiResponse['Errors']['Error']['__text'] ?? 'Unknown error';
                    throw new \Exception("Domain registration failed: $errorMessage");
                }
            } else {
                throw new \Exception('Invalid API response');
            }
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('There was an error purchasing the domain.')
                ->body($e->getMessage())
                ->send();
        }
    }
    private function resetForm()
    {
        $this->form->fill();
        $this->domainStatus = null;
        $this->domainName = '';
    }
    private function formatPhoneNumber($countryCode, $phoneNumber)
    {
        $digits = preg_replace('/[^0-9]/', '', $phoneNumber);
        $countryCode = ltrim($countryCode, '+');
        return '+' . $countryCode . '.' . $digits;
    }
}
