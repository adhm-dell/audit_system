<?php

namespace App\Filament\Resources\OwnerWithdrawalResource\Pages;

use App\Filament\Resources\OwnerWithdrawalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOwnerWithdrawals extends ListRecords
{
    protected static string $resource = OwnerWithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('تسجيل مسحوبات'),
        ];
    }
}
