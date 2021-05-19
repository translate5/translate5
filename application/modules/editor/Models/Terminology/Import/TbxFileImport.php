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

    /** @var editor_Models_Terminology_Models_ImagesModel */
    protected editor_Models_Terminology_Models_ImagesModel $tbxImagesModel;

    /** @var editor_Models_Terminology_Models_AttributeDataType */
    protected editor_Models_Terminology_Models_AttributeDataType $attributeLabelModel;

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
    protected string $termTigId;
    /**
     * file path for collection
     * @var string
     */
    protected string $tbxFilePath;
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
     * $termEntriesCollection['collectionId-groupId' => 'id-isProposal-descrip'];
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
     * All images from terms_images to check if image isset.
     * @var array
     */
    protected array $tbxImagesCollection;
    /**
     * Collection of attributes (note, ref, xref, descrip...) as object prepared for insert or update.
     * @var array
     */
    protected array $attributes;
    /**
     * Collection of attributeLabels for attribute mapping
     * @var array
     */
    protected array $attributeLabel;
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
     * Collection of images from <back> as object prepared for insert or update.
     * @var array
     */
    protected array $tbxImages;
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
    /** @var editor_Models_Terminology_TbxObjects_Image|mixed  */
    protected editor_Models_Terminology_TbxObjects_Image $tbxImageObject;

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
        $this->attributeLabelModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeDataType');
        $this->transacGrpModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');
        $this->tbxImagesModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_ImagesModel');

        $this->termEntryObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_TermEntry');
        $this->langsetObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_Langset');
        $this->termObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_Term');
        $this->attributesObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_Attribute');
        $this->transacGrpObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_TransacGrp');
        $this->tbxImageObject = ZfExtended_Factory::get('editor_Models_Terminology_TbxObjects_Image');

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
        $termEntries = [];
        $this->tbxFilePath = $tbxFilePath;

        try {
            $tbxAsSimpleXml = new SimpleXMLElement(file_get_contents($tbxFilePath), LIBXML_NOERROR);
        } catch (Exception $e) {
            throw new Zend_Exception('TBX file can not be opened.');
        }

        if ($tbxAsSimpleXml) {
            $this->prepareImportArrays($collection, $user, $mergeTerms);
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
        $this->attributeLabel = [];

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

        // get custom attribute label text and prepare array to check if custom label text exist.
        $attributeLabels = $this->attributeLabelModel->loadAllTranslated();
        foreach ($attributeLabels as $attributeLabel) {
            $this->attributeLabel[$attributeLabel['label'].'-'.$attributeLabel['type']] = $attributeLabel['id'];
        }

        if ($mergeTerms) {
            $this->termEntriesCollection = $this->termEntryModel->getAllTermEntryAndCollection($this->collectionId);
            $this->attributesCollection = $this->attributeModel->getAttributeByCollectionId($this->collectionId);
            $this->termCollection = $this->termModel->getAllTermsByCollectionId($this->collectionId);
            $this->transacGrpCollection = $this->transacGrpModel->getTransacGrpByCollectionId($this->collectionId);
            $this->tbxImagesCollection = $this->tbxImagesModel->getAllImagesByCollectionId($this->collectionId);
        } else {
            $this->termEntriesCollection = [];
            $this->termCollection = [];
            $this->attributesCollection = [];
            $this->transacGrpCollection = [];
            $this->tbxImagesCollection = [];
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
        $this->tbxMap[$this::TBX_TERM_ENTRY] = $tbxAsSimpleXml->text->body->termEntry ? 'termEntry' : 'conceptEntry';

        foreach ($tbxAsSimpleXml->text->body->{$this->tbxMap[$this::TBX_TERM_ENTRY]} as $termEntry) {
            $parsedEntry = null;
            $this->emptyVariables();
            $parsedEntry = $this->handleTermEntry($termEntry);
            foreach ($termEntry->{$this->tbxMap[$this::TBX_LANGSET]} as $languageGroup) {
                $this->langSetGuid = null;
                $this->language = $this->checkIfLanguageIsProceed($languageGroup);

                if (isset($this->language['language'])) {
                    $parsedLangSet = null;
                    $parsedLangSet = $this->handleLanguageGroup($languageGroup, $parsedEntry);

                    foreach ($languageGroup->{$this->tbxMap[$this::TBX_TIG]} as $termGroup) {
                        $this->termId = null;
                        $this->termGuid = null;
                        $this->handleTermGroup($termGroup, $parsedLangSet);
                        $this->termId = null;
                        $this->termGuid = null;
                    }
                }
            }
            $this->saveParsedTbx();
        }

        if ($tbxAsSimpleXml->text->back->refObjectList) {
            $this->getTbxImages($tbxAsSimpleXml->text->back->refObjectList);
            $this->getTbxRespUserBack($tbxAsSimpleXml->text->back->refObjectList);
        }

        if ($this->unknownLanguages) {
            $this->logUnknownLanguages();
        }

        $termSelect = $this->termModel->updateAttributeAndTransacTermIdAfterImport($this->collectionId);

        return $this->attributes;
    }

    private function emptyVariables()
    {
        $this->termEntryDbId = 0;
        $this->termEntryTbxId = '';
        $this->termEntryGuid = null;
        $this->langSetGuid = null;
        $this->descripGrpGuid = null;
        $this->termGuid = null;
        $this->transacGrps = [];
        $this->termId = null;
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

//        $allObjectsFromTbxElement = get_object_vars($termEntry);
//        foreach($termEntry->rows->row as $name => $row)
//        {
//            if (!$name->{$name}) {
////                Store unknown element to DB
//            }
//        }

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

        foreach ($languageGroup->descripGrp as $descripGrp) {
            $this->descripGrpGuid = $this->getGuid();
            $this->setAttributeTypes($descripGrp->descrip);
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
        $newTerm->setTermTbxId($this->getIdOrGenerate($tig->term, $this->tbxMap[$this::TBX_TIG]));
        $newTerm->setCollectionId($this->collectionId);
        $newTerm->setTermEntryId($this->termEntryDbId);
        $newTerm->setTermEntryTbxId($this->termEntryTbxId);
        $newTerm->setTermEntryGuid($this->termEntryGuid);
        $newTerm->setLangSetGuid($this->langSetGuid);
        $newTerm->setGuid($this->termGuid);
        $newTerm->setLanguage($this->language['language']);
        $newTerm->setLanguageId((int)$this->language['id']);
        $newTerm->setTerm((string)$tig->term);
        $newTerm->setDescrip($parsedLangSet->getDescrip());
        $newTerm->setDescripTarget($parsedLangSet->getDescripTarget());
        $newTerm->setDescripType($parsedLangSet->getDescripType());
        $newTerm->setUserGuid($this->user->getUserGuid());
        $newTerm->setUserName($this->user->getUserName());
        $newTerm->setCreated(NOW_ISO);

        $this->termTigId = $newTerm->getTermTbxId();

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
            $this->language['language'] = null;
        }
        $attributes = [];
        /** @var SimpleXMLElement $value */
        foreach ($element as $key => $value) {
            /** @var editor_Models_Terminology_TbxObjects_Attribute $attribute */
            $attribute = new $this->attributesObject;
            $attribute->setElementName($key);
            $attribute->setCollectionId($this->collectionId);
            $attribute->setTermEntryId($this->termEntryDbId);
            $attribute->setTermEntryGuid($this->termEntryGuid);
            $attribute->setLangSetGuid($this->langSetGuid);
            $attribute->setTermGuid($this->termGuid);
            $attribute->setGuid($this->getGuid());
            $attribute->setLanguage($this->language['language']);
            $attribute->setValue((string)$value);
            $attribute->setType((string)$value->attributes()->{'type'});
            $attribute->setTarget((string)$value->attributes()->{'target'});
//            $attribute->setAttrLang($this->getActualLanguageAttribute($value));
            $attribute->setAttrLang($this->language['language'] ? $this->language['language'] : '');
            $attribute->setUserGuid($this->user->getUserGuid());
            $attribute->setUserName($this->user->getUserName());
            $attribute->setCreated(NOW_ISO);

            if (isset($this->attributeLabel[$attribute->getElementName().'-'.$attribute->getType()])) {
                $attribute->setDataTypeId($this->attributeLabel[$attribute->getElementName().'-'.$attribute->getType()]);
            } else {
                $this->attributeLabelModel->loadOrCreate($attribute->getElementName(), $attribute->getType());
                $attributeLabels = $this->attributeLabelModel->loadAllTranslated();
                foreach ($attributeLabels as $attributeLabel) {
                    $this->attributeLabel[$attributeLabel['label'].'-'.$attributeLabel['type']] = $attributeLabel['id'];
                }
                $attribute->setDataTypeId($this->attributeLabel[$attribute->getElementName().'-'.$attribute->getType()]);
            }
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
            $transacGrpObject->setTermEntryId($this->termEntryDbId);
            $transacGrpObject->setTermId($this->termId);
            $transacGrpObject->setTermGuid($this->termGuid);
            $transacGrpObject->setTermEntryGuid($this->termEntryGuid);
            $transacGrpObject->setLangSetGuid($this->langSetGuid);
            $transacGrpObject->setDescripGrpGuid($this->descripGrpGuid);
            $transacGrpObject->setGuid($this->getGuid());
            $transacGrpObject->setElementName($elementName);
            $transacGrpObject->setLanguage($this->language['language']);
            $transacGrpObject->setAttrLang($this->getActualLanguageAttribute($transacGrp));

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
     * @param SimpleXMLElement $element
     * @return array
     */
    private function getTbxImages(SimpleXMLElement $element): array
    {
        $parsedTbxImages = [];
        $tbxImagesAsObject = [];
        /** @var SimpleXMLElement $refObjectList */
        foreach ($element as $refObjectList) {
            if ((string)$refObjectList->attributes()->{'type'} === 'binaryData') {
                $count = 0;
                /** @var SimpleXMLElement $refObject */
                foreach ($refObjectList as $refObject) {
                    $parsedTbxImages[$count]['id'] = (string)$refObject->attributes()->{'id'};
                    foreach ($refObject->item as $key => $item) {
                        $parsedTbxImages[$count][(string)$item->attributes()->{'type'}] = (string)$item;
                    }
                    /** @var editor_Models_Terminology_TbxObjects_Image $tbxImage */
                    $tbxImage = new $this->tbxImageObject;
                    $tbxImage->setTargetId((string)$refObject->attributes()->{'id'});
//                    $tbxImage->setName($parsedTbxImages[$count]['name']);
//                    $tbxImage->setEncoding($parsedTbxImages[$count]['encoding']);

                    if (isset($parsedTbxImages[$count]['encoding'])) {
                        $tbxImage->setName($parsedTbxImages[$count]['name']);
                        $tbxImage->setEncoding($parsedTbxImages[$count]['encoding']);
                    } else {
                        $tbxImage->setName((string)$refObject->attributes()->{'id'}.'.'.$parsedTbxImages[$count]['format']);
                        $tbxImage->setEncoding($parsedTbxImages[$count]['codePage']);
                    }

                    $tbxImage->setFormat($parsedTbxImages[$count]['format']);
//                    $tbxImage->setXbase('');
                    $tbxImage->setHexOrXbaseValue($parsedTbxImages[$count]['data']);

                    $tbxImage->setCollectionId($this->collectionId);
                    $tbxImagesAsObject[] = $tbxImage;
                    $count++;

                    if ($tbxImagesAsObject) {
                        foreach ($tbxImagesAsObject as $image) {
                            $hexOrXbaseWithoutSpace = str_replace(' ', '', $image->getHexOrXbaseValue());
                            if ($image->getEncoding() === 'hex') {
                                # convert the hex string to binary
                                $img = hex2bin($hexOrXbaseWithoutSpace);
                            } else {
                                # convert the base64 string to binary
                                $img = base64_decode($hexOrXbaseWithoutSpace);
                            }

                            $image->setHexOrXbaseValue('');
                            $this->saveFileLocal($this->tbxFilePath, $image->getName(), $img);
                        }

                        $this->createOrUpdateElement($this->tbxImagesModel, $tbxImagesAsObject, $this->tbxImagesCollection, $this->mergeTerms);
                        $tbxImagesAsObject = [];
                    }
                }
            }
        }

        return $tbxImagesAsObject;
    }

    /**
     * @param SimpleXMLElement $element
     * @return array
     */
    private function getTbxRespUserBack(SimpleXMLElement $element): array
    {
        $parsedTbxRespUser = [];
        /** @var SimpleXMLElement $refObjectList */
        foreach ($element as $refObjectList) {
            if ((string)$refObjectList->attributes()->{'type'} === 'respPerson') {
                $count = 0;
                /** @var SimpleXMLElement $refObject */
                foreach ($refObjectList as $refObject) {
                    $parsedTbxRespUser[$count]['target'] = (string)$refObject->attributes()->{'id'};
                    foreach ($refObject->item as $key => $item) {
                        $parsedTbxRespUser[$count][(string)$item->attributes()->{'type'}] = (string)$item;
                    }
                    $count++;
                }
            }
        }

        // ToDo: Sinisa, Update user in table... define how and what is mean in: TRANSLATE-1274
        $testUsers = $parsedTbxRespUser;

        return $parsedTbxRespUser;
    }

    /**
     * log all unknown languages in DB table ZF_errorlog
     */
    private function logUnknownLanguages()
    {
        foreach ($this->unknownLanguages as $key => $language) {
            $this->log("Unable to import terms in this language set. Invalid Rfc5646 language code. Language code: " . $key);
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


    /***
     * Save the imported file to the disk.
     * The file location will be "trasnalte5 parh" /data/tbx-import/tbx-for-filesystem-import/tc_"collectionId"/the file"
     *
     * @param string $filepath: source file location
     * @param string|null $name: source file name
     */
    private function saveFileLocal(string $filepath, string $name, $image)
    {
        $tbxImportDirectoryPath = APPLICATION_PATH.'/../data/tbx-import/';
        $newFilePath = $tbxImportDirectoryPath.'tbx-for-filesystem-import/tc_'.$this->collectionId.'/images';

        //check if the directory exist and it is writable
        if (is_dir($tbxImportDirectoryPath) && !is_writable($tbxImportDirectoryPath)) {
            $this->log("Unable to save the tbx file to the tbx import path. The file is not writable. Import path: ".$tbxImportDirectoryPath." , termcollectionId: ".$this->collectionId);
            return;
        }

        try {
            if (!file_exists($newFilePath) && !@mkdir($newFilePath, 0777, true)) {
                $this->log("Unable to create directory for imported tbx files. Directory path: ".$newFilePath." , termcollectionId: ".$this->collectionId);
                return;
            }
        } catch (Exception $e) {
            $this->log("Unable to create directory for imported image files. Directory path: ".$newFilePath." , termcollectionId: ".$this->collectionId);
            return;
        }

        $newFileName = $newFilePath.'/'.$name;

        file_put_contents($newFileName, $image);
    }
}
