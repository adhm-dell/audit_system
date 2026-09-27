<?php

namespace App\Filament\Resources\BarEnvelopeResource\Pages;

use App\Filament\Resources\BarEnvelopeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBarEnvelope extends EditRecord
{
    protected static string $resource = BarEnvelopeResource::class;

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
