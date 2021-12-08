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
use WilsonGlasser\Spout\Writer\Common\Creator\Style\StyleBuilder;
use WilsonGlasser\Spout\Writer\Common\Creator\WriterEntityFactory;
use WilsonGlasser\Spout\Writer\Common\Helper\CellHelper;
use WilsonGlasser\Spout\Common\Entity\Style\Style;
use WilsonGlasser\Spout\Common\Entity\ColumnDimension;

/**
 * exports term data stored in translate5 to valid XLSX files
 */
class editor_Models_Export_Terminology_Xlsx {

    /**
     * XLSX document header columns description
     *
     * @var array
     */
    public $cols = [
        'main' => [
            'cols' => [
                'termEntryId' => 'Term Entry ID',
                'language' => 'Language',
                'termId' => 'Term ID',
                'term' => 'Term',
                'processStatus' => 'processStatus',
            ],
        ],
        'entry.origination' => [
            'text' => 'TermEntry creator/creation properties',
            'color' => 'fffbcc',
            'cols' => [
                'name' => 'name',
                'guid' => 'guid',
                'email' => 'e-mail',
                'date' => 'date'
            ]
        ],
        'entry.modification' => [
            'text' => 'TermEntry last modifier/modification properties',
            'color' => 'fff9ae',
            'cols' => [
                'name' => 'name',
                'guid' => 'guid',
                'email' => 'e-mail',
                'date' => 'date'
            ]
        ],
        'entry.attribs' => [
            'text' => 'TermEntry level attributes',
            'color' => 'fff685',
            'cols' => []
        ],
        'language.origination' => [
            'text' => 'Language level creator/creation properties',
            'color' => 'dfcce4',
            'cols' => [
                'name' => 'name',
                'guid' => 'guid',
                'email' => 'e-mail',
                'date' => 'date'
            ]
        ],
        'language.modification' => [
            'text' => 'Language level last modifier/modification properties',
            'color' => 'c7a0cb',
            'cols' => [
                'name' => 'name',
                'guid' => 'guid',
                'email' => 'e-mail',
                'date' => 'date'
            ]
        ],
        'language.attribs' => [
            'text' => 'Language level attributes',
            'color' => 'bd7cb5',
            'cols' => []
        ],
        'term.origination' => [
            'text' => 'Term level creator/creation properties',
            'color' => 'e0efd4',
            'cols' => [
                'name' => 'name',
                'guid' => 'guid',
                'email' => 'e-mail',
                'date' => 'date'
            ]
        ],
        'term.modification' => [
            'text' => 'Term level last modifier/modification properties',
            'color' => 'c2e0ae',
            'cols' => [
                'name' => 'name',
                'guid' => 'guid',
                'email' => 'e-mail',
                'date' => 'date'
            ]
        ],
        'term.attribs' => [
            'text' => 'Term level attributes',
            'color' => 'add58a',
            'cols' => []
        ]
    ];

    /**
     * [userGuid => email] pairs from `Zf_users` + [key => data.email] pairs from `terms_ref_object`
     *
     * @var array
     */
    public $emailA = [];

    /**
     * Info about attribute usage for a collection. Looks like:
     * [
     *     'usage' => [
     *          'entry' => [
     *              'dataTypeId1' => 'title1'
     *              ...
     *          ],
     *          'language' => [
     *              ...
     *          ],
     *          'term' => [
     *              ...
     *          ]
     *      ],
     *     'multiple' => [                            // Attributes, that may exist more than once at the same level
     *          'dataTypeId1' => 'type1',
     *          'dataTypeId2' => 'type2',
     *      ]
     *     'double' = [                              // Attributes, that require 2 columns in the excel document
     *          'dataTypeId1' => 'type1',
     *          'dataTypeId3' => 'type3',
     *      ]
     * ]
     *
     * @var array
     */
    public $usage = [];

