<?php
namespace spec\Jihe\Services\Excel;

use PhpSpec\ObjectBehavior;
use \PHPUnit_Framework_Assert as Assert;

class ExcelReaderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(\Jihe\Services\Excel\ExcelReader::class);
    }
    
    function it_throws_exception_if_excel_format_error()
    {
        // excel file is Excel2007 format, but we read it as 'Excel5'
        $this->shouldThrow(new \InvalidArgumentException('excel内容格式不正确'))
             ->duringRead(__DIR__ .'/test-data/hello_world_excel_2007.xls', function () {
                          }, [ 'excel_type' => 'Excel5' ]);
    }
    
    function it_succeeds_with_no_explicit_type_requested()
    {
        $this->shouldNotThrow()
             ->duringRead(__DIR__ .'/test-data/hello_world_excel_2007.xls', function () {});
    }
    
    function it_can_read_successfully_the_whole_file()
    {
        $expected = [
            ['Product Id', 'Description', 'Price', 'Amount', 'Total'],
            ['0X124789',   'Apple Watch',  1099,   2,         2198  ],
            ['0X562894',   'Apple TV',     99,     1,         99    ],
        ];
        $this->read(__DIR__ .'/test-data/excel5.xls', function ($values, $row, $totalRow) use($expected) {
            Assert::assertEquals($expected[$row], $values);
        });
    }
    
    function it_can_read_successfully_skip_few_rows()
    {
        $expected = [
            ['0X124789',   'Apple Watch',  1099,   2,         2198],
            ['0X562894',   'Apple TV',     99,     1,         99  ],
        ];
        $this->read(__DIR__ .'/test-data/excel5.xls', function ($values, $row, $totalRow) use($expected) {
            Assert::assertEquals($expected[$row], $values);
        }, [ 'from_row' => 2 ]);
    }
    
    function it_can_read_successfully_skip_few_rows_and_columns()
    {
        $expected = [
            ['Apple Watch',  1099,   2,         2198],
            ['Apple TV',     99,     1,         99  ],
        ];
        $this->read(__DIR__ .'/test-data/excel5.xls', function ($values, $row, $totalRow) use($expected) {
            Assert::assertEquals($expected[$row], $values);
        }, [ 'from_row' => 2, 'from_column' => 'B' ]);
    }
    
    function it_can_read_successfully_limit_rows_and_columns()
    {
        $expected = [
            ['Apple Watch',  1099],
            ['Apple TV',     99  ],
        ];
        $this->read(__DIR__ .'/test-data/excel5.xls', function ($values, $row, $totalRow) use($expected) {
            Assert::assertEquals($expected[$row], $values);
        }, [ 'from_row' => 2, 'to_row' => 3,  // we only get 2 rows containg products
             'from_column' => 'B', 'to_column' => 'C' ]);
    }
    
    function it_can_read_successfully_even_if_to_row_exceeds_higest_row()
    {
        $expected = [
            ['Apple Watch',  1099],
            ['Apple TV',     99  ],
            [null,           null], // not existing row
            [null,           null], // not existing row
        ];
        $this->read(__DIR__ .'/test-data/excel5.xls', function ($values, $row, $totalRow) use($expected) {
            Assert::assertEquals($expected[$row], $values);
        }, [ 'from_row' => 2, 'to_row' => 5,  // we only get 2 rows containg products
            'from_column' => 'B', 'to_column' => 'C' ]);
    }

    function it_can_read_successfully_the_whole_file_in_batch()
    {
        $expected = [
            ['Product Id', 'Description', 'Price', 'Amount', 'Total'],
            ['0X124789',   'Apple Watch',  1099,   2,         2198  ],
            ['0X562894',   'Apple TV',     99,     1,         99    ],
        ];
        $this->read(__DIR__ .'/test-data/excel5.xls', function ($values, $row, $totalRow) use($expected) {
           if ($row ==  0) { // first batch
               Assert::assertEquals($expected[$row], $values[0]);
               Assert::assertEquals($expected[$row + 1], $values[1]);
           } else if ($row == 2) { // second batch
               Assert::assertEquals($expected[$row], $values[0]);
           }
        }, [
            'batch_size' => 2
        ]);
    }

    function it_stops_on_user_request()
    {
        $expected = [
            ['Apple Watch',  1099],
        ];
        $this->read(__DIR__ .'/test-data/excel5.xls', function ($values, $row) use($expected) {
            Assert::assertEquals($expected[$row], $values);
            if ($row == 0) {  // only read the first line
                return true;
            }
        }, [ 'from_row' => 2, 'to_row' => 5,  // we only get 2 rows containg products
            'from_column' => 'B', 'to_column' => 'C' ]);
    }
    
}