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

use MittagQI\ZfExtended\Controller\Response\Header;

/**
 * FIXME: 1. code cleanup
 *        2. extract the file generation stream as separate classes. One for download, one for raw/file based export
 *
 * exports term data stored in translate5 to valid TBX files
 */
class editor_Models_Export_Terminology_Tbx
{
    /**
     * @var Zend_Db_Table_Rowset_Abstract|null
     */
    protected Zend_Db_Table_Rowset_Abstract $data;

    /**
     * Holds the XML Tree
     */
    protected SimpleXMLElement $tbx;

    protected string $target = '';

    protected array $languageCache = [];

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
     */
    protected array $tabs = [];

    /**
     * File path where the tbx will be saved. Only evaluated if $exportAsFile is set to true.
     */
    protected string $file = '';

    /**
     * Array containing absolute paths to be used while exporting to zip
     */
    protected array $zip = [

        // '.../terms-images-public/tc_<collectionId>/' directory where all
        // images of current term-collection are stored under their <uniqueName>-names
        'tc_root' => '',

        // Temporary '<tc_root>/media/' subdirectory where same images are collected but
        // under their <name>-names to be added to zip-archive for it to be importable
        'media' => '',

        // Destination zip-archive file
        'archive' => '',

        // [name.jpg => 123] pairs where 123 is how many times such name was used during zip-export
        // this is need to inject counter to the filename so that older files having same names are not
        // overwritten by further ones as names would looks like name.jpg, name-1.jpg, etc
        'qtyByName' => [],
    ];

    /**
     * Flag, indicating whether definition-attrs should be skipped while exporting tbx contents
     */
    public bool $skipDefinition = false;

    /***
     * True to export the collection as file at $file location
     * @var bool
     */
    private bool $exportAsFile = false;

    protected ?array $selected = [];

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
     */
    public function setTarget(string $target)
    {
        $this->target = $target;
    }

    /**
     * creates the TBX Element and returns the body node to add data
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
    public function export(bool $prettyPrint = false, ?string $spoofLangIfSameMajor = null): string
    {
        $body = $this->createTbx();

        //we assume that we got the data already sorted from DB
        $oldTermEntry = '';
        $oldLanguage = 0;
        foreach ($this->data as $row) {
            if ($this->isEmptyTerm($row->term)) {
                $oldTermEntry = $row->termEntryTbxId;
                $oldLanguage = $row->languageId;

                continue;
            }
            if ($oldTermEntry != $row->termEntryTbxId) {
                $termEntry = $body->addChild('termEntry');
                $termEntry->addAttribute('id', $row->termEntryTbxId);
                $oldTermEntry = $row->termEntryTbxId;
            }
            if ($oldLanguage != $row->languageId) {
                $langSet = $termEntry->addChild('langSet');
                $langSet->addAttribute('lang', $this->getLanguage((int) $row->languageId));
                $oldLanguage = $row->languageId;
            }

            // If $spoofLangIfSameMajor arg is given
            if ($spoofLangIfSameMajor) {
                // Get term actual lang
                $termLang = $this->getLanguage((int) $row->languageId);

                // Get term major lang
                $termMajorLang = ZfExtended_Languages::primaryCodeByRfc5646($termLang);

                // Get major lang for language given as $spoofLangIfSameMajor
                $spoofMajorLang = ZfExtended_Languages::primaryCodeByRfc5646($spoofLangIfSameMajor);

                // If task source language HAS sub-language
                if ($spoofLangIfSameMajor !== $spoofMajorLang) {
                    // If term language HAS NO sub-language
                    if ($termLang === $termMajorLang) {
                        // If term language is same as task source major language
                        if ($termLang === $spoofMajorLang) {
                            // Spoof term language for it to exactly match task source language
                            // as otherwise this term will be ignored by TermTagger
                            $langSet['lang'] = $spoofLangIfSameMajor;
                        }
                    }
                }
            }

            $tig = $langSet->addChild('tig');
            if (isset($row->tigId)) {
                $tigId = $row->tigId;
            }
            if (empty($tigId)) {
                $tigId = $this->convertToTigId($row->termTbxId);
            }

            $tig->addAttribute('id', $tigId);

            // Replace nbsp-chars with ordinary spaces
            $term = $tig->addChild('term', htmlspecialchars(str_replace([' '], [' '], $row->term), ENT_XML1));
            $term->addAttribute('id', $row->termTbxId);

            $termNote = $tig->addChild('termNote', $row->status);
            $termNote->addAttribute('type', 'normativeAuthorization');
        }

        $toFile = strlen($this->target) > 0;

        if ($prettyPrint) {
            $dom = dom_import_simplexml($this->tbx)->ownerDocument;
            $dom->formatOutput = true;
            if ($toFile) {
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
     */
    protected function getStatus(string $status): string
    {
        if (empty($this->statusMap[$status])) {
            $default = $this->statusMap[editor_Models_Terminology_TbxObjects_Term::STAT_STANDARDIZED];
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $log->logError('Error on TBX creation, missing term status "' . $status . '", set to "' . $default . '" in file ' . $this->target);

            return $default;
        }

        return $this->statusMap[$status];
    }

