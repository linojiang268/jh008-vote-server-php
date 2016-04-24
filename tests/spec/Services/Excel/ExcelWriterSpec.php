<?php
namespace spec\Jihe\Services\Excel;

use PhpSpec\ObjectBehavior;
use Jihe\Services\Excel\ExcelWriter;
use Jihe\Utils\StreamUtil;

class ExcelWriterSpec extends ObjectBehavior
{
    function it_can_write_data_from_scratch_with_simple_header()
    {
        // write header in column 0, 1 and 3
        $headers = ['Product Id', 'Name', 3 => 'Price'];
        $writer = ExcelWriter::fromScratch();
        $writer->writeHeader($headers);
        
        self::saveAndReadExcelSheet($writer, function ($sheet) {
            \PHPUnit_Framework_Assert::assertEquals('Product Id', self::getSheetCellValue($sheet, 0, 1));
            \PHPUnit_Framework_Assert::assertEquals('Name',       self::getSheetCellValue($sheet, 1, 1));
            \PHPUnit_Framework_Assert::assertNull(self::getSheetCellValue($sheet, 2, 1));
            \PHPUnit_Framework_Assert::assertEquals('Price',      self::getSheetCellValue($sheet, 3, 1));
        });
    }
    
    function it_can_write_data_from_scratch_with_multiple_header()
    {
        $writer = ExcelWriter::fromScratch();
        $writer->writeHeader([['Product Id', 'Name'],
                               ['Product Id 2', 'Name 2']
        ]);
        
        self::saveAndReadExcelSheet($writer, function ($sheet) {
            \PHPUnit_Framework_Assert::assertEquals('Product Id',   self::getSheetCellValue($sheet, 0, 1));
            \PHPUnit_Framework_Assert::assertEquals('Name',         self::getSheetCellValue($sheet, 1, 1));
            \PHPUnit_Framework_Assert::assertEquals('Product Id 2', self::getSheetCellValue($sheet, 0, 2));
            \PHPUnit_Framework_Assert::assertEquals('Name 2',       self::getSheetCellValue($sheet, 1, 2));
        });
    }
    
    function it_can_write_data_from_template()
    {
        $writer = ExcelWriter::fromTemplate(__DIR__ . '/test-data/template.xls', [ 'from_row' => 2 ]);
        $writer->write(['0X124789', 'Apple Watch']);
        self::saveAndReadExcelSheet($writer, function ($sheet) {
            // the header from template
            \PHPUnit_Framework_Assert::assertEquals('Product Id',   self::getSheetCellValue($sheet, 0, 1));
            \PHPUnit_Framework_Assert::assertEquals('Description',  self::getSheetCellValue($sheet, 1, 1));
            \PHPUnit_Framework_Assert::assertEquals('Price',        self::getSheetCellValue($sheet, 2, 1));
            \PHPUnit_Framework_Assert::assertEquals('Amount',       self::getSheetCellValue($sheet, 3, 1));
            
            // content written
            \PHPUnit_Framework_Assert::assertEquals('0X124789',     self::getSheetCellValue($sheet, 0, 2));
            \PHPUnit_Framework_Assert::assertEquals('Apple Watch',  self::getSheetCellValue($sheet, 1, 2));
        });
    }
    
    function it_can_write_data_in_incremental_manner()
    {
        $writer = ExcelWriter::fromScratch();
        $writer->write(['0X124789', 'Apple Watch']); // first row
        $writer->write(['0X689321', 'Apple TV']);    // second row
        $writer->write([                             // multiple rows in a batch
            [ '0X5239622', 'MacBook Pro 2015' ],
            [ '0X7623922', 'Apple Server' ],
        ]);    // second row
        
        self::saveAndReadExcelSheet($writer, function ($sheet) {
            // first row
            \PHPUnit_Framework_Assert::assertEquals('0X124789',    self::getSheetCellValue($sheet, 0, 1));
            \PHPUnit_Framework_Assert::assertEquals('Apple Watch', self::getSheetCellValue($sheet, 1, 1));
            
            // second row
            \PHPUnit_Framework_Assert::assertEquals('0X689321',     self::getSheetCellValue($sheet, 0, 2));
            \PHPUnit_Framework_Assert::assertEquals('Apple TV',     self::getSheetCellValue($sheet, 1, 2));
            
            // third row
            \PHPUnit_Framework_Assert::assertEquals('0X5239622',        self::getSheetCellValue($sheet, 0, 3));
            \PHPUnit_Framework_Assert::assertEquals('MacBook Pro 2015', self::getSheetCellValue($sheet, 1, 3));
            
            // fourth row
            \PHPUnit_Framework_Assert::assertEquals('0X7623922',        self::getSheetCellValue($sheet, 0, 4));
            \PHPUnit_Framework_Assert::assertEquals('Apple Server',     self::getSheetCellValue($sheet, 1, 4));
        });
    }
    
    function it_can_overwrite_data()
    {
        $writer = ExcelWriter::fromScratch();
        $writer->write(['0X124789', 'Apple Watch']);       // first row
        $writer->overwrite(['0X689321', 'Apple TV'], 1);   // overwrite the first row
        
        self::saveAndReadExcelSheet($writer, function ($sheet) {
            // first row should be overwritten
            \PHPUnit_Framework_Assert::assertEquals('0X689321',     self::getSheetCellValue($sheet, 0, 1));
            \PHPUnit_Framework_Assert::assertEquals('Apple TV',     self::getSheetCellValue($sheet, 1, 1));
        });
    }
    
    private static function saveAndReadExcelSheet(ExcelWriter $writer, callable $callback, $filename = null)
    {
        // save the resouce as a temporary file
        $file = $filename ?: sys_get_temp_dir() . '/' . uniqid('excel_writer');
        $writer->save(['file' => $file]);
        
        try {
            $phpExcel = \PHPExcel_IOFactory::load($file);
            $callback($phpExcel->getSheet(0));
            unlink($file);
        } catch (\Exception $ex) {
            unlink($file);
            
            throw $ex;
        }
    }
    
    private static function getSheetCellValue(\PHPExcel_Worksheet $sheet, $column, $row)
    {
        return $sheet->getCellByColumnAndRow($column, $row)->getValue();
    }
}