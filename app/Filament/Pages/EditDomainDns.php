<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\NamecheapAccount;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Classes\NamecheapWrapper\Contracts\ApiWrapperFactoryServiceInterface;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EditDomainDns extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.edit-domain-dns';

    protected ApiWrapperFactoryServiceInterface $apiFactory;
    public ?NamecheapAccount $account = null;
    public $accountId;
    public ?string $domain = null;
    public ?array $dnsRecords = [];

    public function __construct()
    {
        $this->apiFactory = app(ApiWrapperFactoryServiceInterface::class);
    }

    public function mount($accountId = null, $domain = null): void
    {
        if (!auth()->check()) {
            redirect('/admin');
        }

        if ($accountId === null || $domain === null) {
            abort(404);
        }

        try {
            $this->account = NamecheapAccount::findOrFail($accountId);
            $this->accountId = $accountId;
            $this->domain = $domain;
            $this->loadDnsRecords();
        } catch (ModelNotFoundException $e) {
            abort(404);
        }
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Repeater::make('dnsRecords')
                ->schema([
                    Forms\Components\Select::make('RecordType')
                        ->options([
                            'A' => 'A',
                            'AAAA' => 'AAAA',
                            'CNAME' => 'CNAME',
                            'MX' => 'MX',
                            'TXT' => 'TXT',
                            'NS' => 'NS',
                            'URL' => 'URL',
                            'URL301' => 'URL301',
                            'FRAME' => 'FRAME',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('HostName')
                        ->required(),
                    Forms\Components\TextInput::make('Address')
                        ->required(),
                    Forms\Components\TextInput::make('MXPref')
                        ->numeric()
                        ->visible(fn(callable $get) => $get('RecordType') === 'MX')
                        ->required(fn(callable $get) => $get('RecordType') === 'MX'),
                    Forms\Components\TextInput::make('TTL')
                        ->numeric()
                        ->default(1800)
                        ->required(),
                ])
                ->columns(5)
                ->defaultItems(0)
                ->addActionLabel('Add DNS Record'),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('dnsRecords');
    }

    protected function loadDnsRecords(): void
    {
        try {
            $apiInstance = $this->apiFactory->getNewInstanceFromModel($this->account);
            $response = $apiInstance->getDomainsDns()->getHosts($this->getDomainSLD(), $this->getDomainTLD());

            $decodedResponse = json_decode($response, true);

            if (isset($decodedResponse['ApiResponse']['CommandResponse']['DomainDNSGetHostsResult']['host'])) {
                $hosts = $decodedResponse['ApiResponse']['CommandResponse']['DomainDNSGetHostsResult']['host'];

                // If one record, we make array from it
                if (isset($hosts['_HostId'])) {
                    $hosts = [$hosts];
                }

                $this->dnsRecords = collect($hosts)
                    ->map(function ($record) {
                        return [
                            'RecordType' => $record['_Type'],
                            'HostName' => $record['_Name'],
                            'Address' => $record['_Address'],
                            'MXPref' => $record['_MXPref'] ?? null,
                            'TTL' => $record['_TTL'],
                        ];
                    })
                    ->toArray();

                $this->form->fill(['dnsRecords' => $this->dnsRecords]);
            } else {
                Log::warning('No DNS records found in API response');
                $this->dnsRecords = [];
                $this->form->fill(['dnsRecords' => []]);
            }
        } catch (\Exception $e) {
            Log::error('Error loading DNS records', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Failed to load DNS records')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function saveDnsRecords(): void
    {
        $this->validate();

        $apiInstance = $this->apiFactory->getNewInstanceFromModel($this->account);

        $hostNames = [];
        $recordTypes = [];
        $addresses = [];
        $mXPrefs = [];
        $ttls = [];

        // We check whether there is an embedded key 'dnsRecords'
        $records = $this->dnsRecords['dnsRecords'] ?? $this->dnsRecords;

        $counter = 1;
        foreach ($records as $record) {

            if (!isset($record['HostName']) || !isset($record['RecordType']) || !isset($record['Address']) || !isset($record['TTL'])) {
                Log::error('Invalid record structure', ['record' => $record]);
                Notification::make()
                    ->title('Invalid DNS record')
                    ->body("Record at index {$counter} is missing required fields")
                    ->danger()
                    ->send();
                return;
            }

            $hostNames["HostName{$counter}"] = $record['HostName'];
            $recordTypes["RecordType{$counter}"] = $record['RecordType'];
            $addresses["Address{$counter}"] = $record['Address'];
            $mXPrefs["MXPref{$counter}"] = $record['MXPref'] ?? '10';
            $ttls["TTL{$counter}"] = $record['TTL'];

            $counter++;
        }

        try {
            Log::info('Sending setHosts request', [
                'SLD' => $this->getDomainSLD(),
                'TLD' => $this->getDomainTLD(),
                'hostNames' => $hostNames,
                'recordTypes' => $recordTypes,
                'addresses' => $addresses,
                'mXPrefs' => $mXPrefs,
                'ttls' => $ttls,
            ]);

            $response = $apiInstance->getDomainsDns()->setHosts(
                $this->getDomainSLD(),
                $this->getDomainTLD(),
                $hostNames,
                $recordTypes,
                $addresses,
                $mXPrefs,
                null, // EmailType
                $ttls
            );

            $decodedResponse = json_decode($response, true);

            if (
                isset($decodedResponse['ApiResponse']['CommandResponse']['DomainDNSSetHostsResult']['_IsSuccess'])
                && $decodedResponse['ApiResponse']['CommandResponse']['DomainDNSSetHostsResult']['_IsSuccess'] === 'true'
            ) {
                Notification::make()
                    ->title('DNS records update request sent successfully')
                    ->success()
                    ->send();
                sleep(5);

                $this->loadDnsRecords();
            } else {
                $errorMessage = $decodedResponse['ApiResponse']['Errors']['Error'][0] ?? 'Unknown error';
                Log::error('Failed to update DNS records', ['error' => $errorMessage]);
                Notification::make()
                    ->title('Failed to update DNS records')
                    ->body($errorMessage)
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Error saving DNS records', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('An error occurred')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getDomainSLD(): string
    {
        return explode('.', $this->domain)[0];
    }

    protected function getDomainTLD(): string
    {
        return implode('.', array_slice(explode('.', $this->domain), 1));
    }
}
