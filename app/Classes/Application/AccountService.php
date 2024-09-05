<?php

namespace App\Classes\Application;

use App\Classes\Application\Contracts\AccountServiceInterface;
use App\Classes\Application\Exceptions\NamecheapAccountException;
use App\Classes\NamecheapWrapper\Contracts\ApiWrapperFactoryServiceInterface;
use Illuminate\Support\Facades\Log;

class AccountService implements AccountServiceInterface
{

    private ApiWrapperFactoryServiceInterface $apiFactory;

    public function __construct(ApiWrapperFactoryServiceInterface $apiFactory)
    {
        $this->apiFactory = $apiFactory;
    }

    public function getBalances(string $username, string $apiKey): array
    {
        $api = $this->apiFactory->getNewInstance(
            $username,
            $apiKey,
            $username
        );
        $response = json_decode($api->getUsers()->getBalances(), true);

        if (isset($response['ApiResponse']['_Status']) && $response['ApiResponse']['_Status'] === 'OK') {
            return $response['ApiResponse']['CommandResponse']['UserGetBalancesResult'] ?? [];
        }

        $errorMessage = $response['ApiResponse']['Errors']['Error']['__text'] ?? 'Unable to fetch balance';
        $errorCode = $response['ApiResponse']['Errors']['Error']['_Number'] ?? 'N/A';
        throw new NamecheapAccountException($errorMessage, $errorCode);
    }
}
