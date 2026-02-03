<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ContactImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Reader\CSV\Options;
use OpenSpout\Reader\CSV\Reader;
use RuntimeException;

final readonly class ParseCsvFileAction
{
    /**
     * Parse a CSV file and extract headers and data.
     *
     * @param  array<int, string>|null  $overrideHeaders
     * @return array{headers: array<int, string>, data: array<int, array<string, mixed>>, total_rows: int}
     */
    public function handle(
        ContactImport $import,
        int $maxRows = 1000,
        ?array $overrideHeaders = null,
        bool $firstRowIsHeader = true,
        ?string $filePathOverride = null
    ): array {
        try {
            $filePath = $filePathOverride ?? $import->file_path;

            if (! Storage::exists($filePath)) {
                throw new InvalidArgumentException('Import file not found');
            }

            $filePath = Storage::path($filePath);
            $options = new Options();
            $reader = new Reader($options);

            $reader->open($filePath);

            $data = [];
            $headers = $overrideHeaders ?? [];
            $rowNumber = 0;
            $isFirstSheet = true;
            $totalRows = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                if (! $isFirstSheet) {
                    break;
                }

                foreach ($sheet->getRowIterator() as $row) {
                    $rowData = [];
                    foreach ($row->getCells() as $cell) {
                        $rowData[] = $cell->getValue();
                    }

                    if ($rowNumber === 0 && $firstRowIsHeader) {
                        if ($overrideHeaders !== null) {
                            $rowNumber++;

                            continue;
                        }

                        $headers = array_map(
                            static fn (mixed $header): string => is_string($header) ? mb_trim($header) : (string) $header,
                            $rowData
                        );
                        $rowNumber++;

                        continue;
                    }

                    if (array_filter($rowData) === []) {
                        continue;
                    }

                    $totalRows++;

                    $associativeRow = [];
                    foreach ($headers as $index => $header) {
                        if ($header === '') {
                            continue;
                        }

                        $associativeRow[$header] = $rowData[$index] ?? null;
                    }

                    if (count($data) < $maxRows) {
                        $data[] = $associativeRow;
                    }
                    $rowNumber++;
                }

                $isFirstSheet = false;
            }

            $reader->close();

            return [
                'headers' => $headers,
                'data' => $data,
                'total_rows' => $totalRows,
            ];
        } catch (IOException $exception) {
            Log::error('Failed to parse CSV file', [
                'import_id' => $import->id,
                'file_path' => $import->file_path,
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Failed to parse CSV file: '.$exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
