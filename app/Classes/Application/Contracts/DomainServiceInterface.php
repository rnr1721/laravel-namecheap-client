<?php

namespace App\Classes\Application\Contracts;

interface DomainServiceInterface
{
    public function getAll(string $userName, string $apiKey, ?string $searchQuery, ?int $currentPage = 1, ?int $pageSize = 10): array;
    public function isAvailable(string $userName, string $apiKey, string $domainName): array;
    public function purchaseDomain(string $userName, string $apiKey, array $data): array;
    public function getDomainContacts(string $userName, string $apiKey, string $domain): array;
    public function setDomainContacts(string $userName, string $apiKey, string $domain, array $data): array;
}
