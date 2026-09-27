<?php

namespace App\Filament\Resources\DailyEnvelopeResource\Pages;

use App\Filament\Resources\DailyEnvelopeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyEnvelope extends EditRecord
{
    protected static string $resource = DailyEnvelopeResource::class;

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