    /**
     * converts the given mid to a tig id
     */
    protected function convertToTigId(string $mid): string
    {
        if (strpos($mid, 'term_') === false) {
            return 'tig_' . $mid;
        }

        // check if mid (aka term-Id) is autogenerated
        $midParts = explode('_', $mid);
        if (count($midParts) < 6) {
            // if not autogenerated..
            return str_replace('term', 'tig', $mid);
        }

        // if mid (aka term-Id) is autogenerated, generate tig-Id from splitted parts
        $this->counterTig += 1;
        $tempReturn = 'tig_' . $midParts[1]
                        . '_' . $midParts[2]
                        . '_' . str_pad($this->counterTig, 7, '0', STR_PAD_LEFT)
                        . '_' . $midParts[3];

        return $tempReturn;
    }

    /**
     * Create term-images-public/tc_<collectionId>/media/ directory and setup
     * values in $this->zip array under 'tc_root', 'media' and 'archive' keys
     * to be further used as paths shortcuts
     */
    protected function prepareZipExport(int $collectionId): void
    {
        // Setup term collection's images root directory
        $this->zip['tc_root'] = ZfExtended_Factory
            ::get(editor_Models_Terminology_Models_ImagesModel::class)
                ->getImagePath($collectionId);

        // Setup temporary media/ subdirectory
        mkdir($this->zip['media'] = $this->zip['tc_root'] . '/media', 0777, true);

        // Set $this->file
        $this->setFile($this->zip['tc_root'] . '/exported.tbx');

        // Setup archive file path
        $this->zip['archive'] = preg_replace('~\.tbx$~', '.zip', $this->getFile());
    }

    /**
     * Export collection as a TBX file, or ZIP-file containing that TBX-file
     * along with media/ folder inside, if $exportImages arg is 'zip'
     *
     * @param bool $tbxBasicOnly
     * @param string|bool $exportImages
     * @param int $byTermEntryQty How many termEntries should be processed at once
     * @param int $byImageQty How many image binaries should be processed at once
     * @param null $selected Bunch of arguments passed by $this->renderRawForTaskImport(). Should look like below: [
     *    'termEntryIds' => [123, 234],
     *    'language' => ['en-us', 'en-gb'],
     *    'termIds' => [456, 567]
     * ]
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function exportCollectionById(
        int $collectionId,
        string $userName,
        $tbxBasicOnly = false,
        $exportImages = 'tbx',
        $byTermEntryQty = 1000,
        $byImageQty = 50,
        $selected = null
    ) {
        if ($this->isExportAsFile() && empty($this->getFile())) {
            // export as file is set but the file path is empty or not valid
            throw new editor_Models_Export_Terminology_Exception('E1449');
        }

        // Models shortcuts
        $termM = ZfExtended_Factory::get(editor_Models_Terminology_Models_TermModel::class);
        $attrM = ZfExtended_Factory::get(editor_Models_Terminology_Models_AttributeModel::class);
        $trscM = ZfExtended_Factory::get(editor_Models_Terminology_Models_TransacgrpModel::class);

        // Setup $this->selected to accessible in other methods
        $this->selected = $selected;

        // Get total qty of entries to be processed
        $qty = $selected
            ? count($selected['termEntryIds'])
            : ZfExtended_Factory
                ::get(editor_Models_Terminology_Models_TermEntryModel::class)
                    ->getQtyByCollectionId($collectionId);

        /** @var editor_Models_Terminology_Models_TermEntryModel $m */
        $m = ZfExtended_Factory::get(editor_Models_Terminology_Models_TermEntryModel::class);

        // Build WHERE clause
        $where = 'collectionId = ' . $collectionId;

