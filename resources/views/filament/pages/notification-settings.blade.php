<x-filament-panels::page>
    @php
        $labels = $this->getSettingLabels();
    @endphp

    <form wire:submit="save">
        <div class="space-y-6">
            @foreach ($labels as $type => $label)
                @if (isset($this->settings[$type]))
                    <x-filament::section :icon="$label['icon']" :heading="$label['title']">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ $label['description'] }}</p>

                        <div class="flex flex-wrap items-center gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model.live="settings.{{ $type }}.is_enabled"
                                    class="fi-checkbox-input rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                />
                                <span class="text-sm font-medium">مفعّل</span>
                            </label>

                            @if ($label['has_threshold'])
                                <div class="flex items-center gap-2">
                                    <label class="text-sm text-gray-500 dark:text-gray-400">الحد:</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        wire:model.lazy="settings.{{ $type }}.threshold_amount"
                                        class="fi-input block w-32 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        placeholder="المبلغ"
                                    />
                                    <span class="text-sm text-gray-500 dark:text-gray-400">ج.م</span>
                                </div>
                            @endif

                            @if ($label['has_days'])
                                <div class="flex items-center gap-2">
                                    <label class="text-sm text-gray-500 dark:text-gray-400">قبل:</label>
                                    <input
                                        type="number"
                                        min="1"
                                        wire:model.lazy="settings.{{ $type }}.days_before_due"
                                        class="fi-input block w-20 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        placeholder="أيام"
                                    />
                                    <span class="text-sm text-gray-500 dark:text-gray-400">يوم</span>
                                </div>
                            @endif
                        </div>
                    </x-filament::section>
                @endif
            @endforeach
        </div>

        <div class="mt-6">
            <x-filament::button type="submit" icon="heroicon-o-check">
                حفظ الإعدادات
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
