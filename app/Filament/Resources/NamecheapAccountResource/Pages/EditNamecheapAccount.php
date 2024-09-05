<?php

namespace App\Filament\Resources\NamecheapAccountResource\Pages;

use App\Classes\Application\Contracts\AccountServiceInterface;
use App\Classes\Application\Exceptions\NamecheapAccountException;
use App\Filament\Resources\NamecheapAccountResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditNamecheapAccount extends EditRecord
{
    protected AccountServiceInterface $accountService;
    protected static string $resource = NamecheapAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function boot(AccountServiceInterface $accountService)
    {
        $this->accountService = $accountService;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
