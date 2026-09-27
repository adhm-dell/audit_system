<?php

namespace App\Filament\Resources\OwnerWithdrawalResource\Pages;

use App\Filament\Resources\OwnerWithdrawalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOwnerWithdrawal extends CreateRecord
{
    protected static string $resource = OwnerWithdrawalResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
