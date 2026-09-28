<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AutoBackupMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->performAutoBackup();

        return $next($request);
    }

    protected function performAutoBackup(): void
    {
        try {
            $dbPath = DB::connection()->getDatabaseName();
            if (!file_exists($dbPath) || $dbPath === ':memory:') {
                return;
            }

            $backupDirectory = 'backups';
            $today = date('Y-m-d');
            $backupFilename = "database_backup_{$today}.sqlite";
            
            // Check if backup for today already exists
            if (Storage::disk('local')->exists("{$backupDirectory}/{$backupFilename}")) {
                return;
            }

            // Create backup directory if it doesn't exist
            if (!Storage::disk('local')->exists($backupDirectory)) {
                Storage::disk('local')->makeDirectory($backupDirectory);
            }

            // Copy file
            $backupPath = Storage::disk('local')->path("{$backupDirectory}/{$backupFilename}");
            copy($dbPath, $backupPath);

            // Optional: Keep only last 30 days of backups to save space
            $files = Storage::disk('local')->files($backupDirectory);
            if (count($files) > 30) {
                // Sort by last modified ascending
                usort($files, function($a, $b) {
                    return Storage::disk('local')->lastModified($a) <=> Storage::disk('local')->lastModified($b);
                });
                
                // Delete oldest files until we have 30
                while (count($files) > 30) {
                    $oldest = array_shift($files);
                    Storage::disk('local')->delete($oldest);
                }
            }
        } catch (\Exception $e) {
            // Silently fail if backup cannot be created to avoid breaking the application
        }
    }
}
