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
use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

/**
 * exports term data stored in translate5 to valid XLSX files
 */
class editor_Models_Export_Terminology_Xlsx {

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
            'cols' => [
            ]
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
            'cols' => [
            ]
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
            'cols' => [
            ]
        ]
    ];

    /**
     * @var array
     */
    public $emailA = [];

    /**
     * @var array
     */
    public $usage = [];

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

        // Models shortcuts
        $dataTypeM  = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeDataType');
        $termEntryM = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        $termM      = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        $attrM      = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        $trscM      = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');

        // ... and a writer to create the new file
        $this->writer = WriterEntityFactory::createXLSXWriter();
        $this->writer->openToFile(join(DIRECTORY_SEPARATOR, [APPLICATION_ROOT, 'data', 'out.xlsx']));

        // Get attribute datatypes usage info
        $this->usage = $dataTypeM->getUsageForLevelsByCollectionId($collectionId);

        // Setup attrib-cols
        foreach ($this->usage->usage as $level => $dataTypeIdA) {
            foreach ($dataTypeIdA as $dataTypeId => $title) {
                if ($this->usage->double[$dataTypeId] ?? 0) {
                    $this->cols[$level . '.attribs']['cols'][$dataTypeId . '-value'] = $title;
                    $this->cols[$level . '.attribs']['cols'][$dataTypeId . '-target'] = '';
                } else {
                    $this->cols[$level . '.attribs']['cols'][$dataTypeId] = $title;
                }
            }
        }

        // White 2 header rows
        $this->writeFirstHeaderRow()->writeSecondHeaderRow();

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

        // Build WHERE clause
        $where = 'collectionId = ' . $collectionId;

        // Fetch usages by $byTermEntryQty at a time
        for ($p = 1; $p <= ceil($termEntryQty / $byTermEntryQty); $p++) {

            // Get termEntries
            $termEntryA = $termEntryM->db->fetchAll($where, null, $byTermEntryQty, ($p - 1) * $byTermEntryQty)->toArray();

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

                        //
                        $shift = 0;

                        // Init new row
                        $row = WriterEntityFactory::createRow([]);

                        // Foreach column groups
                        foreach ($this->cols as $group => &$info) {

                            // If group style is not yet prepared - prepare it
                            if (!array_key_exists('style', $info))
                                $info['style'] = ($info['color'] ?? 0)
                                    ? (new StyleBuilder())->setBackgroundColor($info['color'])->build()
                                    : null;

                            //
                            if ($group == 'main') {

                                // Prepare data
                                $data = [
                                    'termEntryId' => $termEntry['id'],
                                    'language' => $lang,
                                    'termId' => $term['id'],
                                    'term' => $term['term'],
                                    'processStatus' => $term['processStatus'],
                                ];

                                // Foreach column in group
                                foreach (array_keys($info['cols']) as $idx => $key) {

                                    // Create cell
                                    $cell = WriterEntityFactory::createCell($data[$key], $info['style']);

                                    // Add cell to a row
                                    $row->setCellAtIndex($cell, $shift + $idx);
                                }

                            //
                            } else if (preg_match('~(entry|language|term)\.(origination|modification)~', $group, $m)) {

                                // Prepare level-path
                                $path = [$termEntry['id']];
                                if ($m[1] != 'entry') $path []= $lang;
                                if ($m[1] == 'term') $path []= $term['id'];
                                $path = join(':', $path);

                                // Prepare data
                                $data = $this->transacGrpCells($m[1], $m[2], $trscA, $path);

                                // Foreach column in group
                                foreach (array_keys($info['cols']) as $idx => $key) {

                                    // Create cell
                                    $cell = WriterEntityFactory::createCell($data[$key], $info['style']);

                                    // Add cell to a row
                                    $row->setCellAtIndex($cell, $shift + $idx);
                                }

                            //
                            } else if (preg_match('~(entry|language|term)\.attribs~', $group, $m)) {

                                // Prepare level-path
                                $path = [$termEntry['id']];
                                if ($m[1] != 'entry') $path []= $lang;
                                if ($m[1] == 'term') $path []= $term['id'];
                                $path = join(':', $path);

                                // Prepare data
                                $data = $this->attributeCells($m[1], $attrA, $path);

                                // Foreach column in group
                                foreach (array_keys($info['cols']) as $idx => $key) {

                                    // Create cell
                                    $cell = WriterEntityFactory::createCell($data[$key] ?? '-', $info['style']);

                                    // Add cell to a row
                                    $row->setCellAtIndex($cell, $shift + $idx);
                                }
                            }

                            // Increase cell index shift
                            $shift += count($info['cols']);
                        }

                        // Write row
                        $this->writer->addRow($row);
                    }
                }
            }
        }


        // Save
        $this->writer->close();
        die('xxxx');
    }

    public function attributeCells($level, $attrA, $path) {

        //
        $_ = explode(':', $path);
        $termEntryId = $_[0];
        $language = $_[1] ?? '';
        $termId = $_[2] ?? '';

        //
        $data = [];

        //
        foreach ($attrA[$termEntryId][$language][$termId] ?? [] as $attr) {
            if ($type = $this->usage->multi[$attr['dataTypeId']] ?? 0) {
                if ($this->usage->double[$attr['dataTypeId']] ?? 0) {
                    $data[$attr['dataTypeId'] . '-value']  []= $attr['value'];
                    $data[$attr['dataTypeId'] . '-target'] []= $attr['target'];
                } else {
                    $data[$attr['dataTypeId']] []= $attr[$type == 'crossReference' ? 'target' : 'value'];
                }
            } else {
                $data[$attr['dataTypeId']] []= $attr['value'];
            }
        }
        d($termEntryId);
        foreach ($data as &$value) $value = join("\n", $value);
        d($data);
        d($this->cols['entry.attribs']);

        return $data;
    }

    public function transacGrpCells($level, $transac, $trscA, $path) {

        //
        $_ = explode(':', $path);
        $termEntryId = $_[0];
        $language = $_[1] ?? '';
        $termId = $_[2] ?? '';

        //
        foreach ($trscA[$termEntryId][$language][$termId] ?? [] as $trsc) {
            if ($trsc['transac'] == $transac) {
                return [
                    'name' => $trsc['transacNote'],
                    'guid' => $trsc['target'],
                    'email' => $this->emailA[$trsc['target']] ?? '',
                    'date' => explode(' ', $trsc['date'])[0]
                ];
            }
        }

        return [
            'name' => '',
            'guid' => '',
            'email' => '',
            'date' => ''
        ];
    }

    public function writeFirstHeaderRow() {
        $shift = 0;
        $row = WriterEntityFactory::createRow([]);
        foreach ($this->cols as $group => $info) {
            $style = ($info['color'] ?? 0) ? (new StyleBuilder())->setBackgroundColor($info['color'])->build() : null;
            foreach (array_values($info['cols']) as $idx => $text) {
                $cell = WriterEntityFactory::createCell($idx ? '': ($info['text'] ?? ''), $style);
                $row->setCellAtIndex($cell, $shift + $idx);
            }
            $shift += count($info['cols']);
        }
        $this->writer->addRow($row);

        return $this;
    }

    public function writeSecondHeaderRow() {
        $shift = 0;
        $row = WriterEntityFactory::createRow([]);
        foreach ($this->cols as $group => $info) {

            $styleBuilder = new StyleBuilder();
            if ($info['color'] ?? 0) $styleBuilder->setBackgroundColor($info['color']);
            $style = $styleBuilder->setFontBold()->build();

            foreach (array_values($info['cols']) as $idx => $text) {
                $cell = WriterEntityFactory::createCell($text, $style);
                $row->setCellAtIndex($cell, $shift + $idx);
            }
            $shift += count($info['cols']);
        }
        $this->writer->addRow($row);

        return $this;
    }
}
