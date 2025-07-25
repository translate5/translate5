<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Collect the terms and the terms attributes from the tbx file and save them to the database
 */
use editor_Models_Terminology_Models_CollectionAttributeDataType as CollectionAttributeDataType;

class editor_Models_Terminology_Import_TbxFileImport
{
    public const TBX_TIG = 'tig';

    public const TBX_TERM_ENTRY = 'termEntry';

    public const TBX_LANGSET = 'langSet';

    protected ZfExtended_Logger $logger;

    protected Zend_Config $config;

    protected ZfExtended_Models_User $user;

    protected editor_Models_Terminology_Models_AttributeDataType $attributeDataTypeModel;

    protected editor_Models_Terminology_TbxObjects_DataType $dataType;

    protected editor_Models_Terminology_Import_TbxBinaryDataImport $binaryImport;

    /**
     * Array of [ECODE => info] pairs, where info is an array of [qty => ..., msg => ...] with quantity to be incremented
     * each time event with a given ECODE occurs. WARNING: for any ECODE, added as a key into this array events having such a
     * ECODE won't be logged individually, but a single event will be logged only on tbx import completion, with quantity
     * of occurrences as an extraData, so that extraData for each individual events is NOT preserved
     *
     * @var array|array[]
     */
    protected array $eventQty = [
        'E1472' => [
            'qty' => 0,
            'msg' => '',
            'cases' => [],
        ],
        'E1446' => [
            'qty' => 0,
            'msg' => '',
            'cases' => [],
        ],
    ];

    /**
     * $tbxMap = segment names for different TBX standards
     * $this->tbxMap['tig'] = 'tig'; - or if 'ntig' element - $this->tbxMap['tig'] = 'ntig';
     * each possible segment for TBX standard must be defined and will be merged in translate5 standard!
     */
    protected array $tbxMap;

    /**
     * Array to map any known values of transac and transacType to tbx-standard values,
     * for converting non-standard values into standard prior saving into database
     */
    protected array $transacMap = [
        'origination' => 'origination',
        'modification' => 'modification',
        'creation' => 'origination',                // Not part of tbx-standart

        'responsibility' => 'responsibility',
        'responsiblePerson' => 'responsibility',    // Not part of tbx-standart
    ];

    /***
     * @var bool
     */
    protected bool $mergeTerms;

    /***
     * Value of $tbxFilePath-arg used in last importXmlFile() method call
     *
     * @var string
     */
    protected string $tbxFilePath;

    /**
     * current term collection
     */
    protected editor_Models_TermCollection_TermCollection $collection;

    /**
     * All available languages in Translate5
     * $languages['de_DE' => 4]
     */
    protected array $languages;

    /**
     * Collected unknown languages
     */
    protected array $unknownLanguages = [];

    /**
     * @var editor_Models_Terminology_TbxObjects_Langset|mixed
     */
    protected editor_Models_Terminology_TbxObjects_Langset $langsetObject;

    protected editor_Models_Terminology_BulkOperation_Attribute $bulkAttribute;

    protected editor_Models_Terminology_BulkOperation_TransacGrp $bulkTransacGrp;

    protected editor_Models_Terminology_BulkOperation_Term $bulkTerm;

    protected editor_Models_Terminology_BulkOperation_RefObject $bulkRefObject;

    /**
     * In this class is the whole merge logic
     */
    protected editor_Models_Terminology_BulkOperation_TermEntry $bulkTermEntry;

    /**
     * contains the whole term note status mapping
     */
    protected editor_Models_Terminology_TermStatus $termNoteStatus;

    protected ZfExtended_EventManager $events;

    /**
     * Array of transacgrp responsible person names => ids.
     * If it's null, it means it was not yet lazy-loaded
     *
     * @var null|array
     */
    protected $transacgrpPersons = null;

    /**
     * editor_Models_Import_TermListParser_TbxFileImport constructor.
     * @throws Zend_Exception
     */
    public function __construct()
    {
        if (! defined('LIBXML_VERSION') || LIBXML_VERSION < '20620') {
            //Mindestversion siehe http://www.php.net/manual/de/xmlreader.readstring.php
            throw new Zend_Exception('LIBXML_VERSION must be at least 2.6.20 (or as integer 20620).');
        }
        $this->config = Zend_Registry::get('config');
        $this->logger = Zend_Registry::get('logger');
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', [get_class($this)]);

        $this->attributeDataTypeModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeDataType');

        $this->langsetObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_Langset');

        $this->dataType = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_DataType');

        $this->bulkTermEntry = ZfExtended_Factory::get('editor_Models_Terminology_BulkOperation_TermEntry');
        $this->bulkAttribute = new editor_Models_Terminology_BulkOperation_Attribute();
        $this->bulkTransacGrp = new editor_Models_Terminology_BulkOperation_TransacGrp();
        $this->bulkTerm = new editor_Models_Terminology_BulkOperation_Term();

        $this->termNoteStatus = new editor_Models_Terminology_TermStatus();
    }

