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
class editor_Models_Terminology_Import_TbxFileImport
{
    const TBX_TIG = 'tig';
    const TBX_TERM_ENTRY = 'termEntry';
    const TBX_LANGSET = 'langSet';

    /** @var $logger ZfExtended_Logger */
    protected ZfExtended_Logger $logger;

    /** @var Zend_Config */
    protected Zend_Config $config;

    /** @var ZfExtended_Models_User */
    protected ZfExtended_Models_User $user;

    /** @var editor_Models_Terminology_Models_AttributeDataType */
    protected editor_Models_Terminology_Models_AttributeDataType $attributeDataTypeModel;

    /** @var editor_Models_Terminology_TbxObjects_DataType  */
    protected editor_Models_Terminology_TbxObjects_DataType  $dataType;

    /**
     * $tbxMap = segment names for different TBX standards
     * $this->tbxMap['tig'] = 'tig'; - or if 'ntig' element - $this->tbxMap['tig'] = 'ntig';
     * each possible segment for TBX standard must be defined and will be merged in translate5 standard!
     * @var array
     */
    protected array $tbxMap;

    /**
     * Array to map any known values of transac and transacType to tbx-standard values,
     * for converting non-standard values into standard prior saving into database
     *
     * @var array
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

    /**
     * current term collection
     * @var editor_Models_TermCollection_TermCollection
     */
    protected editor_Models_TermCollection_TermCollection $collection;

    /**
     * All available languages in Translate5
     * $languages['de_DE' => 4]
     * @var array
     */
    protected array $languages;

    /**
     * Collected unknown languages
     * @var array
     */
    protected array $unknownLanguages = [];

    /** @var editor_Models_Terminology_TbxObjects_Langset|mixed  */
    protected editor_Models_Terminology_TbxObjects_Langset $langsetObject;

    /**
     * @var editor_Models_Terminology_BulkOperation_Attribute
     */
    protected editor_Models_Terminology_BulkOperation_Attribute $bulkAttribute;

    /**
     * @var editor_Models_Terminology_BulkOperation_TransacGrp
     */
    protected editor_Models_Terminology_BulkOperation_TransacGrp $bulkTransacGrp;

    /**
     * @var editor_Models_Terminology_BulkOperation_Term
     */
    protected editor_Models_Terminology_BulkOperation_Term $bulkTerm;

    /**
     * @var editor_Models_Terminology_BulkOperation_RefObject
     */
    protected editor_Models_Terminology_BulkOperation_RefObject $bulkRefObject;

    /**
     * In this class is the whole merge logic
     * @var editor_Models_Terminology_BulkOperation_TermEntry
     */
    protected editor_Models_Terminology_BulkOperation_TermEntry $bulkTermEntry;

    /**
     * contains the whole term note status mapping
     * @var editor_Models_Terminology_TermNoteStatus
     */
    protected editor_Models_Terminology_TermNoteStatus $termNoteStatus;

    /**
     * @var ZfExtended_EventManager
     */
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
    public function __construct() {
        if(!defined('LIBXML_VERSION') || LIBXML_VERSION < '20620') {
            //Mindestversion siehe http://www.php.net/manual/de/xmlreader.readstring.php
            throw new Zend_Exception('LIBXML_VERSION must be at least 2.6.20 (or as integer 20620).');
        }
        $this->config = Zend_Registry::get('config');
        $this->logger = Zend_Registry::get('logger');
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(get_class($this)));

        $this->attributeDataTypeModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeDataType');

        $this->langsetObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_Langset');

        $this->dataType = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_DataType');

        $this->bulkTermEntry = ZfExtended_Factory::get('editor_Models_Terminology_BulkOperation_TermEntry');
        $this->bulkAttribute = new editor_Models_Terminology_BulkOperation_Attribute();
        $this->bulkTransacGrp = new editor_Models_Terminology_BulkOperation_TransacGrp();
        $this->bulkTerm = new editor_Models_Terminology_BulkOperation_Term();

