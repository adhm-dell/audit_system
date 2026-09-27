<?php

namespace App\Filament\Resources\BarEnvelopeResource\Pages;

use App\Filament\Resources\BarEnvelopeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBarEnvelope extends CreateRecord
{
    protected static string $resource = BarEnvelopeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
