<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class DatabaseManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';
    
    protected static ?string $navigationLabel = 'إدارة قاعدة البيانات';
    protected static ?string $title = 'إدارة قاعدة البيانات';

    protected static string $view = 'filament.pages.database-management';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backup')
                ->label('تحميل نسخة احتياطية')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $dbPath = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
                    if (file_exists($dbPath) && $dbPath !== ':memory:') {
                        return response()->download($dbPath, 'database_backup_' . date('Y-m-d_H-i-s') . '.sqlite');
                    }
                    
                    Notification::make()
                        ->title('خطأ')
                        ->body('لم يتم العثور على قاعدة البيانات.')
                        ->danger()
                        ->send();
                }),

            Action::make('restore')
                ->label('استعادة بيانات')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('استعادة قاعدة البيانات')
                ->modalDescription('تحذير: سيتم مسح جميع البيانات الحالية في البرنامج واستبدالها بالنسخة المرفوعة. يرجى التأكد من أن الملف بصيغة SQLite.')
                ->form([
                    FileUpload::make('database_file')
                        ->label('ملف قاعدة البيانات (database.sqlite)')
                        ->disk('local')
                        ->directory('temp-backups')
                        ->required()
                ])
                ->action(function (array $data) {
                    $uploadedPath = Storage::disk('local')->path($data['database_file']);
                    $dbPath = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
                    
                    if (!file_exists($uploadedPath)) {
                        Notification::make()
                            ->title('خطأ')
                            ->body('لم يتم العثور على الملف المرفوع: ' . $uploadedPath)
                            ->danger()
                            ->send();
                        return;
                    }

                    try {
                        // Read the uploaded file into memory first
                        $newDbContents = file_get_contents($uploadedPath);
                        
                        if ($newDbContents === false) {
                            throw new \Exception('فشل قراءة الملف المرفوع.');
                        }

                        // Disconnect ALL database connections to release file locks
                        \Illuminate\Support\Facades\DB::disconnect();
                        
                        // Also purge the connection so it's fully released
                        \Illuminate\Support\Facades\DB::purge();
                        
                        // Small delay to ensure Windows releases the file handle
                        usleep(200000); // 200ms
                        
                        // Backup the current one just in case
                        if (file_exists($dbPath)) {
                            copy($dbPath, $dbPath . '.bak');
                        }
                        
                        // Write the new database from memory to disk
                        $written = file_put_contents($dbPath, $newDbContents);
                        
                        if ($written === false) {
                            throw new \Exception('فشل استبدال قاعدة البيانات. الملف قيد الاستخدام.');
                        }
                        
                        // Remove WAL and SHM journal files if they exist
                        @unlink($dbPath . '-wal');
                        @unlink($dbPath . '-shm');
                        
                        // Delete temp uploaded file
                        @unlink($uploadedPath);
                        
                        Notification::make()
                            ->title('تمت الاستعادة بنجاح')
                            ->body('تم استبدال قاعدة البيانات. سيتم إعادة تحميل الصفحة.')
                            ->success()
                            ->send();
                            
                        return redirect('/admin');
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('خطأ في الاستعادة')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
        ];
    }
}