    /**
     * Import given TBX file and prepare Import arrays, if file can not be opened throw Zend_Exception.
     * returns the count of available term entries in the loaded file
     * @throws Zend_Exception
     * @throws Exception
     */
    public function importXmlFile(
        string $tbxFilePath,
        editor_Models_TermCollection_TermCollection $collection,
        ZfExtended_Models_User $user,
        bool $mergeTerms
    ): int {
        $this->collection = $collection;
        $this->mergeTerms = $mergeTerms;
        $this->tbxFilePath = $tbxFilePath;
        $this->prepareImportArrays($user);

        //reset internal XML error list
        //libxml_use_internal_errors(true)
        libxml_clear_errors();
        $xmlReader = (new class() extends XMLReader {
            public function reopen(string $tbxFilePath)
            {
                $this->close();
                $this->open($tbxFilePath, flags: LIBXML_PARSEHUGE);
            }
        });

        if (! $xmlReader->open($tbxFilePath, flags: LIBXML_PARSEHUGE)) {
            throw new Zend_Exception('TBX file can not be opened.');
        }

        $totalCount = $this->countTermEntries($xmlReader);

        // Setup binaryImport instance
        $this->binaryImport = ZfExtended_Factory
            ::get(editor_Models_Terminology_Import_TbxBinaryDataImport::class, [
                $this->tbxFilePath,
                $this->collection,
            ]);

        $xmlReader->reopen($tbxFilePath); //reset pointer to beginning
        $this->processRefObjects($xmlReader);

        $xmlReader->reopen($tbxFilePath); //reset pointer to beginning
        $this->processTermEntries($xmlReader, $totalCount);

        $this->logUnknownLanguages();

        $dataTypeAssoc = ZfExtended_Factory::get(CollectionAttributeDataType::class);
        /* @var $dataTypeAssoc CollectionAttributeDataType */
        // insert all attribute data types for current collection in the terms_collection_attribute_datatype table
        $dataTypeAssoc->updateCollectionAttributeAssoc($this->collection->getId());

        //syncronizes the term status picklists to the valid values
        $this->termNoteStatus->syncStatusToDataTypes();

        // remove all empty term entries after the tbx import
        $termEntry = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        /* @var $termEntry editor_Models_Terminology_Models_TermEntryModel */
        $termEntry->removeEmptyFromCollection([$this->collection->getId()]);

        // update the collection import statistics with the new counted totals
        $this->setCollectionImportStatistic();

        $unknownStates = $this->termNoteStatus->getUnknownStates();
        if (! empty($unknownStates)) {
            $this->log('TBX Import: The TBX contains terms with unknown administrative / normative states. See details for a list of states.', 'E1360', [
                'unknown' => $unknownStates,
            ], 'warn');
        }

        // If some image definitions are missing
        if ($definitions = $this->binaryImport->missingImages['definitions']) {
            // Prepare msg
            $msg = "TBX Import: Image definition is missing in back-matter for the figure-attribute's target";

            // Do log
            $this->log($msg, 'E1540', [
                'forWhichTargets' => array_keys($definitions),
            ], 'warn');
        }

        // If some image files are missing
        if ($files = $this->binaryImport->missingImages['files']) {
            // Prepare log msg
            $msg = "TBX Import: Image file is missing in extracted zip-archive under the path given by xGraphic's target or figure's back-matter";

            // Do log
            $this->log($msg, 'E1544', [
                'forWhichPaths' => array_keys($files),
            ], 'warn');
        }

        // Foreach counted events
        foreach ($this->eventQty as $ecode => $info) {
            // If no occurences
            if ($info['qty'] === 0) {
                continue;
            }

            // Make sure below $this->log() call with do log instead of increment count
            unset($this->eventQty[$ecode]);

            // Do log
            $this->log($info['msg'], $ecode, [
                'occurrencesQty' => $info['qty'],
                'cases' => array_values($info['cases']),
            ], 'warn');
        }

        $data = [
            'termEntries' => $this->bulkTermEntry->getStatistics(),
            'terms' => $this->bulkTerm->getStatistics(),
            'attributes' => $this->bulkAttribute->getStatistics(),
            'transacGroups' => $this->bulkTransacGrp->getStatistics(),
            'images' => $this->binaryImport->imageQty + [
                'totalCount' => array_sum($this->binaryImport->imageQty),
            ],
            'refObjects' => $this->bulkRefObject->getStatistics(),
            'collection' => $this->collection->getName(),
            'maxMemUsed in MB' => round(memory_get_peak_usage() / 2 ** 20),
        ];
        $this->log('Imported TBX data into collection {collection}', 'E1028', $data);

        return $totalCount;
    }

    /**
     * Prepare init array variables for merge procedure and check isset function.
     * @throws Zend_Db_Statement_Exception
     */
    private function prepareImportArrays(ZfExtended_Models_User $user)
    {
        $memLog = function ($msg) {
            //error_log($msg.round(memory_get_usage()/2**20).' MB');
        };
        $this->user = $user;
        $this->dataType->resetData();

        //TODO how to distinguish between TBX V2 (termEntry) and V3 (conceptEntry)?
        $this->tbxMap[$this::TBX_TERM_ENTRY] = $this::TBX_TERM_ENTRY; //for V3 set conceptEntry here (implement as subclass???)
        $this->tbxMap[$this::TBX_LANGSET] = 'langSet';
        $this->tbxMap[$this::TBX_TIG] = 'tig';

        $memLog('Start Loading Data:  ');
        $languagesModel = ZfExtended_Factory::get('editor_Models_Languages')->getAvailableLanguages();
        foreach ($languagesModel as $language) {
            $this->languages[strtolower($language['value'])] = $language['id'];
        }

        // get custom attribute label text and prepare array to check if custom label text exist.
        $memLog('Loaded languages:    ');
        $this->dataType->loadData();
        $memLog('Loaded datatype:     ');

        $this->bulkTermEntry->loadExisting($this->collection->getId());  //FIXME
        $memLog('Loaded term entries: ');
        $this->bulkTerm->loadExisting($this->collection->getId());
        $memLog('Loaded terms:        ');
    }

