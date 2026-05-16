<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handles CSV/Excel exports using LazyCollection for memory efficiency.
 * Streams data directly to the client without buffering the entire dataset.
 *
 * Usage:
 *   return ExportService::streamCsv(
 *       query: Invoice::where('tenant_id', $tenantId),
 *       headers: ['ID', 'Client', 'Amount', 'Status', 'Date'],
 *       rowMapper: fn ($invoice) => [
 *           $invoice->id,
 *           $invoice->client->name ?? '',
 *           $invoice->total,
 *           $invoice->status,
 *           $invoice->created_at->format('Y-m-d'),
 *       ],
 *       filename: 'invoices_export.csv',
 *   );
 */
class ExportService
{
    /**
     * Stream a CSV export from an Eloquent query.
     * Uses cursor() + LazyCollection to avoid memory issues on large datasets.
     *
     * @param  Builder  $query  The Eloquent query builder
     * @param  array<string>  $headers  CSV column headers
     * @param  callable  $rowMapper  Maps each model to an array of values
     * @param  string  $filename  Download filename
     * @param  int  $chunkSize  Flush output buffer every N rows
     */
    public static function streamCsv(
        Builder $query,
        array $headers,
        callable $rowMapper,
        string $filename = 'export.csv',
        int $chunkSize = 500,
    ): StreamedResponse {
        return new StreamedResponse(function () use ($query, $headers, $rowMapper, $chunkSize) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Write headers
            fputcsv($handle, $headers);

            $count = 0;

            // Use cursor for memory-efficient iteration
            $query->cursor()->each(function ($model) use ($handle, $rowMapper, &$count, $chunkSize) {
                $row = $rowMapper($model);
                fputcsv($handle, $row);

                $count++;
                if ($count % $chunkSize === 0) {
                    flush();
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Stream a JSON Lines export (one JSON object per line).
     * Useful for large datasets that need to be machine-readable.
     */
    public static function streamJsonl(
        Builder $query,
        callable $rowMapper,
        string $filename = 'export.jsonl',
    ): StreamedResponse {
        return new StreamedResponse(function () use ($query, $rowMapper) {
            $handle = fopen('php://output', 'w');

            $query->cursor()->each(function ($model) use ($handle, $rowMapper) {
                fwrite($handle, json_encode($rowMapper($model), JSON_UNESCAPED_UNICODE)."\n");
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'application/x-ndjson',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store',
        ]);
    }

    /**
     * Stream an Excel-compatible XML spreadsheet (SpreadsheetML).
     * No external dependencies — Excel opens this format natively.
     */
    public static function streamExcel(
        Builder $query,
        array $headers,
        callable $rowMapper,
        string $filename = 'export.xls',
        string $sheetName = 'Sheet1',
    ): StreamedResponse {
        return new StreamedResponse(function () use ($query, $headers, $rowMapper, $sheetName) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
            fwrite($handle, '<?mso-application progid="Excel.Sheet"?>'."\n");
            fwrite($handle, '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"');
            fwrite($handle, ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'."\n");
            fwrite($handle, '<Styles><Style ss:ID="Header"><Font ss:Bold="1"/></Style>');
            fwrite($handle, '<Style ss:ID="Number"><NumberFormat ss:Format="#,##0.00"/></Style></Styles>'."\n");
            fwrite($handle, "<Worksheet ss:Name=\"{$sheetName}\"><Table>\n");

            fwrite($handle, '<Row ss:StyleID="Header">');
            foreach ($headers as $header) {
                $escaped = htmlspecialchars($header, ENT_XML1, 'UTF-8');
                fwrite($handle, "<Cell><Data ss:Type=\"String\">{$escaped}</Data></Cell>");
            }
            fwrite($handle, "</Row>\n");

            $query->cursor()->each(function ($model) use ($handle, $rowMapper) {
                $row = $rowMapper($model);
                fwrite($handle, '<Row>');
                foreach ($row as $value) {
                    $type = is_numeric($value) ? 'Number' : 'String';
                    $escaped = htmlspecialchars((string) $value, ENT_XML1, 'UTF-8');
                    $style = $type === 'Number' ? ' ss:StyleID="Number"' : '';
                    fwrite($handle, "<Cell{$style}><Data ss:Type=\"{$type}\">{$escaped}</Data></Cell>");
                }
                fwrite($handle, "</Row>\n");
            });

            fwrite($handle, '</Table></Worksheet></Workbook>');
            fclose($handle);
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store',
        ]);
    }

    /**
     * Convert a LazyCollection to an in-memory array for smaller datasets.
     * Use only when you need the full dataset in memory (< 10k rows).
     */
    public static function toArray(Builder $query, callable $rowMapper): array
    {
        return $query->cursor()->map($rowMapper)->toArray();
    }
}
