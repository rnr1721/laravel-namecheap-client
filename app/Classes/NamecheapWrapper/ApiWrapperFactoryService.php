<?php

namespace App\Classes\NamecheapWrapper;

use App\Models\NamecheapAccount;
use App\Classes\NamecheapWrapper\Contracts\ApiWrapperFactoryServiceInterface;
use App\Classes\NamecheapWrapper\Contracts\ApiWrapperInstanceInterface;

class ApiWrapperFactoryService implements ApiWrapperFactoryServiceInterface
{
    private bool $isSandbox;
    private string $returnType = 'array';
    private string $clientIp = '';

    /**
     * Constructor
     *
     * @param string $clientIp Client IP (IP must be allowed in sandbox)
     * @param boolean $isSandbox Is it sandbox mode?
     * @param string $returnType Possible: xml, array, json
     */
    public function __construct(string $clientIp, bool $isSandbox = true, string $returnType = 'json')
    {
        $this->clientIp = $clientIp;
        $this->isSandbox = $isSandbox;
        $this->returnType = $returnType;
    }

    public function getNewInstance(string $apiUser, string $apiKey, string $userName): ApiWrapperInstanceInterface
    {
        return new ApiWrapperInstance(
            $this->isSandbox,
            $apiUser,
            $apiKey,
            $userName,
            $this->clientIp,
            $this->returnType
        );
    }

    public function getNewInstanceFromModel(NamecheapAccount $account): ApiWrapperInstanceInterface
    {
        return $this->getNewInstance(
            $account->username,
            $account->api_key,
            $account->username,
            $account->client_ip
        );
    }
}