    /**
     * Counts the term entries in the loaded document
     */
    private function countTermEntries(XMLReader $xmlReader): int
    {
        // find first termEntry and count them from there
        while ($xmlReader->read() && $xmlReader->name !== $this->tbxMap[$this::TBX_TERM_ENTRY]);
        $totalCount = 0;
        while ($xmlReader->name === $this->tbxMap[$this::TBX_TERM_ENTRY]) {
            $totalCount++;
            $xmlReader->next($this->tbxMap[$this::TBX_TERM_ENTRY]);

            if ($error = libxml_get_last_error()) {
                // 'E1393' => 'TBX Import: The XML structure of the TBX file is invalid: {message}',
                throw new editor_Models_Terminology_Import_Exception('E1393', get_object_vars($error));
            }
        }

        return $totalCount;
    }

    /**
     * processes the term entries found in the XML
     * @throws Exception
     */
    protected function processTermEntries(XMLReader $xmlReader, int $totalCount)
    {
        $importCount = 0;
        $progress = 0;

        while ($xmlReader->read() && $xmlReader->name !== $this->tbxMap[$this::TBX_TERM_ENTRY]);
        //process termentry
        while ($xmlReader->name === $this->tbxMap[$this::TBX_TERM_ENTRY]) {
            if (strlen($_xml = $xmlReader->readOuterXML())) {
                $termEntryNode = new SimpleXMLElement($_xml);
                $parentEntry = $this->handleTermEntry($termEntryNode);

                foreach ($termEntryNode->{$this->tbxMap[$this::TBX_LANGSET]} as $languageGroup) {
                    $parentLangSet = $this->handleLanguageGroup($languageGroup, $parentEntry);
                    if (is_null($parentLangSet)) {
                        continue;
                    }
                    foreach ($languageGroup->{$this->tbxMap[$this::TBX_TIG]} as $termGroup) {
                        $this->handleTermGroup($termGroup, $parentLangSet);
                    }
                }
                $this->saveParsedTermEntryNode();
                $importCount++;

                //since we do not want to kill the worker table by updating the progress too often,
                // we do that only 100 times per import, in other words once per percent
                $newProgress = min(100, round(($importCount / $totalCount) * 100));
                if ($newProgress > $progress) {
                    $progress = $newProgress;
                    $this->events->trigger('afterTermEntrySave', $this, [
                        'progress' => $progress / 100,
                    ]); //we store the value as value between 0 and 1
                }
            }

            // Uncomment this to print the progress
            //error_log("Update progress: [".$importCount.'/'.$totalCount.'] ( progress: '.$progress.'  %)');
            $xmlReader->next($this->tbxMap[$this::TBX_TERM_ENTRY]);
        }
        $this->bulkFreeMemory();
    }

    /**
     * @throws editor_Models_Terminology_Import_Exception
     * @throws Exception
     */
    protected function processRefObjects(XMLReader $xmlReader)
    {
        $this->bulkRefObject = new editor_Models_Terminology_BulkOperation_RefObject();
        $this->bulkRefObject->loadExisting($this->collection->getId());
        while ($xmlReader->read() && $xmlReader->name !== 'refObjectList');
        while ($xmlReader->name === 'refObjectList') {
            $listType = $xmlReader->getAttribute('type');
            $node = new SimpleXMLElement($xmlReader->readOuterXML(), LIBXML_PARSEHUGE);
            if ($listType === 'binaryData') {
                $this->binaryImport->import($node);
            } else {
                $this->importOtherRefObjects($node, $listType);
            }
            $xmlReader->next('refObjectList');
        }
    }

    /**
     * Save parsed elements.
     * @throws Zend_Db_Table_Exception|editor_Models_Terminology_Import_Exception|Zend_Db_Statement_Exception
     */
    private function saveParsedTermEntryNode()
    {
        //before we save anything to the database we have to perform the merges
        $this->bulkTerm->mergeTerms($this->bulkTermEntry, $this->mergeTerms);

        $this->bulkTermEntry->createOrUpdateElement();

        // Load the attributes and transac for the current term entry. Loading this on each term entry saves memory and it is faster as loading all at once.
        $this->bulkTransacGrp->loadExisting($this->bulkTermEntry->getCurrentEntry()->id);
        $this->bulkAttribute->loadExisting($this->bulkTermEntry->getCurrentEntry()->id);

        //bulkTerm create or update must be called before attributes and transacGrps in order to save the termId there correctly
        $this->bulkTerm->createOrUpdateElement();
        $this->bulkAttribute->createOrUpdateElement();
        $this->bulkTransacGrp->createOrUpdateElement();
    }

