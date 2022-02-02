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
                'termEntryTbxId' => 'Term Entry TBX ID',
                'language' => 'Language',
                'termTbxId' => 'Term TBX ID',
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
     * Images model
     *
     * @var editor_Models_Terminology_Models_ImagesModel
     */
    public $imagesModel;

    /**
     * Collection id
     *
     * @var int
     */
    private $collectionId;

    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    private $l10n;

    /**
     * Get exported file path
     *
     * @param int $collectionId
     * @return string
     */
    public function file(int $collectionId) {
        return join(DIRECTORY_SEPARATOR, [APPLICATION_ROOT, 'data', 'tmp', 'tc_' . $collectionId . '.xlsx']);
    }

    /**
     * Print status msg
     *
     * @param $msg
     * @param null $arg If given, will be used as 2nd arg for sprintf() call
     * @throws Zend_Exception
     */
    public function status($msg, $arg = null) {

        // Get translate instance if not yet got
        if (!$this->l10n) {
            $this->l10n = ZfExtended_Zendoverwrites_Translate::getInstance();
        }

        // Localize $msg arg
        $msg = $this->l10n->_($msg);

        // If $arg arg is given - use as template
        if ($arg !== null) {
            $msg = sprintf($msg, $arg);
        }

        // Print
        d($msg);
    }

    /**
     * Do export
     *
     * @param int $collectionId
     * @param int $byTermEntryQty
     * @throws Zend_Exception
     * @throws \WilsonGlasser\Spout\Common\Exception\IOException
     * @throws \WilsonGlasser\Spout\Common\Exception\UnsupportedTypeException
     */
    public function exportCollectionById(int $collectionId, $byTermEntryQty = 1000) {

        // Load utils
        class_exists('editor_Utils'); mt();

        // Force immediate flushing
        ob_implicit_flush(true); ob_end_flush(); ob_end_flush();

        // Set no time limit
        set_time_limit(0);

        // Assign collectionId as a class property
        $this->collectionId = $collectionId;

        // Models shortcuts
        $termEntryM           = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        $termM                = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        $attrM                = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        $trscM                = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');
        $refObjectModelM      = ZfExtended_Factory::get('editor_Models_Terminology_Models_RefObjectModel');
        $this->imagesModel    = ZfExtended_Factory::get('editor_Models_Terminology_Models_ImagesModel');

        // Setup <level>.attribs-columns and write 2 header rows
        $this->openXlsxFile()->calcLevelAttribsCols()->writeFirstHeaderRow()->writeSecondHeaderRow();

        // Get total qty of entries to be processed
        $termEntryQty = $termEntryM->getQtyByCollectionId($collectionId);

        // Fetch emails of persons/users mentioned in transacgrp-data
        $this->emailA = $refObjectModelM->getEmailsByCollectionId($collectionId);

        // Flush info on how many termEntries are going to be exported
        $this->status('Gesamtzahl der Termeinträge: %s', $termEntryQty);
        $this->status('Beginn des Exports...');

        // Fetch usages by $byTermEntryQty at a time
        for ($p = 1; $p <= ceil($termEntryQty / $byTermEntryQty); $p++) {

            // Fetch offset
            $offset = ($p - 1) * $byTermEntryQty;

            // Fetch termEntries
            $termEntryA = $termEntryM->db->fetchAll('collectionId = ' . $collectionId, null, $byTermEntryQty, $offset)->toArray();

            // Get termEntryIds
            $termEntryIds = join(',', array_column($termEntryA, 'id') ?: [0]);

            // Get inner data for given termEntries
            $termA = $termM->getExportData($termEntryIds);
            $attrA = $attrM->getExportData($termEntryIds);
            $trscA = $trscM->getExportData($termEntryIds, true);

            // Write rows
            foreach ($termEntryA as $entryIdx => $termEntry) {
                foreach ($termA[$termEntry['id']] as $lang => $terms) {
                    foreach ($terms as $termIdx => $term) {
                        $this->writeRow($termEntry, $lang, $term, $attrA, $trscA);
                    }
                }
            }

            // Calc progress percentage
            $progress = ($offset + count($termEntryA)) / $termEntryQty * 100;

            // Print progress percentage
            $this->status('Fortschritt: %s', floor($progress)  . '%');

            //
            //if ($p == 2) break;
        }

        // Flush preparing
        $this->status('Vorbereiten des Downloads...');

        // Finish creating xlsx file
        $this->writer->close();

        // Flush done
        $this->status('Erledigt in %s Sek.', round(mt(), 3));

        // Setup session's 'download'-flag, so that on reload we could catch that and initiate download
        $_SESSION['download'] = true;

        // Do javascript reload
        echo '<script>window.location=window.location</script>';

        // Exit
        exit;
    }

    /**
     * Write row to an excel-export spreadsheet
     *
     * @param $termEntry
     * @param $lang
     * @param $term
     * @param $attrA
     * @param $trscA
     * @throws Zend_Db_Statement_Exception
     */
    public function writeRow($termEntry, $lang, $term, $attrA, $trscA) {

        // Excel column index shift
        $shift = 0;

        // Init new row
        $row = WriterEntityFactory::createRow();

        // Foreach column groups
        foreach ($this->cols as $group => $info) {

            // If it's main group
            if ($group == 'main') {

                // Prepare data
                $data = [
                    'termEntryTbxId' => $termEntry['termEntryTbxId'],
                    'language' => $lang,
                    'termTbxId' => $term['termTbxId'],
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

    /**
     * Prepare data for attributes cells
     *
     * @param $attrA
     * @param $termEntryId
     * @param string $language
     * @param string $termId
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function attributeCells($attrA, $termEntryId, $language = '', $termId = '') {

        // Aux variables
        $data = []; $dataTypeId_figure = 0;

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
                    $data[$dataTypeId_figure = $attr['dataTypeId']] [$attr['target']]= $attr['target'];

                // Else if it's crossReference-attr
                } else {
                    $data[$attr['dataTypeId']] []= $attr['target'];
                }

            // Else
            } else {
                $data[$attr['dataTypeId']] []= $attr['value'];
            }
        }

        // Get images URLs
        if ($data[$dataTypeId_figure] ?? 0) {

            // Get image paths by target ids
            $paths = $this->imagesModel->getImagePathsByTargetIds($this->collectionId, $data[$dataTypeId_figure]);

            // Foreach path
            foreach ($paths as $target => $src) {
                $data[$dataTypeId_figure][$target] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $src;
            }
        }

        // Join multi-values by newlines
        foreach ($data as &$value) {
            $value = join(" \n", $value);
        }

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

            // If no cols - skip
            if (!$info['cols']) continue;

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
        foreach ($this->cols as $group => &$info) {

            // Prepare style for column header cells
            $styleBuilder = new StyleBuilder();
            if ($info['color'] ?? 0) $styleBuilder->setBackgroundColor($info['color']);
            $style = $styleBuilder->setFontBold()->build();

            // Prepare style for data-cells
            $info['style'] = ($info['color'] ?? 0)
                ? (new StyleBuilder())->setBackgroundColor($info['color'])->setShouldWrapText(false)->build()
                : null;

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
    public function calcLevelAttribsCols() {

        // Get attribute datatypes usage info
        $this->usage = ZfExtended_Factory
            ::get('editor_Models_Terminology_Models_AttributeDataType')
            ->getUsageForLevelsByCollectionId($this->collectionId);

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

    /**
     * Instantiate xlsx-writer and open the destination xlsx-file
     *
     * @return $this
     * @throws \WilsonGlasser\Spout\Common\Exception\IOException
     * @throws \WilsonGlasser\Spout\Common\Exception\UnsupportedTypeException
     */
    public function openXlsxFile() {

        // Build file path
        $file = $this->file($this->collectionId);

        // Create writer and open file for writing
        $this->writer = WriterEntityFactory::createWriter('xlsx');
        $this->writer->openToFile($file);

        // Return $this
        return $this;
    }
}
