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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Collect the terms and the terms attributes from the tbx file and save them to the database
 *
 */
class editor_Models_Import_TermListParser_Tbx implements editor_Models_Import_IMetaDataImporter {
    const TBX_ARCHIV_NAME = 'terminology.tbx';
    
    /**
     * @var XmlReader
     */
    protected $xml;

    /**
     * @var editor_Models_Task
     */
    protected $task;

    /**
     * @var editor_Models_Term
     */
    protected $term;

    /**
     * TermEntry ID des aktuell bearbeiteten Term Entry
     * @var string
     */
    protected $actualTermEntry;

   /**
     * Language string ID des aktuell bearbeiteten langSet Tags
     * @var string
     */
    protected $actualLang;

    /**
     * ermittelte interne Language ID des aktuell bearbeiteten langSet Tags
     * @var string
     */
    protected $actualLangId;

    /**
     * Term Definition des aktuellen langSet Tags
     * @var string
     */
    protected $actualDefinition='';

    /**
     * @var array
     */
    protected $languages = array();

    /**
     * @var array
     */
    protected $processedLanguages = array();

    /**
     * Das Array beinhaltet eine Zuordnung der in TBX möglichen Term Stati zu den im Editor verwendeten
     * @var array
     */
    protected $statusMap = array(
        'preferredTerm' => editor_Models_Term::STAT_PREFERRED,
        'admittedTerm' => editor_Models_Term::STAT_ADMITTED,
        'legalTerm' => editor_Models_Term::STAT_LEGAL,
        'regulatedTerm' => editor_Models_Term::STAT_REGULATED,
        'standardizedTerm' => editor_Models_Term::STAT_STANDARDIZED,
        'deprecatedTerm' => editor_Models_Term::STAT_DEPRECATED,
        'supersededTerm' => editor_Models_Term::STAT_SUPERSEDED,
            
        //some more states (uncomplete!), see TRANSLATE-714
        'proposed' => editor_Models_Term::STAT_PREFERRED,
        'deprecated' => editor_Models_Term::STAT_DEPRECATED,
        'admitted' => editor_Models_Term::STAT_ADMITTED,
    );
    
    /**
     * collected term states not listed in statusMap 
     * @var array
     */
    protected $unknownStates = array();
    
    protected $counterTermEntry = 0;
    protected $counterTig = 0;
    protected $counterTigInLangSet = 0;
    protected $counterTerm = 0;
    protected $counterTermInTig = 0;
    
    
    /***
     * Term collection id
     * 
     * @var integer
     */
    public $termCollectionId;
    
    /***
     * The actual term entry id from the lek_term_entry table
     * 
     * @var int
     */
    protected $actualTermEntryIdDb;

    /***
     * Id of the last inserted attribute
     * 
     * @var mixed
     */
    protected $actualParentId;
    
    /***
     * Id of the actuel db term record
     * 
     * @var mixed
     */
    protected $actualTermIdDb;
    
    /***
     * Id of the actual tbx id of term
     * @var string
     */
    protected $actualTermIdTbx;
    
    
    /**
     * Will be set in first <termEntry> of the tbx-file.
     * Detects if ids should be added to the termEntries or not
     * @var boolean
     */
    protected $addTermEntryIds = true;
    
    /**
     * Will be set in first <term> of the tbx-file.
     * Detects if ids should be added to the terms or not
     * @var boolean
     */
    protected $addTermIds = true;
    
    /***
     * The customer of the term collection
     * 
     * @var mixed
     */
    public $customerId=null;
    
    /***
     * if the current node is inside ntig
     * 
     * @var boolean
     */
    private $isInsideTig=false;
    
    
    /***
     * if the current node is inside desciption group
     * @var string
     */
    private $isInsideDescripGrp=false;
    
    /***
     * Is the current active termEntry exist in the current collection
     * 
     * @var string
     */
    private $isExistingTermEntry=false;
    
    /***
     * Flag if the unfounded terms in the termCollection should be merged.
     * If the parameter is true, translate5 will search all existing terms to see if the same term already exists in the TermCollection in the same language
     * If the parameter is false, a new term is added with the termEntry ID and term ID from the TBX in the TermCollection
     * @var boolean
     */
    public $mergeTerms=false;
    
    /***
     * Collection of terms who will be merged at the tbx termEntry change
     * @var array
     */
    private $termsContainer=array();
    
    /***
     * Collection of all inserted or updated term attributes
     * @var array
     */
    private $termAttirbuteContainer=array();
    
    /***
     * Collection of all inserted or updated term entry attributes
     * @var array
     */
    private $termEntryAttributeContainer=array();
    
    /***
     * Term entry id from the database of the last merged term 
     * @var integer
     */
    private $lastMergeTermEntryIdDb;
    
    /***
     * Term entry id from the tbx of the last merged term
     * 
     * @var string
     */
    private $lastMergeTermEntryId;
    
    
    /***
     * Count the note tag in each level
     * @var array
     */
    private $noteLevelCount=[
            'termEntry'=>0,
            'transacGrp'=>0,
            'langSet'=>0,
            'descripGrp'=>0,
            'tig'=>0
    ];
    
    /***
     * Array of actual tag parent tree
     * @var array
     */
    private $actualLevel=array();
    
    
    /***
     * Resource import source. It is used to make the difference between filesystem import and crossapi import
     * @var string
     */
    public $importSource="";
    
    public function __construct() {
        if(!defined('LIBXML_VERSION') || LIBXML_VERSION < '20620') {
            //Mindestversion siehe http://www.php.net/manual/de/xmlreader.readstring.php
            throw new Zend_Exception('LIBXML_VERSION must be at least 2.6.20 (or as integer 20620).');
        }
    }