        // If $termEntryIds arg is given - make sure only those will be fetched
        if ($selected) {
            $where .= $m->db->getAdapter()->quoteInto(' AND `id` IN (?)', $selected['termEntryIds'] ?: [0]);
        }

        // Lines array
        $line = [];

        // If $tbxBasicOnly arg is true, overwrite it with comma-separated dataTypeIds of tbx-basic attributes
        if ($tbxBasicOnly) {
            $tbxBasicOnly = ZfExtended_Factory
                ::get(editor_Models_Terminology_Models_AttributeDataType::class)
                    ->getTbxBasicIds();
        }

        // Prepare indents
        for ($i = 0; $i < 20; $i++) {
            $this->tabs[$i] = str_pad('', $i * 4, ' ');
        }

        // Create temporary folder to be further zipped and do other things
        if ($exportImages === 'zip') {
            $this->prepareZipExport($collectionId);
        }

        // Prepare xml header
        $line[] = '<?xml version=\'1.0\'?><!DOCTYPE martif SYSTEM "TBXBasiccoreStructV02.dtd">';
        $line[] = '<martif>';

        // Get collection name
        $collection = ZfExtended_Factory::get(editor_Models_TermCollection_TermCollection::class);
        $collection->load($collectionId);
        $collectionName = $collection->getName();

        // Get list of languages
        $languages = join(', ', $selected['languages'] ?? array_column($collection->getLanguagesInTermCollections([$collectionId]), 'rfc5646'));

        // Write <martifHeader> nodes
        $line[] = $this->tabs[1] . '<martifHeader>';
        $line[] = $this->tabs[2] . '<fileDesc>';
        $line[] = $this->tabs[3] . '<titleStmt>';
        $line[] = $this->tabs[4] . '<title>Export of translate5 termCollection ' . $collectionName . '</title>';
        $line[] = $this->tabs[4] . '<note>Contains the languages: ' . $languages . '</note>';
        $line[] = $this->tabs[3] . '</titleStmt>';
        $line[] = $this->tabs[3] . '<sourceDesc>';
        $line[] = $this->tabs[4] . '<p>File is exported from translate5 instance at https://' . $_SERVER['HTTP_HOST'] . ' by the user ' . $userName . '</p>';
        $line[] = $this->tabs[3] . '</sourceDesc>';
        $line[] = $this->tabs[2] . '</fileDesc>';
        $line[] = $this->tabs[2] . '<encodingDesc>';
        $line[] = $this->tabs[3] . '<p type="XCSURI">http://www.lisa.org/fileadmin/standards/tbx_basic/TBXBasicXCSV02.xcs</p>';
        $line[] = $this->tabs[2] . '</encodingDesc>';
        $line[] = $this->tabs[1] . '</martifHeader>';

        // Open <text> and <body> nodes
        $line[] = $this->tabs[1] . '<text>';
        $line[] = $this->tabs[2] . '<body>';

