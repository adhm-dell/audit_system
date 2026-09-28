<x-filament-panels::page>
    <div class="space-y-4">
        <h2 class="text-lg font-bold">نسخ واستعادة قاعدة البيانات (SQLite)</h2>
        <p class="text-gray-600 dark:text-gray-400">
            يمكنك من خلال هذه الصفحة أخذ نسخة احتياطية لجميع البيانات الخاصة بالنظام، أو استعادة نسخة سابقة. 
        </p>
        <p class="text-gray-600 dark:text-gray-400">
            <strong>تحذير:</strong> عند الاستعادة سيتم حذف كافة البيانات الحالية فوراً واستبدالها بالنسخة المرفوعة، الرجاء التأكد دائماً من أخذ نسخة احتياطية قبل الاستعادة.
        </p>
        <div class="mt-8 border-t pt-4 dark:border-gray-700">
            <h3 class="text-md font-bold mb-4 text-primary-600">النسخ الاحتياطية التلقائية</h3>
            <p class="text-sm text-gray-500 mb-4">يتم أخذ نسخة احتياطية أوتوماتيكياً كل يوم عند استخدام النظام. يتم الاحتفاظ بآخر 30 نسخة.</p>
            
            @if(count($backups) > 0)
                <div class="border rounded-lg overflow-hidden dark:border-gray-700">
                    <table class="w-full text-sm text-right">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">اسم الملف</th>
                                <th class="px-4 py-3">التاريخ</th>
                                <th class="px-4 py-3">الحجم</th>
                                <th class="px-4 py-3">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($backups as $backup)
                                <tr class="bg-white dark:bg-gray-900">
                                    <td class="px-4 py-3 font-medium">{{ $backup['name'] }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $backup['date'] }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $backup['size'] }}</td>
                                    <td class="px-4 py-3">
                                        <button wire:click="downloadAutoBackup('{{ $backup['path'] }}')" type="button" class="text-primary-600 hover:text-primary-900 font-bold">
                                            تحميل
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500">لا توجد نسخ احتياطية تلقائية حتى الآن.</p>
            @endif
        </div>
    </div>
</x-filament-panels::page>
