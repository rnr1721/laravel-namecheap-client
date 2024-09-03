<?php

namespace App\Classes\NamecheapWrapper;

use Namecheap\Api;
use Namecheap\Domain\Domains;
use Namecheap\Domain\DomainsDns;
use Namecheap\Domain\DomainsNs;
use Namecheap\Users\Users;
use App\Classes\NamecheapWrapper\Contracts\ApiWrapperInstanceInterface;

class ApiWrapperInstance implements ApiWrapperInstanceInterface
{

    private ?Api $client = null;
    private string $apiUser;
    private string $apiKey;
    private string $userName;
    private string $clientIp;
    private string $returnType;
    private bool $isSandbox = false;

    public function __construct(bool $isSandbox, string $apiUser, string $apiKey, string $userName, string $clientIp, string $returnType = 'array')
    {
        $this->isSandbox = $isSandbox;
        $this->apiUser = $apiUser;
        $this->apiKey = $apiKey;
        $this->userName = $userName;
        $this->clientIp = $clientIp;
        $this->returnType = $returnType;
    }

    public function getUsers(): Users
    {
        $users = new Users($this->getClient());
        return $this->checkAndMaybeEnableSandbox($users);
    }

    public function getDomains(): Domains
    {
        $domains = new Domains($this->getClient());
        return $this->checkAndMaybeEnableSandbox($domains);
    }

    public function getDomainsDns(): DomainsDns {
        $domainsDns = new DomainsDns($this->getClient());
        return $this->checkAndMaybeEnableSandbox($domainsDns);
    }

    public function getDomainsNs(): DomainsNs {
        $domainsNs = new DomainsNs($this->getClient());
        return $this->checkAndMaybeEnableSandbox($domainsNs);
    }

    public function getClient(): Api
    {
        if ($this->client) {
            return $this->client;
        }
        $this->client = new Api($this->apiUser, $this->apiKey, $this->userName, $this->clientIp, $this->returnType);
        return $this->client;
    }

    public function getApiUser(): string
    {
        return $this->apiUser;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getClientIp(): string
    {
        return $this->clientIp;
    }

    public function getReturnType(): string
    {
        return $this->returnType;
    }

    private function checkAndMaybeEnableSandbox(Api $element): Api
    {
        if ($this->isSandbox) {
            $element->enableSandbox();
        }
        return $element;
    }
}
