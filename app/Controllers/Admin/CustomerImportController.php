<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Services\ActivityLogger;
use App\Services\CustomerService;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class CustomerImportController
{
    public function form(): void
    {
        View::render('admin/customers_import', ['errors' => [], 'preview' => []]);
    }

    public function import(): void
    {
        Csrf::requireValid();
        $file = $this->uploadedFile();
        if (!$file || empty($file['tmp_name'])) {
            View::render('admin/customers_import', ['errors' => ['file' => 'فایل CSV یا Excel انتخاب نشود.'], 'preview' => []]);
            return;
        }

        try {
            $rows = $this->readRows($file);
        } catch (\Throwable $exception) {
            View::render('admin/customers_import', ['errors' => ['file' => $exception->getMessage()], 'preview' => []]);
            return;
        }

        $preview = [];
        $errors = [];
        $service = new CustomerService();
        $imported = 0;

        foreach ($rows as $entry) {
            $rowNum = $entry['row'];
            $row = $entry['values'];
            $data = [
                'first_name' => $row[0] ?? '',
                'last_name' => $row[1] ?? '',
                'national_code' => $row[2] ?? '',
                'phone_number' => $row[3] ?? '',
                'birthday' => $row[4] ?? '',
            ];
            if (!empty($_POST['preview_only'])) {
                $preview[] = ['row' => $rowNum, 'data' => $data];
                continue;
            }
            $result = $service->create($data);
            if (!$result['ok']) {
                $errors[] = "ردیف {$rowNum}: " . implode(', ', $result['errors']);
            } else {
                $imported++;
            }
        }

        if (!empty($_POST['preview_only'])) {
            View::render('admin/customers_import', ['errors' => [], 'preview' => $preview]);
            return;
        }

        (new ActivityLogger())->log('customer_import', "واردات مشتریان: {$imported} مشتری");
        Flash::set('success', "{$imported} مشتری وارد شد." . ($errors ? ' برخی ردیف‌ها خطا داشتند.' : ''));
        \redirect('/customers');
    }

    private function uploadedFile(): ?array
    {
        if (!empty($_FILES['import_file']['tmp_name'])) {
            return $_FILES['import_file'];
        }

        return !empty($_FILES['csv']['tmp_name']) ? $_FILES['csv'] : null;
    }

    /**
     * @return array<int, array{row: int, values: array<int, string>}>
     */
    private function readRows(array $file): array
    {
        $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

        return match ($extension) {
            'csv' => $this->readCsvRows((string) $file['tmp_name']),
            'xlsx' => $this->readXlsxRows((string) $file['tmp_name']),
            default => throw new \RuntimeException('فقط فایل‌های CSV و XLSX پشتیبانی می‌شوند.'),
        };
    }

    /**
     * @return array<int, array{row: int, values: array<int, string>}>
     */
    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new \RuntimeException('خواندن فایل ممکن نیست.');
        }

        fgetcsv($handle, null, ',', '"', '');
        $rows = [];
        $rowNum = 1;
        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $rowNum++;
            $values = $this->normalizeRow($row);
            if ($this->isBlankRow($values)) {
                continue;
            }
            $rows[] = ['row' => $rowNum, 'values' => $values];
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array{row: int, values: array<int, string>}>
     */
    private function readXlsxRows(string $path): array
    {
        if (!class_exists(IOFactory::class)) {
            return $this->readXlsxRowsWithZip($path);
        }

        $spreadsheet = IOFactory::load($path);
        $sheetRows = $spreadsheet->getActiveSheet()->toArray('', false, false, false);
        $spreadsheet->disconnectWorksheets();

        $rows = [];
        foreach ($sheetRows as $index => $row) {
            if ($index === 0) {
                continue;
            }
            $values = $this->normalizeRow($row);
            if ($this->isBlankRow($values)) {
                continue;
            }
            $rows[] = ['row' => $index + 1, 'values' => $values];
        }

        return $rows;
    }

    /**
     * Lightweight XLSX reader for shared hosting installs without Composer vendor files.
     *
     * @return array<int, array{row: int, values: array<int, string>}>
     */
    private function readXlsxRowsWithZip(string $path): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('برای خواندن فایل Excel، افزونه ZipArchive لازم است.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('خواندن فایل Excel ممکن نیست.');
        }

        try {
            $sharedStrings = $this->xlsxSharedStrings($zip);
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if (!is_string($sheetXml) || $sheetXml === '') {
                throw new \RuntimeException('فایل Excel شیت قابل خواندن ندارد.');
            }

            $sheet = simplexml_load_string($sheetXml);
            if (!$sheet instanceof \SimpleXMLElement) {
                throw new \RuntimeException('ساختار فایل Excel نامعتبر است.');
            }

            $mainNamespace = $this->xlsxMainNamespace($sheet);
            if ($mainNamespace !== null) {
                $sheet->registerXPathNamespace('x', $mainNamespace);
                $sheetRows = $sheet->xpath('//x:sheetData/x:row') ?: [];
            } else {
                $sheetRows = iterator_to_array($sheet->sheetData->row ?? []);
            }

            $rows = [];
            foreach ($sheetRows as $row) {
                $rowNum = (int) ($row['r'] ?? 0);
                if ($rowNum <= 1) {
                    continue;
                }

                $values = [];
                if ($mainNamespace !== null) {
                    $row->registerXPathNamespace('x', $mainNamespace);
                    $cells = $row->xpath('x:c') ?: [];
                } else {
                    $cells = iterator_to_array($row->c ?? []);
                }

                foreach ($cells as $cell) {
                    $reference = (string) ($cell['r'] ?? '');
                    $column = $this->xlsxColumnIndex($reference);
                    if ($column === null) {
                        continue;
                    }

                    $type = (string) ($cell['t'] ?? '');
                    if ($type === 's') {
                        $value = $sharedStrings[(int) $this->xlsxNodeText($cell, 'v', $mainNamespace)] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $value = $this->xlsxNodeText($cell, 'is/t', $mainNamespace);
                    } else {
                        $value = $this->xlsxNodeText($cell, 'v', $mainNamespace);
                    }
                    $values[$column] = $value;
                }

                if ($values === []) {
                    continue;
                }
                ksort($values);
                $normalized = [];
                $maxColumn = max(array_keys($values));
                for ($i = 0; $i <= $maxColumn; $i++) {
                    $normalized[$i] = $values[$i] ?? '';
                }
                $normalized = $this->normalizeRow($normalized);
                if ($this->isBlankRow($normalized)) {
                    continue;
                }
                $rows[] = ['row' => $rowNum, 'values' => $normalized];
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<int, string>
     */
    private function xlsxSharedStrings(\ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if (!is_string($xml) || $xml === '') {
            return [];
        }

        $sharedStrings = simplexml_load_string($xml);
        if (!$sharedStrings instanceof \SimpleXMLElement) {
            return [];
        }

        $values = [];
        $mainNamespace = $this->xlsxMainNamespace($sharedStrings);
        if ($mainNamespace !== null) {
            $sharedStrings->registerXPathNamespace('x', $mainNamespace);
            $items = $sharedStrings->xpath('//x:si') ?: [];
        } else {
            $items = iterator_to_array($sharedStrings->si ?? []);
        }

        foreach ($items as $item) {
            $plainText = $this->xlsxNodeText($item, 't', $mainNamespace);
            if ($plainText !== '') {
                $values[] = $plainText;
                continue;
            }

            $text = '';
            if ($mainNamespace !== null) {
                $item->registerXPathNamespace('x', $mainNamespace);
                $runs = $item->xpath('x:r') ?: [];
            } else {
                $runs = iterator_to_array($item->r ?? []);
            }
            foreach ($runs as $run) {
                $text .= $this->xlsxNodeText($run, 't', $mainNamespace);
            }
            $values[] = $text;
        }

        return $values;
    }

    private function xlsxMainNamespace(\SimpleXMLElement $xml): ?string
    {
        $namespaces = $xml->getNamespaces(true);

        return $namespaces[''] ?? $namespaces['x'] ?? null;
    }

    private function xlsxNodeText(\SimpleXMLElement $node, string $path, ?string $mainNamespace): string
    {
        if ($mainNamespace !== null) {
            $node->registerXPathNamespace('x', $mainNamespace);
            $parts = array_map(static fn (string $part): string => 'x:' . $part, explode('/', $path));
            $matches = $node->xpath(implode('/', $parts)) ?: [];

            return isset($matches[0]) ? (string) $matches[0] : '';
        }

        $current = $node;
        foreach (explode('/', $path) as $part) {
            if (!isset($current->{$part})) {
                return '';
            }
            $current = $current->{$part};
        }

        return (string) $current;
    }

    private function xlsxColumnIndex(string $reference): ?int
    {
        if (!preg_match('/^([A-Z]+)/i', $reference, $matches)) {
            return null;
        }

        $letters = strtoupper($matches[1]);
        $index = 0;
        for ($i = 0, $length = strlen($letters); $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    private function normalizeRow(array $row): array
    {
        return array_map(
            static fn ($value): string => ltrim(trim((string) $value), "\xEF\xBB\xBF"),
            array_values($row)
        );
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }
}