    /**
     * Imports only the first TBX file found!
     * (non-PHPdoc)
     * @see editor_Models_Import_IMetaDataImporter::import()
     */
    public function import(editor_Models_Task $task, editor_Models_Import_MetaData $meta){
        $tbxFilterRegex = '/\.tbx$/i';
        $tbxfiles = $meta->getMetaFileToImport($tbxFilterRegex);
        if(empty($tbxfiles)){
            return;
        }
        
        $this->task = $task;

        //create term collection for the task and customer
        $termCollectionId=$this->createTermCollection($this->customerId);
        //add termcollection to task assoc
        $model=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $model editor_Models_TermCollection_TermCollection */
        $model->addTermCollectionTaskAssoc($termCollectionId, $task->getTaskGuid());
        
        //all tbx files in the same term collection
        /* @var $importer editor_Models_Import_TermListParser_Tbx */
        foreach($tbxfiles as $file) {
            
            if(! $file->isReadable()){
                throw new ZfExtended_Exception($file.' is not Readable!');
            }
            
            $this->task->setTerminologie(1);
            
            //languages welche aus dem TBX importiert werden sollen
            $this->languages[$meta->getSourceLang()->getId()] = $this->normalizeLanguage($meta->getSourceLang()->getRfc5646());
            $this->languages[$meta->getTargetLang()->getId()] = $this->normalizeLanguage($meta->getTargetLang()->getRfc5646());
            
            //start with file parse
            $this->parseTbxFile([$file->getPathname()],$termCollectionId);
            
            //check if import languages are can be found in the tbx file
            if($this->validateTbxLanguages()){
                $this->assertTbxExists($this->task, new SplFileInfo(self::getTbxPath($this->task)));
            }
            
        }
        
        if(!empty($this->unknownStates)) {
            $this->log('TBX contains the following unknown term states: '.join(', ', $this->unknownStates));
        }
    }
    
    /***
     * Parse the tbx file and save the term, term attribute and term entry attribute in the database.
     * @param array $filePath : the path of the tbx files
     * @param mixed $termCollectionId : the database id of the term collection
     */
    public function parseTbxFile(array $filePath,$termCollectionId){
        //if something is wrong with the fileparse,
        try {
            foreach ($filePath as $path){
                
                $this->xml = new XmlReader();
                //$this->xml->open(self::getTbxPath($task));
                $this->xml->open($path, null, LIBXML_PARSEHUGE);
                
                $this->termCollectionId = $termCollectionId;
                
                //find the customer for this term collection
                $termCollection=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
                /* @var $termCollection editor_Models_TermCollection_TermCollection */
                $termCollection->load($this->termCollectionId);
                $this->customerId=$termCollection->getCustomerId();
                
                //Bis zum ersten TermEntry springen und alle TermEntries verarbeiten.
                while($this->fastForwardTo('termEntry')) {
                    $this->setActualLevel();
                    $this->handleTermEntry();
                }
                
                $this->xml->close();
                
                $this->saveFileLocal($path,$termCollection->getId());
            }
        }catch (Exception $e){
            error_log("Something went wrong with tbx file parsing. Error message:".$e->getMessage());
            return false;
        }
        
        return true;
    }
    
    /**
     * checks if the needed TBX file exists, otherwise recreate if from DB
     * @param editor_Models_Task $task
     * @param SplFileInfo $tbxPath
     */
    public function assertTbxExists(editor_Models_Task $task, SplFileInfo $tbxPath) {
        //fallback for recreation of TBX file:
        $term = ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        
        $export = ZfExtended_Factory::get('editor_Models_Export_Terminology_Tbx');
        /* @var $export editor_Models_Export_Terminology_Tbx */
        
        $tbxData = $term->export($task, $export);
        
        $meta = $task->meta();
        //ensure existence of the tbxHash field
        $meta->addMeta('tbxHash', $meta::META_TYPE_STRING, null, 'Contains the MD5 hash of the original imported TBX file before adding IDs', 36);
        
        $hash = md5($tbxData);
        $meta->setTbxHash($hash);
        $meta->save();
        
        
        file_put_contents($tbxPath, $tbxData);
        
        return $tbxData;
    }
    
    
    /**
     * returns the path to the archived TBX file
     * @param editor_Models_Task $task
     */
    public static function getTbxPath(editor_Models_Task $task) {
        return $task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR.self::TBX_ARCHIV_NAME;
    }
    
    
    /**
     * bewegt den Zeiger durch den Datenstrom bis zum ersten Tag mit dem angegebenen Namen
     * Gibt zurück ob ein entsprechender Tag gefunden wurde.
     * @param string $tagName
     * @return boolean
     */
    protected function fastForwardTo($tagName) {
        while ($this->xml->read() && $this->xml->name !== $tagName);
        return ($this->xml->name === $tagName);
    }

