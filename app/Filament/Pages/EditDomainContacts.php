<?php

namespace App\Filament\Pages;

use App\Classes\Application\Contracts\DomainServiceInterface;
use App\Classes\Application\Contracts\PhoneUtilsInterface;
use App\Classes\Application\Exceptions\NamecheapDomainException;
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

    protected DomainServiceInterface $domainService;
    protected PhoneUtilsInterface $phoneUtils;
    protected CountriesInterface $countries;
    protected ApiWrapperFactoryServiceInterface $apiFactory;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.edit-domain-contacts';
    protected static bool $shouldRegisterNavigation = false;

    public ?NamecheapAccount $account = null;

    public $accountId;
    public ?string $domain = null;
    public $contacts = [];

    public function boot(ApiWrapperFactoryServiceInterface $apiFactory, DomainServiceInterface $domainService, CountriesInterface $countries, PhoneUtilsInterface $phoneUtils)
    {
        $this->apiFactory = $apiFactory;
        $this->domainService = $domainService;
        $this->countries = $countries;
        $this->phoneUtils = $phoneUtils;
    }

    public function mount($accountId = null, $domain = null): void
    {

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
        try {
            $contacts = $this->domainService->getDomainContacts(
                $this->account->username,
                $this->account->api_key,
                $this->domain
            );
            $this->form->fill($contacts);
            Notification::make()
                ->title(__('Contacts loaded successfully'))
                ->success()
                ->send();
        } catch (NamecheapDomainException $ex) {
            Notification::make()
                ->title(__('Failed to load domain contacts'))
                ->danger()
                ->body(__($ex->getMessage()))
                ->send();
        }
    }

    public function save(): void
    {
        $data = $this->form->getState();
        try {
            $this->domainService->setDomainContacts(
                $this->account->username,
                $this->account->api_key,
                $this->domain,
                $data
            );
            Notification::make()
                ->title(__('Domain contacts updated successfully'))
                ->success()
                ->send();
        } catch (NamecheapDomainException $ex) {
            Notification::make()
                ->title(__('Failed to update domain contacts'))
                ->danger()
                ->body(__($ex->getMessage()))
                ->send();
        }
    }
}
