<?php
namespace Jihe\Services\Excel;

class ExcelReader
{
    /**
     * read excel file
     *
     * @param string $file          excel file path. ensure that excel exists.
     * @param callable $callback    callback function to be invoked per row.
     *                              parameters to be passed to are (in order):
     *                              - rowData      (array) row data
     *                              - rowNumber    (int) the row from which data is read
     *                              - totalRow     (int) total row to be read
     *
     * @param array $options  read options
     *                        - excel_type   string (optional) hint to show the excel file type
     *                                                         acceptable value should be one of the following:
     *                                                         * Excel5
     *                                                         * Excel2007
     *                                                         if not specified, its type will be guessed.
     *                        - sheet        int (optional) index of worksheet in the workbook to read.
     *                                                      starting from 0. default to "active" sheet, which
     *                                                      is the one that is active before the excel file is closed
     *                        - from_row     int (optional) from which row to read data, starting from 1,
     *                                                      which is the default value as well.
     *                        - to_row       int (optional) at which row data reading will stop. default to the
     *                                                      highest available row.
     *                        - from_column  string (optional) from which column to read data, string from 'A' column,
     *                                                         which is the default value as well.
     *                        - to_column    string (optional) at which column data reading will stop. default to
     *                                                         highest available column.
     *
     * @throws \Exception   callback fails
     */
    public function read($file, callable $callback, array $options = [])
    {
        $phpExcel = $this->load($file, array_get($options, 'excel_type'));
    
        // open sheet to read
        $sheet = $this->getSheet($phpExcel, array_get($options, 'sheet', 0));
    
        // read data from worksheet
        $this->readWorksheet($sheet, $options, $callback);
    }
    
    /**
     * get worksheet object for given sheet index.
     * 
     * @param \PHPExcel $phpExcel   Excel instance
     * @param int $sheetIndex       sheet index, starting from 0
     * @return PHPExcel_Worksheet   worksheet specified
     */
    private function getSheet(\PHPExcel $phpExcel, $sheetIndex = null)
    {
        if (empty($sheetIndex)) {
            $sheetIndex = 0;
        }
    
        return $phpExcel->getSheet($sheetIndex);
    }
    
    /**
     * load excel file
     * @param string $file          excel file
     * @param string $type          type of the excel. see excel_type option of ExcelService::read()
     *                              for more detail.
     * @param bool $readDataOnly    (optional) when true(default), to read data only (don't read things like
     *                                         cell style, etc)
     *
     * @throws \InvalidArgumentException   if the excel file cannot be read(e.g., corrupted), or incorrect
     *                                     file type is given
     * @return PHPExcel
     */
    public function load($file, $type = null, $readDataOnly = true)
    {
        // create reader
        try {
            if (empty($type)) { // no explict excel file type is given
                $reader = \PHPExcel_IOFactory::createReaderForFile($file);
            } else {
                $reader = \PHPExcel_IOFactory::createReader($type);
            }
        } catch (\PHPExcel_Reader_Exception $ex) { // reader cannot be created
            throw new \InvalidArgumentException('非法的文件格式');
        }
        
        // test whether the file is readable
        if (!$reader->canRead($file)) {
            throw new \InvalidArgumentException('excel内容格式不正确');
        }
    
        // load excel
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly($readDataOnly);
        }
        return $reader->load($file);
    }
    
    // read data from given worksheet
    private function readWorksheet(\PHPExcel_Worksheet $sheet, $options, callable $callback)
    {
        // set up cell caching mechanism
        self::setUpCellCaching();
    
        // get the start row
        $fromRow = array_get($options, 'from_row', 1);
        // get the end row
        $toRow = array_get($options, 'to_row', $sheet->getHighestRow());
        // get the start column and convert it to number so that we can perform iteration
        $fromColumn = \PHPExcel_Cell::columnIndexFromString(array_get($options, 'from_column', 'A'));
        // get the end column and convert it to number so that we can perform iteration
        $toColumn = \PHPExcel_Cell::columnIndexFromString(array_get($options, 'to_column', $sheet->getHighestColumn()));
    
        // sane column index, PHPExcel_Worksheet::getCellByColumnAndRow() takes column that starts from 0
        // but PHPExcel_Cell::columnIndexFromString() used one-based column
        $fromColumn = max($fromColumn - 1, 0);
        $toColumn   = max($toColumn - 1,   0);


        $batchSize = array_get($options, 'batch_size', 1);

        $rowValues = [];
        $rowsAdded = 0;  // how many rows added == count($rowValues)
        for ($row = $fromRow; $row <= $toRow; ++$row) {
            $rowValue = []; // values of the $row
    
            for ($column = $fromColumn; $column <= $toColumn; ++$column) {
                $cell = $sheet->getCellByColumnAndRow($column, $row);
                // now we just simply return the value of that cell,
                // not take cell value type (e.g., number, date) into  consideration
                $rowValue[] = $cell->getValue();
            }
            $rowValues[] = $rowValue;

            if (++$rowsAdded >= $batchSize) {
                // we make row index zero-based by subtracting it from $fromRow
                if (call_user_func($callback, $batchSize > 1 ? $rowValues : $rowValues[0],
                                   $row - $fromRow - $rowsAdded + 1, $toRow - $fromRow)) {
                    // return value of $callback being true is a sign of no more reading
                    return;
                }

                // reset
                $rowsAdded = 0;
                $rowValues = [];
            }
        }

        if ($rowsAdded > 0) { // last batch
            call_user_func($callback, $batchSize > 1 ? $rowValues : $rowValues[0],
                           $row - $fromRow - $rowsAdded, $toRow - $fromRow);
        }
    }
    
    /**
     * PHPExcel caches cells so that it does not have to create them again and
     * again. By default, cells are cached in memory(and no compression).
     */
    private static function setUpCellCaching()
    {
        // cache cells in memory with gzip compression
        $method = \PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;
        
        // MemoryGZip doesn't take any configuration, so we leave the second
        // param of setCacheStorageMethod to be default (an empty array)
        \PHPExcel_Settings::setCacheStorageMethod($method);
    }
}