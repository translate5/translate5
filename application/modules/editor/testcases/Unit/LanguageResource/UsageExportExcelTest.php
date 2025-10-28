<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

namespace LanguageResource;

use MittagQI\Translate5\LanguageResource\UsageExporter\Excel;
use PHPUnit\Framework\TestCase;
use WilsonGlasser\Spout\Reader\Common\Creator\ReaderEntityFactory;
use WilsonGlasser\Spout\Writer\Common\Creator\WriterEntityFactory;
use WilsonGlasser\Spout\Writer\XLSX\Writer;

class UsageExportExcelTest extends TestCase
{
    public function testExcelGeneration(): void
    {
        $writer = WriterEntityFactory::createWriter('xlsx');
        $testFile = sys_get_temp_dir() . "/test.xlsx";

        if (! ($writer instanceof Writer)) {
            $this->fail('No XLSX Writer available, but ' . get_class($writer));
        }

        $excel = new Excel($writer);
        $excel->open($testFile);
        $excel->addWorksheet(1, 'TestWorksheet');
        $excel->loadArrayData([
            'col1' => 'Heading1',
        ], [
            [
                'col1' => 'Test1ColA',
                'fourmulaDisabledByDefault' => '=1+1',
                'date1' => 51234,
                'percent1' => 11.1,
                'hidden1' => 'Hidden1',
            ],
            [
                'col1' => 'Test2ColA',
                'fourmulaDisabledByDefault' => '=2+1',
                'date1' => '2025-10-22 10:00:00',
                'percent1' => 10,
            ],
        ]);
        $excel->close();

        $reader = ReaderEntityFactory::createReader('xlsx');
        $reader->open($testFile);

        $rowsArray = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $rowsArray[$rowIndex] = $row->toArray();
            }
        }

        $reader->close();

        $this->assertEquals(
            [
                1 => [
                    'Heading1',
                    'fourmulaDisabledByDefault',
                    'date1',
                    'percent1',
                    'hidden1',
                ],
                2 => [
                    'Test1ColA',
                    ' =1+1',
                    '51234',
                    '11.1',
                    'Hidden1',
                ],
                3 => [
                    'Test2ColA',
                    ' =2+1',
                    '2025-10-22 10:00:00',
                    '10',
                ],
            ],
            $rowsArray,
            'Generated Array of Excel file is not as expected'
        );
    }
}