    /**
     * handle termEntry element
     */
    protected function handleTermEntry() {
        $this->isExistingTermEntry=false;
        if(!$this->isStartTag()) {
            return; // END Tag => raus
        }
        
        // check if aktu termEntry is empty self-closing tag
        if ($this->xml->isEmptyElement) {
            return;
        }
        
        // save actual termEntryId
        $this->actualTermEntry = $this->getIdTermEntry();            
        
        if(empty($this->actualTermEntry)) {
            $this->log('termEntry Tag without an ID found and ignored!');
            return;
        }
        
        $termEntry=ZfExtended_Factory::get('editor_Models_TermCollection_TermEntry');
        /* @var $termEntry editor_Models_TermCollection_TermEntry */
        //check if the termEntry exist in the current collection

        $existingEntry=$termEntry->getTermEntryByIdAndCollection($this->actualTermEntry,$this->termCollectionId);
        
        
        if($existingEntry && $existingEntry['id']>0){
            $this->isExistingTermEntry=true;
            $this->actualTermEntryIdDb=$existingEntry['id'];
        }else{
            //create term entry and get the id
            $this->actualTermEntryIdDb=$this->createTermEntryRecord();
        }
        
        $tmpParrentId=null;
        // handle all inner elements of termEntry
        while($this->xml->read() && $this->xml->name !== 'termEntry') {
            if($this->isIgnoreTag()){
                continue;
            }
            switch($this->xml->name) {
                case 'langSet':
                    $this->setActualLevel();
                    $this->counterTigInLangSet = 0;
                    $this->actualParentId=null;
                    $this->handleLanguage();
                    break;
                case 'descrip':
                    $this->handleDefinition(); //type="Definition"
                    $this->handleDescrip();
                    break;
                case 'transacGrp':
                    $this->setActualLevel();
                    $tmpParrentId=null;
                    break;
                case 'transac':
                    
                    if($this->isInsideTig){
                        $entry=$this->saveTermAttribute($this->actualParentId);
                    }else{
                        $entry=$this->saveEntryAttribute($this->actualParentId);
                    }
                    if($entry){
                        $tmpParrentId = $entry->getId();
                    }
                    break;
                case 'date':
                case 'transacNote':
                    $this->isInsideTig ? $this->saveTermAttribute($tmpParrentId) : $this->saveEntryAttribute($tmpParrentId);
                    break;
                case 'descripGrp':
                    $this->setActualLevel();
                    $this->isInsideDescripGrp=$this->isStartTag();
                    break;
                case 'termGrp':
                    $this->setActualLevel();
                    break;
                case 'tig':
                case 'ntig':
                    $this->counterTermInTig = 0;
                    $this->setActualLevel();
                    $this->handleTig();
                    break;
                case 'term':
                    $this->handleTerm();
                    break;
                case 'termNote':
                    $this->checkTermStatus();
                    $this->saveTermAttribute(null);
                    break;
                case 'admin':
                    $this->isInsideTig ? $this->saveTermAttribute(null) : $this->saveEntryAttribute(null);
                    break;
                case 'ref':
                    $this->handleRef($tmpParrentId);
                    break;
                case 'note':
                    $this->handleNote($tmpParrentId);
                    break;
                default:
                    $this->handleUnknown();
                    break;
            }
        }

        //post term entry
        if(!empty($this->termsContainer)){
            //true = in the current tbx termEntry set, one term is found for merging
            $isMerged=!empty($this->lastMergeTermEntryId);
            
            //get the termEntry id of the merged data and use it for the collectedData
            foreach ($this->termsContainer as $singleTerm){
                //set the termEntry -> groupId -> from tbx
                $singleTerm->setGroupId($isMerged ? $this->lastMergeTermEntryId : $this->actualTermEntry);
                //set the termEntryId -> if from database
                $singleTerm->setTermEntryId($isMerged ? $this->lastMergeTermEntryIdDb : $this->actualTermEntryIdDb);
                $singleTerm->save();
            }
            
            $this->termsContainer=array();

            //if the terms are merged, remove the new created termEntry, since all of the terms are merged in the existing termEntry.
            if($isMerged){
                $termEntry=ZfExtended_Factory::get('editor_Models_Db_TermCollection_TermEntry');
                /* @var $termEntry editor_Models_Db_TermCollection_TermEntry */
                $termEntry->delete(array('id = ?' => $this->actualTermEntryIdDb));
            }
        }
        
        $termEntryAttributes=ZfExtended_Factory::get('editor_Models_Db_TermCollection_TermEntryAttributes');
        /* @var $termEntryAttributes editor_Models_Db_TermCollection_TermEntryAttributes */
        $deleteParams=array();
        
        $deleteParams['termEntryId = ?']=$this->actualTermEntryIdDb;
        
        if(!empty($this->termEntryAttributeContainer)){
            $deleteParams['id NOT IN (?)']=$this->termEntryAttributeContainer;
        }
        
            //remove the old term entry attributes
        $termEntryAttributes->delete($deleteParams);
        
        $this->termEntryAttributeContainer=array();
        $this->lastMergeTermEntryId=null;
        $this->lastMergeTermEntryIdDb=null;
    }

    /**
     * Sprach Tag verarbeiten
     */
    protected function handleLanguage() {
        if($this->isEndTag()){
            $this->actualDefinition = '';
            $this->actualLang = null;
            $this->actualLangId=0;
        }
        
        if(! $this->isStartTag()) {
            return; // END oder anderer Tag => raus
        }
        
        // check if aktu langSet is empty self-closing tag
        if ($this->xml->isEmptyElement) {
            return;
        }
        
        $this->actualLang = $this->xml->getAttribute('xml:lang');
        if(empty($this->actualLang)) {
            $this->actualLang = $this->xml->getAttribute('lang');
            if(empty($this->actualLang)) {
                $this->log('langSet Tag without an xml:lang found and ignored!');
            }
        }
        //if there is a task(import from 'task import') and the language should not be processed
        if($this->task && !$this->isLanguageToProcess()) {
            //bis zum Ende des aktuellen LangTags gehen.
            while($this->xml->read() && $this->xml->name !== 'langSet'){}
        }
        
        //If the actualLangId is not set in isLanguageToProcess -> try to set it from actualLang (language(langset tag) rfc value from the tbx file )
        if($this->actualLangId<1){
            $langModel=ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $langModel editor_Models_Languages */
            try {
                $langModel->loadByRfc5646($this->actualLang);
                $this->actualLangId=$langModel->getId();
            } catch (ZfExtended_Models_Entity_NotFoundException$e) {
                error_log("Unable to imprt terms in this language set. Invalid Rfc5646 language code. Language code:".$this->actualLang);
                while($this->xml->read() && $this->xml->name !== 'langSet'){}
            }
        }
    }

