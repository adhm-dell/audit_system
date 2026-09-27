<?php

namespace Database\Seeders;

use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default admin user
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'مدير النظام',
                'password' => Hash::make('adminadmin'),
                'role' => 'owner_admin',
                'is_active' => true,
            ]
        );

        // Default notification settings — must match migration enum values
        $notificationTypes = [
            [
                'type' => 'low_cash_balance',
                'threshold_amount' => 5000.00,
                'days_before_due' => null,
                'is_enabled' => true,
                'notify_roles' => json_encode(['owner_admin', 'accountant']),
            ],
            [
                'type' => 'low_digital_balance',
                'threshold_amount' => 5000.00,
                'days_before_due' => null,
                'is_enabled' => true,
                'notify_roles' => json_encode(['owner_admin', 'accountant']),
            ],
            [
                'type' => 'installment_due_soon',
                'threshold_amount' => null,
                'days_before_due' => 7,
                'is_enabled' => true,
                'notify_roles' => json_encode(['owner_admin', 'accountant']),
            ],
            [
                'type' => 'installment_overdue',
                'threshold_amount' => null,
                'days_before_due' => 0,
                'is_enabled' => true,
                'notify_roles' => json_encode(['owner_admin']),
            ],
        ];

        foreach ($notificationTypes as $setting) {
            NotificationSetting::firstOrCreate(
                ['type' => $setting['type']],
                $setting
            );
        }
    }
}
