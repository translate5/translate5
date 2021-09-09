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

    const ATTRIBUTE_ALLOWED_TYPES = ['normativeAuthorization', 'administrativeStatus'];

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

    protected array $importMap;
    protected array $allowedTypes;

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
     * Collected term states not listed in statusMap
     * @var array
     */
    protected array $unknownStates = [];
    /**
     * The array have an assignment of the TBX-enabled Term Static that be used in the editor
     * @var array
     */
    protected array $statusMap = [
        'preferredTerm' => editor_Models_Terminology_TbxObjects_Term::STAT_PREFERRED,
        'admittedTerm' => editor_Models_Terminology_TbxObjects_Term::STAT_ADMITTED,
        'legalTerm' => editor_Models_Terminology_TbxObjects_Term::STAT_LEGAL,
        'regulatedTerm' => editor_Models_Terminology_TbxObjects_Term::STAT_REGULATED,
        'standardizedTerm' => editor_Models_Terminology_TbxObjects_Term::STAT_STANDARDIZED,
        'deprecatedTerm' => editor_Models_Terminology_TbxObjects_Term::STAT_DEPRECATED,
        'supersededTerm' => editor_Models_Terminology_TbxObjects_Term::STAT_SUPERSEDED,

        //some more states (uncomplete!), see TRANSLATE-714
        'proposed' => editor_Models_Terminology_TbxObjects_Term::STAT_PREFERRED,
        'deprecated' => editor_Models_Terminology_TbxObjects_Term::STAT_DEPRECATED,
        'admitted' => editor_Models_Terminology_TbxObjects_Term::STAT_ADMITTED,
    ];
    /**
     * Collected term states not listed in statusMap
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
     * In this class is the whole merge logic
     * @var editor_Models_Terminology_BulkOperation_TermEntry
     */
    protected editor_Models_Terminology_BulkOperation_TermEntry $bulkTermEntry;

    /**
     * @var ZfExtended_EventManager
     */
    protected ZfExtended_EventManager $events;

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
    }

    /**
     * Import given TBX file and prepare Import arrays, if file can not be opened throw Zend_Exception.
     * @param string $tbxFilePath
     * @param editor_Models_TermCollection_TermCollection $collection
     * @param ZfExtended_Models_User $user
     * @param bool $mergeTerms
     * @throws Zend_Exception
     * @throws Exception
     */
    public function importXmlFile(string $tbxFilePath, editor_Models_TermCollection_TermCollection $collection, ZfExtended_Models_User $user, bool $mergeTerms)
    {
        $this->collection = $collection;
        $this->prepareImportArrays($user, $mergeTerms);

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

        // remove all empty term entries after the tbx import
        $termEntry = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        /* @var $termEntry editor_Models_Terminology_Models_TermEntryModel */
        $termEntry->removeEmptyFromCollection([$this->collection->getId()]);

        $data = [
            'termEntries' => $this->bulkTermEntry->getStatistics(),
            'terms' => $this->bulkTerm->getStatistics(),
            'attributes' => $this->bulkAttribute->getStatistics(),
            'transacGroups' => $this->bulkTransacGrp->getStatistics(),
            'collection' => $this->collection->getName(),
            'maxMemUsed in MB' => round(memory_get_peak_usage() / 2**20),
        ];
error_log("Imported TBX data into collection ".$this->collection->getId().' '.print_r($data, 1));
        $this->log('Imported TBX data into collection {collection}', 'E1028', $data);
    }

    /**
     * Prepare init array variables for merge procedure and check isset function.
     * @param ZfExtended_Models_User $user
     * @param bool $mergeTerms
     * @throws Zend_Db_Statement_Exception
     */
    private function prepareImportArrays(ZfExtended_Models_User $user, bool $mergeTerms)
    {
        error_log('MEM 1 '.memory_get_usage());
        $this->user = $user;
        $this->mergeTerms = $mergeTerms;
        $this->dataType->resetData();


        //TODO how to distinguish between TBX V2 (termEntry) and V3 (conceptEntry)?
        $this->tbxMap[$this::TBX_TERM_ENTRY] = $this::TBX_TERM_ENTRY; //for V3 set conceptEntry here (implement as subclass???)
        $this->tbxMap[$this::TBX_LANGSET] = 'langSet';
        $this->tbxMap[$this::TBX_TIG] = 'tig';

        error_log('MEM 2 '.memory_get_usage());
        $languagesModel = ZfExtended_Factory::get('editor_Models_Languages')->getAvailableLanguages();
        foreach ($languagesModel as $language) {
            $this->languages[strtolower($language['value'])] = $language['id'];
        }

        $this->importMap = $this->config->runtimeOptions->tbx->termImportMap->toArray();
        //merge system allowed note types with configured ones:
        $this->allowedTypes = array_merge($this::ATTRIBUTE_ALLOWED_TYPES, array_keys($this->importMap));

        // get custom attribute label text and prepare array to check if custom label text exist.
        error_log('MEM 3 '.memory_get_usage());
        $this->dataType->loadData();
        error_log('MEM 4 '.memory_get_usage());

        error_log('MEM 5 '.memory_get_usage());
        $this->bulkTermEntry->loadExisting($this->collection->getId());  //FIXME
        error_log('MEM 6 '.memory_get_usage());
        $this->bulkTerm->loadExisting($this->collection->getId());
        error_log('MEM 7 '.memory_get_usage());
        //FIXME would be ok to load before each term entry to keep memory lower
        $this->bulkTransacGrp->loadExisting($this->collection->getId());
        error_log('MEM 8 '.memory_get_usage());
        //FIXME would be ok to load before each term entry to keep memory lower
        $this->bulkAttribute->loadExisting($this->collection->getId());
        error_log('MEM 9 '.memory_get_usage());
        error_log('MEM 11'.memory_get_usage());
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
            $termEntryNode = new SimpleXMLElement($xmlReader->readOuterXML());
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
                $this->events->trigger('afterTermEntrySave', $this, ['progress' => $progress]);
            }

            // Uncomment this to print the progress
            //error_log("Update progress: [".$importCount.'/'.$totalCount.'] ( progress: '.$progress.'  %)');
            $xmlReader->next($this->tbxMap[$this::TBX_TERM_ENTRY]);
        }
    }

    /**
     * @throws editor_Models_Terminology_Import_Exception
     * @throws Exception
     */
    protected function processRefObjects(XMLReader $xmlReader) {
        while ($xmlReader->read() && $xmlReader->name !== 'refObjectList');
        while ($xmlReader->name === 'refObjectList') {
            if($xmlReader->getAttribute('type') == 'binaryData') {
                /** @var $binImport editor_Models_Terminology_Import_TbxBinaryDataImport */
                $binImport = ZfExtended_Factory::get('editor_Models_Terminology_Import_TbxBinaryDataImport');
                $binImport->import($this->collection->getId(), new SimpleXMLElement($xmlReader->readOuterXML()));
            }
            //FIXME implement getTbxRespUserBack → neuen Issue hierzu anlegen:
//            Marc Mittag  17:09 Uhr @Thomas Lauria I do not know. I know, that we discussed this and said, that we will link these person references to a translate5 user, if the id match the user-guid or the mail address matches. And if not, we will simply put the user as creator / modifier in the termportal. Yet this is not very important to the overall termportal - so if not implemented, we should put it into an issue of remaining todos and park it in our JIRA until someone misses it.
//            Thomas Lauria  17:10 Uhr Ok, then I will do that.
//            if($xmlReader->getAttribute('type') == 'respPerson') {
//                $this->getTbxRespUserBack(new SimpleXMLElement($xmlReader->readOuterXML()));
//            }
            $xmlReader->next('refObjectList');
        }
    }

    /**
     * Save parsed elements.
     * @throws Zend_Db_Table_Exception
     */
    private function saveParsedTermEntryNode()
    {
        //before we save anything to the database we have to perform the merges
        $this->bulkTerm->mergeTerms($this->bulkTermEntry, $this->mergeTerms);

        //bulkTerm create or update must be called before attributes and transacGrps in order to save the termId there correctly
        $this->bulkTermEntry->createOrUpdateElement($this->mergeTerms);
        $this->bulkTerm->createOrUpdateElement($this->mergeTerms);
        $this->bulkAttribute->createOrUpdateElement($this->mergeTerms);
        $this->bulkTransacGrp->createOrUpdateElement($this->mergeTerms);
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
        /** @var editor_Models_Terminology_TbxObjects_TermEntry $newEntry */
        $newEntry = new ($this->bulkTermEntry->getNewImportObject())();
        $newEntry->collectionId = $this->collection->getId();
        $newEntry->termEntryTbxId = $this->getIdOrGenerate($termEntry);

        if (isset($termEntry->descrip)) {
            //FIXME there was no definition which descrip element should be used as text value for the entry
            // so we use the first descrip only, if multiple
            //FIXME use the descrip with type = "thesaurusDescriptor" as discussed with pavel
            $newEntry->descrip = reset($termEntry->descrip)->value ?? '';
        }

        $this->bulkTermEntry->add($newEntry);

        if (isset($termEntry->descrip)) {
            $this->setAttributeTypes($termEntry->descrip, $newEntry);
        }

        if (isset($termEntry->transacGrp)) {
            foreach ($termEntry->transacGrp as $transacGrp) {
                $newEntry->transacGrp = $this->setTransacAttributes($transacGrp, false, 'termEntry', $newEntry);
            }
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

        // INFO: In TBX-Basic, the <descripGrp> element is used only to associate a source to a definition or to
        //a context. The following child elements are not supported: <descripNote>, <admin>,<adminGrp>, <note>, <ref>, and <xref>.
        foreach ($languageGroup->descripGrp as $descripGrp) {
            $this->setAttributeTypes($descripGrp->descrip, $newLangSet);
            $newLangSet->descrip = (string)$descripGrp->descrip;
            $newLangSet->descripTarget = (string)$descripGrp->descrip->attributes()->{'target'};
            $newLangSet->descripType = (string)$descripGrp->descrip->attributes()->{'type'};
            if (isset($descripGrp->transacGrp)) {
                foreach ($descripGrp->transacGrp as $transac) {
                    $this->setTransacAttributes($transac, true, 'langSet', $newLangSet);
                }
            }

            // INFO: if note appears on <descripGrp> level, import the note as normal attribute.
            // This kind of note is not supported by tbx basic
            if (isset($descripGrp->note)) {
                $this->setAttributeTypes($descripGrp->note, $newLangSet);
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
     * @param SimpleXMLElement $tigElement
     * @param editor_Models_Terminology_TbxObjects_Langset $parsedLangSet
     * @throws ZfExtended_ErrorCodeException
     */
    private function handleTermGroup(SimpleXMLElement $tigElement, editor_Models_Terminology_TbxObjects_Langset $parsedLangSet)
    {
        if ($tigElement->termGrp) {
            $tig = $tigElement->termGrp;
        } else {
            $tig = $tigElement;
        }

        /** SimpleXMLElement $tig */
        /** @var editor_Models_Terminology_TbxObjects_Term $newTerm */
        $newTerm = new ($this->bulkTerm->getNewImportObject())();
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

        if (strtolower($parsedLangSet->descripType) === $newTerm::TERM_DEFINITION) {
            $newTerm->definition = $parsedLangSet->descrip;
        }

        $hasTermNote = isset($tig->termNote);
        $this->addProcessStatusNodeIfNotExists($tig);
        $newTerm->termNote = $this->setAttributeTypes($tig->termNote, $newTerm);
        if ($hasTermNote) {
            $newTerm->status = $this->getTermNoteStatus($newTerm->termNote);
            $newTerm->processStatus = $this->getProcessStatus($newTerm->termNote);
        } else {
            $newTerm->status = $this->config->runtimeOptions->tbx->defaultTermStatus;
            $newTerm->processStatus = $newTerm::TERM_STANDARD_PROCESS_STATUS;
        }

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
     * @return array
     * @throws ZfExtended_ErrorCodeException
     */
    private function setAttributeTypes(SimpleXMLElement $element, editor_Models_Terminology_TbxObjects_Abstract $parentNode): array
    {
        $attributes = [];
        /** @var SimpleXMLElement $value */
        foreach ($element as $key => $value) {
            /** @var editor_Models_Terminology_TbxObjects_Attribute $attribute */
            $attribute = $this->bulkAttribute->getNewImportObject();
            $attribute->setParent($parentNode);
            $attribute->collectionId = $this->collection->getId();
            $attribute->termEntryId = $attribute->parentEntry->id;
            $attribute->language = $attribute->parentLangset->language ?? null;
            // termId is updated after inserting all the terms!
            $attribute->termTbxId = $attribute->parentTerm->termTbxId ?? null;
            $attribute->type = (string)$value->attributes()->{'type'};
            $attribute->value = (string) $value;
            $attribute->target = (string)$value->attributes()->{'target'};
            $attribute->createdBy = $this->user->getId();
            $attribute->createdAt = NOW_ISO; //is saved only on INSERT / on UPDATE it is not send to server, otherwise the hash would change
            $attribute->updatedBy = $this->user->getId();

            $attribute->termEntryGuid = $attribute->parentEntry->entryGuid;
            $attribute->langSetGuid = $attribute->parentLangset->langSetGuid ?? null;
            $attribute->termGuid = $attribute->parentTerm->guid ?? null;

            $attribute->elementName = $key;
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

            $attributes[] = $attribute;

            // add the attribute to the global attributes collection
            $this->bulkAttribute->add($attribute);
        }

        return $attributes;
    }

    /**
     * @param SimpleXMLElement $transacGrp
     * @param bool $ifDescripGrp
     * @param string $elementName
     * @param editor_Models_Terminology_TbxObjects_Abstract $parentNode parent main TBX node
     * @return array
     */
    private function setTransacAttributes(SimpleXMLElement $transacGrp, bool $ifDescripGrp, string $elementName, editor_Models_Terminology_TbxObjects_Abstract $parentNode): array
    {
        $parsedTransacGrp = [];
        /** @var editor_Models_Terminology_TbxObjects_TransacGrp $transacGrpObject */
        $transacGrpObject = new ($this->bulkTransacGrp->getNewImportObject())();
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
        $transacGrpObject->attrLang = $this->getActualLanguageAttribute($transacGrp);

        if (isset($transacGrp->transac)) {
            $transacGrpObject->transac = (string)$transacGrp->transac;
        }
        if (isset($transacGrp->date)) {
            $transacGrpObject->date = ZfExtended_Utils::toMysqlDateTime((string)$transacGrp->date);
        }
        if (isset($transacGrp->transacNote)) {
            $transacGrpObject->transacType = (string)$transacGrp->transacNote->attributes()->{'type'};
            $transacGrpObject->transacNote = (string)$transacGrp->transacNote;
        }

        $transacGrpObject->ifDescripGrp = (int)$ifDescripGrp;
        $parsedTransacGrp[] = $transacGrpObject;
        $this->bulkTransacGrp->add($transacGrpObject);
        return $parsedTransacGrp;
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
        if (!(string)$language) {
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
     * ToDo: Sinisa, Update user in table... define how and what is mean in: TRANSLATE-1274
     * @param SimpleXMLElement $refObjectList
     * @return array
     */
    private function getTbxRespUserBack(SimpleXMLElement $refObjectList): array
    {
        $parsedTbxRespUser = [];
        $count = 0;
        /** @var SimpleXMLElement $refObject */
        foreach ($refObjectList as $refObject) {
            $parsedTbxRespUser[$count]['target'] = (string)$refObject->attributes()->{'id'};
            foreach ($refObject->item as $item) {
                $parsedTbxRespUser[$count][(string)$item->attributes()->{'type'}] = (string)$item;
            }
            $count++;
        }

        return $parsedTbxRespUser;
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
            $this->log('Unable to import terms in this language set. Invalid Rfc5646 language code. Language code: ' . $key);
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
    /**
     * returns the translate5 internal available term status to the one given in TBX
     * @param array $termNotes
     * @return string
     *
     *
     * FIXME wir brauchen eine Funktion die den terms_term status aus den Attributen liefert, verwendbar when one attribute is updated.
     * statusMap is coming here already from config - regarding client overwriting???
     */
    protected function getTermNoteStatus(array $termNotes) : string
    {
        /** @var editor_Models_Terminology_TbxObjects_Attribute $attribute */
        foreach ($termNotes as $attribute) {
            $tbxStatus = $attribute->value;
            $tbxType = $attribute->type;

            //if current termNote is no starttag or type is not allowed to provide a status then we jump out
            if (in_array($tbxType, $this->allowedTypes)) {
                // termNote type administrativeStatus are similar to normativeAuthorization,
                // expect that the values have a suffix which must be removed
                if ($tbxType === 'administrativeStatus') {
                    $tbxStatus = str_replace('-admn-sts$', '', $attribute->value . '$');
                }

                //add configured status map
                $statusMap = $this->statusMap;
                if (!empty($this->importMap[$tbxType])) {
                    $statusMap = array_merge($this->statusMap, $this->importMap[$tbxType]);
                }

                if (!empty($statusMap[$tbxStatus])) {
                    return $statusMap[$tbxStatus];
                }

                if (!in_array($tbxStatus, $this->unknownStates)) {
                    $this->unknownStates[] = $tbxStatus;
                }
            }
        }

        return $this->config->runtimeOptions->tbx->defaultTermStatus;
    }

    private function log($logMessage, $code = 'E1028', array $extra = [])
    {
        $extra['languageResource'] = $this->collection;
        if (!empty($this->task)) {
            $extra['task'] = $this->task;
        }
        $this->logger->info($code, $logMessage, $extra);
    }
}
