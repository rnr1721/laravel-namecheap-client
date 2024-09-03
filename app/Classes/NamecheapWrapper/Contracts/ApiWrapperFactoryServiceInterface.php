<?php

namespace App\Classes\NamecheapWrapper\Contracts;

use App\Models\NamecheapAccount;

interface ApiWrapperFactoryServiceInterface
{
    public function getNewInstance(string $apiUser, string $apiKey, string $userName): ApiWrapperInstanceInterface;
    public function getNewInstanceFromModel(NamecheapAccount $account): ApiWrapperInstanceInterface;
}
