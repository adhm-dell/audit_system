<?php

namespace App\Filament\Resources\OwnerWithdrawalResource\Pages;

use App\Filament\Resources\OwnerWithdrawalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOwnerWithdrawal extends EditRecord
{
    protected static string $resource = OwnerWithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
