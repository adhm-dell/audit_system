<?php

namespace App\Observers;

use App\Models\DailyEnvelopeItem;

class DailyEnvelopeItemObserver
{
    public function saved(DailyEnvelopeItem $item): void
    {
        $item->dailyEnvelope->recalculate();
    }

    public function deleted(DailyEnvelopeItem $item): void
    {
        $item->dailyEnvelope->recalculate();
    }
}
