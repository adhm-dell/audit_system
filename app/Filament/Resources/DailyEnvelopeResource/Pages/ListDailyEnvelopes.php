<?php

namespace App\Filament\Resources\DailyEnvelopeResource\Pages;

use App\Filament\Resources\DailyEnvelopeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyEnvelopes extends ListRecords
{
    protected static string $resource = DailyEnvelopeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة ظرف يومي'),
        ];
    }
}
