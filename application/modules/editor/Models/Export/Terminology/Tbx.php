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

/**
 * exports term data stored in translate5 to valid TBX files
 */
class editor_Models_Export_Terminology_Tbx {
    /**
     * @var Zend_Db_Table_Rowset_Abstract|null
     */
    protected Zend_Db_Table_Rowset_Abstract $data;

    /**
     * Holds the XML Tree
     * @var SimpleXMLElement
     */
    protected SimpleXMLElement $tbx;

    /**
     * @var string
     */
    protected string $target = '';

    /**
     * @var array
     */
    protected array $languageCache = [];

    /**
     * @var array
     */
    protected array $statusMap;

    /**
     * Counter for number of tigs on create tbx while export.
     * Needed to generate tig-id attribute in tbx-xml
     * @var integer
     */
    protected int $counterTig = 0;

    /**
     * Whitespace-indents by levels
     * That is not actually tabs, but whitespaces.
     *
     * @var array
     */
    protected array $tabs = [];

    /**
     * @var string
     */
    protected string $file = '';

    public function __construct()
    {
        $tbxImport = ZfExtended_Factory::get('editor_Models_Import_TermListParser_Tbx');
        /* @var $tbxImport editor_Models_Import_TermListParser_Tbx */
        $this->statusMap = array_flip($tbxImport->getStatusMap());
    }

    /**
     * Sets the Terminology data to be processed
     * Data must already be sorted by: groupId, language, id
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * sets the target where the data should be exported to
     * expects a TBX filename
     * @param string $target
     */
    public function setTarget(string $target)
    {
        $this->target = $target;
    }

    /**
     * creates the TBX Element and returns the body node to add data
     * @return SimpleXMLElement
     */
    protected function createTbx(): SimpleXMLElement
    {
        $this->tbx = new SimpleXMLElement('<martif/>');
        $this->tbx->addAttribute('noNamespaceSchemaLocation', 'TBXcsV02.xsd');
        $this->tbx->addAttribute('type', 'TBX');
        $this->tbx->addAttribute('TBX', 'en');
        $head = $this->tbx->addChild('martifHeader');
        $fileDesc = $head->addChild('fileDesc');
        $sourceDesc = $fileDesc->addChild('sourceDesc');
        $sourceDesc->addChild('p', 'TBX recovered from Translate5 DB');
        $text = $this->tbx->addChild('text');

        return $text->addChild('body');
    }

    /**
     * TODO: add the term attributes and term entry attributes
     * exports the internally stored data
     * @return string the generated data
     */
    public function export(): string
    {
        $body = $this->createTbx();

        //we assume that we got the data already sorted from DB
        $oldTermEntry = '';
        $oldLanguage = 0;
        foreach($this->data as $row) {
            if($oldTermEntry != $row->termEntryTbxId) {
                $termEntry = $body->addChild('termEntry');
                $termEntry->addAttribute('id', $row->termEntryTbxId);
                $oldTermEntry = $row->termEntryTbxId;
            }
            if($oldLanguage != $row->languageId) {
                $langSet = $termEntry->addChild('langSet');
                $langSet->addAttribute('lang', $this->getLanguage($row->languageId));
                $oldLanguage = $row->languageId;
            }
            $tig = $langSet->addChild('tig');
            if (isset($row->tigId)) {
                $tigId = $row->tigId;
            }
            if (empty($tigId)) {
                $tigId = $this->convertToTigId($row->termTbxId);
            }

            $tig->addAttribute('id', $tigId);

            $term = $tig->addChild('term', $row->term);
            $term->addAttribute('id', $row->termTbxId);

            $termNote = $tig->addChild('termNote', $row->status); //FIXME Status gemapped???
            $termNote->addAttribute('type', 'normativeAuthorization');
        }
        //SimpleXML throws an error when giving null, so we need this workaround:
        if (empty($this->target) && $this->target !== '0') {
            return $this->tbx->asXML();
        }

        return $this->tbx->asXML($this->target);
    }

    /**
     * returns the Rfc5646 language code to the given language id
     * @param int $langId
     * @return string
     */
    protected function getLanguage(int $langId): string
    {
        if (empty($this->languageCache[$langId])) {
            $lang = ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $lang editor_Models_Languages */
            $lang->load($langId);
            $this->languageCache[$langId] = $lang->getRfc5646();
        }
        return $this->languageCache[$langId];
    }

    /**
     * reverts the status mapping of the TBX Import
     * @param string $status
     * @return string
     */
    protected function getStatus(string $status): string
    {
        if (empty($this->statusMap[$status])) {
            $default = $this->statusMap[editor_Models_Terminology_TbxObjects_Term::STAT_STANDARDIZED];
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $log->logError('Error on TBX creation, missing term status "'.$status.'", set to "'.$default.'" in file '.$this->target);

            return $default;
        }

        return $this->statusMap[$status];
    }