    /**
     * Iterate over the termEntry structure and call handler for each element.
     * There will be parsed all child elements and returns termEntry as object.
     * @throws ZfExtended_ErrorCodeException
     */
    private function handleTermEntry(SimpleXMLElement $termEntry): editor_Models_Terminology_TbxObjects_TermEntry
    {
        $cls = $this->bulkTermEntry->getNewImportObject();
        /** @var editor_Models_Terminology_TbxObjects_TermEntry $newEntry */
        $newEntry = new $cls();
        $newEntry->collectionId = $this->collection->getId();
        $newEntry->termEntryTbxId = $this->getIdOrGenerate($termEntry);

        $this->bulkTermEntry->add($newEntry);

        if (isset($termEntry->descrip)) {
            //collect and set the descrip attributes, and check if there is a definition
            $descrips = $this->setAttributeTypes($termEntry->descrip, $newEntry);
            /* @var editor_Models_Terminology_TbxObjects_Attribute $descrip */
            foreach ($descrips as $descrip) {
                if ($descrip->type == 'definition') {
                    $newEntry->definition = $descrip->value;

                    break;
                }
            }
        }

        $this->setDiscriptGrp($termEntry, $newEntry, 'termEntry');

        if (isset($termEntry->transacGrp)) {
            foreach ($termEntry->transacGrp as $transacGrp) {
                $newEntry->transacGrp = $this->setTransacAttributes($transacGrp, false, 'termEntry', $newEntry);
            }
        }
        if (isset($termEntry->note)) {
            $this->setAttributeTypes($termEntry->note, $newEntry);
        }
        if (isset($termEntry->xref)) {
            $this->setAttributeTypes($termEntry->xref, $newEntry);
        }
        if (isset($termEntry->ref)) {
            $this->setAttributeTypes($termEntry->ref, $newEntry);
        }

        return $newEntry;
    }

    /**
     * Iterate over the langSet structure and call handler for each element.
     * There will be parsed all child elements and returns langSet as object.
     * @throws ZfExtended_ErrorCodeException
     */
    private function handleLanguageGroup(SimpleXMLElement $languageGroup, editor_Models_Terminology_TbxObjects_TermEntry $parentEntry): ?editor_Models_Terminology_TbxObjects_Langset
    {
        $language = $this->getNormalizedLanguage($languageGroup);

        if (empty($this->languages[$language])) {
            $this->unknownLanguages[$language] = $language;

            return null;
        }

        $this->tbxMap[$this::TBX_TIG] = $languageGroup->tig ? 'tig' : 'ntig';
        /** @var editor_Models_Terminology_TbxObjects_Langset $newLangSet */
        $newLangSet = new $this->langsetObject();
        $newLangSet->setParent($parentEntry);
        $newLangSet->collectionId = $this->collection->getId();
        // since we do not save language sets to DB we have to load their guids from the terms, where they are saved
        $newLangSet->langSetGuid = $this->bulkTerm->getExistingLangsetGuid($parentEntry->termEntryTbxId, $language) ?? $this->getGuid();
        $newLangSet->language = $language;
        $newLangSet->languageId = $this->languages[$language];
        $newLangSet->entryId = $parentEntry->id;
        $newLangSet->termEntryGuid = $parentEntry->entryGuid;

        // Collect and set the descrip attributes, and check if there is a definition
        if (isset($languageGroup->descrip)) {
            $descrips = $this->setAttributeTypes($languageGroup->descrip, $newLangSet);
            /* @var editor_Models_Terminology_TbxObjects_Attribute $descrip */
            foreach ($descrips as $descrip) {
                if ($descrip->type == 'definition') {
                    $newLangSet->definition = $descrip->value;

                    break;
                }
            }
        }

        $this->setDiscriptGrp($languageGroup, $newLangSet, 'langSet');

        if (isset($languageGroup->transacGrp)) {
            foreach ($languageGroup->transacGrp as $transacGrp) {
                $newLangSet->transacGrp = $this->setTransacAttributes($transacGrp, false, 'langSet', $newLangSet);
            }
        }

        if (isset($languageGroup->note)) {
            $this->setAttributeTypes($languageGroup->note, $newLangSet);
        }

        return $newLangSet;
    }

