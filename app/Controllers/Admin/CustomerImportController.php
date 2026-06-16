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
            throw new \RuntimeException('برای خواندن فایل Excel، وابستگی PhpSpreadsheet نصب نشده است.');
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