    /**
     * Die Methode implementiert folgenden Algorithmus
     *  - Sprachen die nicht verwendet werden ignorieren
     *  - Im Lektorat eingestellt:
     *   de => importiert de-de de de-at etc.pp.
     *   de-de => importiert de-de de
     *   restliche Sprachen ignorieren => return false
     * @return boolean
     */
    protected function isLanguageToProcess() {
        $langToImport = $this->normalizeLanguage($this->actualLang);
        $lastLangId;
        $this->actualLangId = 0;
        $matched = false;
        foreach($this->languages as $langString => $langAllowed) {
            if($matched) {
                $this->processedLanguages[$lastLangId] = 1;
                $this->actualLangId = $lastLangId;
                return true;
            }
            $compareFirstOnly = count($langToImport) == 1 || count($langAllowed) < 2;
            $matched = ($compareFirstOnly && $langAllowed[0] === $langToImport[0] || $langAllowed === $langToImport);
            $lastLangId = $langString;
        }
        if($matched) {
            $this->processedLanguages[$lastLangId] = 1;
            $this->actualLangId = $lastLangId;
        }
        return $matched;
    }

    /**
     * normalisiert den übergebenen Sprachstring für die interne Verwendung.
     * => strtolower
     * => trennt die per - oder _ getrennten Bestandteile in ein Array auf
     * @param string $langString
     * @return array
     */
    protected function normalizeLanguage($langString) {
        return explode('-',strtolower(str_replace('_','-',$langString)));
    }
    
    /**
     * Save the data to the lek terms table and set the actual term database id 
     */
    protected function handleTerm() {
        if(!$this->isStartTag()){
            //set the term status to defualt(from the config) if the status is empty/not set
            $this->handleEmptyTermStatus();
            $this->actualTermIdTbx=null;
            return;
        }
        
        // check if actual term is empty self-closing tag
        if ($this->xml->isEmptyElement) {
            return;
        }
        $this->actualTermIdTbx=$this->getIdTerm();
        $this->handleTermDb();
    }

    /**
     * Check if the termNote is of a type normativeAuthorization.
     * Update the status to the current term in the database.
     */
    protected function checkTermStatus() {
        $type = $this->xml->getAttribute('type');
        $config = Zend_Registry::get('config');
        $importMap = $config->runtimeOptions->tbx->termImportMap->toArray();
        $allowedTypes = ['normativeAuthorization', 'administrativeStatus'];
        //merge system allowed note types with configured ones:
        if(!empty($importMap)) {
            $allowedTypes = array_merge($allowedTypes, array_keys($importMap));
        }
        //if current termNote is no starttag or type is not allowed to provide a status the we jump out 
        if(!$this->isStartTag() || !in_array($type, $allowedTypes)){
          return;
        }
        $actualTermNoteStatus= $this->getMappedStatus($this->xml->readString(), $type);

        //update the term with the status
        $term = ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        
        try {
            $term->load($this->actualTermIdDb);
            $term->setStatus($actualTermNoteStatus);
            $term->setUpdated(date("Y-m-d H:i:s"));
            $term->save();
            return;
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            //if the term exist in the unsaved terms, update the status there
            if(isset($this->termsContainer[$this->actualTermIdTbx])){
                $term=$this->termsContainer[$this->actualTermIdTbx];
                $term->setStatus($actualTermNoteStatus);
                $term->setUpdated(date("Y-m-d H:i:s"));
                $term->save();
            }
        }
    }

    /**
     * returns the translate5 internal availabable term status to the one given in TBX
     * @param string $tbxStatus
     * @return string
     */
    protected function getMappedStatus($tbxStatus, $type) {
        //termNote type administrativeStatus are similar to normativeAuthorization, 
        // expect that the values have a suffix which must be removed
        if($type == 'administrativeStatus') {
            $tbxStatus = str_replace('-admn-sts$', '', $tbxStatus.'$');
        }
        
        //add configured status map
        $config = Zend_Registry::get('config');
        $importMap = $config->runtimeOptions->tbx->termImportMap->toArray();
        $statusMap = $this->statusMap;
        if(!empty($importMap[$type])) {
            $statusMap = array_merge($this->statusMap, $importMap[$type]);
        }
        
        if(!empty($statusMap[$tbxStatus])){
            return $statusMap[$tbxStatus];
        }
        
        if(!in_array($tbxStatus, $this->unknownStates)) {
            $this->unknownStates[] = $tbxStatus;
        }
        return $config->runtimeOptions->tbx->defaultTermStatus;
    }
    
    /**
     * returns the status map
     */
    public function getStatusMap() {
        return $this->statusMap;
    }

