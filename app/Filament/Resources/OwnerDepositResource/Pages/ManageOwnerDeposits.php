<?php

namespace App\Filament\Resources\OwnerDepositResource\Pages;

use App\Filament\Resources\OwnerDepositResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageOwnerDeposits extends ManageRecords
{
    protected static string $resource = OwnerDepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
