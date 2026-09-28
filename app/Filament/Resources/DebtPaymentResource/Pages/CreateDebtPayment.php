<?php

namespace App\Filament\Resources\DebtPaymentResource\Pages;

use App\Filament\Resources\DebtPaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDebtPayment extends CreateRecord
{
    protected static string $resource = DebtPaymentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!isset($data['is_split']) || !$data['is_split']) {
            $source = $data['single_source'] ?? 'cash';
            $amount = $data['single_amount'] ?? 0;
            
            $data['cash_amount'] = 0;
            $data['instapay_amount'] = 0;
            $data['wallet_amount'] = 0;
            $data['fawry_amount'] = 0;
            
            if ($source === 'cash') $data['cash_amount'] = $amount;
            elseif ($source === 'instapay') $data['instapay_amount'] = $amount;
            elseif ($source === 'wallet') $data['wallet_amount'] = $amount;
            elseif ($source === 'fawry') $data['fawry_amount'] = $amount;
        }
        
        return $data;
    }
}
