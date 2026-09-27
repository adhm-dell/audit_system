<?php

namespace App\Filament\Resources\DailyEnvelopeResource\Pages;

use App\Filament\Resources\DailyEnvelopeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyEnvelope extends CreateRecord
{
    protected static string $resource = DailyEnvelopeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
