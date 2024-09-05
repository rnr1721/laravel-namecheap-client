<?php

namespace App\Filament\Pages;

use App\Classes\Application\Contracts\DomainServiceInterface;
use App\Classes\Application\Exceptions\NamecheapDomainException;
use App\Classes\Countries\Contracts\CountriesInterface;
use App\Models\NamecheapAccount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PurchaseDomain extends Page
{
    protected CountriesInterface $countries;
    protected DomainServiceInterface $domainService;
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

    public function boot(DomainServiceInterface $domainService, CountriesInterface $countries)
    {
        $this->domainService = $domainService;
        $this->countries = $countries;
    }

    protected function getFormSchema(): array
    {

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
                ->options($this->countries->getCountriesList())
                ->required(),
            Select::make('countryCode')
                ->label(_('Country Code'))
                ->options($this->countries->getCountryCodes())
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

        try {
            $result = $this->domainService->isAvailable($this->account->username, $this->account->api_key, $this->domainName);
            $this->domainMessage = 'Domain is available for registration.';
            $this->domainStatus = 'available';
            if (isset($result['premium_info'])) {
                $this->premiumInfo = $result['premium_info'];
                $this->domainMessage .= ' This is a premium domain.';
            }
            Notification::make()
                ->success()
                ->title('Domain is available')
                ->body($this->domainMessage)
                ->send();
        } catch (NamecheapDomainException $ex) {
            $this->domainStatus = 'unavailable';
            $this->handleError($ex->getMessage());
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
        $data = $data = $this->form->getState();
        try {
            $domainCreateResult = $this->domainService->purchaseDomain($this->account->username, $this->account->api_key, $data);
            $domain = $domainCreateResult['_Domain'];
            $chargedAmount = $domainCreateResult['_ChargedAmount'];
            Notification::make()
                ->success()
                ->title('Domain purchased successfully!')
                ->body("Domain {$domain} has been registered for {$data['years']} year(s). Charged amount: {$chargedAmount}")
                ->send();
            $this->resetForm();
        } catch (NamecheapDomainException $ex) {
            Notification::make()
                ->danger()
                ->title('There was an error purchasing the domain.')
                ->body($ex->getMessage())
                ->send();
        }
    }

    private function resetForm()
    {
        $this->form->fill();
        $this->domainStatus = null;
        $this->domainName = '';
    }
}
