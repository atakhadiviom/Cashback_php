<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\Admin\CustomerImportController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

final class CustomerImportControllerTest extends TestCase
{
    public function testReadRowsParsesCsvAndSkipsHeaderAndBlankRows(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'customers') . '.csv';
        file_put_contents($path, "\xEF\xBB\xBFنام,نام خانوادگی,کد,موبایل,تولد\nAli,Ahmadi,12345678901,09123456789,1403/01/01\n,,,,\n");

        $rows = $this->readRows(['name' => 'customers.csv', 'tmp_name' => $path]);
        unlink($path);

        $this->assertCount(1, $rows);
        $this->assertSame(2, $rows[0]['row']);
        $this->assertSame('Ali', $rows[0]['values'][0]);
        $this->assertSame('12345678901', $rows[0]['values'][2]);
    }

    public function testReadRowsParsesXlsxAndSkipsHeader(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['نام', 'نام خانوادگی', 'کد', 'موبایل', 'تولد'],
            ['Sara', 'Karimi', '1234567890', '09123456789', '1403/01/02'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'customers') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $rows = $this->readRows(['name' => 'customers.xlsx', 'tmp_name' => $path]);
        unlink($path);

        $this->assertCount(1, $rows);
        $this->assertSame(2, $rows[0]['row']);
        $this->assertSame('Sara', $rows[0]['values'][0]);
        $this->assertSame('1234567890', $rows[0]['values'][2]);
    }

    public function testFallbackXlsxReaderParsesSharedStringsWithoutComposerRuntime(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['نام', 'نام خانوادگی', 'کد', 'موبایل', 'تولد'],
            ['Neda', 'Rahimi', '10987654321', '09123456789', '1403/01/03'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'customers') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $controller = new CustomerImportController();
        $method = new \ReflectionMethod($controller, 'readXlsxRowsWithZip');
        $rows = $method->invoke($controller, $path);
        unlink($path);

        $this->assertCount(1, $rows);
        $this->assertSame(2, $rows[0]['row']);
        $this->assertSame('Neda', $rows[0]['values'][0]);
        $this->assertSame('10987654321', $rows[0]['values'][2]);
    }

    public function testReadRowsRejectsUnsupportedFormats(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('فقط فایل‌های CSV و XLSX پشتیبانی می‌شوند.');

        $this->readRows(['name' => 'customers.xls', 'tmp_name' => '/tmp/customers.xls']);
    }

    public function testFallbackXlsxReaderParsesStringTypedCells(): void
    {
        $controller = new CustomerImportController();
        $cellValue = new \ReflectionMethod($controller, 'xlsxCellValue');
        $cell = new \SimpleXMLElement('<c r="A1" t="str"><v>Reza</v></c>');

        $value = $cellValue->invoke($controller, $cell, null, []);

        $this->assertSame('Reza', $value);
    }

    public function testFallbackXlsxReaderAcceptsImportTemplateWithoutDataRows(): void
    {
        $path = dirname(__DIR__, 2) . '/outputs/cashback_import_template.xlsx';
        if (!is_file($path)) {
            $this->markTestSkipped('Import template file is not available.');
        }

        $controller = new CustomerImportController();
        $method = new \ReflectionMethod($controller, 'readXlsxRowsWithZip');
        $rows = $method->invoke($controller, $path);

        $this->assertSame([], $rows);
    }

    private function readRows(array $file): array
    {
        $controller = new CustomerImportController();
        $method = new \ReflectionMethod($controller, 'readRows');

        return $method->invoke($controller, $file);
    }
}
