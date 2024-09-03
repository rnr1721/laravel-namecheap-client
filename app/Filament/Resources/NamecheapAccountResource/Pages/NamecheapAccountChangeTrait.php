<?php

namespace App\Filament\Resources\NamecheapAccountResource\Pages;

use App\Classes\NamecheapWrapper\Contracts\ApiWrapperFactoryServiceInterface;
use Illuminate\Support\Facades\App;
use Filament\Notifications\Notification;

trait NamecheapAccountChangeTrait
{
    protected function checkNamechipAccountExists(array $data): array
    {
        $apiFactory = App::make(ApiWrapperFactoryServiceInterface::class);
        $api = $apiFactory->getNewInstance(
            $data['username'],
            $data['api_key'],
            $data['username']
        );
        $response = json_decode($api->getUsers()->getBalances(), true);

        if (!isset($response['ApiResponse']['_Status']) || $response['ApiResponse']['_Status'] !== 'OK') {
            $errorMessage = $response['ApiResponse']['Errors']['Error']['__text'] ?? 'Unable to fetch balance';
            Notification::make()->danger()
                ->title($errorMessage)
                ->send();
            $this->halt();
        }
        return $data;
    }
}
