<?php

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

    /**
     * $tbxMap = segment names for different TBX standards
     * $this->tbxMap['tig'] = 'tig'; - or if 'ntig' element - $this->tbxMap['tig'] = 'ntig';
     * each possible segment for TBX standard must be defined and will be merged in translate5 standard!
     * @var array
     */
    protected array $tbxMap;

    protected array $importMap;
    protected array $allowedTypes;
    protected bool $mergeTerms;

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
    protected string $termEntryId;
    /**
     * Unique Guid for each termEntry
     * @var string
     */
    protected string $termEntryGuid;
    /**
     * Unique Guid for each LangSet
     * @var string
     */
    protected string $langSetGuid;
    /**
     * Unique Guid for each term
     * @var string
     */
    protected string $termId;
    /**
     * Unique Guid for each descripGrpGuid
     * @var string
     */
    protected string $descripGrpGuid;
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
     * All collection from LEK_LanguageResources to check if termEntry isset in actual collection.
     * $termEntriesCollection['collectionId-groupId' => 'id-isProposal-descrip'];
     * @var array
     */
    protected array $termEntriesCollection;
    /**
     * All terms from LEK_terms to check if term isset.
     * $termCollection['mid-groupId-collectionId' => $term];
     * @var array
     */
    protected array $termCollection;
    /**
     * Collection of attributes (note, ref, xref, descrip...) as object prepared for insert or update.
     * @var array
     */
    protected array $attributes;
    /**
     * Collection of term as object prepared for insert or update.
     * @var array
     */
    protected array $terms;
    /**
     * Collection of transacGrp as object prepared for insert or update.
     * @var array
     */
    protected array $transacGrps;
    /**
     * Collected term states not listed in statusMap
     * @var array
     */
    protected array $unknownStates = [];
    /**
     * Collected term states not listed in statusMap
     * @var array
     */
    protected array $unknownLanguages = [];
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
     * @var editor_Models_Terminology_Import_TermMerge|mixed
     */
    protected editor_Models_Terminology_Import_TermMerge $termMerge;
    /**
     * In this class is the whole merge logic
     * @var editor_Models_Terminology_Import_AttributeMerge|mixed
     */
    protected editor_Models_Terminology_Import_AttributeMerge $attributeMerge;
    /**
     * In this class is the whole merge logic
     * @var editor_Models_Terminology_Import_TransacGrpMerge|mixed
     */
    protected editor_Models_Terminology_Import_TransacGrpMerge $transacGrpMerge;
    /**
     * In this class is the whole merge logic
     * @var editor_Models_Terminology_Import_TermEntryMerge|mixed
     */
    protected editor_Models_Terminology_Import_TermEntryMerge $termEntryMerge;

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
        $this->taskModel = ZfExtended_Factory::get('editor_Models_Task');

        $this->termCollectionModel = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        $this->termEntryModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        $this->termModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        $this->attributeModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        $this->transacGrpModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');

        $this->termEntryObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_TermEntry');
        $this->langsetObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_Langset');
        $this->termObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_Term');
        $this->attributesObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_Attribute');
        $this->transacGrpObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_TransacGrp');

        $this->termMerge = ZfExtended_Factory::get('editor_Models_Terminology_Import_TermMerge');
        $this->termEntryMerge = ZfExtended_Factory::get('editor_Models_Terminology_Import_TermEntryMerge');
        $this->attributeMerge = ZfExtended_Factory::get('editor_Models_Terminology_Import_AttributeMerge');
        $this->transacGrpMerge = ZfExtended_Factory::get('editor_Models_Terminology_Import_TransacGrpMerge');
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
        $termEntries = [];
        $tbxAsSimpleXml = null;

        try {
            $tbxAsSimpleXml = new SimpleXMLElement(file_get_contents($tbxFilePath), LIBXML_NOERROR);
        } catch (Exception $e) {
            throw new Zend_Exception('TBX file can not be opened.');
        }

        if ($tbxAsSimpleXml) {
            $this->prepareImportArrays($collection, $user, $mergeTerms);
            // ToDo: Sinisa, check if parameter merge terms is set.
            $termEntries = $this->importTbx($tbxAsSimpleXml);
        }

        return $termEntries;
    }

    /**
     * Prepare init array variables for merge procedure and check isset function.
     * @param object $collection
     * @param ZfExtended_Models_User $user
     * @param bool $mergeTerms
     */
    private function prepareImportArrays(object $collection, ZfExtended_Models_User $user, bool $mergeTerms)
    {
        $this->user = $user;
        $this->collectionId = $collection->getId();
        $this->mergeTerms = $mergeTerms;

        $this->tbxMap[$this::TBX_TERM_ENTRY] = 'termEntry';
        $this->tbxMap[$this::TBX_LANGSET] = 'langSet';
        $this->tbxMap[$this::TBX_TIG] = 'tig';

        $languagesModel = ZfExtended_Factory::get('editor_Models_Languages')->getAvailableLanguages();
        foreach ($languagesModel as $key => $language) {
            $this->languages[strtolower($language['value'])] = $language['id'];
        }

        $this->importMap = $this->config->runtimeOptions->tbx->termImportMap->toArray();
        //merge system allowed note types with configured ones:
        $this->allowedTypes = array_merge($this::ATTRIBUTE_ALLOWED_TYPES, array_keys($this->importMap));

        if ($mergeTerms) {
            $this->termEntriesCollection = $this->termEntryModel->getAllTermEntryAndCollection($this->collectionId);
        } else {
            $this->termEntriesCollection = [];
            $this->termCollection = [];
        }
    }

    /**
     * Iterate over the TBX structure and call handler for each element.
     * There will be parsed all child elements.
     * Only a termEntry row in terms_termEntry table will be created (or not, if import to exist collection and exist!)
     * after handleTermEntry() method in foreach termEntry.
     *
     * LangSet and Terms will be created or updated after each termEntry
     *
     * @param SimpleXMLElement $tbxAsSimpleXml
     * @return array
     */
    private function importTbx(SimpleXMLElement $tbxAsSimpleXml): array
    {
        $startEntryTime = microtime(true);
        $this->tbxMap[$this::TBX_TERM_ENTRY] = $tbxAsSimpleXml->text->body->termEntry ? 'termEntry' : 'conceptEntry';

        foreach ($tbxAsSimpleXml->text->body->{$this->tbxMap[$this::TBX_TERM_ENTRY]} as $termEntry) {
            $parsedEntry = null;
            $this->emptyVariables();
            $parsedEntry = $this->handleTermEntry($termEntry);
            foreach ($termEntry->{$this->tbxMap[$this::TBX_LANGSET]} as $languageGroup) {
                $this->langSetGuid = '';
                $this->language = $this->checkIfLanguageIsProceed($languageGroup);
                if ($this->language) {
                    $parsedLangSet = null;
                    $parsedLangSet = $this->handleLanguageGroup($languageGroup, $parsedEntry);

                    foreach ($languageGroup->{$this->tbxMap[$this::TBX_TIG]} as $termGroup) {
                        $this->termId = '';
                        $this->handleTermGroup($termGroup, $parsedLangSet);
                        $this->termId = '';
                    }
                }
            }
            $this->saveParsedTbx();
        }
        $statisticEntry = "ENTRY sec.: " . (microtime(true) - $startEntryTime)
            . " - Memory usage: " . ((memory_get_usage() / 1024) / 1024) .' MB';

        if ($this->unknownLanguages) {
            $this->logUnknownLanguages();
        }

        $this->log($statisticEntry);

        return $this->attributes;
    }

    private function emptyVariables()
    {
        $this->termEntryDbId = 0;
        $this->termEntryId = '';
        $this->termEntryGuid = '';
        $this->langSetGuid = '';
        $this->descripGrpGuid = '';
        $this->transacGrps = [];
        $this->termId = '';
    }

    /**
     * Save parsed elements.
     */
    private function saveParsedTbx()
    {
        if ($this->attributes) {
            $elementCollection = $this->attributeModel->getAttributeCollectionByEntryId($this->collectionId, $this->termEntryDbId);
            $result = $this->createOrUpdateElement($this->attributes, $elementCollection, $this->mergeTerms);
            $this->attributeMerge->createOrUpdateAttribute($result);
            $this->attributes = [];
        }

        if ($this->terms) {
            $elementCollection = $this->termModel->getAllTermsByCollectionId($this->collectionId);
            $result = $this->createOrUpdateElement($this->terms, $elementCollection, $this->mergeTerms);
            $this->termMerge->createOrUpdateTerms($result);
            $this->terms = [];
        }

        if ($this->transacGrps) {
            $elementCollection = $this->transacGrpModel->getTransacGrpCollectionByEntryId($this->collectionId, $this->termEntryDbId);
            $result = $this->createOrUpdateElement($this->transacGrps, $elementCollection, $this->mergeTerms);
            $this->transacGrpMerge->createOrUpdateTransacGrp($result);
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
        $this->termEntryId = $this->getIdOrGenerate($termEntry, $this->tbxMap[$this::TBX_TERM_ENTRY]);
        $this->termEntryGuid = $this->getGuid();

        /** @var editor_Models_Terminology_TbxObjects_TermEntry $newEntry */
        $newEntry = new $this->termEntryObject;
        $newEntry->setCollectionId($this->collectionId);
        $newEntry->setTermEntryId($this->termEntryId);
        $newEntry->setEntryGuid($this->termEntryGuid);

        if ($termEntry->descrip) {
            $newEntry->setDescrip($this->setAttributeTypes($termEntry->descrip, false));
        }

        // Before setting first attribute we retrieve actual termEntryDbId fom table
        /** @var editor_Models_Terminology_Models_TermEntryModel $actualTermEntry */
        $this->termEntryDbId = $this->termEntryMerge->createOrUpdateTermEntry($newEntry, $this->termEntriesCollection);

        if ($termEntry->descrip) {
            $newEntry->setDescrip($this->setAttributeTypes($termEntry->descrip));
            $newEntry->setDescripValue((string)$termEntry->descrip);
        }
        if (isset($transacGrp)) {
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

        // OVO SE JOŠ NE SPREMA
        foreach ($languageGroup->descripGrp as $descripGrp) {
            $this->descripGrpGuid = $this->getGuid();
            $newLangSet->setDescripGrpGuid($this->descripGrpGuid);
            $newLangSet->setDescrip((string)$descripGrp->descrip);
            $newLangSet->setDescripTarget((string)$descripGrp->descrip->attributes()->{'target'});
            $newLangSet->setDescripType((string)$descripGrp->descrip->attributes()->{'type'});
            if (isset($languageGroup->descripGrp->transacGrp)) {
                $newLangSet->setDescripGrp($this->setTransacAttributes($languageGroup->descripGrp->transacGrp, true, 'langSet'));
            }
        }

        return $newLangSet;
    }

    /**
     * Iterate over the term structure and call handler for each element.
     * There will be parsed all child elements and returns term as object.
     * Elements - term, termNote, transacGrp, transacNote, admin
     * @param SimpleXMLElement $tig
     * @param editor_Models_Terminology_TbxObjects_Langset $parsedLangSet
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    private function handleTermGroup(SimpleXMLElement $tig, editor_Models_Terminology_TbxObjects_Langset $parsedLangSet): editor_Models_Terminology_TbxObjects_Term
    {
        /** @var editor_Models_Terminology_TbxObjects_Term $newTerm */
        $newTerm = new $this->termObject;
        $newTerm->setTermId($this->getIdOrGenerate($tig->term, $this->tbxMap[$this::TBX_TIG]));
        $newTerm->setCollectionId($this->collectionId);
        $newTerm->setEntryId($this->termEntryDbId);
        $newTerm->setTermEntryGuid($this->termEntryGuid);
        $newTerm->setLangSetGuid($this->langSetGuid);
        $newTerm->setGuid($this->getGuid());
        $newTerm->setLanguage($this->language['language']);
        $newTerm->setLanguageId((int)$this->language['id']);
        $newTerm->setTerm((string)$tig->term);
        $newTerm->setDescrip($parsedLangSet->getDescrip());
        $newTerm->setDescripTarget($parsedLangSet->getDescripTarget());
        $newTerm->setDescripType($parsedLangSet->getDescripType());
        $newTerm->setUserGuid($this->user->getUserGuid());
        $newTerm->setUserName($this->user->getUserName());

        $this->termId = $newTerm->getTermId();

        if (strtolower($parsedLangSet->getDescripType()) === $newTerm::TERM_DEFINITION) {
            $newTerm->setDefinition($parsedLangSet->getDescrip());
        }

        if (isset($tig->termNote)) {
            $newTerm->setTermNote($this->setAttributeTypes($tig->termNote));
            $newTerm->setStatus($this->getTermNoteStatus($newTerm->getTermNote()));
            $newTerm->setProcessStatus($this->getProcessStatus($newTerm->getTermNote()));
        } else {
            $newTerm->setStatus($this->config->runtimeOptions->tbx->defaultTermStatus);
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
            $newTerm->setTransacGrp($this->setTransacAttributes($tig->transacGrp, false, 'tig'));
        }
        $this->terms[] = $newTerm;

        return $newTerm;
    }

    /**
     * Prepare all Elements for Attribute table
     * Elements - termNote, descrip, transacNote, admin, note
     * @param SimpleXMLElement $element
     * @param bool $addToAttributesCollection
     * @return array
     */
    private function setAttributeTypes(SimpleXMLElement $element, bool $addToAttributesCollection = true): array
    {
        if (!isset($this->language['language'])) {
            $this->language['language'] = 'none';
        }
        $attributes = [];
        foreach ($element as $key => $value) {
            /** @var editor_Models_Terminology_TbxObjects_Attribute $attribute */
            $attribute = new $this->attributesObject;
            $attribute->setElementName($key);
            $attribute->setCollectionId($this->collectionId);
            $attribute->setEntryId($this->termEntryDbId);
            $attribute->setTermEntryGuid($this->termEntryGuid);
            $attribute->setLangSetGuid($this->langSetGuid);
            $attribute->setTermId($this->termId);
            $attribute->setGuid($this->getGuid());
            $attribute->setLanguage($this->language['language']);
            $attribute->setValue((string)$value);
            $attribute->setType((string)$value->attributes()->{'type'});
            $attribute->setTarget((string)$value->attributes()->{'target'});
            $attributes[] = $attribute;
            if ($addToAttributesCollection) {
                $this->attributes[] = $attribute;
            }
        }

        return $attributes;
    }

    /**
     * @param SimpleXMLElement $transacGrps
     * @param bool $ifDescripGrp
     * @param string $elementName
     * @return array
     */
    private function setTransacAttributes(SimpleXMLElement $transacGrps, bool $ifDescripGrp, string $elementName): array
    {
        $parsedTransacGrp = [];
        foreach ($transacGrps as $key => $transacGrp) {
            /** @var editor_Models_Terminology_TbxObjects_TransacGrp $transacGrpObject */
            $transacGrpObject = new $this->transacGrpObject;
            $transacGrpObject->setCollectionId($this->collectionId);
            $transacGrpObject->setEntryId($this->termEntryDbId);
            $transacGrpObject->setTermId($this->termId);
            $transacGrpObject->setTermEntryGuid($this->termEntryGuid);
            $transacGrpObject->setLangSetGuid($this->langSetGuid);
            $transacGrpObject->setDescripGrpGuid($this->descripGrpGuid);
            $transacGrpObject->setGuid($this->getGuid());
            $transacGrpObject->setElementName($elementName);
            $transacGrpObject->setLanguage($this->language['language']);
            $transacGrpObject->setAdminValue('');
            $transacGrpObject->setAdminType('');

            if (isset($transacGrp->transac)) {
                $transacGrpObject->setTransac((string)$transacGrp->transac);
            }
            if (isset($transacGrp->date)) {
                $transacGrpObject->setDate((string)$transacGrp->date);
            }
            if (isset($transacGrp->transacNote)) {
                $transacGrpObject->setTransacType((string)$transacGrp->transacNote->attributes()->{'type'});
                $transacGrpObject->setTransacNote((string)$transacGrp->transacNote);
            }

            $transacGrpObject->setIfDescripGrp((int)$ifDescripGrp);
            $parsedTransacGrp[] = $transacGrpObject;
            $this->transacGrps[] = $transacGrpObject;
        }

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
     * log all unknown languages in DB
     */
    private function logUnknownLanguages()
    {
        foreach ($this->unknownLanguages as $key => $language) {
            $this->log("Unable to import terms in this language set. Invalid Rfc5646 language code. Language code:" . $key);
        }
    }
    /**
     * - $this->getIdOrGenerate($elementName, 'term')
     * this will return 'id' attribute as string from given element,
     * if element dont have 'id' attribute we will generate one.
     *
     * @param SimpleXMLElement $xmlElement
     * @param string $map
     * @return string
     */
    private function getIdOrGenerate(SimpleXMLElement $xmlElement, string $map): string
    {
        return (string)$xmlElement->attributes()->{'id'} ?: $this->getUniqueId($map . '_');
    }

    /**
     * @param $prefixType
     * @return string
     */
    private function getUniqueId($prefixType): string
    {
        // ToDo: Sinisa, mit Alex besprechen welcher wert uebergeben werden soll
        return uniqid($prefixType);
    }

    /**
     * @return string
     */
    private function getGuid(): string
    {
        $set_uuid = ZfExtended_Utils::guid();

        return trim($set_uuid, '{}');
    }

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
     * returns the translate5 internal available term status to the one given in TBX
     * @param array $termNotes
     * @return string
     */
    protected function getTermNoteStatus(array $termNotes) : string
    {
        /** @var editor_Models_Terminology_TbxObjects_Attribute $attribute */
        foreach ($termNotes[0] as $attribute) {
            $tbxStatus = $attribute->getValue();
            $tbxType = $attribute->getType();

            //if current termNote is no starttag or type is not allowed to provide a status the we jump out
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
