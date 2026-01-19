<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RNC;
use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

final class SyncDGII extends Command
{
    protected $signature = 'sync:dgii';

    protected $description = 'Sync DGII RNC data into the database';

    private string $url = 'https://dgii.gov.do/app/WebApps/Consultas/RNC/DGII_RNC.zip';

    public function handle(): int
    {
        $zipFilePath = storage_path('app/dgii/DGII_RNC.zip');
        $extractPath = storage_path('app/dgii');
        $txtFilePath = $extractPath . '/DGII_RNC.txt';

        if (! file_exists($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        $this->info('Downloading DGII data...');
        $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($this->url);

        if (! $response->ok()) {
            $this->error('Failed to download file. HTTP status: ' . $response->status());

            return 1;
        }

        file_put_contents($zipFilePath, $response->body());
        $this->info('Download complete. Extracting...');

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) === true) {
            $zip->extractTo($extractPath);
            $zip->close();
        } else {
            $this->error('Failed to open the ZIP file.');

            return 1;
        }

        // find the .txt file inside (recursive)
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractPath, FilesystemIterator::SKIP_DOTS)
        );

        $foundFile = null;
        foreach ($iterator as $file) {
            if ($file->isFile() && mb_strtolower((string) $file->getExtension()) === 'txt') {
                $foundFile = $file->getPathname();
                break;
            }
        }

        if (! $foundFile) {
            $this->error('No .txt file found in the archive.');

            return 1;
        }

        copy($foundFile, $txtFilePath);

        // clean up extracted junk
        $this->deleteSubfolders($extractPath);
        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }

        $this->info('Importing DGII data into database...');

        DB::disableQueryLog();
        RNC::query()->truncate();

        $handle = fopen($txtFilePath, 'r');
        $batch = [];
        $count = 0;

        while (($data = fgetcsv($handle, 0, '|')) !== false) {
            $row = [
                'identification' => $data[0] ?? null,
                'name' => isset($data[1]) ? mb_convert_encoding($data[1], 'UTF-8', 'Windows-1252') : null,
                'comercial_name' => isset($data[2]) ? mb_convert_encoding($data[2], 'UTF-8', 'Windows-1252') : null,
                'status' => $data[9] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // keep last occurrence if duplicate within batch
            $batch[$row['identification']] = $row;

            if (count($batch) >= 1000) {
                RNC::query()->upsert(
                    array_values($batch),
                    ['identification'],
                    // unique key
                    ['name', 'comercial_name', 'status', 'updated_at']
                );
                $count += count($batch);
                $batch = [];
                $this->info("Imported {$count} records...");
            }
        }

        if ($batch !== []) {
            RNC::query()->upsert(array_values($batch), ['identification'], ['name', 'comercial_name', 'status', 'updated_at']);
            $count += count($batch);
        }

        fclose($handle);

        $this->info("DGII data synchronized successfully. Total imported: {$count}");

        return 0;
    }

    private function deleteSubfolders(string $directory): void
    {
        $items = scandir($directory);
        foreach ($items as $item) {
            if (in_array($item, ['.', '..', 'DGII_RNC.txt'])) {
                continue;
            }
            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectoryRecursive($path);
            } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) !== 'txt') {
                unlink($path);
            }
        }
    }

    private function deleteDirectoryRecursive(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            if (is_dir($path)) {
                $this->deleteDirectoryRecursive($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