    /**
     * Iterate over the term structure and call handler for each element.
     * There will be parsed all child elements and returns term as object.
     * Elements - term, termNote, transacGrp, transacNote, admin
     * @throws ZfExtended_ErrorCodeException
     */
    private function handleTermGroup(SimpleXMLElement $tigElement, editor_Models_Terminology_TbxObjects_Langset $parsedLangSet)
    {
        /** @var SimpleXMLElement $tig */
        if ($tigElement->termGrp) {
            $tig = $tigElement->termGrp;
        } else {
            $tig = $tigElement;
        }

        $term = (string) $tig->term;

        //if the term is empty, there is nothing to be processed - although if there are attributes.
        if (editor_Models_Terminology_Models_TermModel::isEmptyTerm($term)) {
            return;
        }

        $cls = $this->bulkTerm->getNewImportObject();
        /** @var editor_Models_Terminology_TbxObjects_Term $newTerm */
        $newTerm = new $cls();
        $newTerm->setParent($parsedLangSet);
        $newTerm->updatedBy = $this->user->getId();
        $newTerm->updatedAt = NOW_ISO;
        $newTerm->collectionId = $this->collection->getId();
        $newTerm->termEntryId = $newTerm->parentEntry->id;
        $newTerm->language = $parsedLangSet->language;
        $newTerm->languageId = $parsedLangSet->languageId;
        $newTerm->termEntryGuid = $newTerm->parentEntry->entryGuid;
        $newTerm->termEntryTbxId = $newTerm->parentEntry->termEntryTbxId;
        // Remove all leading and trailing whitespaces and remove the non-breaking spaces
        $newTerm->term = ZfExtended_Utils::cleanString($term);
        $newTerm->termTbxId = $this->getIdOrGenerate($tig->term);
        $newTerm->langSetGuid = $parsedLangSet->langSetGuid;

        $newTerm->descrip = $parsedLangSet->descrip;
        $newTerm->descripTarget = $parsedLangSet->descripTarget;
        $newTerm->descripType = $parsedLangSet->descripType;

        //if there is a definition on languageLevel use that, if not check if there is one on entry level
        if (strtolower($parsedLangSet->descripType) === $newTerm::TERM_DEFINITION) {
            $newTerm->definition = $parsedLangSet->descrip;
        } elseif (! is_null($newTerm->definition) && ! is_null($newTerm->parentEntry->definition)) {
            $newTerm->definition = $newTerm->parentEntry->definition;
        }

        $this->addProcessStatusNodeIfNotExists($tig);
        $newTerm->termNote = $this->setAttributeTypes($tig->termNote, $newTerm);

        if (isset($tig->note)) {
            $this->setAttributeTypes($tig->note, $newTerm);
        }
        if (isset($tig->descrip)) {
            $this->setAttributeTypes($tig->descrip, $newTerm);
        }
        if (isset($tig->admin)) {
            $this->setAttributeTypes($tig->admin, $newTerm);
        }
        if (isset($tig->xref)) {
            $this->setAttributeTypes($tig->xref, $newTerm);
        }
        if (isset($tig->ref)) {
            $this->setAttributeTypes($tig->ref, $newTerm);
        }
        if (isset($tig->transacGrp)) {
            foreach ($tig->transacGrp as $transac) {
                $this->setTransacAttributes($transac, false, 'tig', $newTerm);
            }
        }
        $this->setDiscriptGrp($tig, $newTerm, 'tig');

        $admnStatFound = false;
        $this->termNoteStatus->setTermStatusOnImport($newTerm, $this->bulkAttribute, $admnStatFound);
        $newTerm->processStatus = $this->getProcessStatus($newTerm->termNote);

        //if no termNote with administrativeStatus was found, we create one
        if (! $admnStatFound) {
            $newStatus = $this->termNoteStatus->getAdmnStatusFromTermStatus($newTerm->status);
            $newTerm->termNote[] = $this->createAndAddAttribute($newTerm, 'termNote', 'administrativeStatus', '', $newStatus);
        }

        //check if termNote administrativeStatus is set, if not add it to $newTerm->termNote and bulk with $newTerm->status value

        if ($newTerm->processStatus === '') {
            $newTerm->processStatus = $newTerm::TERM_STANDARD_PROCESS_STATUS;
        }

        $this->bulkTerm->add($newTerm);
    }

    /**
     * Check whether <termNote type="processStatus">-node exists within given <tig>-node,
     * and if no - add it, so that 'processStatus' attribute will be added in a way
     * like it would be defined by tbx-file
     */
    public function addProcessStatusNodeIfNotExists($tig)
    {
        // If '<termNote type="processStatus">'-node already exists - return
        if (isset($tig->termNote)) {
            foreach ($tig->termNote as $termNote) {
                if ((string) $termNote->attributes()->{'type'} == 'processStatus') {
                    return;
                }
            }
        }

        // Get default processStatus
        $defaultProcessStatus = $this->config->runtimeOptions->tbx->defaultTermAttributeStatus;

        //$value = $defaultProcessStatus ?: editor_Models_Terminology_TbxObjects_Term::TERM_STANDARD_PROCESS_STATUS;
        //file_put_contents('log.txt', print_r($this->config->runtimeOptions->tbx, true), FILE_APPEND);

        // Create '<termNote type="processStatus">finalized</termNote>'-node
        $tig->addChild('termNote', $defaultProcessStatus)->addAttribute('type', 'processStatus');
    }

    /**
     * Prepare all Elements for Attribute table
     * Elements - termNote, descrip, transacNote, admin, note
     * @param editor_Models_Terminology_TbxObjects_Abstract $parentNode parent main TBX node
     * @throws ZfExtended_ErrorCodeException
     * @throws editor_Models_Terminology_Import_Exception
     */
    private function setAttributeTypes(SimpleXMLElement $element, editor_Models_Terminology_TbxObjects_Abstract $parentNode, bool $isDescripGrp = false): array
    {
        $attributes = [];
        /** @var SimpleXMLElement $value */
        foreach ($element as $elementName => $value) {
            // Get type
            $type = (string) $value->attributes()->{'type'};

            // If no 'type'-attr on node and node-name is not 'note'
            // Note: '0'-value of $type is also considered as empty
            // because it's a falsy value and proceeding with that may
            // lead to problems with the other parts of the application
            if (! $type && $elementName !== 'note') {
                // Skip that
                continue;
            }

            // Get xml tag name
            $target = (string) $value->attributes()->target;

            // If elementName is xref and target-attr is a local path
            if ($elementName === 'xref' && $type === 'xGraphic' && $this->targetIsLocalPath($target)) {
                // Spoof $elementName and $type
                $elementName = 'descrip';
                $type = 'figure';

                // Import from xGraphic or collect missing file's path
                if ($this->binaryImport->importSingleImage($target) === false) {
                    // Prepare log data
                    $this->binaryImport->missingImages['files'][$target] = true;
                }

                // Setup flag indicating that we spoofed xGraphic with figure
                $figureIsSpoof = true;

                // Else
            } else {
                // Setup flag indicating that we haven't spoofed xGraphic with figure
                $figureIsSpoof = false;
            }

            // Create attribute
            $attributes[] = $this->createAndAddAttribute(
                $parentNode,
                $elementName,
                $type,
                $target,
                (string) $value,
                $isDescripGrp
            );

            // If it's a figure-attribute
            if ($elementName === 'descrip' && $type === 'figure' && ! $figureIsSpoof) {
                // If there is no file behind
                if (! isset($this->binaryImport->figureExists[$target])) {
                    // Prepare log data
                    $this->binaryImport->missingImages['definitions'][$target] = true;

                    // Increment missing images counter
                    $this->binaryImport->imageQty['missing']++;
                }
            }
        }

        return $attributes;
    }