    /**
     * converts the given mid to a tig id
     * @param string $mid
     * @return string
     */
    protected function convertToTigId(string $mid): string
    {
        if (strpos($mid, 'term_') === false) {
            return 'tig_'.$mid;
        }

        // check if mid (aka term-Id) is autogenerated
        $midParts = explode('_', $mid);
        if (count($midParts) < 6) {
            // if not autogenerated..
            return str_replace('term', 'tig', $mid);
        }

        // if mid (aka term-Id) is autogenerated, generate tig-Id from splitted parts
        $this->counterTig += 1;
        $tempReturn =   'tig_'.$midParts[1]
                        .'_'.$midParts[2]
                        .'_'.str_pad($this->counterTig, 7, '0', STR_PAD_LEFT)
                        .'_'.$midParts[3];

        return $tempReturn;
    }

    /**
     * Export collection as a TBX file
     *
     * @param int $collectionId
     * @param bool $tbxBasicOnly
     * @param bool $exportImages
     * @param int $byTermEntryQty How many termEntries should be processed at once
     * @param int $byImageQty How many image binaries should be processed at once
     * @throws Zend_Db_Statement_Exception
     */
    public function exportCollectionById(int $collectionId, $tbxBasicOnly = false, $exportImages = true,
                                         $byTermEntryQty = 1000, $byImageQty = 50) {

        // Setup export file absolute path
        $this->file = editor_Models_LanguageResources_LanguageResource::exportFilename($collectionId);

        // Get total qty of entries to be processed
        $qty = editor_Utils::db()->query('
            SELECT COUNT(*) FROM `terms_term_entry` WHERE `collectionId` = ?'
        , $collectionId)->fetchColumn();

        /** @var editor_Models_Terminology_Models_TermEntryModel $m */
        $m = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');

        // Build WHERE clause
        $where = 'collectionId = ' . $collectionId;

        // Lines array
        $line = [];

        // If $tbxBasicOnly arg is true, overwrite it with comma-separated dataTypeIds of tbx-basic attributes
        if ($tbxBasicOnly) $tbxBasicOnly = implode(',', editor_Utils::db()->query('
            SELECT `id` FROM `terms_attributes_datatype` WHERE `isTbxBasic` = 1
        ')->fetchAll(PDO::FETCH_COLUMN));

        // Prepare indents
        for ($i = 0; $i < 20; $i++) {
            $this->tabs[$i] = str_pad('', $i * 4, ' ');
        }

        // Prepare and write tbx header into export.tbx file
        $line []= '<?xml version=\'1.0\'?><!DOCTYPE martif SYSTEM "TBXBasiccoreStructV02.dtd">';
        $line []= '<martif>';
        $line []= $this->tabs[1] . '<text>';
        $line []= $this->tabs[2] . '<body>';
        $this->write($line, true);

        // Fetch usages by $byTermEntryQty at a time
        for ($p = 1; $p <= ceil($qty / $byTermEntryQty); $p++) {

            // Get termEntries
            $termEntryA = $m->db->fetchAll($where, null, $byTermEntryQty, ($p - 1) * $byTermEntryQty)->toArray();

            // Get termEntryIds
            $termEntryIdA = array_column($termEntryA, 'id') ?: [0];

            // Get term-records
            $termA = array_group_by(editor_Utils::db()->query('
                SELECT `termEntryId`, `id`, `term`, `language`, `termTbxId` 
                FROM `terms_term`
                WHERE `termEntryId` IN (' . join(',', $termEntryIdA) . ')
            ')->fetchAll(), 'termEntryId', 'language');

            // Get attribute-records
            $attrA = array_group_by(editor_Utils::db()->query('
                SELECT `termEntryId`, `language`, `termId`, `elementName`, `type`, `value`, `target`  
                FROM `terms_attributes`
                WHERE `termEntryId` IN (' . join(',', $termEntryIdA) . ')' . editor_Utils::rif($tbxBasicOnly, ' AND `dataTypeId` IN ($1)')
            )->fetchAll(), 'termEntryId', 'language', 'termId');

            // Get transacgrp-records
            $trscA = array_group_by(editor_Utils::db()->query('
                SELECT `termEntryId`, `language`, `termId`, `elementName`, `transac`, `date`, `transacNote`, `transacType`, `ifDescripgrp`  
                FROM `terms_transacgrp`
                WHERE `termEntryId` IN (' . join(',', $termEntryIdA) . ')
            ')->fetchAll(), 'termEntryId', 'language', 'termId');

            // Foreach termEntry
            foreach ($termEntryA as $termEntry) {
                $line []= $this->tabs[3] . '<termEntry id="' . $termEntry['termEntryTbxId'] . '">';
                $this->attributeNodes(4, $line, $attrA, $termEntry['id']);
                $this->transacGrpNodes(4, $line, $trscA, $termEntry['id']);
                foreach ($termA[$termEntry['id']] as $lang => $terms) {
                    $line []= $this->tabs[4] . '<langSet xml:lang="' . $lang . '">';
                    $this->attributeNodes(5, $line, $attrA, $termEntry['id'], $lang);
                    $this->transacGrpNodes(5, $line, $trscA, $termEntry['id'], $lang);
                    foreach ($terms as $term) {
                        $line []= $this->tabs[5] . '<tig>';
                        $line []= $this->tabs[6] . '<term id="' . $term['termTbxId'] . '">' . $term['term'] . '</term>';
                        $this->attributeNodes(6, $line, $attrA, $termEntry['id'], $lang, $term['id']);
                        $this->transacGrpNodes(6, $line, $trscA, $termEntry['id'], $lang, $term['id']);
                        $line []= $this->tabs[5] . '</tig>';
                    }
                    $line []= $this->tabs[4] . '</langSet>';
                }
                $line []= $this->tabs[3] . '</termEntry>';
            }

            // Append into tbx file
            $this->write($line);
        }

        // Append closing body- and opening back-node
        $line []= $this->tabs[2] . '</body>';
        $line []= $this->tabs[2] . '<back>';
        $this->write($line);

        // Get terms_images-records for a given collection
        if ($exportImages && $qty = editor_Utils::db()->query('
            SELECT COUNT(`id`) FROM `terms_images` WHERE `collectionId` = ?'
        , $collectionId)->fetchColumn()) {

            // Images model shortcut
            $i = ZfExtended_Factory::get('editor_Models_Terminology_Models_ImagesModel');

            // Open refObjectList-node
            $line []= $this->tabs[3] . '<refObjectList type="binaryData">';

            // Foreach page by $byImageQty at a time
            for ($p = 1; $p <= ceil($qty / $byImageQty); $p++) {

                // Fetch images
                $imgA = $i->db->fetchAll($where, null, $byImageQty, ($p - 1) * $byImageQty)->toArray();

                // Foreach image
                foreach ($imgA as $imgI) {
                    $line []= $this->tabs[4] . '<refObject id="' . $imgI['targetId'] . '">';
                    $path = $i->getImagePath($collectionId, $imgI['uniqueName']);
                    $file = file_get_contents($path);
                    $line []= $this->tabs[5] . '<item type="name">' . $imgI['name'] . '</item>';
                    $line []= $this->tabs[5] . '<item type="encoding">hex</item>';
                    $line []= $this->tabs[5] . '<item type="format">' . (preg_match('~/~', $imgI['format']) ? '' : 'image/') . $imgI['format'] . '</item>';
                    $text = preg_replace('~.{2}~', '$0 ', bin2hex($file));
                    $line []= $this->tabs[5] . '<item type="data">' . $text . '</item>';
                    $line []= $this->tabs[4] . '</refObject>';
                }

                // Append into tbx file
                $this->write($line);
            }

            // Close refObjectList-node
            $line []= $this->tabs[3] . '</refObjectList>';
        }
        $line []= $this->tabs[2] . '</back>';
        $line []= $this->tabs[1] . '</text>';
        $line []= '</martif>';
        $this->write($line);

        // Read
        header('Content-Type: text/xml;');
        readfile($this->file);
        die();
    }

    public function attributeNodes($level, &$line, $attrA, $termEntryId, $language = '', $termId = '') {
        foreach ($attrA[$termEntryId][$language][$termId] as $attr) {
            $_attr = [];
            if ($attr['type']) $_attr []= 'type="' . $attr['type'] . '"';
            if ($attr['elementName'] == 'xref' || $attr['elementName'] == 'ref' || $attr['target'])
                $_attr []= 'target="' . $attr['target'] . '"';
            $line []= $this->tabs[$level] . '<' . $attr['elementName'] . ' ' . join(' ', $_attr) . '>'
                . $attr['value']
                . '</' . $attr['elementName'] . '>';
        }
    }

    public function transacGrpNodes($level, &$line, $trscA, $termEntryId, $language = '', $termId = '') {
        foreach ($trscA[$termEntryId][$language][$termId] as $trsc) {
            $line []= $this->tabs[$level] . '<transacGrp>';
            $line []= $this->tabs[$level + 1] . '<transac type="transactionType">'. $trsc['transac'] . '</transac>';
            $line []= $this->tabs[$level + 1] . '<transacNote type="' . $trsc['transacType'] . '">Jane</transacNote>';
            $line []= $this->tabs[$level + 1] . '<date>' . explode(' ', $trsc['date'])[0] . '</date>';
            $line []= $this->tabs[$level] . '</transacGrp>';
        }
    }

    /**
     * @param $lines
     */
    public function write(&$lines, $overwrite = false) {

        // Write lines
        file_put_contents($this->file, join("\n", $lines) . "\n", $overwrite ? null : FILE_APPEND);

        // Clear lines
        $lines = [];
    }
}
