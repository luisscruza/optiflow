<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ProductBulkUpdate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Reader\CSV\Options;
use OpenSpout\Reader\CSV\Reader;
use RuntimeException;

final class ParseProductBulkUpdateCsvAction
{
    /**
     * @return array{headers: array<int, string>, data: array<int, array<string, mixed>>, total_rows: int}
     */
    public function handle(ProductBulkUpdate $bulkUpdate): array
    {
        try {
            if (! Storage::exists($bulkUpdate->file_path)) {
                throw new InvalidArgumentException('Bulk update file not found.');
            }

            $options = new Options;
            $reader = new Reader($options);
            $reader->open(Storage::path($bulkUpdate->file_path));

            $headers = [];
            $rows = [];
            $rowIndex = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $values = [];

                    foreach ($row->getCells() as $cell) {
                        $values[] = $cell->getValue();
                    }

                    if ($rowIndex === 0) {
                        $headers = array_map(
                            static fn (mixed $header): string => is_string($header) ? mb_trim($header) : mb_trim((string) $header),
                            $values,
                        );
                        $rowIndex++;

                        continue;
                    }

                    if (array_filter($values, static fn (mixed $value): bool => $value !== null && mb_trim((string) $value) !== '') === []) {
                        continue;
                    }

                    $record = [];

                    foreach ($headers as $columnIndex => $header) {
                        if ($header === '') {
                            continue;
                        }

                        $record[$header] = $values[$columnIndex] ?? null;
                    }

                    $rows[] = $record;
                    $rowIndex++;
                }

                break;
            }

            $reader->close();

            return [
                'headers' => $headers,
                'data' => $rows,
                'total_rows' => count($rows),
            ];
        } catch (IOException $exception) {
            Log::error('Failed to parse product bulk update CSV file.', [
                'bulk_update_id' => $bulkUpdate->id,
                'file_path' => $bulkUpdate->file_path,
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Failed to parse CSV file: '.$exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
