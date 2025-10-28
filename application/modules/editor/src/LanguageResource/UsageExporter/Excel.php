<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
             https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\LanguageResource\UsageExporter;

use WilsonGlasser\Spout\Common\Entity\ColumnDimension;
use WilsonGlasser\Spout\Common\Exception\SpoutException;
use WilsonGlasser\Spout\Writer\Common\Creator\Style\StyleBuilder;
use WilsonGlasser\Spout\Writer\Common\Creator\WriterEntityFactory;
use WilsonGlasser\Spout\Writer\Common\Entity\Sheet;
use WilsonGlasser\Spout\Writer\Exception\SheetNotFoundException;
use WilsonGlasser\Spout\Writer\Exception\WriterNotOpenedException;
use WilsonGlasser\Spout\Writer\XLSX\Writer;

class Excel
{
    /**
     * @throws Exception
     */
    public static function create(): self
    {
        try {
            $writer = WriterEntityFactory::createWriter('xlsx');
        } catch (SpoutException $e) {
            throw new Exception('E1746', previous: $e);
        }

        if ($writer instanceof Writer) {
            return new self($writer);
        }

        throw new Exception('E1748', [
            'instance' => get_class($writer),
        ]);
    }

    public function __construct(
        private readonly Writer $writer
    ) {
        // Prepare default style
        $defaultStyle = (new StyleBuilder())
            ->setFontName('Calibri')
            ->setFontSize(11)
            ->build();

        //ATTENTION: No centralised way possible, to disable formula conversion, so done implicit in loadArrayData!

        // Apply default style
        $this->writer->setDefaultRowStyle($defaultStyle);
    }

    /**
     * @throws Exception
     * @throws WriterNotOpenedException
     * @throws SheetNotFoundException
     */
    private function getFirstSheet(): Sheet
    {
        foreach ($this->writer->getSheets() as $sheet) {
            if ($sheet->getName() === 'Sheet1') {
                $this->writer->setCurrentSheet($sheet);

                return $sheet;
            }
        }

        throw new Exception('E1747');
    }

    /**
     * Add a new worksheet to the excel-spreadsheet
     * @throws Exception
     */
    public function addWorksheet(int $worksheetIndex, string $sheetName): void
    {
        try {
            if ($worksheetIndex == 0) {
                // Get the first autocreated worksheet and rename it.
                // The sheet contains the total sums per customer and month
                $this->getFirstSheet()->setName($sheetName);

                return;
            }

            $this->writer->addNewSheetAndMakeItCurrent();
            $this->writer->getCurrentSheet()->setName($sheetName);
        } catch (SpoutException $e) {
            throw new Exception('E1746', previous: $e);
        }
    }

    /**
     * Loads the array data in the Excel spreadsheet
     * @throws Exception
     */
    public function loadArrayData(array $labels, array $data, int $activeSheetIndex = 0): void
    {
        try {
            $this->setCurrentSheetByIndex($activeSheetIndex);

            // Write headings
            if (count($data)) {
                $this->writeHeadings($labels, array_keys($data[0]));
            }

            foreach ($data as $item) {
                $row = WriterEntityFactory::createRow();

                foreach ($item as $value) {
                    if (is_string($value) && str_starts_with($value, '=')) {
                        //disable formulas for security reasons TRANSLATE-5059
                        $value = ' ' . $value;
                    }
                    $row->addCell(WriterEntityFactory::createCell($value));
                }

                $this->writer->addRow($row);
            }

            // Set active sheet index to the first sheet, so Excel opens this as the first sheet
            $this->setCurrentSheetByIndex(0);
        } catch (SpoutException $e) {
            throw new Exception('E1746', previous: $e);
        }
    }

    /**
     * @throws Exception
     */
    public function setCurrentSheetByIndex(int $sheetIndex): void
    {
        try {
            $this->writer->setCurrentSheet($this->writer->getSheets()[$sheetIndex]);
        } catch (SpoutException $e) {
            throw new Exception('E1746', previous: $e);
        }
    }

    /**
     * @throws Exception
     */
    public function writeHeadings(array $labels, array $headingFields): void
    {
        try {
            // Get current sheet
            $sheet = $this->writer->getCurrentSheet();

            // Init headings row
            $row = WriterEntityFactory::createRow();

            // Columns array
            $column = 'A';

            // Foreach heading field
            foreach ($headingFields as $headingField) {
                // Add to the row
                $row->addCell(WriterEntityFactory::createCell($labels[$headingField] ?? $headingField));

                // Add autoSize
                $sheet->addColumnDimension(new ColumnDimension(
                    $column++,
                    -1,
                    true
                ));
            }

            // Write row to the sheet
            $this->writer->addRow($row);
        } catch (SpoutException $e) {
            throw new Exception('E1746', previous: $e);
        }
    }

    /**
     * @throws Exception
     */
    public function close(): void
    {
        // Set the first sheet as active on file open
        $this->setCurrentSheetByIndex(0);
        $this->writer->close();
    }

    /**
     * @throws Exception
     */
    public function open(string $filename, bool $toFile = true): void
    {
        try {
            if ($toFile) {
                $this->writer->openToFile($filename);
            } else {
                $this->writer->openToBrowser($filename);
            }
        } catch (SpoutException $e) {
            throw new Exception('E1746', previous: $e);
        }
    }
}
