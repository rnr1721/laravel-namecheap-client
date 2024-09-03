<?php

namespace App\Filament\Resources\NamecheapAccountResource\Pages;

use App\Classes\NamecheapWrapper\Contracts\ApiWrapperFactoryServiceInterface;
use App\Filament\Resources\NamecheapAccountResource;
use App\Models\NamecheapAccount;
use Illuminate\Support\Facades\App;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

class ViewNamecheapAccount extends ViewRecord
{

    protected ApiWrapperFactoryServiceInterface $apiFactory;
    protected string $apiStatusAccounts = 'pending';
    protected string $apiStatusDomains = 'pending';
    protected array $balanceData;
    protected array $domains = [];
    protected array $paging = [
        'TotalItems' => 0,
        'CurrentPage' => 1,
        'PageSize' => 10
    ];

    public ?string $searchQuery = null;

    protected static string $resource = NamecheapAccountResource::class;
    protected static string $view = 'filament.pages.namechip-account';

    public function __construct()
    {
        // We can not inject this normally. Why?
        $this->apiFactory = App::make(ApiWrapperFactoryServiceInterface::class);
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
        $api = $this->apiFactory->getNewInstanceFromModel($this->record);
        $responseRaw = $api->getUsers()->getBalances();

        $response = json_decode($responseRaw, true);

        if (isset($response['ApiResponse']['_Status']) && $response['ApiResponse']['_Status'] === 'OK') {
            $this->apiStatusAccounts = 'success';
            $this->balanceData = $response['ApiResponse']['CommandResponse']['UserGetBalancesResult'] ?? [];
        } else {
            $this->apiStatusAccounts = 'failed';
            $this->balanceData = [
                'Errors' => [
                    'Error' => [
                        '__text' => $response['ApiResponse']['Errors']['Error']['__text'] ?? 'Unknown error',
                        '_Number' => $response['ApiResponse']['Errors']['Error']['_Number'] ?? 'N/A'
                    ]
                ]
            ];
        }

    }

    public function getDomainsList(): void
    {

        $api = $this->apiFactory->getNewInstanceFromModel($this->record);
        
        $responseRaw = $api->getDomains()->getList(
            $this->searchQuery,
            null,
            $this->paging['CurrentPage'],
            $this->paging['PageSize']
        );
        $response = json_decode($responseRaw, true);
    
        if (isset($response['ApiResponse']['CommandResponse']['DomainGetListResult']['Domain'])) {
            $this->domains = $response['ApiResponse']['CommandResponse']['DomainGetListResult']['Domain'];
            if (isset($this->domains['_Name'])) {
                $this->domains = [$this->domains];
            }
        } else {
            $this->domains = [];
        }
    
        if (isset($response['ApiResponse']['CommandResponse']['Paging'])) {
            $this->paging = array_merge($this->paging, $response['ApiResponse']['CommandResponse']['Paging']);
        }
    
        $this->paging['TotalItems'] = (int)$this->paging['TotalItems'];
        $this->paging['CurrentPage'] = (int)$this->paging['CurrentPage'];
        $this->paging['PageSize'] = (int)$this->paging['PageSize'];
    
        $this->apiStatusDomains = 'success';
    }

    public function search()
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
