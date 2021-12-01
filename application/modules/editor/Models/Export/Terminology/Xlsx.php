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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * exports term data stored in translate5 to valid XLSX files
 */
class editor_Models_Export_Terminology_Xlsx {

    /**
     * @var Worksheet
     */
    public Worksheet $sheet;

    public $colIdxA = [
        'entry.origination' => 6,
        'entry.modification' => 10,
        'entry.attribs' => 14,

        'language.origination' => 17,
        'language.modification' => 21,
        'language.attribs' => 25,

        'term.origination' => 28,
        'term.modification' => 32,
        'term.attribs' => 36,
    ];

    /**
     * @var array
     */
    public $colGrpA = [];

    /**
     * @var array
     */
    public $colMapA = [];

    /**
     * @var array
     */
    public $emailA = [];

    /**
     * @var array
     */
    public $usage = [];

    /**
     * @var array
     */
    public $double = [];

    /**
     * Column index from string.
     *
     * @param string $pString eg 'A'
     *
     * @return int Column index (A = 1)
     */
    public function columnIndexFromString($pString) {
        return Coordinate::columnIndexFromString($pString);
    }

    /**
     * @param int $collectionId
     * @param bool $tbxBasicOnly
     * @param bool $exportImages
     * @param int $byTermEntryQty
     * @param int $byImageQty
     */
    public function exportCollectionById(int $collectionId, $tbxBasicOnly = false, $exportImages = true,
                                         $byTermEntryQty = 1000, $byImageQty = 50) {
        class_exists('editor_Utils');

        // Load xlsx file
        $xlsx = (new PhpOffice\PhpSpreadsheet\Reader\Xlsx())->load(
            join(DIRECTORY_SEPARATOR, [APPLICATION_ROOT, 'data', 'TermCollectionExportTpl.xlsx'])
        );

        // Make active sheet to be accessible from other methods
        $this->sheet = $xlsx->getActiveSheet();

        // Models shortcuts
        $dataTypeM  = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeDataType');
        $termEntryM = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        $termM      = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        $attrM      = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        $trscM      = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');

        // Get user emails from `Zf_users` table
        $this->emailA = $trscM->db->getAdapter()->query('
            SELECT `userGuid`, `email` FROM `Zf_users`
        ')->fetchAll(PDO::FETCH_KEY_PAIR);

        // Append emails from `terms_ref_object` table
        $this->emailA += $trscM->db->getAdapter()->query('
            SELECT `key`, JSON_UNQUOTE(json_extract(`data`, "$.email")) FROM `terms_ref_object` WHERE `collectionId` = ?
        ', $collectionId)->fetchAll(PDO::FETCH_KEY_PAIR);

        // Get total qty of entries to be processed
        $termEntryQty = $termEntryM->getQtyByCollectionId($collectionId);

        // Get attribute datatypes usage info
        $usage = $dataTypeM->getUsageForLevelsByCollectionId($collectionId);
        $this->usage = $usage['usage']; $this->double = $usage['double'];

        //
        foreach ($this->usage as $level => $dataTypeA) {
            $mapKey = $level . '.attribs';
            $attrIdx_first = $this->colIdxA[$mapKey];
            $attrIdx_last = $attrIdx_first + 2;
            $attrCol_last = Coordinate::stringFromColumnIndex($attrIdx_last);
            $shift = count($dataTypeA) - 3 + (count($dataTypeA) ? 0 : 1);

            //
            foreach (array_keys($dataTypeA) as $dataTypeId)
                if (in_array($dataTypeId, $this->double))
                    $shift ++;

            //
            if ($shift) $this->sheet->insertNewColumnBefore($attrCol_last, $shift);

            // Set up columns header titles
            $idxA = [$attrIdx_first]; $double = -1;
            foreach (array_values($dataTypeA) as $dataTypeIdx => $title) {
                if (in_array(array_keys($dataTypeA)[$dataTypeIdx], $this->double)) $double ++;
                $col = Coordinate::stringFromColumnIndex($attrIdx_first + $dataTypeIdx + ($double > 0 ? $double : 0));
                $this->sheet->setCellValue($col . '2', $title);
            }

            // Shift further columns coords
            $mapKeyA = array_keys($this->colIdxA);
            $mapKeyIdx = array_flip($mapKeyA)[$mapKey];
            foreach ($mapKeyA as $keyIdx => $keyValue)
                if ($keyIdx > $mapKeyIdx)
                    $this->colIdxA[$keyValue] += $shift;
        }

        //
        foreach ($this->colIdxA as $key => $_idx)
            $this->colGrpA[$key] = Coordinate::stringFromColumnIndex($_idx);

        //
        for ($i = 1; $i <= $this->colIdxA['term.attribs'] + count($this->usage['term']); $i++)
            $this->colMapA[$i] = Coordinate::stringFromColumnIndex($i);

        // Indexes
        $idx['start'] = 3; $idx['term'] = 0;

        // Build WHERE clause
        $where = 'collectionId = ' . $collectionId;

        // Fetch usages by $byTermEntryQty at a time
        for ($p = 1; $p <= ceil($termEntryQty / $byTermEntryQty); $p++) {

            // Page start index
            $idx['page'] = ($p - 1) * $byTermEntryQty;

            // Get termEntries
            $termEntryA = $termEntryM->db->fetchAll($where, null, $byTermEntryQty, $idx['page'])->toArray();

            // Get termEntryIds
            $termEntryIds = join(',', array_column($termEntryA, 'id') ?: [0]);

            // Get inner data for given termEntries
            $termA = $termM->getExportData($termEntryIds);
            $attrA = $attrM->getExportData($termEntryIds, $tbxBasicOnly);
            $trscA = $trscM->getExportData($termEntryIds);

            // Foreach termEntry
            foreach ($termEntryA as $entryIdx => $termEntry) {
                foreach ($termA[$termEntry['id']] as $lang => $terms) {
                    foreach ($terms as $termIdx => $term) {
                        $idx['row'] = $idx['start'] + $idx['page'] + ($idx['term']++);
                        $this->sheet->setCellValue('A' . $idx['row'], $termEntry['id']);
                        $this->sheet->setCellValue('B' . $idx['row'], $lang);
                        $this->sheet->setCellValue('C' . $idx['row'], $term['id']);
                        $this->sheet->setCellValue('D' . $idx['row'], $term['term']);
                        $this->sheet->setCellValue('E' . $idx['row'], $term['processStatus']);

                        $this->transacGrpCells('entry',    $idx['row'], $trscA, $termEntry['id']);
                        $this->transacGrpCells('language', $idx['row'], $trscA, $termEntry['id'], $lang);
                        $this->transacGrpCells('term',     $idx['row'], $trscA, $termEntry['id'], $lang, $term['id']);

                        $this->attributeCells('entry',    $idx['row'], $attrA, $termEntry['id']);
                        $this->attributeCells('language', $idx['row'], $attrA, $termEntry['id'], $lang);
                        $this->attributeCells('term',     $idx['row'], $attrA, $termEntry['id'], $lang, $term['id']);
                    }
                }
            }
        }

        $style = $this->sheet->getStyle('AH3')->exportArray();
        $style = [
            //'borders' => [
                //'outline' => [
                    //'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                    //'color' => ['argb' => 'FFFF0000'],
                //],
            //],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => [
                    'argb' => 'FFADD58A'
                ],
                'endColor' => [
                    'argb' => 'FFC2E0AE'
                ]
            ]
        ];
        $this->sheet->getStyle('AI:AJ')->applyFromArray($style);
        //$this->sheet->getStyle('AI2:AJ' . $idx['row'])->applyFromArray($style);
        //$this->sheet->getStyle('AI2:AJ2000000')->applyFromArray($style);

        // Save
        $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($xlsx);
        $out = join(DIRECTORY_SEPARATOR, [APPLICATION_ROOT, 'data', 'out.xlsx']);
        $writer->save($out);
        die('xxxx');
    }

