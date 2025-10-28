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

namespace MittagQI\Translate5\Test\Unit\ZfExtended;

use MittagQI\ZfExtended\Models\Entity\ExcelExport;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PHPUnit\Framework\TestCase;

class ExcelExportTest extends TestCase
{
    public function testExcelGeneration(): void
    {
        $excel = new ExcelExport();
        $excel->addWorksheet('TestWorksheet', 1);
        $excel->setLabel('col1', 'Heading1');
        $excel->setLabel('currency1', 'Money test');
        $excel->setHiddenField('hidden1');
        $excel->setFieldTypeCurrency('currency1');
        $excel->setFieldTypeDate('date1');
        $excel->setFieldTypePercent('percent1');
        $excel->setFieldType('date2', NumberFormat::FORMAT_DATE_DATETIME);
        $excel->setCallback('callback1', fn ($x) => str_replace('FOO', 'BAR', $x));
        $excel->loadArrayData([
            [
                'col1' => 'Test1ColA',
                'col2' => 'Test1ColB',
                'fourmulaDisabledByDefault' => '=1+1',
                'date1' => 51234,
                'date2' => 51234,
                'currency1' => 11.1,
                'percent1' => 11.1,
                'hidden1' => 'Hidden1',
                'callback1' => 'FOO1',
            ],
            [
                'col1' => 'Test2ColA',
                'col2' => 'Test2ColB',
                'fourmulaDisabledByDefault' => '=2+1',
                'date1' => '2025-10-22 10:00:00',
                'date2' => '2025-10-22 10:00:00',
                'currency1' => 10,
                'percent1' => 10,
                'callback1' => 'FOO2',
            ],
        ]);

        $this->assertEquals(
            [
                1 => [
                    'A' => 'Heading1',
                    'B' => 'col2',
                    'C' => 'fourmulaDisabledByDefault',
                    'D' => 'date1',
                    'E' => 'date2',
                    'F' => 'Money test',
                    'G' => 'percent1',
                    'H' => 'callback1',
                ],
                2 => [
                    'A' => 'Test1ColA',
                    'B' => 'Test1ColB',
                    'C' => '=1+1',
                    'D' => '1970-01-01',
                    'E' => '8/4/40 0:00',
                    'F' => '11 €',
                    'G' => '11.1%',
                    'H' => 'BAR1',
                ],
                3 => [
                    'A' => 'Test2ColA',
                    'B' => 'Test2ColB',
                    'C' => '=2+1',
                    'D' => '2025-10-22',
                    'E' => '2025-10-22 10:00:00',
                    'F' => '10 €',
                    'G' => '10.0%',
                    'H' => 'BAR2',
                ],
            ],
            $excel->getSpreadsheet()->getActiveSheet()->toArray(null, true, true, true),
            'Generated Array of Excel file is not as expected'
        );
    }
}
