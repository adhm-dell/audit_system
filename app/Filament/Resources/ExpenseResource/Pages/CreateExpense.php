<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

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

        unset($data['is_split'], $data['single_amount'], $data['single_source']);
        
        return $data;
    }
}