    public function attributeCells($level, $rowIdx, $attrA, $termEntryId, $language = '', $termId = '') {
        $colIdx = $this->colIdxA[$level . '.attribs'];
        foreach (array_keys($this->usage[$level]) as $attrIdx => $dataTypeId) {
            foreach ($attrA[$termEntryId][$language][$termId] ?? [] as $attr) {
                if ($dataTypeId == $attr['dataTypeId']) {
                    $this->sheet->setCellValue($this->colMapA[$colIdx + $attrIdx] . $rowIdx, $attr['value']);
                }
            }
        }
    }

    public function transacGrpCells($level, $rowIdx, $trscA, $termEntryId, $language = '', $termId = '') {
        foreach ($trscA[$termEntryId][$language][$termId] ?? [] as $trsc) {
            $colIdx = $this->colIdxA[$level . '.' . $trsc['transac']];
            if ($trsc['transac'] == 'origination' || $trsc['transac'] == 'modification') {
                $this->sheet->setCellValue($this->colMapA[$colIdx    ] . $rowIdx, $trsc['transacNote']);
                $this->sheet->setCellValue($this->colMapA[$colIdx + 1] . $rowIdx, $trsc['target']);
                if ($email = $this->emailA[$trsc['target']] ?? '') {
                    $this->sheet->setCellValue($this->colMapA[$colIdx + 2] . $rowIdx, $email);
                }
                $this->sheet->setCellValue($this->colMapA[$colIdx + 3] . $rowIdx, explode(' ', $trsc['date'])[0]);
            }
        }
    }
}