    /**
     * Creates a attribute TbxObject out of the given values, add its to the bulk list and returns it
     * @throws ZfExtended_ErrorCodeException
     * @throws editor_Models_Terminology_Import_Exception
     */
    protected function createAndAddAttribute(editor_Models_Terminology_TbxObjects_Abstract $parentNode, string $elementName, string $type, string $target, string $value, bool $isDescripGrp = false): editor_Models_Terminology_TbxObjects_Attribute
    {
        /** @var editor_Models_Terminology_TbxObjects_Attribute $attribute */
        $attribute = $this->bulkAttribute->getNewImportObject();
        $attribute->setParent($parentNode);
        $attribute->collectionId = $this->collection->getId();
        $attribute->termEntryId = $attribute->parentEntry->id;
        $attribute->language = $attribute->parentLangset->language ?? null;
        // termId is updated after inserting all the terms!
        $attribute->termTbxId = $attribute->parentTerm->termTbxId ?? null;
        $attribute->type = $type;
        $attribute->value = $value;
        $attribute->target = $target;
        $attribute->createdBy = $this->user->getId();
        $attribute->createdAt = NOW_ISO; //is saved only on INSERT / on UPDATE it is not send to server, otherwise the hash would change
        $attribute->updatedBy = $this->user->getId();

        $attribute->termEntryGuid = $attribute->parentEntry->entryGuid;
        $attribute->langSetGuid = $attribute->parentLangset->langSetGuid ?? null;
        $attribute->termGuid = $attribute->parentTerm->guid ?? null;

        $attribute->elementName = $elementName;
        $attribute->attrLang = $attribute->parentLangset->language ?? '';

        // check if the dataType exist for the element
        $labelId = $this->dataType->getForAttribute($attribute);

        // If elementName was spoofed
        if (isset($attribute->wasElementName)) {
            // Prepare log msg
            $msg = 'TBX Import: Attribute has known type, but has elementName unexpected for that type so changed to expected one';

            // Do log
            $this->log($msg, 'E1446', [
                'type' => $attribute->type,
                'wasElementName' => $attribute->wasElementName,
                'elementName' => $attribute->elementName,
            ], 'warn');
        }

        // If level is unexpected
        if (isset($attribute->unexpectedLevel)) {
            // Update datatype's expected levels list
            $this->attributeDataTypeModel->load($labelId);
            $unexpected = $attribute->getLevel();
            $wasExpected = $this->attributeDataTypeModel->getLevel();
            $this->attributeDataTypeModel->setLevel($nowExpected = "$wasExpected,$unexpected");
            $this->attributeDataTypeModel->save();

            // Prepare log msg
            $msg = 'TBX Import: Attribute has known type, but is at level unexpected for that type so that level is added to the list of expected';

            // Do log
            $this->log($msg, 'E1463', [
                'type' => $attribute->type,
                'unexpectedLevel' => $unexpected,
                'wasExpectedLevels' => $wasExpected,
                'nowExpectedLevels' => $nowExpected,
            ], 'warn');
        }

        if (empty($labelId)) {
            // the dataType does not exist -> create it
            $this->attributeDataTypeModel->loadOrCreate($attribute->elementName, $attribute->type, [$attribute->getLevel()]);

            // Maintain collection<=>datatype mappings data
            ZfExtended_Factory
                ::get(CollectionAttributeDataType::class)
                    ->onCustomDataTypeInsert($this->attributeDataTypeModel->getId(), $this->collection->getId());

            // reload all dataTypes
            $this->dataType->loadData(true);

            $labelId = $this->dataType->getForAttribute($attribute);
        }

        $attribute->dataTypeId = (int) $labelId;

        $attribute->isDescripGrp = (int) $isDescripGrp;

        // add the attribute to the global attributes collection
        $this->bulkAttribute->add($attribute);

        // If target was cleared due to unsupported for type
        if (isset($attribute->wasTarget)) {
            // Prepare log msg
            $msg = 'TBX Import: Attribute target was emptied as unsupported for that attribute type';

            // Do log
            $this->log($msg, 'E1472', [
                'type' => $attribute->type,
                'wasTarget' => $attribute->wasTarget,
            ], 'warn');
        }

        return $attribute;
    }

