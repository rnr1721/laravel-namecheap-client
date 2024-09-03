<?php

namespace App\Filament\Resources\NamecheapAccountResource\Pages;

use App\Filament\Resources\NamecheapAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateNamecheapAccount extends CreateRecord
{

    use NamecheapAccountChangeTrait;

    protected static string $resource = NamecheapAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->checkNamechipAccountExists($data);
    }
}
