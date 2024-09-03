<?php

namespace App\Filament\Resources\NamecheapAccountResource\Pages;


use App\Filament\Resources\NamecheapAccountResource;
use Filament\Actions;

use Filament\Resources\Pages\EditRecord;

class EditNamecheapAccount extends EditRecord
{

    use NamecheapAccountChangeTrait;

    protected static string $resource = NamecheapAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->checkNamechipAccountExists($data);
    }
}
