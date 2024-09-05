<?php

namespace App\Filament\Resources\NamecheapAccountResource\Pages;

use App\Classes\Application\Contracts\AccountServiceInterface;
use App\Classes\Application\Contracts\DomainServiceInterface;
use App\Classes\Application\DomainService;
use App\Classes\Application\Exceptions\NamecheapAccountException;
use App\Filament\Resources\NamecheapAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewNamecheapAccount extends ViewRecord
{

    protected DomainService $domainService;
    protected AccountServiceInterface $accountService;
    protected string $apiStatusAccounts = 'pending';
    protected string $apiStatusDomains = 'pending';
    protected array $balanceData;
    protected array $domains = [];

    /**
     * @var array{TotalItems: int, CurrentPage: int, PageSize: int}
     */
    protected array $paging = [
        'TotalItems' => 0,
        'CurrentPage' => 1,
        'PageSize' => 10
    ];

    public ?string $searchQuery = null;

    protected static string $resource = NamecheapAccountResource::class;
    protected static string $view = 'filament.pages.namechip-account';

    public function boot(AccountServiceInterface $accountService, DomainServiceInterface $domainService)
    {
        $this->accountService = $accountService;
        $this->domainService = $domainService;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function beforeFill(): void
    {
        $this->getAccountInfo();
        $this->getDomainsList();
    }

    protected function getAccountInfo(): void
    {

        try {
            $this->apiStatusAccounts = 'success';
            $this->balanceData = $this->accountService->getBalances($this->record->username, $this->record->api_key);
        } catch (NamecheapAccountException $ex) {
            $this->apiStatusAccounts = 'failed';
            $this->balanceData = [
                'Errors' => [
                    'Error' => [
                        '__text' => $ex->getMessage(),
                        '_Number' => $ex->getCode()
                    ]
                ]
            ];
        }
    }

    public function getDomainsList(): void
    {

        $result = $this->domainService->getAll(
            $this->record->username,
            $this->record->api_key,
            $this->searchQuery,
            $this->paging['CurrentPage'],
            $this->paging['PageSize']
        );

        $this->domains = $result['domains'];
        $this->paging = $result['paging'];

        $this->apiStatusDomains = 'success';
    }

    public function search(): void
    {
        $this->paging['CurrentPage'] = 1;
        $this->getAccountInfo();
        $this->getDomainsList();
    }

    public function nextPage(): void
    {
        $this->paging['CurrentPage']++;
        $this->getAccountInfo();
        $this->getDomainsList();
    }

    public function previousPage(): void
    {
        $this->getAccountInfo();
        $this->getDomainsList();
    }
}