    /**
     * Extrahiert die Term Definition
     */
    protected function handleDefinition() {
        if(!$this->isStartTag() || !in_array($this->xml->getAttribute('type'), array('definition', 'Definition'))) {
            return;
        }
        
        // if <descrip> on <tig>-level, write term-definition direct into actualTig
        if ($this->xml->getAttribute('type') == 'definition') {
            $this->actualDefinition= $this->xml->readString();
            return;
        }
        
        if ($this->xml->getAttribute('type') == 'Definition') {
            $this->actualDefinition = $this->xml->readString();
        }
    }
    
    
    protected function handleDescrip() {
        if(!$this->isStartTag()){
            return;
        }
        $this->actualParentId=null;
        //insert descript
        if($this->isInsideTig){
            $entry=$this->saveTermAttribute($this->actualParentId);
        }else{
            $entry=$this->saveEntryAttribute($this->actualParentId);
        }

        //if inside description group, set the parent id from the current description tag
        if($this->isInsideDescripGrp){
            $this->actualParentId=$entry->getId();
        }
    }
    
    /***
     * Tig tag handler
     */
    protected function handleTig(){
        $this->isInsideTig=$this->isStartTag();
        
        if(!$this->isInsideTig){
            //remove unneeded term attributes
            $termAttributes=ZfExtended_Factory::get('editor_Models_Db_TermCollection_TermAttributes');
            /* @var $termAttributes editor_Models_Db_TermCollection_TermAttributes */
            
            $deleteParams=array();
            $deleteParams['termId = ?'] = $this->actualTermIdDb;
            
            //remove the old attribute
            if(!empty($this->termAttirbuteContainer)){
                $deleteParams['id NOT IN (?)'] = $this->termAttirbuteContainer;
            }
            
            $termAttributes->delete($deleteParams);
        }else{
            $this->counterTigInLangSet++;
        }
        
        
        $this->actualTermIdDb=null;
        $this->actualParentId=null;
        $this->termAttirbuteContainer=array();
    }
    
    protected function handleRef($tmpParrentId){
        if(!$this->isStartTag()){
            return;
        }
        $this->saveRefOrNote($tmpParrentId);
    }
    
    protected function handleNote($tmpParrentId){
        if(!$this->isStartTag()){
            return;
        }
        
        //increment the current note count in the level
        $this->noteLevelCount[end($this->actualLevel)]++;
        
        //get the current note count in the level
        $internalCount=$this->noteLevelCount[end($this->actualLevel)];
        
        $this->saveRefOrNote($tmpParrentId, $internalCount);
    }
    
    /***
     * Save ref attribute or note attribute 
     * 
     * @param int $tmpParrentId
     * @param int $internalCount
     */
    private function saveRefOrNote($tmpParrentId,$internalCount=null){
        //if yes, inside transacGrp
        if($tmpParrentId){
            $this->isInsideTig ? $this->saveTermAttribute($tmpParrentId,$internalCount) : $this->saveEntryAttribute($tmpParrentId,$internalCount);
            return;
        }
        
        //if inside description grp, use the actualParrentId (the decript id)
        if($this->isInsideDescripGrp){
            $this->isInsideTig ? $this->saveTermAttribute($this->actualParentId,$internalCount) : $this->saveEntryAttribute($this->actualParentId,$internalCount);
            return;
        }
        
        //the attribute is with null parentId, hold the old parent and save the attribute with null parent id
        $oldActualParentId=$this->actualParentId;
        $this->actualParentId = null;
        
        if($this->isInsideTig){
            $this->saveTermAttribute($this->actualParentId,$internalCount);
        }else{
            $this->saveEntryAttribute($this->actualParentId,$internalCount);
        }
        
        $this->actualParentId=$oldActualParentId;
    }
    
    /***
     * Save the unknown parameter to the database
     * @return boolean|void|editor_Models_TermCollection_TermEntryAttributes|boolean|editor_Models_TermCollection_TermEntryAttributes
     */
    protected function handleUnknown(){
        if(!$this->isStartTag()){
            return false;
        }
        
        error_log("Unsupported tag found during the tbx parsing:#Tag name:".$this->xml->name.'#Term collection id:'.$this->termCollectionId.'#');
    }
    
    /***
     * Check if the current term has empty status. If yes use the default term status from the config.
     */
    protected function handleEmptyTermStatus(){
        $term=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */


        //if the term exist, load it from the database
        if(!empty($this->actualTermIdDb)){
            $term->load($this->actualTermIdDb);
        }else if(isset($this->termsContainer[$this->actualTermIdTbx]) && !empty($this->termsContainer[$this->actualTermIdTbx])){
            //the term does not exis, check if it exist in the term container
            //the terms in the term container are not saved yet
            $term=$this->termsContainer[$this->actualTermIdTbx];
        }else{
            //the term is not found in the database, an not in the term container. Log the info
            error_log("TermCollection parser message: Unable to set the term status. The term does not exist so far. Collectionid: ".$this->termCollectionId);
            return;
        }
        
        //if the statzs is empty, set the default status from the zf config
        if(empty($term->getStatus())){
            $config = Zend_Registry::get('config');
            $term->setStatus($config->runtimeOptions->tbx->defaultTermStatus);

            //if actualTermIdDb is set -> the term exist in the database -> update
            //if actualTermIdDb is not set -> the term does not exist in the db, it is in the term container which will be updated later
            if(!empty($this->actualTermIdDb)){
                $term->save();
            }
        }
        
    }
    
    /***
     * Save term entry attribute in the database.
     * 
     * @param integer $parentId
     * @param int $internalCount: the current tag count of the same type in one group
     * 
     * @return boolean|editor_Models_TermCollection_TermEntryAttributes
     */
    protected function saveEntryAttribute($parentId,$internalCount=null){
        if(!$this->isStartTag()){
            return false;
        }
        $attribute=$this->getAttributeObject(false,$parentId);
        $attribute->setTermEntryId($this->actualTermEntryIdDb);
        $attribute->setInternalCount($internalCount);
        $attribute->saveOrUpdate();

        //add the inserted/update attribute to the collection
        $this->termEntryAttributeContainer[]=$attribute->getId();
        return $attribute;
    }
    
