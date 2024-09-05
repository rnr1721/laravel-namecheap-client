<?php

namespace App\Filament\Resources;

use App\Classes\NamecheapWrapper\Contracts\ApiWrapperFactoryServiceInterface;

use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\NamecheapAccountResource\Pages;
use App\Filament\Resources\NamecheapAccountResource\RelationManagers;
use App\Models\NamecheapAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NamecheapAccountResource extends Resource
{
    protected static ?string $model = NamecheapAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('username')->required(),
                TextInput::make('api_key')->required(),
                TextInput::make('email')->email()->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable()
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNamecheapAccounts::route('/'),
            'create' => Pages\CreateNamecheapAccount::route('/create'),
            'view' => Pages\ViewNamecheapAccount::route('/{record}'),
            'edit' => Pages\EditNamecheapAccount::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return true;
    }
}
