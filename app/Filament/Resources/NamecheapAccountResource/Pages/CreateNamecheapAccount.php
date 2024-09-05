<?php

namespace App\Filament\Resources\NamecheapAccountResource\Pages;

use App\Classes\Application\Contracts\AccountServiceInterface;
use App\Classes\Application\Exceptions\NamecheapAccountException;
use App\Filament\Resources\NamecheapAccountResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateNamecheapAccount extends CreateRecord
{

    protected AccountServiceInterface $accountService;
    protected static string $resource = NamecheapAccountResource::class;
    public function boot(AccountServiceInterface $accountService)
    {
        $this->accountService = $accountService;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            $this->accountService->getBalances($data['username'], $data['api_key']);
        } catch (NamecheapAccountException $e) {
            Notification::make()->danger()
                ->title($e->getMessage())
                ->send();

            $this->halt();
        }
        return $data;
    }
}