        $this->termNoteStatus = new editor_Models_Terminology_TermNoteStatus();
    }

    /**
     * Import given TBX file and prepare Import arrays, if file can not be opened throw Zend_Exception.
     * returns the count of available term entries in the loaded file
     * @param string $tbxFilePath
     * @param editor_Models_TermCollection_TermCollection $collection
     * @param ZfExtended_Models_User $user
     * @param bool $mergeTerms
     * @throws Zend_Exception
     * @throws Exception
     * @return int
     */
    public function importXmlFile(string $tbxFilePath, editor_Models_TermCollection_TermCollection $collection, ZfExtended_Models_User $user, bool $mergeTerms): int
    {
        $this->collection = $collection;
        $this->mergeTerms = $mergeTerms;
        $this->prepareImportArrays($user);

        $xmlReader = (new class() extends XMLReader {
            public function reopen(string $tbxFilePath) {
                $this->close();
                $this->open($tbxFilePath);
            }
        });

        if(!$xmlReader->open($tbxFilePath)) {
            throw new Zend_Exception('TBX file can not be opened.');
        }

        $totalCount = $this->countTermEntries($xmlReader);

        $xmlReader->reopen($tbxFilePath); //reset pointer to beginning
        $this->processTermEntries($xmlReader, $totalCount);

        $xmlReader->reopen($tbxFilePath); //reset pointer to beginning
        $this->processRefObjects($xmlReader);

        $this->logUnknownLanguages();

        $dataTypeAssoc = ZfExtended_Factory::get('editor_Models_Terminology_Models_CollectionAttributeDataType');
        /* @var $dataTypeAssoc editor_Models_Terminology_Models_CollectionAttributeDataType */
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

        $data = [
            'termEntries' => $this->bulkTermEntry->getStatistics(),
            'terms' => $this->bulkTerm->getStatistics(),
            'attributes' => $this->bulkAttribute->getStatistics(),
            'transacGroups' => $this->bulkTransacGrp->getStatistics(),
            'refObjects' => $this->bulkRefObject->getStatistics(),
            'collection' => $this->collection->getName(),
            'maxMemUsed in MB' => round(memory_get_peak_usage() / 2**20),
        ];
        $this->log('Imported TBX data into collection {collection}', 'E1028', $data);

        $unknownStates = $this->termNoteStatus->getUnknownStates();
        if(!empty($unknownStates)) {
            $this->log('TBX Import: The TBX contains terms with unknown administrative / normative states. See details for a list of states.', 'E1360', ['unknown' => $unknownStates], 'warn');
        }
        return $totalCount;
    }

    /**
     * Prepare init array variables for merge procedure and check isset function.
     * @param ZfExtended_Models_User $user
     * @throws Zend_Db_Statement_Exception
     */
    private function prepareImportArrays(ZfExtended_Models_User $user)
    {
$memLog = function($msg) {
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
     * @param XMLReader $xmlReader
     * @return int
     */
    private function countTermEntries(XMLReader $xmlReader): int {
        // find first termEntry and count them from there
        while ($xmlReader->read() && $xmlReader->name !== $this->tbxMap[$this::TBX_TERM_ENTRY]);
        $totalCount = 0;
        while ($xmlReader->name === $this->tbxMap[$this::TBX_TERM_ENTRY])
        {
            $totalCount++;
            $xmlReader->next($this->tbxMap[$this::TBX_TERM_ENTRY]);
        }
        return $totalCount;
    }

    /**
     * processes the term entries found in the XML
     * @param XMLReader $xmlReader
     * @param int $totalCount
     * @throws Exception
     */
    protected function processTermEntries(XMLReader $xmlReader, int $totalCount) {
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
                $newProgress = min(100, round(($importCount/$totalCount)*100));
                if($newProgress > $progress) {
                    $progress = $newProgress;
                    $this->events->trigger('afterTermEntrySave', $this, ['progress' => $progress / 100]); //we store the value as value between 0 and 1
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
    protected function processRefObjects(XMLReader $xmlReader) {
        $this->bulkRefObject = new editor_Models_Terminology_BulkOperation_RefObject();
        $this->bulkRefObject->loadExisting($this->collection->getId());
        while ($xmlReader->read() && $xmlReader->name !== 'refObjectList');
        while ($xmlReader->name === 'refObjectList') {
            $listType = $xmlReader->getAttribute('type');
            $node = new SimpleXMLElement($xmlReader->readOuterXML());
            if($listType == 'binaryData') {
                /** @var $binImport editor_Models_Terminology_Import_TbxBinaryDataImport */
                $binImport = ZfExtended_Factory::get('editor_Models_Terminology_Import_TbxBinaryDataImport');
                $binImport->import($this->collection->getId(), $node);
            }
            else {
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
     * @param SimpleXMLElement $termEntry
     * @return editor_Models_Terminology_TbxObjects_TermEntry
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
            foreach($descrips as $descrip) {
                if($descrip->type == 'definition') {
                    $newEntry->definition = $descrip->value;
                    break;
                }
            }
        }

        $this->setDiscriptGrp($termEntry,$newEntry,'termEntry');

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
     * @param SimpleXMLElement $languageGroup
     * @param editor_Models_Terminology_TbxObjects_TermEntry $parentEntry
     * @return editor_Models_Terminology_TbxObjects_Langset|null
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
        $newLangSet = new $this->langsetObject;
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

        $this->setDiscriptGrp($languageGroup,$newLangSet,'langSet');

        if (isset($languageGroup->note)) {
            $this->setAttributeTypes($languageGroup->note, $newLangSet);
        }

        return $newLangSet;
    }

    /**
     * Iterate over the term structure and call handler for each element.
     * There will be parsed all child elements and returns term as object.
     * Elements - term, termNote, transacGrp, transacNote, admin
     * @param SimpleXMLElement $tigElement
     * @param editor_Models_Terminology_TbxObjects_Langset $parsedLangSet
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
        $newTerm->term = (string)$tig->term;
        $newTerm->termTbxId = $this->getIdOrGenerate($tig->term);
        $newTerm->langSetGuid = $parsedLangSet->langSetGuid;

        $newTerm->descrip = $parsedLangSet->descrip;
        $newTerm->descripTarget = $parsedLangSet->descripTarget;
        $newTerm->descripType = $parsedLangSet->descripType;

        //if there is a definition on languageLevel use that, if not check if there is one on entry level
        if (strtolower($parsedLangSet->descripType) === $newTerm::TERM_DEFINITION) {
            $newTerm->definition = $parsedLangSet->descrip;
        }
        elseif(!is_null($newTerm->definition) && !is_null($newTerm->parentEntry->definition)) {
            $newTerm->definition = $newTerm->parentEntry->definition;
        }

        $hasTermNote = isset($tig->termNote);
        $this->addProcessStatusNodeIfNotExists($tig);
        $newTerm->termNote = $this->setAttributeTypes($tig->termNote, $newTerm);

        $admnStatFound = false;
        if ($hasTermNote) {
            $newTerm->status = $this->termNoteStatus->fromTermNotesOnImport($newTerm->termNote, $admnStatFound);
            $newTerm->processStatus = $this->getProcessStatus($newTerm->termNote);
        } else {
            $newTerm->status = $this->termNoteStatus->getDefaultTermStatus();
            $newTerm->processStatus = $newTerm::TERM_STANDARD_PROCESS_STATUS;
        }

        //if no termNote with administrativeStatus was found, we create one
        if(!$admnStatFound) {
            $newStatus = $this->termNoteStatus->getAdmnStatusFromTermStatus($newTerm->status);
            $newTerm->termNote[] = $this->createAndAddAttribute($newTerm, 'termNote', 'administrativeStatus', '', $newStatus);
        }

        //check if termNote administrativeStatus is set, if not add it to $newTerm->termNote and bulk with $newTerm->status value

        if ($newTerm->processStatus === '') {
            $newTerm->processStatus = $newTerm::TERM_STANDARD_PROCESS_STATUS;
        }

        if (isset($tig->note)) {
            $this->setAttributeTypes($tig->note, $newTerm);
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
            foreach ($tig->transacGrp as $transac){
                $this->setTransacAttributes($transac, false, 'tig', $newTerm);
            }
        }
        $this->setDiscriptGrp($tig,$newTerm,'tig');

        $this->bulkTerm->add($newTerm);
    }

    /**
     * Check whether <termNote type="processStatus">-node exists within given <tig>-node,
     * and if no - add it, so that 'processStatus' attribute will be added in a way
     * like it would be defined by tbx-file
     *
     * @param $tig
     */
    public function addProcessStatusNodeIfNotExists($tig) {

        // If '<termNote type="processStatus">'-node already exists - return
        if (isset($tig->termNote))
            foreach ($tig->termNote as $termNote)
                if ((string) $termNote->attributes()->{'type'} == 'processStatus')
                    return;

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
     * @param SimpleXMLElement $element
     * @param editor_Models_Terminology_TbxObjects_Abstract $parentNode parent main TBX node
     * @param bool $isDescripGrp
     * @return array
     * @throws ZfExtended_ErrorCodeException
     * @throws editor_Models_Terminology_Import_Exception
     */
    private function setAttributeTypes(SimpleXMLElement $element, editor_Models_Terminology_TbxObjects_Abstract $parentNode, bool $isDescripGrp = false): array
    {
        $attributes = [];
        /** @var SimpleXMLElement $value */
        foreach ($element as $key => $value) {
            $attributes[] = $this->createAndAddAttribute($parentNode, $key, (string)$value->attributes()->{'type'}, (string)$value->attributes()->{'target'}, (string) $value, $isDescripGrp);
        }

        return $attributes;
    }

    /**
     * Creates a attribute TbxObject out of the given values, add its to the bulk list and returns it
     * @param editor_Models_Terminology_TbxObjects_Abstract $parentNode
     * @param string $elementName
     * @param string $type
     * @param string $target
     * @param string $value
     * @param bool $isDescripGrp
     * @return editor_Models_Terminology_TbxObjects_Attribute
     * @throws ZfExtended_ErrorCodeException
     * @throws editor_Models_Terminology_Import_Exception
     */
    protected function createAndAddAttribute(editor_Models_Terminology_TbxObjects_Abstract $parentNode, string $elementName, string $type, string $target, string $value, bool $isDescripGrp = false): editor_Models_Terminology_TbxObjects_Attribute {
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
        if (empty($labelId)) {
            // the dataType does not exist -> create it
            $this->attributeDataTypeModel->loadOrCreate($attribute->elementName, $attribute->type, [$attribute->getLevel()]);

            // reload all dataTypes
            $this->dataType->loadData(true);

            $labelId = $this->dataType->getForAttribute($attribute);
        }

        $attribute->dataTypeId = (int)$labelId;

        $attribute->isDescripGrp = (int)$isDescripGrp;


        // add the attribute to the global attributes collection
        $this->bulkAttribute->add($attribute);

        return $attribute;
    }

    /**
     * @param SimpleXMLElement $transacGrp
     * @param bool $isDescripGrp
     * @param string $elementName
     * @param editor_Models_Terminology_TbxObjects_Abstract $parentNode parent main TBX node
     * @return array
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
            if ($elementName == 'tig')
                if (in_array($transacGrpObject->transac, ['origination', 'modification']))
                    $replicateToTerm = $transacGrpObject->transac;
        }
        if (isset($transacGrp->date)) {
            $transacGrpObject->date = ZfExtended_Utils::toMysqlDateTime((string)$transacGrp->date);

            // Replicate origination/modification date into term tbx(Created|Updated)At prop
            if ($replicateToTerm)
                $parentNode->{$replicateToTerm == 'modification' ? 'tbxUpdatedAt' : 'tbxCreatedAt'}
                    = $transacGrpObject->date;
        }
        if (isset($transacGrp->transacNote)) {
            $transacGrpObject->transacType = $this->getTransacMappingIfExists((string)$transacGrp->transacNote->attributes()->{'type'});
            $transacGrpObject->target = (string)$transacGrp->transacNote->attributes()->{'target'};
            $transacGrpObject->transacNote = trim((string)$transacGrp->transacNote);

            // Replicate origination/modification responsible person into term tbx(Created|Updated)By prop
            if ($replicateToTerm && $transacGrpObject->transacType == 'responsibility')
                $parentNode->{$replicateToTerm == 'modification' ? 'tbxUpdatedBy' : 'tbxCreatedBy'}
                    = $this->getTransacPersonIdByName($transacGrpObject->transacNote);
        }

        $transacGrpObject->isDescripGrp = (int)$isDescripGrp;
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
    private function setDiscriptGrp(SimpleXMLElement $parent, editor_Models_Terminology_TbxObjects_Abstract $tbxObject, string $elementName){

        // INFO: In TBX-Basic, the <descripGrp> element is used only to associate a source to a definition or to
        //a context. The following child elements are not supported: <descripNote>, <admin>,<adminGrp>, <note>, <ref>, and <xref>.
        foreach ($parent->descripGrp as $descripGrp) {

            $this->setAttributeTypes($descripGrp->descrip, $tbxObject,true);

            if($tbxObject instanceof editor_Models_Terminology_TbxObjects_Term || $tbxObject instanceof editor_Models_Terminology_TbxObjects_Langset) {
                $tbxObject->descrip = (string)$descripGrp->descrip;
                $tbxObject->descripTarget = (string)$descripGrp->descrip->attributes()->{'target'};
                $tbxObject->descripType = (string)$descripGrp->descrip->attributes()->{'type'};
            }

            $setEntryDefinition = $tbxObject instanceof editor_Models_Terminology_TbxObjects_TermEntry && is_null($tbxObject->definition);
            $isDefinition = (string)$descripGrp->descrip->attributes()->{'type'} === 'definition';
            if($setEntryDefinition && $isDefinition) {
                $tbxObject->definition = (string)$descripGrp->descrip;
            }

            $this->setAttributeTypes($descripGrp->admin, $tbxObject,true);

            if (isset($descripGrp->transacGrp)) {
                foreach ($descripGrp->transacGrp as $transac) {
                    $this->setTransacAttributes($transac, true, $elementName, $tbxObject);
                }
            }

            // INFO: if note appears on <descripGrp> level, import the note as normal attribute.
            // This kind of note is not supported by tbx basic
            if (isset($descripGrp->note)) {
                $this->setAttributeTypes($descripGrp->note, $tbxObject,true);
            }
        }
    }

    /**
     * Get actual language from xmlElement.
     * We need to check if attribute is defined as lang or xml:lang,
     * if xml:lang we need to add parameter true, to define xml is prefix for given attribute.
     * @param SimpleXMLElement $language
     * @return string
     */
    private function getActualLanguageAttribute(SimpleXMLElement $language): string
    {
        if (empty($language)) {
            return '';
        }

        $type = (string)$language->attributes()->{'lang'} ? 'lang' : 'xml';

        if ($type === 'xml') {
            $langSetLanguage = (string)$language->attributes($type, true)->{'lang'};
        } else {
            $langSetLanguage = (string)$language->attributes()->{$type};
        }

        return $langSetLanguage;
    }

    /**
     * @param SimpleXMLElement $languageGroup
     * @return string
     */
    private function getNormalizedLanguage(SimpleXMLElement $languageGroup): string
    {
        return strtolower(str_replace('_','-', $this->getActualLanguageAttribute($languageGroup)));
    }

    /**
     * import the resp persons into the database
     * @param SimpleXMLElement $refObjectList
     * @param string $listType
     */
    private function importOtherRefObjects(SimpleXMLElement $refObjectList, string $listType)
    {
        foreach ($refObjectList as $refObject) {
            $data = [];
            $key = (string)$refObject->attributes()->{'id'};
            foreach ($refObject->item as $item) {
                $data[(string)$item->attributes()->{'type'}] = (string)$item;
            }
            $this->bulkRefObject->createOrUpdateRefObject($listType, $key, $data);
        }
    }

    /**
     * log all unknown languages in DB table ZF_errorlog
     */
    private function logUnknownLanguages()
    {
        if(empty($this->unknownLanguages)) {
            return;
        }
        foreach ($this->unknownLanguages as $key => $language) {
            $this->log('TBX Import: Unable to import terms due invalid Rfc5646 language code "{code}"', 'E1361', ['code' => $key], 'warn');
        }
    }

    /**
     * - $this->getIdOrGenerate($elementName, 'term')
     * this will return 'id' attribute as string from given element,
     * if element don't have 'id' attribute we will generate one.
     *
     * @param SimpleXMLElement $xmlElement
     * @return string
     */
    private function getIdOrGenerate(SimpleXMLElement $xmlElement): string
    {
        if ($xmlElement->attributes()->{'id'}) {
            return (string)$xmlElement->attributes()->{'id'};
        }

        return ZfExtended_Utils::uuid();
    }

    /**
     * returns the translate5 termNote processStatus to the one given in TBX
     * @param array $termNotes
     * @return string
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

    /**
     * @return string
     */
    private function getGuid(): string
    {
        return ZfExtended_Utils::uuid();
    }

    private function log($logMessage, $code = 'E1028', array $extra = [], $level = 'info')
    {
        $extra['languageResource'] = $this->collection;
        if (!empty($this->task)) {
            $extra['task'] = $this->task;
        }
        $this->logger->$level($code, $logMessage, $extra);
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
    protected function setCollectionImportStatistic(){
        $this->collection->updateStats($this->collection->getId());
    }

    /**
     * Get id of terms_transacgrp_person-record by $name from a lazy-loaded dictionary,
     * If no such record - it will be created in db, added into dictionary, and it's id returned
     *
     * @param $name
     * @return int
     */
    protected function getTransacPersonIdByName($name) {

        // Get current collectionId
        $collectionId = $this->collection->getId();

        // If person dictionary was not loaded so far
        // or there is no person with given $name in a dictionary yet
        // - load persons model
        if ($this->transacgrpPersons === null || !isset($this->transacgrpPersons[$name]))
            $m = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpPersonModel');

        // If person dictionary was not loaded so far - do load
        if ($this->transacgrpPersons === null)
            foreach ($m->loadByCollectionIds([$collectionId]) as $person)
                $this->transacgrpPersons[$person['name']] = $person['id'];

        // If there is no person with given $name in a dictionary yet - add it
        if (!isset($this->transacgrpPersons[$name])) {
            $m->init(['collectionId' => $collectionId, 'name' => $name]);
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
     * @param $value
     * @return string
     */
    protected function getTransacMappingIfExists($value) {
        return $this->transacMap[$value] ?? $value;
    }
}