    /**
     * Do export
     *
     * @param int $collectionId
     * @param bool $tbxBasicOnly
     * @param int $byTermEntryQty
     */
    public function exportCollectionById(int $collectionId, $tbxBasicOnly = false, $byTermEntryQty = 1000) {

        // Build file path
        $file = join(DIRECTORY_SEPARATOR, [APPLICATION_ROOT, 'data', 'tmp', 'tc_' . $collectionId . '.xlsx']);

        // If session's 'download' flag is set
        if ($_SESSION['download'] ?? false) {

            // Unset session's download flag
            unset($_SESSION['download']);

            // Get collection name
            $collection = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
            $collection->load($collectionId);
            $collectionName = $collection->getName();

            // If $overwrite arg is a string, assume it's a collection name, else just use 'export' as filename
            $filename = is_string($collectionName) ? rawurlencode($collectionName) : 'export';

            // Set up headers
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');
            header('Content-Type: text/xml');
            header('Content-Disposition: attachment; filename*=UTF-8\'\'' . $filename . '.xlsx; filename=' . $filename . '.xlsx');

            // Flush the entire file
            readfile($file);

            // Delete the file
            unlink($file);

            // Exit
            exit;
        }

        // Load utils
        class_exists('editor_Utils'); mt();

        // Force immediate flushing
        ob_implicit_flush(true); ob_end_flush(); ob_end_flush();

        // Set no time limit
        set_time_limit(0);

        // Models shortcuts
        $dataTypeM  = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeDataType');
        $termEntryM = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        $termM      = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        $attrM      = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        $trscM      = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');

        // Create writer and open file for writing
        $this->writer = WriterEntityFactory::createWriter('xlsx');
        $this->writer->openToFile($file);

        // Get attribute datatypes usage info
        $this->usage = $dataTypeM->getUsageForLevelsByCollectionId($collectionId);

        // Setup <level>.attribs-columns and white 2 header rows
        $this->setLevelAttribsCols()->writeFirstHeaderRow()->writeSecondHeaderRow();

        // Get user emails from `Zf_users` table
        $this->emailA = $trscM->db->getAdapter()->query('
            SELECT `userGuid`, `email` FROM `Zf_users`
        ')->fetchAll(PDO::FETCH_KEY_PAIR);

        // Append emails from `terms_ref_object` table
        $this->emailA += $trscM->db->getAdapter()->query('
            SELECT `key`, JSON_UNQUOTE(JSON_EXTRACT(`data`, "$.email")) FROM `terms_ref_object` WHERE `collectionId` = ?
        ', $collectionId)->fetchAll(PDO::FETCH_KEY_PAIR);

        // Get total qty of entries to be processed
        $termEntryQty = $termEntryM->getQtyByCollectionId($collectionId);

        // Flush info on how may termEntries to be exported
        d('Total termEntry-qty: ' . $termEntryQty);
        d('Starting export...');

        // Build WHERE clause
        $where = 'collectionId = ' . $collectionId;

        // Fetch usages by $byTermEntryQty at a time
        for ($p = 1; $p <= ceil($termEntryQty / $byTermEntryQty); $p++) {

            // Fetch offset
            $offset = ($p - 1) * $byTermEntryQty;

            // Fetch termEntries
            $termEntryA = $termEntryM->db->fetchAll($where, null, $byTermEntryQty, $offset)->toArray();

            // Get termEntryIds
            $termEntryIds = join(',', array_column($termEntryA, 'id') ?: [0]);

            // Get inner data for given termEntries
            $termA = $termM->getExportData($termEntryIds);
            $attrA = $attrM->getExportData($termEntryIds, $tbxBasicOnly);
            $trscA = $trscM->getExportData($termEntryIds, true);

            // Foreach termEntry
            foreach ($termEntryA as $entryIdx => $termEntry) {

                // Foreach language
                foreach ($termA[$termEntry['id']] as $lang => $terms) {

                    // Foreach term
                    foreach ($terms as $termIdx => $term) {

                        // Excel column index shift
                        $shift = 0;

                        // Init new row
                        $row = WriterEntityFactory::createRow();

                        // Foreach column groups
                        foreach ($this->cols as $group => &$info) {

                            // If group style is not yet prepared - prepare it
                            if (!array_key_exists('style', $info))
                                $info['style'] = ($info['color'] ?? 0)
                                    ? (new StyleBuilder())->setBackgroundColor($info['color'])->setShouldWrapText(false)->build()
                                    : null;

                            // If it's main group
                            if ($group == 'main') {

                                // Prepare data
                                $data = [
                                    'termEntryId' => $termEntry['id'],
                                    'language' => $lang,
                                    'termId' => $term['id'],
                                    'term' => $term['term'],
                                    'processStatus' => $term['processStatus'],
                                ];

                            // Else if it's transacgrp-group
                            } else if (preg_match('~(entry|language|term)\.(origination|modification)~', $group, $m)) {

                                // Prepare data
                                     if ($m[1] == 'entry')    $data = $this->transacGrpCells($trscA, $m[2], $termEntry['id']);
                                else if ($m[1] == 'language') $data = $this->transacGrpCells($trscA, $m[2], $termEntry['id'], $lang);
                                else if ($m[1] == 'term')     $data = $this->transacGrpCells($trscA, $m[2], $termEntry['id'], $lang, $term['id']);

                            // Else if it's attribs-group
                            } else if (preg_match('~(entry|language|term)\.attribs~', $group, $m)) {

                                // Prepare data
                                     if ($m[1] == 'entry')    $data = $this->attributeCells($attrA, $termEntry['id']);
                                else if ($m[1] == 'language') $data = $this->attributeCells($attrA, $termEntry['id'], $lang);
                                else if ($m[1] == 'term')     $data = $this->attributeCells($attrA, $termEntry['id'], $lang, $term['id']);
                            }

                            // Foreach column in group
                            foreach (array_keys($info['cols']) as $idx => $key) {

                                // Create cell
                                $cell = WriterEntityFactory::createCell($data[$key] ?? '', $info['style']);

                                // Add cell to a row
                                $row->setCellAtIndex($cell, $shift + $idx);
                            }

                            // Increase cell index shift
                            $shift += count($info['cols']);
                        }

                        // Write row
                        $this->writer->addRow($row);
                    }
                }
            }

            // Calc progress percentage
            $progress = ($offset + count($termEntryA)) / $termEntryQty * 100;

            // Print progress percentage
            d('Progress: ' . floor($progress)  . '%');
        }

        // Flush preparing
        d('Preparing the download...');

        // Finish creating xlsx file
        $this->writer->close();

        // Flush done
        d('<strong>Done in ' . round(mt(), 3) . ' sec</strong>');

        // Setup session's 'download'-flag, so that on reload we could catch that and initiate download
        $_SESSION['download'] = true;

        // Do javascript reload
        echo '<script>window.location=window.location</script>';

        // Exit
        exit;
    }

    /**
     * Prepare data for attributes cells
     *
     * @param $attrA
     * @param $termEntryId
     * @param string $language
     * @param string $termId
     * @return array
     */
    public function attributeCells($attrA, $termEntryId, $language = '', $termId = '') {

        // Aux variables
        $data = []; $figure = []; $dataTypeId_figure = 0;

        // Foreach attribute
        foreach ($attrA[$termEntryId][$language][$termId] ?? [] as $attr) {

            // If it's multi-occurence attribute
            if ($type = $this->usage->multi[$attr['dataTypeId']] ?? 0) {

                // If it require two columns (e.g. it's xGraphic- or externalCrossReference-attr)
                if ($this->usage->double[$attr['dataTypeId']] ?? 0) {
                    $data[$attr['dataTypeId'] . '-value']  []= $attr['value'];
                    $data[$attr['dataTypeId'] . '-target'] []= $attr['target'];

                // Else if it's figure-attr
                } else if ($type == 'figure') {
                    $data[$dataTypeId_figure = $attr['dataTypeId']][$attr['target']] = $attr['target'];

                // Else if it's crossReference-attr
                } else {
                    $data[$attr['dataTypeId']] []= $attr['target'];
                }

            // Else
            } else $data[$attr['dataTypeId']] []= $attr['value'];
        }

        // Join multi-values by newlines
        foreach ($data as &$value) $value = join(" \n", $value);

        // Return data
        return $data;
    }

    /**
     * Prepare data for transacgrp cells
     *
     * @param $trscA
     * @param $transac
     * @param $termEntryId
     * @param string $language
     * @param string $termId
     * @return array
     */
    public function transacGrpCells($trscA, $transac, $termEntryId, $language = '', $termId = '') {

        // Foreach transacgrp-record
        foreach ($trscA[$termEntryId][$language][$termId] ?? [] as $trsc)

            // If record's 'transac' prop is $transac ('origination', or 'modification') - prepare and return data
            if ($trsc['transac'] == $transac) return [
                'name' => $trsc['transacNote'],
                'guid' => $trsc['target'],
                'email' => $this->emailA[$trsc['target']] ?? '',
                'date' => explode(' ', $trsc['date'])[0]
            ];
    }

    /**
     * Write first header row
     *
     * @return $this
     */
    public function writeFirstHeaderRow() {

        // Get current sheet
        $sheet = $this->writer->getCurrentSheet();

        // Column index shift
        $shift = 0;

        // Create row
        $row = WriterEntityFactory::createRow();

        // Foreach column group
        foreach ($this->cols as $group => $info) {

            // Prepare style
            $styleBuilder = new StyleBuilder();
            if ($info['color'] ?? 0) $styleBuilder->setBackgroundColor($info['color']);
            $styleBuilder->setHorizontalAlign(Style::ALIGN_MIDDLE);
            $style = $styleBuilder->build();

            // Foreach column in current group
            foreach (array_values($info['cols']) as $idx => $text) {

                // Create cell
                $cell = WriterEntityFactory::createCell($info['text'] ?? '', $style);

                // Put it in a row at certain index
                $row->setCellAtIndex($cell, $shift + $idx);
            }

            // Start building merge range
            $merge = CellHelper::getCellIndexFromColumnIndex($shift) . '1';

            // Increase columns $shift
            $shift += count($info['cols']);

            // Finish building merge range
            $merge .= ':' . CellHelper::getCellIndexFromColumnIndex($shift - 1) . '1';

            // Apply merge
            $sheet->mergeCells($merge);
        }

        // Add row
        $this->writer->addRow($row);

        // Return $this
        return $this;
    }

    /**
     * Write second header row
     *
     * @return $this
     */
    public function writeSecondHeaderRow() {

        // Column index shift
        $shift = 0;

        // Create row
        $row = WriterEntityFactory::createRow();

        // Foreach columns groups
        foreach ($this->cols as $group => $info) {

            // Prepare style
            $styleBuilder = new StyleBuilder();
            if ($info['color'] ?? 0) $styleBuilder->setBackgroundColor($info['color']);
            $style = $styleBuilder->setFontBold()->build();

            // Foreach column in current group
            foreach (array_values($info['cols']) as $idx => $text) {

                // Create cell
                $cell = WriterEntityFactory::createCell($text, $style);

                // Add cell at certain index
                $row->setCellAtIndex($cell, $shift + $idx);

                // Setup width
                $this->writer->getCurrentSheet()->addColumnDimension(new ColumnDimension(
                    CellHelper::getCellIndexFromColumnIndex($shift + $idx), max(mb_strlen($text), 10)
                ));
            }

            // Increase $shift
            $shift += count($info['cols']);
        }

        // Add row
        $this->writer->addRow($row);

        // Set auto filter
        $this->writer->getCurrentSheet()->setAutoFilter('A2:' . CellHelper::getCellIndexFromColumnIndex($shift - 1) . '2');

        // Return $this
        return $this;
    }

    /**
     * Setup $this->cols[<level>.attribs]['cols'] columns definitions
     * based on attributes dataTypes usage
     */
    public function setLevelAttribsCols() {

        // Foreach $level => $dataTypeIdA pair
        foreach ($this->usage->usage as $level => $dataTypeIdA) {

            // Foreach $dataTypeId => $title pair
            foreach ($dataTypeIdA as $dataTypeId => $title) {

                // If two columns required for his $dataTypeId
                if ($this->usage->double[$dataTypeId] ?? 0) {

                    // Append two columns, for 'value' and 'target'
                    $this->cols[$level . '.attribs']['cols'][$dataTypeId . '-value'] = $title;
                    $this->cols[$level . '.attribs']['cols'][$dataTypeId . '-target'] = '';

                // Else append one column
                } else {
                    $this->cols[$level . '.attribs']['cols'][$dataTypeId] = $title;
                }
            }
        }

        // Return $this
        return $this;
    }
}
