<?php
namespace Jihe\Services\Excel;

class ExcelWriter
{
    /**
     * @var \PHPExcel
     */
    private $phpExcel;
    
    /**
     * current working sheet
     * @var PHPExcel_Worksheet
     */
    private $sheet;
    
    /**
     * current row that is writing on
     * @var int
     */
    private $row;
    
    private function __construct(\PHPExcel $phpExcel, \PHPExcel_Worksheet $sheet = null, array $options = [])
    {
        $this->phpExcel = $phpExcel;
        $this->sheet = $sheet ?: $phpExcel->getActiveSheet();
        $this->row = array_get($options, 'from_row', 1);
        
        // add excel properties
        $this->writeExcelProperties($this->phpExcel, array_get($options, 'properties', []));
    }
    
    /**
     * create writer from template excel file
     * 
     * @param string $template   template excel file
     * @param array $options     options including:
     *                           - excel_type     string (optional) hint to show the excel file type
     *                                                   acceptable value should be one of the following:
     *                                                   * Excel5
     *                                                   * Excel2007
     *                                                   if not specified, its type will be guessed from template file.
     *                           - data_only      boolean (optional) when turned on, only data from template file
     *                                                    will be read(thus, things like cell style will be discarded.).
     *                                                    by default, it is false.
     *                           - sheet         int (optional) which sheet of template will be read. if not specified, 
     *                                                          the first sheet will.
     *                           - from_row      int (optional) from which row to read data, starting from 1 (default).
     *                           - properties    array (optional) properties for excel file, including
     *                                                 * creator       (string) who creates this file
     *                                                 * title         (string) title of the excel
     *                                                 * subject       (string) subject of the excel
     *                                                 * description   (string) description of the excel 
     * @return \Jihe\Services\Excel\ExcelWriter
     */
    public static function fromTemplate($template, array $options = [])
    {
        $reader = new ExcelReader();
        $phpExcel = $reader->load($template, array_get($options, 'excel_type'), 
                                             array_get($options, 'data_only', false));
        $sheet = is_null(array_get($options, 'sheet')) ? $phpExcel->getActiveSheet() 
                                                       : $phpExcel->getSheet(array_get($options, 'sheet'));
        
        return new self($phpExcel, $sheet, $options);
    }
    
    /**
     * create writer from scratch - a completely new excel file will be written on
     *
     * @param array $options     options including:
     *                           - from_row      int (optional) from which row to read data, starting from 1 (default).
     *                           - properties    array (optional) properties for excel file, including
     *                                                 * creator       (string) who creates this file
     *                                                 * title         (string) title of the excel
     *                                                 * subject       (string) subject of the excel
     *                                                 * description   (string) description of the excel
     * @return \Jihe\Services\Excel\ExcelWriter
     */
    public static function fromScratch(array $options = [])
    {
        $phpExcel = new \PHPExcel();
        return new self($phpExcel, $phpExcel->getActiveSheet(), $options);
    }
    
    /**
     * write header
     * todo: merge headers
     * 
     * @param array $headers   header to write, it can be an array for one line of header, or
     *                         array of array for multiple headers
     */
    public function writeHeader(array $headers)
    {
        $this->writeSheetRows($this->sheet, $headers, $this->row);
    }
    
    /**
     * write content 
     * 
     * todo: merge cells
     * 
     * @param array $values   data to write, it can be an array for a row, or 
     *                        array of array for multiple rows
     */
    public function write(array $values)
    {
        $this->writeSheetRows($this->sheet, $values, $this->row);
    }
    
    /**
     * overwrite a row. 
     * note this won't get the current row pointer changed. you may resort to 
     * skip() for syncing current row if needed.
     * 
     * @param array $values   data to write, it can be an array for a row, or 
     *                        array of array for multiple rows
     * @param int $row        from which row to overwrite
     */
    public function overwrite(array $values, $row = 0) {
        // overwrite mode won't change current row
        $this->writeSheetRows($this->sheet, $values, $row);
    }
    
    public function save(array $options = [])
    {
        // save excel
        $excelType = array_get($options, 'excel_type', 'Excel5');
        $writer = \PHPExcel_IOFactory::createWriter($this->phpExcel, $excelType);
        
        $filename = array_get($options, 'file', 'php://output');

        $writer->save($filename);
    }
    
    private function writeExcelProperties(\PHPExcel $phpExcel, array $properties = [])
    {
        if (empty($properties)) {
            return;
        }
        
        $props = $phpExcel->getProperties();
    
        if(isset($properties['creator'])) {
            $props->setCreator($properties['creator']);
        }
    
        if(isset($properties['title'])) {
            $props->setTitle($properties['title']);
        }
    
        if(isset($properties['subject'])) {
            $props->setSubject($properties['subject']);
        }
    
        if(isset($properties['description'])) {
            $props->setDescription($properties['description']);
        }
    }
    
    /**
     * skip given number of rows
     * @param int $row    row to skip
     */
    public function skip($row)
    {
        $this->row += $row;
    }
    
    private function writeSheetRows(\PHPExcel_Worksheet $sheet, array $values, &$row)
    {
        if (empty($values)) {
            return;
        }
        
        $first = current($values);  // check the first element
        if (!is_array($first)) { // we're requested to write header spanning one row
            reset($values);
            $values = [$values]; // force the headers as an array of array,
            // each element in the array reprents a row in headers
        }
    
        foreach ($values as $rowValues) {
            foreach ($rowValues as $column => $value) {
                // write row
                $column = is_int($column) ? $column : \PHPExcel_Cell::columnIndexFromString($column);
                $sheet->setCellValueByColumnAndRow($column, $row, $value);
            }
            $row++;
        }
    
    }
    
//     /**
//      * PHPExcel caches cells so that it doesn't have to create them again and
//      * again. By default, cells are cached in memory(and no compression).
//      */
//     private static function setUpCellCaching()
//     {
//         // cache cells in memory with gzip compression
//         $method = \PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;
    
//         // MemoryGZip doesn't take any configuration, so we leave the second
//         // param of setCacheStorageMethod to be default (an empty array)
//         \PHPExcel_Settings::setCacheStorageMethod($method);
//     }
}