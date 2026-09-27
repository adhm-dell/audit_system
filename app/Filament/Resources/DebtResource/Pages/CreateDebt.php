<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Filament\Resources\DebtResource;
use App\Services\DebtService;
use Filament\Resources\Pages\CreateRecord;

class CreateDebt extends CreateRecord
{
    protected static string $resource = DebtResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['remaining_amount'] = $data['total_amount'];
        $data['status'] = 'active';
        return $data;
    }

    protected function afterCreate(): void
    {
        $debt = $this->record;
        $numberOfInstallments = $this->data['number_of_installments'] ?? null;

        // Generate installments if applicable
        if ($numberOfInstallments && $debt->isScheduled()) {
            app(DebtService::class)->generateInstallments($debt, (int) $numberOfInstallments);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