    /**
     * @param editor_Models_Terminology_TbxObjects_Abstract $parentNode parent main TBX node
     */
    private function setTransacAttributes(SimpleXMLElement $transacGrp, bool $isDescripGrp, string $elementName, editor_Models_Terminology_TbxObjects_Abstract $parentNode): array
    {
        $parsedTransacGrp = [];
        $cls = $this->bulkTransacGrp->getNewImportObject();
        /** @var editor_Models_Terminology_TbxObjects_TransacGrp $transacGrpObject */
        $transacGrpObject = new $cls();
        $transacGrpObject->setParent($parentNode);
        $transacGrpObject->collectionId = $this->collection->getId();
        $transacGrpObject->termEntryId = $transacGrpObject->parentEntry->id;
        // termId is updated after inserting all the terms!
        $transacGrpObject->termTbxId = $transacGrpObject->parentTerm->termTbxId ?? null;
        $transacGrpObject->termGuid = $transacGrpObject->parentTerm->guid ?? null;
        $transacGrpObject->termEntryGuid = $transacGrpObject->parentEntry->entryGuid;
        $transacGrpObject->langSetGuid = $transacGrpObject->parentLangset->langSetGuid ?? null;
        $transacGrpObject->elementName = $elementName;

        // for term entry transac group there is no language
        $transacGrpObject->language = $transacGrpObject->parentLangset->language ?? null;

        // Define $replicateOnTerm flag, indicating whether or not
        // current transacGrp-data should be replicated to a term-level
        $replicateToTerm = false;

        if (isset($transacGrp->transac)) {
            $transacGrpObject->transac = $this->getTransacMappingIfExists((string) $transacGrp->transac);

            // If $elementName is 'tig'
            if ($elementName == 'tig') {
                if (in_array($transacGrpObject->transac, ['origination', 'modification'])) {
                    $replicateToTerm = $transacGrpObject->transac;
                }
            }
        }
        if (isset($transacGrp->date)) {
            $transacGrpObject->date = ZfExtended_Utils::toMysqlDateTime((string) $transacGrp->date);

            // Replicate origination/modification date into term tbx(Created|Updated)At prop
            if ($replicateToTerm) {
                $parentNode->{$replicateToTerm == 'modification' ? 'tbxUpdatedAt' : 'tbxCreatedAt'}
                    = $transacGrpObject->date;
            }
        }
        if (isset($transacGrp->transacNote)) {
            $transacGrpObject->transacType = $this->getTransacMappingIfExists((string) $transacGrp->transacNote->attributes()->{'type'});
            $transacGrpObject->target = (string) $transacGrp->transacNote->attributes()->{'target'};
            $transacGrpObject->transacNote = trim((string) $transacGrp->transacNote);

            // Replicate origination/modification responsible person into term tbx(Created|Updated)By prop
            if ($replicateToTerm && $transacGrpObject->transacType == 'responsibility') {
                $parentNode->{$replicateToTerm == 'modification' ? 'tbxUpdatedBy' : 'tbxCreatedBy'}
                    = $this->getTransacPersonIdByName($transacGrpObject->transacNote);
            }
        }

        $transacGrpObject->isDescripGrp = (int) $isDescripGrp;
        $parsedTransacGrp[] = $transacGrpObject;
        $this->bulkTransacGrp->add($transacGrpObject);

        return $parsedTransacGrp;
    }

    /***
     * Handle discriptGrp element. This can exist on entry, language and term level.
     * Used instead of descrip to document a definition and its source. Contains: one descrip and one admin element.
     * If the source of the definition is not required or available, use only a descrip.
     * @param SimpleXMLElement $parent
     * @param editor_Models_Terminology_TbxObjects_Abstract $tbxObject
     * @param string $elementName
     * @throws ZfExtended_ErrorCodeException
     */
    private function setDiscriptGrp(SimpleXMLElement $parent, editor_Models_Terminology_TbxObjects_Abstract $tbxObject, string $elementName)
    {
        // INFO: In TBX-Basic, the <descripGrp> element is used only to associate a source to a definition or to
        //a context. The following child elements are not supported: <descripNote>, <admin>,<adminGrp>, <note>, <ref>, and <xref>.
        foreach ($parent->descripGrp as $descripGrp) {
            $this->setAttributeTypes($descripGrp->descrip, $tbxObject, true);

            if ($tbxObject instanceof editor_Models_Terminology_TbxObjects_Term || $tbxObject instanceof editor_Models_Terminology_TbxObjects_Langset) {
                $tbxObject->descrip = (string) $descripGrp->descrip;
                $tbxObject->descripTarget = (string) $descripGrp->descrip->attributes()->{'target'};
                $tbxObject->descripType = (string) $descripGrp->descrip->attributes()->{'type'};
            }

            $setEntryDefinition = $tbxObject instanceof editor_Models_Terminology_TbxObjects_TermEntry && is_null($tbxObject->definition);
            $isDefinition = (string) $descripGrp->descrip->attributes()->{'type'} === 'definition';
            if ($setEntryDefinition && $isDefinition) {
                $tbxObject->definition = (string) $descripGrp->descrip;
            }

            $this->setAttributeTypes($descripGrp->admin, $tbxObject, true);

            if (isset($descripGrp->transacGrp)) {
                foreach ($descripGrp->transacGrp as $transac) {
                    $this->setTransacAttributes($transac, true, $elementName, $tbxObject);
                }
            }

            // INFO: if note appears on <descripGrp> level, import the note as normal attribute.
            // This kind of note is not supported by tbx basic
            if (isset($descripGrp->note)) {
                $this->setAttributeTypes($descripGrp->note, $tbxObject, true);
            }
        }
    }

    /**
     * Get actual language from xmlElement.
     * We need to check if attribute is defined as lang or xml:lang,
     * if xml:lang we need to add parameter true, to define xml is prefix for given attribute.
     */
    private function getActualLanguageAttribute(SimpleXMLElement $language): string
    {
        if (empty($language)) {
            return '';
        }

        $type = (string) $language->attributes()->{'lang'} ? 'lang' : 'xml';

        if ($type === 'xml') {
            $langSetLanguage = (string) $language->attributes($type, true)->{'lang'};
        } else {
            $langSetLanguage = (string) $language->attributes()->{$type};
        }

        return $langSetLanguage;
    }

    private function getNormalizedLanguage(SimpleXMLElement $languageGroup): string
    {
        return strtolower(str_replace('_', '-', $this->getActualLanguageAttribute($languageGroup)));
    }

