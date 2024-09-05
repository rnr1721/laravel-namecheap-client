<?php

namespace App\Classes\Application\Contracts;

interface AccountServiceInterface
{
    public function getBalances(string $username, string $apiKey): array;
}
