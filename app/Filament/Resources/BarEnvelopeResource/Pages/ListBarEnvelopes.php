<?php

namespace App\Filament\Resources\BarEnvelopeResource\Pages;

use App\Filament\Resources\BarEnvelopeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBarEnvelopes extends ListRecords
{
    protected static string $resource = BarEnvelopeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
