<?php

namespace App\Filament\Resources\NamecheapAccountResource\Pages;

use App\Filament\Resources\NamecheapAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNamecheapAccounts extends ListRecords
{
    protected static string $resource = NamecheapAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
