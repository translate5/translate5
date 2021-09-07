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
class editor_Models_Terminology_Import_TbxFileImport extends editor_Models_Terminology_Import_AbstractTerminology
{
    const TBX_TIG = 'tig';
    const TBX_TERM_ENTRY = 'termEntry';
    const TBX_LANGSET = 'langSet';

    const ATTRIBUTE_ALLOWED_TYPES = ['normativeAuthorization', 'administrativeStatus'];

    /** @var $logger ZfExtended_Logger */
    protected ZfExtended_Logger $logger;

    /** @var Zend_Config */
    protected Zend_Config $config;

    /** @var editor_Models_Task */
    protected editor_Models_Task $taskModel;

    /** @var ZfExtended_Models_User */
    protected ZfExtended_Models_User $user;

    /** @var editor_Models_TermCollection_TermCollection */
    protected editor_Models_TermCollection_TermCollection $termCollectionModel;

    /** @var editor_Models_Terminology_Models_TermModel */
    protected editor_Models_Terminology_Models_TermModel $termModel;

    /** @var editor_Models_Terminology_Models_TermEntryModel */
    protected editor_Models_Terminology_Models_TermEntryModel $termEntryModel;

    /** @var editor_Models_Terminology_Models_AttributeModel */
    protected editor_Models_Terminology_Models_AttributeModel $attributeModel;

    /** @var editor_Models_Terminology_Models_TransacgrpModel */
    protected editor_Models_Terminology_Models_TransacgrpModel $transacGrpModel;

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

    /**
     * Actual collection Id
     * @var int
     */
    protected int $collectionId;
    /**
     * Actual termEntry Id from database
     * @var int
     */
    protected int $termEntryDbId;
    /**
     * Actual termEntry Id
     * @var string
     */
    protected string $termEntryTbxId;
    /**
     * Unique Guid for each termEntry
     * @var string|null
     */
    protected ?string $termEntryGuid;
    /**
     * Unique Guid for each LangSet
     * @var string|null
     */
    protected ?string $langSetGuid;
    /**
     * Unique Guid for each Term
     * @var string|null
     */
    protected ?string $termGuid;
    /**
     * id for each term
     * @var int|null
     */
    protected ?int $termId;
    /**
     * TBX id for each term
     * @var string
     */
    protected ?string $termTbxId;
    /**
     * Unique Guid for each descripGrpGuid
     * @var string|null
     */
    protected ?string $descripGrpGuid;
    /**
     * Actual language from langSet method checkIfLanguageIsForProceed()
     * $this->language['language'] or $this->language['id']
     * @var array
     */
    protected array $language;
    /**
     * All available languages in Translate5
     * $languages['de_DE' => 4]
     * @var array
     */
    protected array $languages;
    /**
     * All attributes by collectionId from terms_attributes to check if attribute isset in actual collection.
     * @var array
     */
    protected array $attributesCollection;
    /**
     * All collection from LEK_LanguageResources to check if termEntry isset in actual collection.
     * $termEntriesCollection['collectionId-groupId' => 'id-isCreatedLocally-descrip'];
     * @var array
     */
    protected array $termEntriesCollection;
    /**
     * All terms from terms_transacgrp to check if term isset.
     * @var array
     */
    protected array $transacGrpCollection;
    /**
     * All terms from terms_term to check if term isset.
     * @var array
     */
    protected array $termCollection;
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

    /** @var editor_Models_Terminology_TbxObjects_TermEntry|mixed  */
    protected editor_Models_Terminology_TbxObjects_TermEntry $termEntryObject;
    /** @var editor_Models_Terminology_TbxObjects_Langset|mixed  */
    protected editor_Models_Terminology_TbxObjects_Langset $langsetObject;
    /** @var editor_Models_Terminology_TbxObjects_Term|mixed  */
    protected editor_Models_Terminology_TbxObjects_Term $termObject;
    /** @var editor_Models_Terminology_TbxObjects_Attribute|mixed  */
    protected editor_Models_Terminology_TbxObjects_Attribute $attributesObject;
    /** @var editor_Models_Terminology_TbxObjects_TransacGrp|mixed  */
    protected editor_Models_Terminology_TbxObjects_TransacGrp $transacGrpObject;