        if ($this->isExportAsFile()) {
            $this->write($line);
        } else {
            $this->write($line, $selected ? null : $collectionName);
        }

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
                $selected ? 'id' : 'termEntryId'
            );
            $attrA = $attrM->getExportData($termEntryIds, $tbxBasicOnly);
            $trscA = $trscM->getExportData($termEntryIds);

            // Foreach termEntry
            foreach ($termEntryA as $termEntry) {
                $line[] = $this->tabs[3] . '<termEntry id="' . $termEntry['termEntryTbxId'] . '">';

                // If $selected arg is given, it means we do export to transfer terms from TermPortal to main Translate5 app
                // so termEntry-level descripGrp- and attribute-nodes should not be exported as we don't need
                // to overwrite existing values with translated values for them
                if (! $selected) {
                    $this->descripGrpNodes(4, $line, $attrA, $trscA, $termEntry['id']);
                    $this->attributeNodes(4, $line, $attrA, $termEntry['id']);
                }
                $this->transacGrpNodes(4, $line, $trscA, $termEntry['id']);
                foreach ($termA[$termEntry['id']] as $lang => $terms) {
                    $langSet = [];
                    $langSet[] = $this->tabs[4] . '<langSet xml:lang="' . $lang . '">';
                    $this->descripGrpNodes(5, $langSet, $attrA, $trscA, $termEntry['id'], $lang);
                    $this->attributeNodes(5, $langSet, $attrA, $termEntry['id'], $lang);
                    $this->transacGrpNodes(5, $langSet, $trscA, $termEntry['id'], $lang);
                    $termsOut = [];
                    foreach ($terms as $term) {
                        if ($this->isEmptyTerm($term['term'])) {
                            continue;
                        }
                        $termsOut[] = $this->tabs[5] . '<tig>';
                        $tbxId = $selected ? '' : ' id="' . $term['termTbxId'] . '"';
                        $termsOut[] = $this->tabs[6] . '<term' . $tbxId . '>' . htmlentities($term['term'], ENT_XML1) . '</term>';
                        $this->descripGrpNodes(6, $termsOut, $attrA, $trscA, $termEntry['id'], $lang, $term['id']);
                        $this->attributeNodes(6, $termsOut, $attrA, $termEntry['id'], $lang, $term['id']);
                        $this->transacGrpNodes(6, $termsOut, $trscA, $termEntry['id'], $lang, $term['id']);
                        $termsOut[] = $this->tabs[5] . '</tig>';
                    }
                    $langSet = array_merge($langSet, $termsOut);
                    //merge into output only, if there is usable content (count == 1 is only the start tag)
                    if (count($langSet) > 1) {
                        $langSet[] = $this->tabs[4] . '</langSet>';
                        $line = array_merge($line, $langSet);
                    }
                }
                $line[] = $this->tabs[3] . '</termEntry>';

                // Append into tbx file, if the termEntry has content
                if (count($line) > 2) {
                    $this->write($line);
                }
            }
        }

        // Append closing body- and opening back-node
        $line[] = $this->tabs[2] . '</body>';
        $line[] = $this->tabs[2] . '<back>';
        $this->write($line);

        // Get refobject export data
        $refObjectListA = ZfExtended_Factory
            ::get('editor_Models_Terminology_Models_RefObjectModel')
                ->getExportData($collectionId);

        // Foreach refObjectList
        foreach ($refObjectListA as $listType => $refObjectListI) {
            $line[] = $this->tabs[3] . '<refObjectList type="' . $listType . '">';
            foreach ($refObjectListI as $refObject) {
                $line[] = $this->tabs[4] . '<refObject id="' . $refObject['key'] . '">';
                foreach (json_decode($refObject['data']) as $type => $value) {
                    $line[] = $this->tabs[5] . '<item type="' . $type . '">' . htmlentities($value, ENT_XML1) . '</item>';
                }
                $line[] = $this->tabs[4] . '</refObject>';
            }
            $line[] = $this->tabs[3] . '</refObjectList>';
        }

        // Get terms_images-records for a given collection
        if ($exportImages && $qty = ZfExtended_Factory
            ::get('editor_Models_Terminology_Models_ImagesModel')
                ->getQtyByCollectionId($collectionId)) {
            // Images model shortcut
            $i = ZfExtended_Factory::get('editor_Models_Terminology_Models_ImagesModel');

            // Open refObjectList-node
            $line[] = $this->tabs[3] . '<refObjectList type="binaryData">';

            // Foreach page by $byImageQty at a time
            for ($p = 1; $p <= ceil($qty / $byImageQty); $p++) {
                // Fetch images
                $imgA = $i->db->fetchAll($where, null, $byImageQty, ($p - 1) * $byImageQty)->toArray();

                // Foreach image
                foreach ($imgA as $imgI) {
                    // Open refObject-node
                    $line[] = $this->tabs[4] . '<refObject id="' . $imgI['targetId'] . '">';

                    // Get full filepath to the image
                    $storedPath = $i->getImagePath($collectionId, $imgI['uniqueName']);

                    // If images should be exported in hex-encoded format
                    if ($exportImages === 'tbx') {
                        // Add markup
                        $file = file_get_contents($storedPath);
                        $line[] = $this->tabs[5] . '<item type="name">' . $imgI['name'] . '</item>';
                        $line[] = $this->tabs[5] . '<item type="encoding">hex</item>';
                        $line[] = $this->tabs[5] . '<item type="format">' . (preg_match('~/~', $imgI['format']) ? '' : 'image/') . $imgI['format'] . '</item>';
                        $text = preg_replace('~.{2}~', '$0 ', bin2hex($file));
                        $line[] = $this->tabs[5] . '<item type="data">' . $text . '</item>';

                        // Else if images should be exported into media/ directory inside zip-file
                    } else {
                        // Prepare name
                        $name = $this->getImageFilename4Export($imgI['name']);

                        // Add itemSet-node
                        $line[] = $this->tabs[5] . '<itemSet>';
                        $line[] = $this->tabs[6] . '<itemGrp>';
                        $line[] = $this->tabs[7] . '<item>' . $imgI['name'] . '</item>';
                        $line[] = $this->tabs[7] . '<xref target="media/' . $name . '"/>';
                        $line[] = $this->tabs[6] . '</itemGrp>';
                        $line[] = $this->tabs[5] . '</itemSet>';

                        // Copy image to temporary folder
                        if (is_file($storedPath)) {
                            copy($storedPath, $this->zip['media'] . '/' . $name);
                        }
                    }

                    // Close refObject-node
                    $line[] = $this->tabs[4] . '</refObject>';
                }

                // Append into tbx file
                $this->write($line);
            }

            // Close refObjectList-node
            $line[] = $this->tabs[3] . '</refObjectList>';
        }
        $line[] = $this->tabs[2] . '</back>';
        $line[] = $this->tabs[1] . '</text>';
        $line[] = '</martif>';
        $this->write($line);

        // Read
        //header('Content-Type: text/xml;');
        //readfile($this->file);
        if (! $selected && ! $this->isExportAsFile()) {
            die();
        }

        // If images should be exported via zip
        if ($exportImages === 'zip') {
            // Add tbx file with normal compression and media/ folder with NO compression
            $zip = new ZipArchive();
            $zip->open($this->zip['archive'], ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $zip->addFile($this->getFile(), 'exported.tbx');
            $zip->addGlob($this->zip['media'] . '/*', 0, [
                'add_path' => 'media/',
                'remove_all_path' => true,
                'comp_method' => ZipArchive::CM_STORE,
            ]);
            $zip->close();

            // Drop tbx-file
            unlink($this->getFile());

            // Drop temporary media/ directory
            ZfExtended_Utils::recursiveDelete($this->zip['media']);

            // Set up headers to download the zip
            Header::sendDownload(
                $collectionName . '.zip',
                'application/zip',
                'no-cache',
                filesize($this->zip['archive']),
                [
                    'X-Accel-Buffering' => 'no',
                ]
            );

            // Flush zip binary data
            readfile($this->zip['archive']);

            // Drop zip-file
            unlink($this->zip['archive']);
        }
    }

    public function descripGrpNodes($level, &$line, &$attrA, &$trscA, $termEntryId, $language = '', $termId = '')
    {
        //
        $descripGrp = [
            'attr' => [],
            'trsc' => [],
        ];

        // Cut attrs, having isDescripGrp flag
        foreach ($attrA[$termEntryId][$language][$termId] ?? [] as $idx => $attr) {
            if ($attr['isDescripGrp']) {
                if ($descripGrp['attr'][$termEntryId][$language][$termId][] = $attr) {
                    unset($attrA[$termEntryId][$language][$termId][$idx]);
                }
            }
        }

        // Cut trscs, having isDescripGrp flag
        foreach ($trscA[$termEntryId][$language][$termId] ?? [] as $idx => $trsc) {
            if ($trsc['isDescripGrp']) {
                if ($descripGrp['trsc'][$termEntryId][$language][$termId][] = $trsc) {
                    unset($trscA[$termEntryId][$language][$termId][$idx]);
                }
            }
        }

        //
        if ($descripGrp['attr'] || $descripGrp['trsc']) {
            $line[] = $this->tabs[$level] . '<descripGrp>';
            $this->attributeNodes($level + 1, $line, $descripGrp['attr'], $termEntryId, $language, $termId);
            $this->transacGrpNodes($level + 1, $line, $descripGrp['trsc'], $termEntryId, $language, $termId);
            $line[] = $this->tabs[$level] . '</descripGrp>';
        }
    }

    public function attributeNodes($level, &$line, $attrA, $termEntryId, $language = '', $termId = '')
    {
        // Foreach level-attr
        foreach ($attrA[$termEntryId][$language][$termId] ?? [] as $attr) {
            // If skipDefinition flag is set - skip
            if ($this->skipDefinition && $attr['type'] == 'definition') {
                continue;
            }

            // If we're here due to termtranslation - prevent grammaticalGender from being exported
            if ($this->selected && $attr['type'] === 'grammaticalGender') {
                continue;
            }

            // Node attributes
            $_attr = [];

            // Append 'type' node-attr
            if ($attr['type']) {
                $_attr[] = 'type="' . $attr['type'] . '"';
            }

            // Append 'target' node-attr
            if ($attr['elementName'] == 'xref' || $attr['elementName'] == 'ref' || $attr['target']) {
                $_attr[] = 'target="' . htmlentities($attr['target'], ENT_XML1) . '"';
            }

            // Build and append node
            $line[] = $this->tabs[$level] . '<' . $attr['elementName'] . ' ' . join(' ', $_attr) . '>'
                . htmlentities($attr['value'], ENT_XML1)
                . '</' . $attr['elementName'] . '>';
        }
    }

    public function transacGrpNodes($level, &$line, $trscA, $termEntryId, $language = '', $termId = '')
    {
        // Foreach transacGrp-records
        foreach ($trscA[$termEntryId][$language][$termId] ?? [] as $trsc) {
            // If we're here due to termtranslation - export full date, or just Y-m-d otherwise
            $date = $this->selected ? $trsc['date'] : explode(' ', $trsc['date'])[0];

            // Do export
            $line[] = $this->tabs[$level] . '<transacGrp>';
            $line[] = $this->tabs[$level + 1] . '<transac type="transactionType">' . $trsc['transac'] . '</transac>';
            $line[] = $this->tabs[$level + 1] . '<transacNote type="' . $trsc['transacType'] . '" target="' . $trsc['target'] . '">' . htmlentities($trsc['transacNote'], ENT_XML1) . '</transacNote>';
            $line[] = $this->tabs[$level + 1] . "<date>$date</date>";
            $line[] = $this->tabs[$level] . '</transacGrp>';
        }
    }

    public function write(&$lines, $overwrite = false)
    {
        // If $overwrite arg is truly
        if ($overwrite) {
            // If $overwrite arg is a string, assume it's a collection name, else just use 'export' as filename
            $filename = is_string($overwrite) ? rawurlencode($overwrite) : 'export';

            // Set up headers
            Header::sendDownload(
                $filename . '.tbx',
                'text/xml',
                'no-cache',
                -1,
                [
                    'X-Accel-Buffering' => 'no',
                ]
            );

            // Set up output buffering implicit flush mode
            ob_implicit_flush(true);

            // Flush
            ob_end_flush();
        }

        // Build raw output
        $raw = join("\n", $lines) . "\n";

        if ($this->isExportAsFile()) {
            // Write lines
            file_put_contents($this->file, $raw, $overwrite ? null : FILE_APPEND);
        } else {
            // Flush raw output
            echo $raw;
        }

        // Clear lines
        $lines = [];
    }

    /**
     * Render raw tbx contents for given $termIds, as if corrensponding termCollection would have only those terms
     *
     * @param bool $skipDefinition Flag, indicating whether definition-attrs should be skipped while creating tbx-contents
     */
    public function renderRawForTaskImport(array $termIds, $userName = '', $skipDefinition = true)
    {
        // If $termIds arg is an empty array - return empty string
        if (! $termIds) {
            return '';
        }

        // Get distinct values
        $distinct
            = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel')
                ->distinctColsForTermIds('collectionId,termEntryId,language', $termIds);

        // Get distinct values for termEntryId and language columns for given $termIds
        list($collectionId, $termEntryId, $language) = array_values($distinct);

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
                'skipDefinition' => $skipDefinition,
            ]
        );

        // Get buffered tbx contents
        return ob_get_clean();
    }

    /**
     * returns true if term is empty or contains only whitespace
     */
    private function isEmptyTerm(?string $term): bool
    {
        return editor_Models_Terminology_Models_TermModel::isEmptyTerm($term);
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function setFile(string $file): void
    {
        $this->file = $file;

        // Set $this->exportAsFile-flag
        if ($this->file) {
            $this->setExportAsFile(true);
        }
    }

    public function isExportAsFile(): bool
    {
        return $this->exportAsFile;
    }

    public function setExportAsFile(bool $exportAsFile): void
    {
        $this->exportAsFile = $exportAsFile;
    }

    /**
     * Check whether we've already had such a $name given, and if so -
     * append a counter to prevent existing file from being overrwitten
     */
    public function getImageFilename4Export($name): string
    {
        // Get file and extension
        $file = pathinfo($name, PATHINFO_FILENAME);
        $ext = pathinfo($name, PATHINFO_EXTENSION);

        // Build result name
        $result = isset($this->zip['qtyByName'][$name])
            ? "$file-{$this->zip['qtyByName'][$name]}.$ext"
            : $name;

        // Increment $name usage counter
        $this->zip['qtyByName'][$name] = ($this->zip['qtyByName'][$name] ?? 0) + 1;

        // Return name with counter, if need
        return $result;
    }
}
