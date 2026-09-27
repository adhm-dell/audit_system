<?php

namespace App\Services;

use App\Models\DebtInstallment;
use App\Models\Notification as AppNotification;
use App\Models\NotificationSetting;
use App\Models\User;
use Carbon\Carbon;

class NotificationService
{
    public function __construct(
        protected SafeBalanceService $balanceService,
    ) {}

    /**
     * Run all threshold checks and create notifications.
     */
    public function checkThresholds(): void
    {
        $this->checkLowBalance();
        $this->checkUpcomingInstallments();
        $this->checkOverdueInstallments();
    }

    /**
     * Check if cash or digital balance is below threshold.
     */
    public function checkLowBalance(): void
    {
        $settings = NotificationSetting::where('is_enabled', true)
            ->whereIn('type', ['low_cash_balance', 'low_digital_balance'])
            ->get();

        foreach ($settings as $setting) {
            $source = $setting->type === 'low_cash_balance' ? 'cash' : 'digital';
            $balance = $this->balanceService->getBalance($source);
            $label = $source === 'cash' ? 'النقدي' : 'الرقمي';

            if ($setting->threshold_amount !== null && $balance <= $setting->threshold_amount) {
                $message = "⚠️ تنبيه: رصيد الخزنة {$label} منخفض ({$balance} ج.م) — أقل من الحد الأدنى ({$setting->threshold_amount} ج.م)";
                $this->notifyRoles($setting->notify_roles, $setting->type, $message);
            }
        }
    }

    /**
     * Check for installments due within N days.
     */
    public function checkUpcomingInstallments(): void
    {
        $setting = NotificationSetting::where('type', 'installment_due_soon')
            ->where('is_enabled', true)
            ->first();

        if (!$setting || !$setting->days_before_due) {
            return;
        }

        $dueDate = Carbon::today()->addDays($setting->days_before_due);

        $upcoming = DebtInstallment::whereIn('status', ['pending', 'partial'])
            ->whereBetween('due_date', [Carbon::today(), $dueDate])
            ->with('debt')
            ->get();

        foreach ($upcoming as $installment) {
            $message = "📅 قسط رقم {$installment->installment_number} من \"{$installment->debt->title}\" يستحق في " . Carbon::parse($installment->due_date)->format('Y-m-d') . " — المبلغ المتبقي: {$installment->remaining_amount} ج.م";
            $this->notifyRoles(
                $setting->notify_roles,
                'installment_due_soon',
                $message,
                $installment
            );
        }
    }

    /**
     * Check for overdue installments.
     */
    public function checkOverdueInstallments(): void
    {
        $setting = NotificationSetting::where('type', 'installment_overdue')
            ->where('is_enabled', true)
            ->first();

        if (!$setting) {
            return;
        }

        $overdue = DebtInstallment::where('status', 'overdue')
            ->with('debt')
            ->get();

        foreach ($overdue as $installment) {
            $message = "🚨 قسط رقم {$installment->installment_number} من \"{$installment->debt->title}\" متأخر! كان مستحق في " . Carbon::parse($installment->due_date)->format('Y-m-d') . " — المتبقي: {$installment->remaining_amount} ج.م";
            $this->notifyRoles(
                $setting->notify_roles,
                'installment_overdue',
                $message,
                $installment
            );
        }
    }

    /**
     * Send notification to users matching specified roles.
     * Avoids duplicating the exact same message on the same day.
     */
    protected function notifyRoles(array $roles, string $type, string $message, $related = null): void
    {
        $users = User::whereIn('role', $roles)
            ->where('is_active', true)
            ->get();

        foreach ($users as $user) {
            // Avoid duplicate notification for same type+message on same day
            $exists = AppNotification::where('user_id', $user->id)
                ->where('type', $type)
                ->where('message', $message)
                ->whereDate('created_at', Carbon::today())
                ->exists();

            if (!$exists) {
                AppNotification::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'message' => $message,
                    'is_read' => false,
                    'related_type' => $related ? get_class($related) : null,
                    'related_id' => $related?->id,
                ]);
            }
        }
    }
}