    /***
     * Save term attribute in the database
     * 
     * @param integer $parentId
     * @param int $internalCount: the current tag count of the same type in one group
     * 
     * @return void|editor_Models_TermCollection_TermEntryAttributes
     */
    protected function saveTermAttribute($parentId,$internalCount=null){
        if(!$this->isStartTag()){
            return;
        }
        $attribute=$this->getAttributeObject(true,$parentId);
        $attribute->setTermId($this->actualTermIdDb);
        $attribute->setInternalCount($internalCount);
        $attribute->saveOrUpdate();
        
        //add the inserted/update attribute to the collection
        $this->termAttirbuteContainer[]=$attribute->getId();
        return $attribute;
    }
    
    /***
     * Get the term attribute or term entry attribute model
     * 
     * @param boolean $isTermAttribute
     * @param mixed $parentId
     * 
     * @return editor_Models_TermCollection_TermEntryAttributes
     */
    protected function getAttributeObject($isTermAttribute,$parentId){
        $attribute=ZfExtended_Factory::get('editor_Models_TermCollection_TermEntryAttributes');
        if($isTermAttribute){
            $attribute=ZfExtended_Factory::get('editor_Models_TermCollection_TermAttributes');
        }
        
        $attribute->setCollectionId($this->termCollectionId);
        
        $attribute->setLanguage($this->actualLang);
        if(!$parentId){
            $parentId=$this->actualParentId;
        }
        $attribute->setParentId($parentId);
        
        $attrName=$this->xml->name;
        $attrType=$this->xml->getAttribute('type');
        
        $attribute->setName($attrName);
        
        //if it is transac without type use the value as type
        if($attrName==="transac"){
            $attrType = $this->xml->readInnerXml();
        }
        
        $attribute->setAttrType($attrType);
        
        $label=ZfExtended_Factory::get('editor_Models_TermCollection_TermAttributesLabel');
        /* @var $label editor_Models_TermCollection_TermAttributesLabel */
        $labelResult=$label->getLabelByNameAndType($this->xml->name,$attrType);
        
        //if the label is not found, insert a new label entry
        if(empty($labelResult)){
            $label->setLabel($attrName);
            $label->setType($attrType);
            $labelResult=$label->save();
            $attribute->setLabelId($labelResult);
        }else{
            $attribute->setLabelId($labelResult[0]['id']);
        }
        
        $attribute->setAttrDataType($this->xml->getAttribute('datatype'));
        $attribute->setAttrTarget($this->xml->getAttribute('target'));
        $attribute->setAttrId($this->xml->getAttribute('id'));
        
        //if for the attribute there is no xml:lang parameter, get the langSet language
        $xmlLang=$this->xml->getAttribute('xml:lang');
        if(!$xmlLang){
            $xmlLang=$this->actualLang;
        }
        
        $attribute->setAttrLang($xmlLang);
        
        //check if the string contains unneeded character
        $cleanValue=$this->checkValue($this->xml->readInnerXml());
        
        if($attrName =="date"){
            //handle the date format
            $now = new DateTime($cleanValue);
            $cleanValue= $now->format('U');
        }
        $attribute->setValue($cleanValue);
        return $attribute;
    }
    
    /***
       Set the actual tag tree.
       Example:
     	<termEntry>
		   <descrip>Description</descrip>
		   <ref>General</ref>
		   <note>Entry level</note>
		   <transacGrp>
			  <transac>creation</transac>
			  <date>2018-03-22</date>
			  <transacNote>Default Supervisor</transacNote>
			  <ref>General</ref>  <- this is the curent active node => actualLevel -> ('termEntry','transacGrp');
			  
			   
     */
    protected function setActualLevel(){
        if($this->isStartTag()){
            array_push($this->actualLevel, $this->xml->name);
            return;
        }
        $this->noteLevelCount[$this->xml->name]=0;
        unset($this->actualLevel[array_search($this->xml->name, $this->actualLevel)]);
    }
    
    /***
     * Ignore the tag of type figure.
     * TODO: if more tags need to be ignored, extend this!
     * @return boolean
     */
    protected function isIgnoreTag(){
        if($this->isStartTag()){
            return $this->xml->getAttribute('type')==="figure";
        }
        return false;
    }
    
    protected function isEndTag() {
        return ($this->xml->nodeType === XmlReader::END_ELEMENT);
    }

    protected function isStartTag() {
        return ($this->xml->nodeType === XmlReader::ELEMENT);
    }

    protected function log($logMessage) {
        $msg = $logMessage.'. Task: '.$this->task->getTaskGuid();
        /* @var $log ZfExtended_Log */
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        $log->logError($msg);
    }
    
    private function getIdTermEntry() {
        // detect on first call if IDs should be added
        if ($this->counterTermEntry == 0 && $this->addTermEntryIds && ! empty($this->xml->getAttribute('id'))) {
            $this->addTermEntryIds = false;
        }
        
        if ($this->addTermEntryIds == false) {
            return $this->xml->getAttribute('id');
        }
        
        $this->counterTermEntry++;
        
        return 'termEntry_' . str_pad($this->counterTermEntry, 7, '0', STR_PAD_LEFT);
    }

