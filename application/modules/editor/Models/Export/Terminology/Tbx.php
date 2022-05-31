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

    /**
     * Flag, indicating whether definition-attrs should be skipped while exporting tbx contents
     *
     * @var bool
     */
    public bool $skipDefinition = false;

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
     * @param bool $format
     * @return string the generated data
     */
    public function export(bool $format = false): string
    {
        $body = $this->createTbx();

        //we assume that we got the data already sorted from DB
        $oldTermEntry = '';
        $oldLanguage = 0;
        foreach($this->data as $row) {
            if($this->isEmptyTerm($row->term)) {
                $oldTermEntry = $row->termEntryTbxId;
                $oldLanguage = $row->languageId;
                continue;
            }
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

            $term = $tig->addChild('term', htmlspecialchars($row->term, ENT_XML1));
            $term->addAttribute('id', $row->termTbxId);

            $termNote = $tig->addChild('termNote', $row->status);
            $termNote->addAttribute('type', 'normativeAuthorization');
        }

        $toFile = strlen($this->target) > 0;

        if($format) {
            $dom = dom_import_simplexml($this->tbx)->ownerDocument;
            $dom->formatOutput = true;
            if($toFile) {
                $dom->save($this->target);
            }
            return $dom->saveXML();
        }

        //SimpleXML throws an error when giving null, so we need this workaround:
        if ($toFile) {
            $this->tbx->asXML($this->target);
        }
        return $this->tbx->asXML(); //the function signature returns always a string, so we do that

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
     * @param string $userName
     * @param bool $tbxBasicOnly
     * @param bool $exportImages
     * @param int $byTermEntryQty How many termEntries should be processed at once
     * @param int $byImageQty How many image binaries should be processed at once
     * @param null $selected Bunch of arguments passed by $this->renderRawForTaskImport(). Should look like below: [
     *    'termEntryIds' => [123, 234],
     *    'language' => ['en-us', 'en-gb'],
     *    'termIds' => [456, 567]
     * ]
     */
    public function exportCollectionById(int $collectionId, string $userName, $tbxBasicOnly = false, $exportImages = true,
                                         $byTermEntryQty = 1000, $byImageQty = 50, $selected = null) {

        // Setup export file absolute path
        $this->file = editor_Models_LanguageResources_LanguageResource::exportFilename($collectionId);

        // Models shortcuts
        $termM = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        $attrM = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        $trscM = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');

        // Get total qty of entries to be processed
        $qty = $selected
            ? count($selected['termEntryIds'])
            : ZfExtended_Factory
                ::get('editor_Models_Terminology_Models_TermEntryModel')
                ->getQtyByCollectionId($collectionId);

        /** @var editor_Models_Terminology_Models_TermEntryModel $m */
        $m = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');

        // Build WHERE clause
        $where = 'collectionId = ' . $collectionId;

        // If $termEntryIds arg is given - make sure only those will be fetched
        if ($selected) $where .= $m->db->getAdapter()->quoteInto(' AND `id` IN (?)', $selected['termEntryIds'] ?: [0]);

        // Lines array
        $line = [];

        // If $tbxBasicOnly arg is true, overwrite it with comma-separated dataTypeIds of tbx-basic attributes
        if ($tbxBasicOnly) $tbxBasicOnly = ZfExtended_Factory
            ::get('editor_Models_Terminology_Models_AttributeDataType')
            ->getTbxBasicIds();

        // Prepare indents
        for ($i = 0; $i < 20; $i++) {
            $this->tabs[$i] = str_pad('', $i * 4, ' ');
        }

        // Prepare xml header
        $line []= '<?xml version=\'1.0\'?><!DOCTYPE martif SYSTEM "TBXBasiccoreStructV02.dtd">';
        $line []= '<martif>';

        // Get collection name
        $collection = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        $collection->load($collectionId);
        $collectionName = $collection->getName();

        // Get list of languages
        $languages = join(', ', $selected['languages'] ?? array_column($collection->getLanguagesInTermCollections([$collectionId]), 'rfc5646'));

        // Write <martifHeader> nodes
        $line []= $this->tabs[1] . '<martifHeader>';
        $line []=     $this->tabs[2] . '<fileDesc>';
        $line []=         $this->tabs[3] . '<titleStmt>';
        $line []=             $this->tabs[4] . '<title>Export of translate5 termCollection ' . $collectionName . '</title>';
        $line []=             $this->tabs[4] . '<note>Contains the languages: ' . $languages . '</note>';
        $line []=         $this->tabs[3] . '</titleStmt>';
        $line []=         $this->tabs[3] . '<sourceDesc>';
        $line []=             $this->tabs[4] . '<p>File is exported from translate5 instance at https://' . $_SERVER['HTTP_HOST'] . ' by the user ' . $userName . '</p>';
        $line []=         $this->tabs[3] . '</sourceDesc>';
        $line []=     $this->tabs[2] . '</fileDesc>';
        $line []=     $this->tabs[2] . '<encodingDesc>';
        $line []=         $this->tabs[3] . '<p type="XCSURI">http://www.lisa.org/fileadmin/standards/tbx_basic/TBXBasicXCSV02.xcs</p>';
        $line []=     $this->tabs[2] . '</encodingDesc>';
        $line []= $this->tabs[1] . '</martifHeader>';

        // Open <text> and <body> nodes
        $line []= $this->tabs[1] . '<text>';
        $line []= $this->tabs[2] . '<body>';
        $this->write($line, $selected ? null : $collectionName);

        // While in normal use - skipDefinition flag is false,
        // so definitions ARE NOT skipped while exporting tbx contents
        // But if $selected arg is given, then skipDefinition param can be set to true
        if (isset($selected['skipDefinition'])) {
            $this->skipDefinition = $selected['skipDefinition'];
        }

        // Fetch usages by $byTermEntryQty at a time
        for ($p = 1; $p <= ceil($qty / $byTermEntryQty); $p++) {

            // Get termEntries
            $termEntryA = $m->db->fetchAll($where, null, $byTermEntryQty, ($p - 1) * $byTermEntryQty)->toArray();

            // Get termEntryIds
            $termEntryIds = join(',', array_column($termEntryA, 'id') ?: [0]);

            // Get inner data for given termEntries
            $termA = $termM->getExportData(
                $selected ? $selected['termIds'] : $termEntryIds,
                $selected ? 'id'                 : 'termEntryId'
            );
            $attrA = $attrM->getExportData($termEntryIds, $tbxBasicOnly);
            $trscA = $trscM->getExportData($termEntryIds);

            // Foreach termEntry
            foreach ($termEntryA as $termEntry) {
                $line []= $this->tabs[3] . '<termEntry id="' . $termEntry['termEntryTbxId'] . '">';

                // If $selected arg is given, it means we do export to transfer terms from TermPortal to main Translate5 app
                // so termEntry-level descripGrp- and attribute-nodes should not be exported as we don't need
                // to overwrite existing values with translated values for them
                if (!$selected) {
                    $this->descripGrpNodes(4, $line, $attrA, $trscA, $termEntry['id']);
                    $this->attributeNodes (4, $line, $attrA, $termEntry['id']);
                }
                $this->transacGrpNodes(4, $line, $trscA, $termEntry['id']);
                foreach ($termA[$termEntry['id']] as $lang => $terms) {
                    $langSet = [];
                    $langSet []= $this->tabs[4] . '<langSet xml:lang="' . $lang . '">';
                    $this->descripGrpNodes(5, $langSet, $attrA, $trscA, $termEntry['id'], $lang);
                    $this->attributeNodes (5, $langSet, $attrA, $termEntry['id'], $lang);
                    $this->transacGrpNodes(5, $langSet, $trscA, $termEntry['id'], $lang);
                    $termsOut = [];
                    foreach ($terms as $term) {
                        if($this->isEmptyTerm($term['term'])) {
                            continue;
                        }
                        $termsOut []= $this->tabs[5] . '<tig>';
                        $tbxId = $selected ? '' : ' id="' . $term['termTbxId'] . '"';
                        $termsOut []= $this->tabs[6] . '<term' . $tbxId . '>' . htmlentities($term['term'], ENT_XML1) . '</term>';
                        $this->descripGrpNodes(6, $termsOut, $attrA, $trscA, $termEntry['id'], $lang, $term['id']);
                        $this->attributeNodes (6, $termsOut, $attrA, $termEntry['id'], $lang, $term['id']);
                        $this->transacGrpNodes(6, $termsOut, $trscA, $termEntry['id'], $lang, $term['id']);
                        $termsOut []= $this->tabs[5] . '</tig>';
                    }
                    $langSet = array_merge($langSet, $termsOut);
                    //merge into output only, if there is usable content (count == 1 is only the start tag)
                    if(count($langSet) > 1) {
                        $langSet []= $this->tabs[4] . '</langSet>';
                        $line = array_merge($line, $langSet);
                    }
                }
                $line []= $this->tabs[3] . '</termEntry>';

                // Append into tbx file, if the termEntry has content
                if(count($line) > 2) {
                    $this->write($line);
                }
            }
        }

        // Append closing body- and opening back-node
        $line []= $this->tabs[2] . '</body>';
        $line []= $this->tabs[2] . '<back>';
        $this->write($line);

        // Get refobject export data
        $refObjectListA = ZfExtended_Factory
            ::get('editor_Models_Terminology_Models_RefObjectModel')
            ->getExportData($collectionId);

        // Foreach refObjectList
        foreach ($refObjectListA as $listType => $refObjectListI) {
            $line []= $this->tabs[3] . '<refObjectList type="' . $listType . '">';
            foreach ($refObjectListI as $refObject) {
                $line []= $this->tabs[4] . '<refObject id="' . $refObject['key'] . '">';
                foreach (json_decode($refObject['data']) as $type => $value) {
                    $line []= $this->tabs[5] . '<item type="' . $type . '">' . htmlentities($value, ENT_XML1) . '</item>';
                }
                $line []= $this->tabs[4] . '</refObject>';
            }
            $line []= $this->tabs[3] . '</refObjectList>';
        }

        // Get terms_images-records for a given collection
        if ($exportImages && $qty = ZfExtended_Factory
            ::get('editor_Models_Terminology_Models_ImagesModel')
            ->getQtyByCollectionId($collectionId)) {

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
        //header('Content-Type: text/xml;');
        //readfile($this->file);
        if (!$selected) die();
    }

    public function descripGrpNodes($level, &$line, &$attrA, &$trscA, $termEntryId, $language = '', $termId = '') {

        //
        $descripGrp = ['attr' => [], 'trsc' => []];

        // Cut attrs, having isDescripGrp flag
        foreach ($attrA[$termEntryId][$language][$termId] ?? [] as $idx => $attr)
            if ($attr['isDescripGrp'])
                if ($descripGrp['attr'][$termEntryId][$language][$termId] []= $attr)
                    unset($attrA[$termEntryId][$language][$termId][$idx]);

        // Cut trscs, having isDescripGrp flag
        foreach ($trscA[$termEntryId][$language][$termId] ?? [] as $idx => $trsc)
            if ($trsc['isDescripGrp'])
                if ($descripGrp['trsc'][$termEntryId][$language][$termId] []= $trsc)
                    unset($trscA[$termEntryId][$language][$termId][$idx]);

        //
        if ($descripGrp['attr'] || $descripGrp['trsc']) {
            $line []= $this->tabs[$level] . '<descripGrp>';
            $this->attributeNodes($level + 1, $line, $descripGrp['attr'], $termEntryId, $language, $termId);
            $this->transacGrpNodes($level + 1, $line, $descripGrp['trsc'], $termEntryId, $language, $termId);
            $line []= $this->tabs[$level] . '</descripGrp>';
        }
    }

    public function attributeNodes($level, &$line, $attrA, $termEntryId, $language = '', $termId = '') {

        // Foreach level-attr
        foreach ($attrA[$termEntryId][$language][$termId] ?? [] as $attr) {

            // If skipDefinition flag is set - skip
            if ($this->skipDefinition && $attr['type'] == 'definition') {
                continue;
            }

            // Node attributes
            $_attr = [];

            // Append 'type' node-attr
            if ($attr['type']) $_attr []= 'type="' . $attr['type'] . '"';

            // Append 'target' node-attr
            if ($attr['elementName'] == 'xref' || $attr['elementName'] == 'ref' || $attr['target'])
                $_attr []= 'target="' . htmlentities($attr['target'], ENT_XML1) . '"';

            // Build and append node
            $line []= $this->tabs[$level] . '<' . $attr['elementName'] . ' ' . join(' ', $_attr) . '>'
                . htmlentities($attr['value'], ENT_XML1)
                . '</' . $attr['elementName'] . '>';
        }
    }

    public function transacGrpNodes($level, &$line, $trscA, $termEntryId, $language = '', $termId = '') {
        foreach ($trscA[$termEntryId][$language][$termId] ?? [] as $trsc) {
            $line []= $this->tabs[$level] . '<transacGrp>';
            $line []= $this->tabs[$level + 1] . '<transac type="transactionType">'. $trsc['transac'] . '</transac>';
            $line []= $this->tabs[$level + 1] . '<transacNote type="' . $trsc['transacType'] . '" target="' . $trsc['target'] . '">' . htmlentities($trsc['transacNote'], ENT_XML1) . '</transacNote>';
            $line []= $this->tabs[$level + 1] . '<date>' . explode(' ', $trsc['date'])[0] . '</date>';
            $line []= $this->tabs[$level] . '</transacGrp>';
        }
    }

    /**
     * @param $lines
     */
    public function write(&$lines, $overwrite = false) {

        // If $overwrite arg is truly
        if ($overwrite) {

            // If $overwrite arg is a string, assume it's a collection name, else just use 'export' as filename
            $filename = is_string($overwrite) ? rawurlencode($overwrite) : 'export';

            // Set up headers
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');
            header('Content-Type: text/xml');
            header('Content-Disposition: attachment; filename*=UTF-8\'\'' . $filename . '.tbx; filename=' . $filename . '.tbx');

            // Set up output buffering implicit flush mode
            ob_implicit_flush(true);

            // Flush
            ob_end_flush();
        }

        // Build raw output
        $raw = join("\n", $lines) . "\n";

        // Flush raw output
        echo $raw;

        // Write lines
        //file_put_contents($this->file, $raw, $overwrite ? null : FILE_APPEND);

        // Clear lines
        $lines = [];
    }

    /**
     * Render raw tbx contents for given $termIds, as if corrensponding termCollection would have only those terms
     *
     * @param array $termIds
     * @param string $noTranslationForLanguage
     * @param bool $skipDefinition Flag, indicating whether definition-attrs should be skipped while creating tbx-contents
     */
    public function renderRawForTaskImport(array $termIds, $userName = '', $skipDefinition = true) {

        // If $termIds arg is an empty array - return empty string
        if (!$termIds) return '';

        // Get distinct values
        $distinct
            = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel')
                ->distinctColsForTermIds('collectionId,termEntryId,language', $termIds);

        // Get distinct values for termEntryId and language columns for given $termIds
        list ($collectionId, $termEntryId, $language) = array_values($distinct);

        // Start output buffering
        ob_start();

        // Flush tbx contents in buffer
        $this->exportCollectionById(
            collectionId: array_shift($collectionId),
            userName: $userName,
            selected: [
                'termEntryIds' => $termEntryId,
                'languages' => $language,
                'termIds' => $termIds,
                'skipDefinition' => $skipDefinition
            ]
        );

        // Get buffered tbx contents
        return ob_get_clean();
    }

    /**
     * returns true if term is empty or contains only whitespace
     * @param string|null $term
     * @return bool
     */
    private function isEmptyTerm(?string $term): bool
    {
        if(is_null($term) || strlen($term) === 0) {
            return true;
        }
        return (bool) preg_match('/^\s*$/', $term);
    }
}