    /**
     * In this class is the whole merge logic
     * @var editor_Models_Terminology_Import_TermEntryMerge|mixed
     */
    protected editor_Models_Terminology_Import_TermEntryMerge $termEntryMerge;

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

        $this->taskModel = ZfExtended_Factory::get('editor_Models_Task');

        $this->termCollectionModel = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        $this->termEntryModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        $this->termModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        $this->attributeModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        $this->attributeDataTypeModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeDataType');
        $this->transacGrpModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');

        $this->termEntryObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_TermEntry');
        $this->langsetObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_Langset');
        $this->termObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_Term');
        $this->attributesObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_Attribute');
        $this->transacGrpObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_TransacGrp');
        $this->dataType = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_DataType');

        $this->termEntryMerge = ZfExtended_Factory::get('editor_Models_Terminology_Import_TermEntryMerge');
    }

    /**
     * Import given TBX file and prepare Import arrays, if file can not be opened throw Zend_Exception.
     * @param string $tbxFilePath
     * @param editor_Models_TermCollection_TermCollection $collection
     * @param ZfExtended_Models_User $user
     * @param bool $mergeTerms
     * @return array
     * @throws Zend_Exception
     */
    public function importXmlFile(string $tbxFilePath, editor_Models_TermCollection_TermCollection $collection, ZfExtended_Models_User $user, bool $mergeTerms): array
    {
        $this->prepareImportArrays($collection, $user, $mergeTerms);

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

        $this->termModel->updateAttributeAndTransacTermIdAfterImport($this->collectionId);

        $dataTypeAssoc = ZfExtended_Factory::get('editor_Models_Terminology_Models_CollectionAttributeDataType');
        /* @var $dataTypeAssoc editor_Models_Terminology_Models_CollectionAttributeDataType */
        // insert all attribute data types for current collection in the terms_collection_attribute_datatype table
        $dataTypeAssoc->updateCollectionAttributeAssoc($this->collectionId);

        // remove all empty term entries after the tbx import
        $termEntry = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        /* @var $termEntry editor_Models_Terminology_Models_TermEntryModel */
        $termEntry->removeEmptyFromCollection([$this->collectionId]);

        return $this->attributes;
    }

    /**
     * Prepare init array variables for merge procedure and check isset function.
     * @param object $collection
     * @param ZfExtended_Models_User $user
     * @param bool $mergeTerms
     */
    private function prepareImportArrays(object $collection, ZfExtended_Models_User $user, bool $mergeTerms)
    {
        error_log('MEM 1 '.memory_get_usage());
        $this->user = $user;
        $this->collectionId = $collection->getId();
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
        $this->dataType->loadData();

        if ($mergeTerms) {
            $this->termEntriesCollection = $this->termEntryModel->getAllTermEntryAndCollection($this->collectionId);
            $this->attributesCollection = $this->attributeModel->getAttributeByCollectionId($this->collectionId);
            $this->termCollection = $this->termModel->getAllTermsByCollectionId($this->collectionId);
            $this->transacGrpCollection = $this->transacGrpModel->getTransacGrpByCollectionId($this->collectionId);
        } else {
            $this->termEntriesCollection = [];
            $this->termCollection = [];
            $this->attributesCollection = [];
            $this->transacGrpCollection = [];
        }
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
            $parsedEntry = null;
            $this->emptyVariables();
            $parsedEntry = $this->handleTermEntry($termEntryNode);
            foreach ($termEntryNode->{$this->tbxMap[$this::TBX_LANGSET]} as $languageGroup) {
                $this->langSetGuid = null;
                $this->language = $this->checkIfLanguageIsProceed($languageGroup);

                if (isset($this->language['language'])) {
                    $parsedLangSet = null;
                    $parsedLangSet = $this->handleLanguageGroup($languageGroup, $parsedEntry);

                    foreach ($languageGroup->{$this->tbxMap[$this::TBX_TIG]} as $termGroup) {
                        $this->termId = null;
                        $this->termTbxId = null;
                        $this->termGuid = null;
                        $this->handleTermGroup($termGroup, $parsedLangSet);
                        $this->termId = null;
                        $this->termTbxId = null;
                        $this->termGuid = null;
                    }
                }
            }
            $this->saveParsedTbx();
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

    protected function processRefObjects(XMLReader $xmlReader) {
        while ($xmlReader->read() && $xmlReader->name !== 'refObjectList');
        while ($xmlReader->name === 'refObjectList') {
            if($xmlReader->getAttribute('type') == 'binaryData') {
                /** @var $binImport editor_Models_Terminology_Import_TbxBinaryDataImport */
                $binImport = ZfExtended_Factory::get('editor_Models_Terminology_Import_TbxBinaryDataImport');
                $binImport->import($this->collectionId, new SimpleXMLElement($xmlReader->readOuterXML()));
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

    /***
     * Empty all helper variables before each termEntry loop
     */
    private function emptyVariables()
    {
        $this->termEntryDbId = 0;
        $this->termEntryTbxId = '';
        $this->termEntryGuid = null;
        $this->langSetGuid = null;
        $this->language = [];
        $this->descripGrpGuid = null;
        $this->termGuid = null;
        $this->transacGrps = [];
        $this->termId = null;
        $this->termTbxId = null;

    }

    /**
     * Save parsed elements.
     */
    private function saveParsedTbx()
    {
        if (!empty($this->terms)) {
            $this->createOrUpdateElement($this->termModel, $this->terms, $this->termCollection, $this->mergeTerms);
            $this->terms = [];
        }

        if (!empty($this->attributes)) {
            $this->createOrUpdateElement($this->attributeModel, $this->attributes, $this->attributesCollection, $this->mergeTerms);
            $this->attributes = [];
        }

        if (!empty($this->transacGrps)) {
            $this->createOrUpdateElement($this->transacGrpModel, $this->transacGrps, $this->transacGrpCollection, $this->mergeTerms);
            $this->transacGrps = [];
        }
    }

    /**
     * Iterate over the termEntry structure and call handler for each element.
     * There will be parsed all child elements and returns termEntry as object.
     * @param SimpleXMLElement $termEntry
     * @return editor_Models_Terminology_TbxObjects_TermEntry
     */
    private function handleTermEntry(SimpleXMLElement $termEntry): editor_Models_Terminology_TbxObjects_TermEntry
    {
        $this->termEntryTbxId = $this->getIdOrGenerate($termEntry, $this->tbxMap[$this::TBX_TERM_ENTRY]);
        $this->termEntryGuid = $this->getGuid();

        /** @var editor_Models_Terminology_TbxObjects_TermEntry $newEntry */
        $newEntry = new $this->termEntryObject;
        $newEntry->setCollectionId($this->collectionId);
        $newEntry->setTermEntryTbxId($this->termEntryTbxId);
        $newEntry->setEntryGuid($this->termEntryGuid);

        // Before setting first attribute we retrieve actual termEntryDbId fom table
        /** @var editor_Models_Terminology_Models_TermEntryModel $actualTermEntry */
        $this->termEntryDbId = $this->termEntryMerge->createOrUpdateTermEntry($newEntry, $this->termEntriesCollection);

        if (isset($termEntry->descrip)) {
            $newEntry->setDescrip($this->setAttributeTypes($termEntry->descrip));
        }

        if (isset($termEntry->transacGrp)) {
            foreach ($termEntry->transacGrp as $transacGrp) {
                $newEntry->setTransacGrp($this->setTransacAttributes($transacGrp, false, 'termEntry'));
            }
        }
        if (isset($termEntry->xref)) {
            $newEntry->setXref($this->setAttributeTypes($termEntry->xref));
        }
        if (isset($termEntry->ref)) {
            $newEntry->setRef($this->setAttributeTypes($termEntry->ref));
        }

        return $newEntry;
    }

    /**
     * Iterate over the langSet structure and call handler for each element.
     * There will be parsed all child elements and returns langSet as object.
     * @param SimpleXMLElement $languageGroup
     * @param editor_Models_Terminology_TbxObjects_TermEntry $parsedEntry
     * @return editor_Models_Terminology_TbxObjects_Langset
     */
    private function handleLanguageGroup(SimpleXMLElement $languageGroup, editor_Models_Terminology_TbxObjects_TermEntry $parsedEntry): editor_Models_Terminology_TbxObjects_Langset
    {
        $this->tbxMap[$this::TBX_TIG] = $languageGroup->tig ? 'tig' : 'ntig';
        /** @var editor_Models_Terminology_TbxObjects_Langset $newLangSet */
        $newLangSet = new $this->langsetObject;
        $this->langSetGuid = $this->getGuid();
        $newLangSet->setCollectionId($this->collectionId);
        $newLangSet->setLanguage($this->language['language']);
        $newLangSet->setLanguageId($this->language['id']);
        $newLangSet->setLangSetGuid($this->langSetGuid);
        $newLangSet->setEntryId($this->termEntryDbId);
        $newLangSet->setTermEntryGuid($parsedEntry->getEntryGuid());

        // INFO: In TBX-Basic, the <descripGrp> element is used only to associate a source to a definition or to
        //a context. The following child elements are not supported: <descripNote>, <admin>,<adminGrp>, <note>, <ref>, and <xref>.
        foreach ($languageGroup->descripGrp as $descripGrp) {
            $this->descripGrpGuid = $this->getGuid();
            $this->setAttributeTypes($descripGrp->descrip);
            $newLangSet->setDescripGrpGuid($this->descripGrpGuid);
            $newLangSet->setDescrip((string)$descripGrp->descrip);
            $newLangSet->setDescripTarget((string)$descripGrp->descrip->attributes()->{'target'});
            $newLangSet->setDescripType((string)$descripGrp->descrip->attributes()->{'type'});
            if (isset($descripGrp->transacGrp)) {
                foreach ($descripGrp->transacGrp as $transac) {
                    $newLangSet->setDescripGrp($this->setTransacAttributes($transac, true, 'langSet'));
                }
            }

            // INFO: if note appears on <descripGrp> level, import the note as normal attribute.
            // This kind of note is not supported by tbx basic
            if (isset($descripGrp->note)) {
                $newLangSet->setNote($this->setAttributeTypes($descripGrp->note));
            }
        }

        if (isset($languageGroup->note)) {
            $newLangSet->setNote($this->setAttributeTypes($languageGroup->note));
        }

        return $newLangSet;
    }

    /**
     * Iterate over the term structure and call handler for each element.
     * There will be parsed all child elements and returns term as object.
     * Elements - term, termNote, transacGrp, transacNote, admin
     * @param SimpleXMLElement $tigElement
     * @param editor_Models_Terminology_TbxObjects_Langset $parsedLangSet
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    private function handleTermGroup(SimpleXMLElement $tigElement, editor_Models_Terminology_TbxObjects_Langset $parsedLangSet): editor_Models_Terminology_TbxObjects_Term
    {

        if ($tigElement->termGrp) {
            $tig = $tigElement->termGrp;
        } else {
            $tig = $tigElement;
        }

        $this->termGuid = $this->getGuid();
        /** SimpleXMLElement $tig */
        /** @var editor_Models_Terminology_TbxObjects_Term $newTerm */
        $newTerm = new $this->termObject;
        $newTerm->setUpdatedBy($this->user->getId());
        $newTerm->setUpdatedAt(NOW_ISO);
        $newTerm->setCollectionId($this->collectionId);
        $newTerm->setTermEntryId($this->termEntryDbId);
        $newTerm->setLanguageId((int)$this->language['id']);
        $newTerm->setLanguage($this->language['language']);
        $newTerm->setTerm((string)$tig->term);
        $newTerm->setTermEntryTbxId($this->termEntryTbxId);
        $newTerm->setTermTbxId($this->getIdOrGenerate($tig->term, $this->tbxMap[$this::TBX_TIG]));
        $newTerm->setTermEntryGuid($this->termEntryGuid);
        $newTerm->setLangSetGuid($this->langSetGuid);

        //TODO: Merge todo: this must be changed if the term exist in the database.
        $newTerm->setGuid($this->termGuid);

        $newTerm->setDescrip($parsedLangSet->getDescrip());
        $newTerm->setDescripTarget($parsedLangSet->getDescripTarget());
        $newTerm->setDescripType($parsedLangSet->getDescripType());

        $this->termTbxId = $newTerm->getTermTbxId();

        if (strtolower($parsedLangSet->getDescripType()) === $newTerm::TERM_DEFINITION) {
            $newTerm->setDefinition($parsedLangSet->getDescrip());
        }
        $hasTermNote = isset($tig->termNote);
        $this->addProcessStatusNodeIfNotExists($tig);
        $newTerm->setTermNote($this->setAttributeTypes($tig->termNote));
        if ($hasTermNote) {
            //file_put_contents('log.txt', print_r($tig->termNote, true), FILE_APPEND);
            //file_put_contents('log.txt', print_r($tig->termNote->asXML(), true), FILE_APPEND);
            //file_put_contents('log.txt', print_r($newTerm->getTermNote(), true), FILE_APPEND);
            $newTerm->setStatus($this->getTermNoteStatus($newTerm->getTermNote()));
            $newTerm->setProcessStatus($this->getProcessStatus($newTerm->getTermNote()));
        } else {
            $newTerm->setStatus($this->config->runtimeOptions->tbx->defaultTermStatus);
            $newTerm->setProcessStatus($newTerm::TERM_STANDARD_PROCESS_STATUS);
        }

        if ($newTerm->getProcessStatus() === '') {
            $newTerm->setProcessStatus($newTerm::TERM_STANDARD_PROCESS_STATUS);
        }

        if (isset($tig->note)) {
            $newTerm->setNote($this->setAttributeTypes($tig->note));
        }
        if (isset($tig->admin)) {
            $newTerm->setAdmin($this->setAttributeTypes($tig->admin));
        }
        if (isset($tig->xref)) {
            $newTerm->setXref($this->setAttributeTypes($tig->xref));
        }
        if (isset($tig->ref)) {
            $newTerm->setRef($this->setAttributeTypes($tig->ref));
        }
        if (isset($tig->transacGrp)) {
            foreach ($tig->transacGrp as $transac){
                $newTerm->setTransacGrp($this->setTransacAttributes($transac, false, 'tig'));
            }
        }
        $this->terms[] = $newTerm;

        return $newTerm;
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
     * @return array
     */
    private function setAttributeTypes(SimpleXMLElement $element): array
    {
        if (!isset($this->language['language'])) {
            $this->language['language'] = null;
        }
        $attributes = [];
        /** @var SimpleXMLElement $value */
        foreach ($element as $key => $value) {
            /** @var editor_Models_Terminology_TbxObjects_Attribute $attribute */
            $attribute = new $this->attributesObject;
            $attribute->setCollectionId($this->collectionId);
            $attribute->setTermEntryId($this->termEntryDbId);
            $attribute->setLanguage($this->language['language']);
            // termId ?
            $attribute->setTermTbxId($this->termTbxId);
            $attribute->setType((string)$value->attributes()->{'type'});
            $attribute->setValue((string)$value);
            $attribute->setTarget((string)$value->attributes()->{'target'});
            $attribute->setCreatedBy($this->user->getId());
            $attribute->setCreatedAt(NOW_ISO);
            $attribute->setUpdatedBy($this->user->getId());
            $attribute->setUpdatedAt(NOW_ISO);

            $attribute->setTermEntryGuid($this->termEntryGuid);
            $attribute->setLangSetGuid($this->langSetGuid);
            $attribute->setTermGuid($this->termGuid);
            $attribute->setGuid($this->getGuid());

            $attribute->setElementName($key);
//            $attribute->setAttrLang($this->getActualLanguageAttribute($value));
            $attribute->setAttrLang($this->language['language'] ? $this->language['language'] : '');

            // check if the dataType exist for the element
            $labelId = $this->dataType->getForAttribute($attribute);
            if (empty($labelId)) {
                // the dataType does not exist -> create it
                $this->attributeDataTypeModel->loadOrCreate($attribute->getElementName(), $attribute->getType(),[$attribute->getLevel()]);

                // reload all dataTypes
                $this->dataType->loadData(true);

                $labelId = $this->dataType->getForAttribute($attribute);
            }

            $attribute->setDataTypeId((int)$labelId);

            $attributes[] = $attribute;

            // add the attribute to the global attributes collection
            $this->attributes[] = $attribute;
        }

        return $attributes;
    }

    /**
     * @param SimpleXMLElement $transacGrp
     * @param bool $ifDescripGrp
     * @param string $elementName
     * @return array
     */
    private function setTransacAttributes(SimpleXMLElement $transacGrp, bool $ifDescripGrp, string $elementName): array
    {
        $parsedTransacGrp = [];
        /** @var editor_Models_Terminology_TbxObjects_TransacGrp $transacGrpObject */
        $transacGrpObject = new $this->transacGrpObject;
        $transacGrpObject->setCollectionId($this->collectionId);
        $transacGrpObject->setTermEntryId($this->termEntryDbId);
        $transacGrpObject->setTermId($this->termId);
        $transacGrpObject->setTermTbxId($this->termTbxId);
        $transacGrpObject->setTermGuid($this->termGuid);
        $transacGrpObject->setTermEntryGuid($this->termEntryGuid);
        $transacGrpObject->setLangSetGuid($this->langSetGuid);
        $transacGrpObject->setDescripGrpGuid($this->descripGrpGuid);
        $transacGrpObject->setGuid($this->getGuid());
        $transacGrpObject->setElementName($elementName);

        // for term entry transac group there is no language
        $transacGrpObject->setLanguage($this->language['language']);
        $transacGrpObject->setAttrLang($this->getActualLanguageAttribute($transacGrp));

        if (isset($transacGrp->transac)) {
            $transacGrpObject->setTransac((string)$transacGrp->transac);
        }
        if (isset($transacGrp->date)) {
            $transacGrpObject->setDate(ZfExtended_Utils::toMysqlDateTime((string)$transacGrp->date));
        }
        if (isset($transacGrp->transacNote)) {
            $transacGrpObject->setTransacType((string)$transacGrp->transacNote->attributes()->{'type'});
            $transacGrpObject->setTransacNote((string)$transacGrp->transacNote);
        }

        $transacGrpObject->setIfDescripGrp((int)$ifDescripGrp);
        $parsedTransacGrp[] = $transacGrpObject;
        $this->transacGrps[] = $transacGrpObject;

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
        if (!$language) {
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
     * @return array
     */
    private function checkIfLanguageIsProceed(SimpleXMLElement $languageGroup): array
    {
        $language = strtolower(str_replace('_','-', $this->getActualLanguageAttribute($languageGroup)));

        if (isset($this->languages[$language])) {
            return ['language' => $language, 'id' => $this->languages[$language]];
        }

        $this->unknownLanguages[$language] = $language;
        return [];
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
     * @param string $map
     * @return string
     */
    private function getIdOrGenerate(SimpleXMLElement $xmlElement, string $map): string
    {
        if ($xmlElement->attributes()->{'id'}) {
            return (string)$xmlElement->attributes()->{'id'};
        }

        return $this->getUniqueId($map . '_');
    }

    /**
     * @param $prefixType
     * @return string
     */
    private function getUniqueId($prefixType): string
    {
        return uniqid($prefixType);
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
        foreach ($termNotes[0] as $termNote) {
            if ($termNote->getType() === 'processStatus') {
                $processStatus = $termNote->getValue();
            }
        }

        return $processStatus;
    }

    /**
     * @return string
     */
    private function getGuid(): string
    {
        $set_uuid = ZfExtended_Utils::guid();

        return trim($set_uuid, '{}');
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
        foreach ($termNotes[0] as $attribute) {
            $tbxStatus = $attribute->getValue();
            $tbxType = $attribute->getType();

            //if current termNote is no starttag or type is not allowed to provide a status then we jump out
            if (in_array($tbxType, $this->allowedTypes)) {
                // termNote type administrativeStatus are similar to normativeAuthorization,
                // expect that the values have a suffix which must be removed
                if ($tbxType === 'administrativeStatus') {
                    $tbxStatus = str_replace('-admn-sts$', '', $attribute->getValue() . '$');
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

    private function log($logMessage, $code = 'E1028')
    {
        $data = [
            'languageResource' => $this->termEntryDbId
        ];
        if (!empty($this->task)) {
            $data['task'] = $this->task;
        }
        $data['userGuid'] = $this->user->getUserGuid();
        $data['userName'] = $this->user->getUserName();
        $this->logger->info($code, $logMessage, $data);
    }
}
