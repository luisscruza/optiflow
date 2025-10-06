<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ProductImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;

final readonly class ParseExcelFileAction
{
    /**
     * Parse an Excel file and extract headers and data.
     */
    public function handle(ProductImport $import, int $maxRows = 1000): array
    {
        try {
            if (! Storage::exists($import->file_path)) {
                throw new InvalidArgumentException('Import file not found');
            }

            $filePath = Storage::path($import->file_path);
            $options = new Options();
            $reader = new Reader($options);

            $reader->open($filePath);

            $data = [];
            $headers = [];
            $rowNumber = 0;
            $isFirstSheet = true;

            foreach ($reader->getSheetIterator() as $sheet) {
                // Only process the first sheet
                if (! $isFirstSheet) {
                    break;
                }

                foreach ($sheet->getRowIterator() as $row) {
                    $rowData = [];
                    $cells = $row->getCells();

                    // Convert cells to array
                    foreach ($cells as $cell) {
                        $rowData[] = $cell->getValue();
                    }

                    // First row contains headers
                    if ($rowNumber === 0) {
                        $headers = $rowData;
                        $rowNumber++;

                        continue;
                    }

                    // Skip empty rows
                    if (array_filter($rowData) === []) {
                        continue;
                    }

                    // Respect max rows limit
                    if (count($data) >= $maxRows) {
                        break;
                    }

                    // Create associative array with headers as keys
                    $associativeRow = [];
                    foreach ($headers as $index => $header) {
                        $associativeRow[$header] = $rowData[$index] ?? null;
                    }

                    $data[] = $associativeRow;
                    $rowNumber++;
                }

                $isFirstSheet = false;
            }

            $reader->close();

            return [
                'headers' => $headers,
                'data' => $data,
                'total_rows' => count($data),
            ];

        } catch (IOException $e) {
            Log::error('Failed to parse Excel file', [
                'import_id' => $import->id,
                'file_path' => $import->file_path,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Failed to parse Excel file: '.$e->getMessage(), $e->getCode(), $e);
        }
    }
}
