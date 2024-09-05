<?php

namespace App\Filament\Pages;

use App\Classes\Application\Contracts\DomainDnsServiceInterface;
use App\Classes\Application\Exceptions\NamecheapDomainDnsException;
use Filament\Pages\Page;
use App\Models\NamecheapAccount;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Classes\NamecheapWrapper\Contracts\ApiWrapperFactoryServiceInterface;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class EditDomainDns extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.edit-domain-dns';

    protected DomainDnsServiceInterface $domainDnsService;
    protected ApiWrapperFactoryServiceInterface $apiFactory;
    public ?NamecheapAccount $account = null;
    public $accountId;
    public ?string $domain = null;
    public ?array $dnsRecords = [];

    public function boot(ApiWrapperFactoryServiceInterface $apiFactory, DomainDnsServiceInterface $domainDnsService)
    {
        $this->apiFactory = $apiFactory;
        $this->domainDnsService = $domainDnsService;
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
            $dnsRecords = $this->domainDnsService->getDnsRecords($this->account->username, $this->account->api_key, $this->domain);
            $this->form->fill(['dnsRecords' => $dnsRecords]);
        } catch (\Exception $e) {
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

        try {
            $this->domainDnsService->setDnsRecords($this->account->username, $this->account->api_key, $this->domain, $this->dnsRecords);
            Notification::make()
                ->title("DNS records for domain {$this->domain} update request sent successfully")
                ->success()
                ->send();
            sleep(5);
            $this->loadDnsRecords();
        } catch (NamecheapDomainDnsException $ex) {
            Notification::make()
                ->title('Failed to update DNS records')
                ->body($ex->getMessage())
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