    /**
     * Generates a unic id for a term-element.
     * If autoIds is set to false and there is an id in the tbx-file this id is used
     *
     * @return string
     */
    private function getIdTerm() {
        // detect on first call if IDs should be added
        if ($this->counterTerm == 0 && $this->addTermIds && ! empty($this->xml->getAttribute('id'))) {
            $this->addTermIds = false;
        }
        
        if ($this->addTermIds == false) {
            return $this->xml->getAttribute('id');
        }
        
        $this->counterTermInTig++;
        $this->counterTerm++;
        
        $tempId = 'term'
                .'_'.str_pad($this->counterTermEntry, 7, '0', STR_PAD_LEFT)
                .'_'.str_pad($this->counterTigInLangSet, 3, '0', STR_PAD_LEFT)
                .'_'.$this->actualLang
                .'_'.str_pad($this->counterTermInTig, 3, '0', STR_PAD_LEFT)
                .'_'.str_pad($this->counterTerm, 7, '0', STR_PAD_LEFT);
        return $tempId;
    }
    
    /***
     * Create the term collection and return the id
     * @param mixed $customerId
     */
    private function createTermCollection($costumerId){
        $termCollection=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $termCollection editor_Models_TermCollection_TermCollection */
        
        if($costumerId){
            $termCollection->setCustomerId((integer)$costumerId);
        }
        $termCollection->setAutoCreatedOnImport(1);
        $termCollection->setName("Term Collection for ".$this->task->getTaskGuid());
        return $termCollection->save();
    }
    
    /***
     * Create a term entry record in the database, for the current collection and the
     * actual termEntryId
     *  
     * @return int
     */
    private function createTermEntryRecord(){
        //actualTermEntry
        $termEntry=ZfExtended_Factory::get('editor_Models_TermCollection_TermEntry');
        /* @var $termEntry editor_Models_TermCollection_TermEntry */
        $termEntry->setCollectionId($this->termCollectionId);
        $termEntry->setGroupId($this->actualTermEntry);
        $termEntry->save();
        return $termEntry->getId();
    }
    
    /***
     * Handle the current active term.
     * 1. update the term if:
     *    - same termEntryId (tbx)
     *    - same termId (tbx)
     *    - same collectionId 
     * 2: Get all terms with same termEntryId (tbx), same collectionId and different termId (tbx)    
     *       2a. Update the term if
     *          - same termEntryId(tbx)
     *          - same language
     *          - same term Text
     *    
     *       2b. Add new term if
     *          - no term from 2a is update
     * 3. Add new term if :
     *      - mergeTerms=false
     *    3a. Update the term if:
     *      - same collectionId
     *      - same language
     *      - same termText
     *    3b. Add new term(terms-all collected terms in the same term entry) if no matched terms from 3a  
     *        
     */
    private function handleTermDb(){
        $term=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        $terms=$term->isUpdateTermForCollection($this->actualTermEntry,$this->actualTermIdTbx,$this->termCollectionId);
        //if term is found(should return single row since termId is unique)
        if($terms->count()>0){
            foreach ($terms as $t){
                //update the term
                $t = (object) $t;
                //update the term
                $termModel=ZfExtended_Factory::get('editor_Models_Term');
                /* @var $termModel editor_Models_Term */
                $termModel->load($t->id);
                $termModel->setTerm($this->xml->readInnerXml());
                $termModel->setUpdated(date("Y-m-d H:i:s"));
                $termModel->save();
                $this->actualTermIdDb=$termModel->getId();
                return;
            }
        }
        //check if the term with the same termEntry,collection but different termId exist
        
        $tmpTermValue=$term->getRestTermsOfGroup($this->actualTermEntry, $this->actualTermIdTbx, $this->termCollectionId);

        $addNewTerm=$tmpTermValue->count()>0;
        if($addNewTerm){

            //foreach term in the db term entry, find if the current term has the same language and value
            foreach ($tmpTermValue as $t){
                $t = (object) $t;
                $checkCase=$t->language==$this->actualLangId;
                $checkCase=$checkCase && ($t->term==$this->xml->readInnerXml());
                //the groupId is already the same
                //$checkCase=$checkCase && ($t->groupId==$this->actualTermIdTbx);
                
                if($checkCase){
                    //update the term, so the timestamp is update, and the term entry attributes are updated to
                    $termModel=ZfExtended_Factory::get('editor_Models_Term');
                    /* @var $termModel editor_Models_Term */
                    $termModel->load($t->id);
                    $termModel->setDefinition($this->actualDefinition);
                    $termModel->setUpdated(date("Y-m-d H:i:s"));
                    $termModel->save();
                    $this->actualTermIdDb=$t->id;
                    $addNewTerm=false;
                    break;
                }
            }
            if(!$addNewTerm){
                return;
            }
            
        }
        
        if($this->mergeTerms){
            
            //check if the term text exist in the term collection within the language
            $tmpTermValue=$term->findTermInCollection($this->xml->readInnerXml(), $this->actualLangId, $this->termCollectionId);
            
            if($tmpTermValue && $tmpTermValue->count()>0){
                //the first term thus found is updated by the values ​​in the TBX file. 
                //The term-ID and termEntry-ID remain the same as they already existed in translate5.
                $tmpTermValue=$tmpTermValue->toArray();
                $tmpTermValue=$tmpTermValue[0];
                
                //update the term, so the timestamp is update, and the term entry attributes are updated to
                $termModel=ZfExtended_Factory::get('editor_Models_Term');
                /* @var $term editor_Models_Term */
                
                $termModel->load($tmpTermValue['id']);
                $termModel->setUpdated(date("Y-m-d H:i:s"));
                $termModel->save();
                $termModel->setDefinition($this->actualDefinition);
                $this->actualTermIdDb=$tmpTermValue['id'];
                
                if(!$this->lastMergeTermEntryId){
                    $this->lastMergeTermEntryIdDb=$termModel->getTermEntryId();
                    $this->lastMergeTermEntryId=$termModel->getGroupId();
                }
                
                return;
            }
            
            //if the term is no merged but his term entry exist in the database, add it as a new term
            if($addNewTerm){
                $this->saveTerm();
                return;
            }
            
            //save the term without termEntryId
            $term=ZfExtended_Factory::get('editor_Models_Term');
            /* @var $term editor_Models_Term */
            
            $term->setTerm($this->xml->readInnerXml());
            $term->setMid($this->actualTermIdTbx);
            //the status will be updated when is found from the termNote
            $term->setDefinition($this->actualDefinition);
            $term->setLanguage((integer)$this->actualLangId);
            $term->setCollectionId($this->termCollectionId);
            $term->setUpdated(date("Y-m-d H:i:s"));
            
            $this->actualTermIdDb=$term->save();

            //collect the term so later can be updated
            $this->termsContainer[$this->actualTermIdTbx]=$term;
            return;
        }
        //add new
        $this->saveTerm();
    }
    
