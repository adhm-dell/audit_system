<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Filament\Resources\DebtResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDebt extends ViewRecord
{
    protected static string $resource = DebtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('cancel')
                ->label('إلغاء المديونية')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('تأكيد الإلغاء')
                ->modalDescription('هل أنت متأكد من إلغاء هذه المديونية؟ لن يمكن التراجع.')
                ->visible(fn() => $this->record->status === 'active')
                ->action(function () {
                    $this->record->update(['status' => 'cancelled']);
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}