    /**
     * import the resp persons into the database
     */
    private function importOtherRefObjects(SimpleXMLElement $refObjectList, string $listType)
    {
        foreach ($refObjectList as $refObject) {
            $data = [];
            $key = (string) $refObject->attributes()->{'id'};
            foreach ($refObject->item as $item) {
                $data[(string) $item->attributes()->{'type'}] = (string) $item;
            }
            $this->bulkRefObject->createOrUpdateRefObject($listType, $key, $data);
        }
    }

    /**
     * log all unknown languages in DB table ZF_errorlog
     */
    private function logUnknownLanguages()
    {
        if (empty($this->unknownLanguages)) {
            return;
        }
        foreach ($this->unknownLanguages as $key => $language) {
            $this->log('TBX Import: Unable to import terms due invalid Rfc5646 language code "{code}"', 'E1361', [
                'code' => $key,
            ], 'warn');
        }
    }

    /**
     * - $this->getIdOrGenerate($elementName, 'term')
     * this will return 'id' attribute as string from given element,
     * if element don't have 'id' attribute we will generate one.
     */
    private function getIdOrGenerate(SimpleXMLElement $xmlElement): string
    {
        if ($xmlElement->attributes()->{'id'}) {
            return (string) $xmlElement->attributes()->{'id'};
        }

        return ZfExtended_Utils::uuid();
    }

    /**
     * returns the translate5 termNote processStatus to the one given in TBX
     */
    protected function getProcessStatus(array $termNotes): string
    {
        $processStatus = '';
        /** @var editor_Models_Terminology_TbxObjects_Attribute $termNote */
        foreach ($termNotes as $termNote) {
            if ($termNote->type === 'processStatus') {
                $processStatus = $termNote->value;
            }
        }

        return $processStatus;
    }

    private function getGuid(): string
    {
        return ZfExtended_Utils::uuid();
    }

    private function log($logMessage, $code = 'E1028', array $extra = [], $level = 'info')
    {
        if (array_key_exists($code, $this->eventQty)) {
            $this->eventQty[$code]['qty']++;
            $this->eventQty[$code]['msg'] = $logMessage;
            if ($code === 'E1446') {
                $val = "Type => {$extra['type']}; Was elementName => {$extra['wasElementName']}; Now elementName => {$extra['elementName']}";
                $this->eventQty[$code]['cases'][$val] = $val;
            }
        } else {
            $extra['languageResource'] = $this->collection;
            if (! empty($this->task)) {
                $extra['task'] = $this->task;
            }
            $this->logger->$level($code, $logMessage, $extra);
        }
    }

    /**
     * reset bulk operations to free memory
     */
    protected function bulkFreeMemory()
    {
        $this->bulkTerm->freeMemory();
        $this->bulkTransacGrp->freeMemory();
        $this->bulkAttribute->freeMemory();
        $this->bulkTermEntry->freeMemory();
    }

    /***
     * Update and save the counted collection import totals as specific data attribute
     */
    protected function setCollectionImportStatistic()
    {
        $this->collection->updateStats($this->collection->getId());
    }

    /**
     * Get id of terms_transacgrp_person-record by $name from a lazy-loaded dictionary,
     * If no such record - it will be created in db, added into dictionary, and it's id returned
     *
     * @return int
     */
    protected function getTransacPersonIdByName($name)
    {
        // Get current collectionId
        $collectionId = $this->collection->getId();

        // If person dictionary was not loaded so far
        // or there is no person with given $name in a dictionary yet
        // - load persons model
        if ($this->transacgrpPersons === null || ! isset($this->transacgrpPersons[$name])) {
            $m = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpPersonModel');
        }

        // If person dictionary was not loaded so far - do load
        if ($this->transacgrpPersons === null) {
            foreach ($m->loadByCollectionIds([$collectionId]) as $person) {
                $this->transacgrpPersons[$person['name']] = $person['id'];
            }
        }

        // If there is no person with given $name in a dictionary yet - add it
        if (! isset($this->transacgrpPersons[$name])) {
            $m->init([
                'collectionId' => $collectionId,
                'name' => $name,
            ]);
            $m->save();
            $this->transacgrpPersons[$name] = $m->getId();
        }

        // Return person id
        return $this->transacgrpPersons[$name];
    }

    /**
     * Get mapping for a $value, according to tbx-standard.
     * If no mapping exists - $value is returned
     *
     * @return string
     */
    protected function getTransacMappingIfExists($value)
    {
        return $this->transacMap[$value] ?? $value;
    }

    /**
     * Check whether given $target arg is a string that looks like a local path to a file
     */
    protected function targetIsLocalPath(string $target): bool
    {
        // If $target is empty - return false
        if (! $target) {
            return false;
        }

        // If $target contains double dots, double shashes, or backslash - return false
        if (preg_match('~(\.\./|/\.\.|//|' . preg_quote('\\', '~') . ')~', $target)) {
            return false;
        }

        // If $target contains any characters that are not in list - return false
        //if (preg_match('~[^a-z0-9_\-/.äöüß ]~i', $target)) {
        if (preg_match('~[^\p{L}\p{N}\s\p{Pd}_./\~!@#$%^&(),+\[\]{};\'`]~iu', $target)) {
            return false;
        }

        // If $target starts with / - return false
        if (preg_match('~^/~', $target)) {
            return false;
        }

        // Get extension
        $ext = pathinfo($target, PATHINFO_EXTENSION);

        // If no extension or it's longer than 4 chars - return false
        if (! $ext || strlen($ext) > 4) {
            return false;
        }

        // Hm, seems like $target looks like local path
        return true;
    }
}