    /***
     * Validate import langages against tbx languages
     * @return boolean
     */
    private function validateTbxLanguages(){
        
        $langs=array();
        $langs[$this->task->getSourceLang()]=$this->task->getSourceLang();
        $langs[$this->task->getTargetLang()]=$this->task->getTargetLang();
        
        $collection=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $collection editor_Models_TermCollection_TermCollection */
        $collLangs=$collection->getLanguagesInTermCollections(array($this->termCollectionId));
        
        //disable terminology when no terms for the term collection are available
        if(empty($collLangs)){
            error_log("Terminologie is disabled because no terms in the termcollection are found. TermcollectionId: ".$this->termCollectionId);
            $this->task->setTerminologie(0);
            return false;
        }
        
        $collLangKeys=array();
        
        foreach ($collLangs as $lng){
            $collLangKeys[$lng['id']]=$lng['id'];
        }
        
        //missing langs
        $notProcessed = array_diff(
            array_keys($langs),
            array_keys($collLangKeys));
        
        if(empty($notProcessed)) {
            return true;
        }

        $langsDb = array();
        foreach ($notProcessed as $value) {
            $langsDb[]= $langsDb[$value];
        }
        error_log('For the following languages no term has been found in the tbx file: '.implode(', ', $langsDb));
        $this->task->setTerminologie(0);
        return false;
    }
    
    /***
     * Save the term to the database from the current tbx data.
     * The actualTermidDb will be set.
     */
    private function saveTerm(){
        $term=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        
        $term->setTerm($this->xml->readInnerXml());
        $term->setMid($this->actualTermIdTbx);
        //the status will be updated when is found from the termNote
        $term->setDefinition($this->actualDefinition);
        $term->setGroupId($this->actualTermEntry);
        $term->setLanguage((integer)$this->actualLangId);
        $term->setCollectionId($this->termCollectionId);
        $term->setTermEntryId($this->actualTermEntryIdDb);
        $term->setUpdated(date("Y-m-d H:i:s"));
        $this->actualTermIdDb=$term->save();
    }
    
    /***
     * Save the imported file to the disk.
     * The file location will be "trasnalte5 parh" /data/tbx-import/tbx-for-filesystem-import/tc_"collectionId"/the file"
     * 
     * @param string $filepath: source file location
     * @param string $collectionId: termcollectin id
     */
    private function saveFileLocal($filepath,$collectionId) {
        
        //if import source is not defined save it in filesystem folder
        if(!$this->importSource){
            $this->importSource="filesystem";
        }
        
        $tbxImportDirectoryPath=APPLICATION_PATH.'/../data/tbx-import/';
        $newFilePath=$tbxImportDirectoryPath.'tbx-for-'.$this->importSource.'-import/tc_'.$collectionId;
        
        //check if the directory exist and it is writable
        if(is_dir($tbxImportDirectoryPath) && !is_writable($tbxImportDirectoryPath)){
            error_log("Unable to save the tbx file to the tbx import path. The file is not writable. Import path: ".$tbxImportDirectoryPath." , termcollectionId: ".$collectionId);
            return;
        }
        
        try {
            if(!file_exists($newFilePath) && !@mkdir($newFilePath, 0777, true)){
                error_log("Unable to create directory for imported tbx files. Directory path: ".$newFilePath." , termcollectionId: ".$collectionId);
                return;
            }
        } catch (Exception $e) {
            error_log("Unable to create directory for imported tbx files. Directory path: ".$newFilePath." , termcollectionId: ".$collectionId);
            return;
        }
        
        $fi = new FilesystemIterator($newFilePath, FilesystemIterator::SKIP_DOTS);
        
        $fileName=iterator_count($fi).'-'.basename($filepath);
        
        $newFileName=$newFilePath.'/'.$fileName;
        
        //copy the new file (rename probably not possible, if whole import folder is readonly in folder based imports)
        copy($filepath, $newFileName);
    }
    
    /***
     * Replace window alt+enter with unix linebreak
     * @param string $value
     * @return string
     */
    private function checkValue($value){
        $tempFunnyChars = array(
                [json_decode('"\uE70A"'), "\n"]
        );
        $replaceSpecialChars = function ($text, $chars) {
            foreach ($chars as $char) {
                $text = str_replace($char[0], $char[1], $text);
            }
            return $text;
        };
        return $replaceSpecialChars($value, $tempFunnyChars);
    }
    
    /***
     * Get filesystem imported collection directory
     */
    static public function getFilesystemCollectionDir(){
        return APPLICATION_PATH.'/../data/tbx-import/tbx-for-filesystem-import/';
    }
}
