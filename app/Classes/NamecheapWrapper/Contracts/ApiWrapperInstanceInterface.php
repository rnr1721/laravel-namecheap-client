<?php

namespace App\Classes\NamecheapWrapper\Contracts;

use Namecheap\Api;
use Namecheap\Users\Users;
use Namecheap\Domain\Domains;
use Namecheap\Domain\DomainsDns;
use Namecheap\Domain\DomainsNs;

interface ApiWrapperInstanceInterface
{
    public function getClient(): Api;
    public function getUsers(): Users;
    public function getDomains(): Domains;
    public function getDomainsDns(): DomainsDns;
    public function getDomainsNs(): DomainsNs;
    public function getApiUser(): string;
    public function getApiKey(): string;
    public function getUserName(): string;
    public function getClientIp(): string;
    public function getReturnType(): string;
}
