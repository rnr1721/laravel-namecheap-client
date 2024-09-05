<?php

namespace App\Classes\Application\Contracts;

interface DomainDnsServiceInterface
{
    public function getDnsRecords(string $userName, string $apiKey, string $domain): array;
    public function setDnsRecords(string $userName, string $apiKey, string $domain, array $dnsRecords): array;
}
