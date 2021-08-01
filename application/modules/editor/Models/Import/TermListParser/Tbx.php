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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Collect the terms and the terms attributes from the tbx file and save them to the database
 *
 */
class editor_Models_Import_TermListParser_Tbx implements editor_Models_Import_MetaData_IMetaDataImporter {
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
     * The customers of the term collection
     *
     * @var array
     */
    public $customerIds=array();
    
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
    private $termsToMergeContainer=array();
    
    /***
     * Current term data. After the tig/ntig tag is closed, a new database term will be created out of this data
     * @var array
     */
    private $termContainer=[];
    
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
     * Term entry id from the tbx (groupId) of the last merged term
     *
     * @var string
     */
    private $lastMergeGroupId;
    
    
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
    
    /***
     * @var editor_Models_TermCollection_TermCollection
     */
    protected $termCollection;
    
    /***
     * @var $logger ZfExtended_Logger
     */
    protected $logger;
    
    /**
     * @var Zend_Config
     */
    protected $config;

	/***
     *
     * @var ZfExtended_Models_User
     */
    protected $user;
    
    /***
     * Term entry model instance (this is a helper instance)
     *
     * @var editor_Models_TermCollection_TermEntry
     */
    protected $termEntryModel;
    
    /***
     * Term model instance (this is a helper instance)
     *
     * @var editor_Models_Term
     */
    protected $termModel;
    
    public function __construct() {
        if(!defined('LIBXML_VERSION') || LIBXML_VERSION < '20620') {
            //Mindestversion siehe http://www.php.net/manual/de/xmlreader.readstring.php
            throw new Zend_Exception('LIBXML_VERSION must be at least 2.6.20 (or as integer 20620).');
        }
        $this->config = Zend_Registry::get('config');
        
        //init the logger (this will write in the language resources log and in the main log)
        $this->logger=Zend_Registry::get('logger');
        $this->user=ZfExtended_Factory::get('ZfExtended_Models_User');
        $this->termCollection = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        $this->termEntryModel=ZfExtended_Factory::get('editor_Models_TermCollection_TermEntry');
        $this->termModel=ZfExtended_Factory::get('editor_Models_Term');
    }

    /**
     * Imports the tbx files into the term collection
     * (non-PHPdoc)
     * @see editor_Models_Import_MetaData_IMetaDataImporter::import()
     */
    public function import(editor_Models_Task $task, editor_Models_Import_MetaData $meta){
        $tbxFilterRegex = '/\.tbx$/i';
        $tbxfiles = $meta->getMetaFileToImport($tbxFilterRegex);
        if(empty($tbxfiles)){
            return;
        }
        
        $this->task = $task;

        //the termcollection customer is the one in the task
        if(empty($this->customerIds)){
            $this->customerIds=[$this->task->getCustomerId()];
        }
        
        $this->loadUser($task->getPmGuid());
        
        //create term collection for the task and customer
        //the term collection will be created with autoCreateOnImport flag
        $this->termCollection->create("Term Collection for ".$this->task->getTaskName(), $this->customerIds);
        
        //add termcollection to task assoc
        $this->termCollection->addTermCollectionTaskAssoc($this->termCollection->getId(), $task->getTaskGuid());
        
        //reset the taskHash for the task assoc of the current term collection
        $this->resetTaskTbxHash($this->termCollection->getId());
        
        //all tbx files in the same term collection
        foreach($tbxfiles as $file) {
            if(!$file->isReadable()){
                throw new editor_Models_Import_TermListParser_Exception('E1023',[
                    'filename'=>$file,
                    'languageResource'=>$this->termCollection
                ]);
            }
            $this->task->setTerminologie(1);
            
            //languages welche aus dem TBX importiert werden sollen
            $this->languages[$meta->getSourceLang()->getId()] = $this->normalizeLanguage($meta->getSourceLang()->getRfc5646());
            $this->languages[$meta->getTargetLang()->getId()] = $this->normalizeLanguage($meta->getTargetLang()->getRfc5646());
           
            //start with file parse
            $this->parseTbxFile([$file->getPathname()],$this->termCollection->getId());

            //check if the languages in the task are valid for the term collection
            $this->validateTbxLanguages();
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
            
            $this->termCollection=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
            $this->termCollection->load($termCollectionId);
            
            //reset the taskHash for the task assoc of the current term collection
            $this->resetTaskTbxHash();
            
            foreach ($filePath as $path){
                
                $tmpName = $path['tmp_name'] ?? $path;
                $fileName = $path['name'] ?? null;
                
                //save the imported tbx to the disc
                $this->saveFileLocal($tmpName,$fileName);
                
                $this->xml = new XmlReader();
                //$this->xml->open(self::getTbxPath($task));
                $this->xml->open($tmpName, null, LIBXML_PARSEHUGE);
                
                //Bis zum ersten TermEntry springen und alle TermEntries verarbeiten.
                while($this->fastForwardTo('termEntry')) {
                    $this->setActualLevel();
                    $this->handleTermEntry();
                }
                
                $this->xml->close();
                
                //update termcollection languages in the assoc table
                $this->updateCollectionLanguage();
            }
        }catch (Exception $e){
            $this->logger->exception($e,[
                'level'=>ZfExtended_Logger::LEVEL_ERROR,
                'extra'=>[
                    'languageResource'=>$this->termCollection
                ]
            ]);
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
        $tbxData = $this->termModel->exportForTagging($task);
        
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
        if(!$this->isStartTag()) {
            return; // END Tag => raus
        }
        
        // check if aktu termEntry is empty self-closing tag
        if ($this->xml->isEmptyElement) {
            return;
        }
        
        // save actual termEntryId
        $this->actualTermEntry = $this->getNodeId();
        
        //check if the termEntry exist in the current collection
        if(!is_null($this->actualTermEntry)) {
            $existingEntry = $this->termEntryModel->getTermEntryByIdAndCollection($this->actualTermEntry,$this->termCollection->getId());
        }
        else {
            $existingEntry = false;
        }
        
        if($existingEntry && $existingEntry['id']>0){
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
        if(!empty($this->termsToMergeContainer)){
            //true = in the current tbx termEntry set, one term is found for merging
            $isMerged=!empty($this->lastMergeGroupId);
            
            $termAttributes=ZfExtended_Factory::get('editor_Models_Db_Term_Attribute');
            /* @var $termAttributes editor_Models_Db_Term_Attribute */
            
            $singleTerm=ZfExtended_Factory::get('editor_Models_Db_Terms');
            /* @var $singleTerm editor_Models_Db_Terms */

            $termEntryIdToSave=$isMerged ? $this->lastMergeTermEntryIdDb : $this->actualTermEntryIdDb;
            
            //get the termEntry id of the merged data and use it for the collectedData
            foreach ($this->termsToMergeContainer as $termData){
                if(empty($termData['id'])) {
                    continue;
                }
                //update the groupId and termEntryId for the merged terms
                $singleTerm->update([
                    'groupId'=>$isMerged ? $this->lastMergeGroupId : $this->actualTermEntry,
                    'termEntryId'=>$termEntryIdToSave
                ],['id=?'=>$termData['id']]);
                
                //update the termEntryId also for the term attributes
                $termAttributes->update(['termEntryId'=>$termEntryIdToSave],['termId=?'=>$termData['id']]);
            }
            
            $this->termsToMergeContainer=[];

            //if the terms are merged, remove the new created termEntry, since all of the terms are merged in the existing termEntry.
            if($isMerged){
                $this->termEntryModel->db->delete(['id = ?' => $this->actualTermEntryIdDb]);
            }
        }
        
        $termEntryAttributes=ZfExtended_Factory::get('editor_Models_Db_Term_Attribute');
        /* @var $termEntryAttributes editor_Models_Db_Term_Attribute */
        $deleteParams=[];
        
        $deleteParams['termEntryId = ?']=$this->actualTermEntryIdDb;
        $deleteParams['termId is null'] = '';
        
        //TODO: add additional flag (from zf config) if this should be triggered or not
        if(!empty($this->termEntryAttributeContainer)){
            $deleteParams['id NOT IN (?)']=$this->termEntryAttributeContainer;
        }
        
        //remove the old term entry attributes
        $termEntryAttributes->delete($deleteParams);
        
        $this->termEntryAttributeContainer=[];
        $this->lastMergeGroupId=null;
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
        //if there is a task(import from 'task import') and the language should not be processed, we jump to the end of the whole lang tag
        if($this->task && !$this->isLanguageToProcess()) {
            //bis zum Ende des aktuellen LangTags gehen.
            while($this->xml->read() && $this->xml->name !== 'langSet'){}
            return;
        }
        //If the actualLangId is not set in isLanguageToProcess -> try to set it from actualLang (language(langset tag) rfc value from the tbx file )
        // if that fails too, ignore that langSet
        if($this->actualLangId<1){
            $langModel=ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $langModel editor_Models_Languages */
            try {
                $langModel->loadByRfc5646($this->actualLang);
                $this->actualLangId=$langModel->getId();
            } catch (ZfExtended_Models_Entity_NotFoundException$e) {
                $this->log("Unable to imprt terms in this language set. Invalid Rfc5646 language code. Language code:".$this->actualLang);
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
     *
     *   FIXME Performance: foreach term this loop is called!
     *
     * @return boolean
     */
    protected function isLanguageToProcess() {
        $langToImport = $this->normalizeLanguage($this->actualLang);
        $lastLangId = '';
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
            $this->actualTermIdTbx=null;
            return;
        }
        
        //ignore empty term
        if ($this->xml->isEmptyElement || $this->xml->readInnerXml()=='') {
            $this->actualTermIdTbx=null;
            return;
        }
        $this->actualTermIdTbx=$this->getNodeId();
        $this->handleTermDb();
    }

    /**
     * Check if the termNote is of a type normativeAuthorization.
     * Update the status to the current term in the database.
     */
    protected function checkTermStatus() {
        $type = $this->xml->getAttribute('type');
        $importMap = $this->config->runtimeOptions->tbx->termImportMap->toArray();
        $allowedTypes = ['normativeAuthorization', 'administrativeStatus'];
        //merge system allowed note types with configured ones:
        if(!empty($importMap)) {
            $allowedTypes = array_merge($allowedTypes, array_keys($importMap));
        }
        //if current termNote is no starttag or type is not allowed to provide a status the we jump out
        if(!$this->isStartTag() || !in_array($type, $allowedTypes)){
          return;
        }
        $this->termContainer['status']=$this->getMappedStatus($this->xml->readString(), $type);
        $this->termContainer['updated']=NOW_ISO;
        $this->termContainer['userGuid']=$this->user->getUserGuid();
        $this->termContainer['userName']=$this->user->getUserName();
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
        $importMap = $this->config->runtimeOptions->tbx->termImportMap->toArray();
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
        return $this->config->runtimeOptions->tbx->defaultTermStatus;
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
        if($this->isInsideDescripGrp && isset($entry)){
            $this->actualParentId=$entry->getId();
        }
    }
    
    /***
     * Tig tag handler
     */
    protected function handleTig(){
        $this->isInsideTig=$this->isStartTag();
        
        if($this->isInsideTig){
            $this->actualParentId=null;
            $this->termAttirbuteContainer=[];
            $this->termContainer=[];
            return;
        }
        
        //ignore the saveing when the term is empty
        if(!isset($this->termContainer['term']) || empty($this->termContainer['term'])){
            $this->actualParentId=null;
            $this->termAttirbuteContainer=[];
            $this->termContainer=[];
            return;
        }
            
        $term=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        $term->init($this->termContainer);
        
        //if the status is not set, set the default value
        if($term->getStatus()==null || empty($term->getStatus())){
            $term->setStatus($this->config->runtimeOptions->tbx->defaultTermStatus);
            $this->termContainer['status']=$term->getStatus();
        }
        
        //if it is existing term, update the record
        if(isset($this->termContainer['id'])){
            //INFO: using save also for update, creates a new record!
            $term->db->update($this->termContainer, ['id=?'=>$this->termContainer['id']]);
        }else{
            $term->setId($term->save());
        }
        $this->termContainer['id']=$term->getId();

        //if the termsToMergeContainer contains the current term, update the values there
        if(isset($this->termsToMergeContainer[$term->getMid()])){
            $this->termsToMergeContainer[$term->getMid()]=$this->termContainer;
        }
        
        $termAttributes=ZfExtended_Factory::get('editor_Models_Db_Term_Attribute');
        /* @var $termAttributes editor_Models_Db_Term_Attribute */
        
        if(!empty($this->termAttirbuteContainer)){
            //update the term id for all collected term attributes
            $termAttributes->update(['termId'=>$term->getId()],['id IN (?)'=>$this->termAttirbuteContainer]);
        }
        
        //check if the proposals should be removed
        $this->handleCurrentTermProposal();
        
        //check the processStatus attribute for the term. If there is no process status attribute for the term, new one will be created.
        $statusAttributeId=$this->handleTermProcessStatus($term->getId());
        if($statusAttributeId && $statusAttributeId>0){
            $this->termAttirbuteContainer[]=$statusAttributeId;
        }
        
        $deleteParams=[];
        $deleteParams['termId = ?'] = $term->getId();
        
        //remove the old attribute
        if(!empty($this->termAttirbuteContainer)){
            $deleteParams['id NOT IN (?)'] = $this->termAttirbuteContainer;
        }
        
        //remove unneeded term attributes
        $termAttributes->delete($deleteParams);
            
        $this->actualParentId=null;
        $this->termAttirbuteContainer=[];
        $this->termContainer=[];
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
     * @return boolean
     */
    protected function handleUnknown(){
        if(!$this->isStartTag()){
            return false;
        }
        
        $this->log("Unsupported tag found during the tbx parsing:#Tag name:".$this->xml->name.'#Term collection id:'.$this->termCollection->getId().'#');
    }
    
    /***
     * Check if the current term has attribute with processStatus. If not create a default processStatus attribute
     * @param int $termId
     * @return NULL|mixed|array
     */
    protected function handleTermProcessStatus(int $termId){
        $attribute=ZfExtended_Factory::get('editor_Models_Term_Attribute');
        /* @var $attribute editor_Models_Term_Attribute */
        return $attribute->checkOrCreateProcessStatus($termId);
    }
    
    /***
     * Save term entry attribute in the database.
     *
     * @param int $parentId
     * @param int $internalCount: the current tag count of the same type in one group
     *
     * @return boolean|editor_Models_Term_Attribute
     */
    protected function saveEntryAttribute($parentId,$internalCount=null){
        if(!$this->isStartTag()){
            return false;
        }
        $attribute=$this->getAttributeObject($parentId);
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
     * @param int $parentId
     * @param int $internalCount: the current tag count of the same type in one group
     *
     * @return null|editor_Models_Term_Attribute
     */
    protected function saveTermAttribute($parentId,$internalCount=null){
        if(!$this->isStartTag()){
            return null;
        }
        //do not save attributes on empty term
        if(!isset($this->termContainer['term']) || empty($this->termContainer['term'])){
            return null;
        }
        
        $attribute=$this->getAttributeObject($parentId);

        $attribute->setTermId(isset($this->termContainer['id']) ? $this->termContainer['id'] : null);
        $attribute->setInternalCount($internalCount);
        $attribute->saveOrUpdate();
        
        //if it is procesStatuss attribute, update the term procesStatuss value
        if($attribute->isProcessStatusAttribute()){
            $this->termContainer['processStatus']=$attribute->getValue();
        }
        
        //add the inserted/update attribute to the collection
        $this->termAttirbuteContainer[]=$attribute->getId();
        return $attribute;
    }
    
    /***
     * Get the term attribute or term entry attribute model
     *
     * @param bool $isTermAttribute
     * @param mixed $parentId
     *
     * @return editor_Models_Term_Attribute
     */
    protected function getAttributeObject($parentId){
        $attribute = ZfExtended_Factory::get('editor_Models_Term_Attribute');
        
        $attribute->setCollectionId($this->termCollection->getId());
        
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
        
        $label = ZfExtended_Factory::get('editor_Models_TermCollection_TermAttributesLabel');
        /* @var $label editor_Models_TermCollection_TermAttributesLabel */
        $label->loadOrCreate($this->xml->name, $attrType);
        $attribute->setLabelId($label->getId());
        
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
        
        $attribute->setUserGuid($this->user->getUserGuid());
        $attribute->setUserName($this->user->getUserName());
        
        //find the default status from the config
        $attributeStatus=$this->config->runtimeOptions->tbx->defaultTermAttributeStatus;
        if(empty($this->config->runtimeOptions->tbx->defaultTermAttributeStatus)){
            $attributeStatus=editor_Models_Term::PROCESS_STATUS_FINALIZED;
        }
        //Set the attribute status to finalized since the systems where the imported terms/attributes come from
        //are considered as leading system
        $attribute->setProcessStatus($attributeStatus);
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
        if($this->xml->name=='termEntry'){
            //reset the actual level on each new termentry
            //the actual level is relevant only inside the termentry
            $this->actualLevel=[];
        }
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

    protected function log($logMessage,$code='E1028') {
        $data=[
            'languageResource'=>$this->termCollection
        ];
        if(!empty($this->task)){
            $data['task']=$this->task;
        }
        $data['userGuid']=$this->user->getUserGuid();
        $data['userName']=$this->user->getUserName();
        $this->logger->info($code,$logMessage,$data);
    }
    
    /**
     * Return the term/termEntry id from the tbx file. If no id is provided in the tbx,
     * we return null, which triggers automatic calculation by insert id
     *
     * @return string
     */
    private function getNodeId() {
        if(!empty($this->xml->getAttribute('id'))){
            return $this->xml->getAttribute('id');
        }
        return null;
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
        $termEntry->setCollectionId($this->termCollection->getId());
        if(is_null($this->actualTermEntry)) {
            $termEntry->save();
            $this->actualTermEntry = 'termEntry_'.$termEntry->getId();
        }
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
        //if actualTermIdTbx is null, in the previous code the next auto inc value was used,
        // which implies that a new term will be created. We just preinsert the term here, and just update it later.
        if(is_null($this->actualTermIdTbx)) {
            $this->termContainer['id'] = $this->termModel->db->insert([
                'mid' => 'preinsert',
                'collectionId' => $this->termCollection->getId(),
            ]);
            $this->actualTermIdTbx = 'term_'.$this->termContainer['id'];
        }
        $terms=$this->termModel->isUpdateTermForCollection($this->actualTermEntry,$this->actualTermIdTbx,$this->termCollection->getId());
        //if term is found(should return single row since termId is unique)
        if($terms->count()>0){
            foreach ($terms as $t){
                //update the term
                $t = (object) $t;
                $this->termContainer['id']=$t->id;
                $this->termContainer['language']=$t->language;
                $this->termContainer['term']=$this->xml->readInnerXml();
                $this->termContainer['updated']=NOW_ISO;
                $this->termContainer['userGuid']=$this->user->getUserGuid();
                $this->termContainer['userName']=$this->user->getUserName();
                return;
            }
        }
        //check if the term with the same termEntry,collection but different termId exist
        
        $tmpTermValue=$this->termModel->getRestTermsOfGroup($this->actualTermEntry, $this->actualTermIdTbx, $this->termCollection->getId());

        $addNewTerm=$tmpTermValue->count()>0;
        if($addNewTerm){

            //foreach term in the db term entry, find if the current term has the same language and value
            foreach ($tmpTermValue as $t){
                $t = (object) $t;
                $checkCase=$t->language==$this->actualLangId;
                $checkCase=$checkCase && ($t->term==$this->xml->readInnerXml());
                //the groupId is already the same
                if(!$checkCase){
                    continue;
                }
                $this->termContainer['id']=$t->id;
                $this->termContainer['term']=$this->xml->readInnerXml();
                $this->termContainer['language']=$t->language;
                $this->termContainer['definition']=$this->actualDefinition;
                $this->termContainer['updated']=NOW_ISO;
                $this->termContainer['userGuid']=$this->user->getUserGuid();
                $this->termContainer['userName']=$this->user->getUserName();
                return;
            }
        }
        
        
        if($this->mergeTerms){
            //check if the term text exist in the term collection within the language
            $tmpTermValue=$this->termModel->findTermInCollection($this->xml->readInnerXml(), $this->actualLangId, $this->termCollection->getId());

            if($tmpTermValue && $tmpTermValue->count()>0){
                
                //the first term thus found is updated by the values ​​in the TBX file.
                //The term-ID and termEntry-ID remain the same as they already existed in translate5.
                $tmpTermValue=$tmpTermValue->toArray();
                $tmpTermValue=$tmpTermValue[0];
                
                $this->termContainer['id']=$tmpTermValue['id'];
                $this->termContainer['term']=$this->xml->readInnerXml();
                $this->termContainer['language']=$tmpTermValue['language'];
                $this->termContainer['definition']=$this->actualDefinition;
                $this->termContainer['updated']=NOW_ISO;
                $this->termContainer['userGuid']=$this->user->getUserGuid();
                $this->termContainer['userName']=$this->user->getUserName();
                
                if(empty($this->lastMergeGroupId)){
                    $this->lastMergeTermEntryIdDb=$tmpTermValue['termEntryId'];
                    $this->lastMergeGroupId=$tmpTermValue['groupId'];
                }
                
                return;
            }

            $this->termContainer['term']=$this->xml->readInnerXml();
            $this->termContainer['mid']=$this->actualTermIdTbx;
            //the status will be updated when is found from the termNote
            $this->termContainer['definition']=$this->actualDefinition;
            $this->termContainer['language']=(int)$this->actualLangId;
            $this->termContainer['collectionId']=$this->termCollection->getId();
            $this->termContainer['updated']=NOW_ISO;
            $this->termContainer['userGuid']=$this->user->getUserGuid();
            $this->termContainer['userName']=$this->user->getUserName();
            
            //if the term is no merged but his term entry exist in the database, add it as a new term
            if($addNewTerm){
                $this->termContainer['termEntryId']=$this->actualTermEntryIdDb;
                $this->termContainer['groupId']=$this->actualTermEntry;
                return;
            }
            
            //collect the term so later can be updated
            $this->termsToMergeContainer[$this->actualTermIdTbx]=$this->termContainer;
            return;
        }
        
        $this->termContainer['term']=$this->xml->readInnerXml();
        $this->termContainer['mid']=$this->actualTermIdTbx;
        //the status will be updated when is found from the termNote
        $this->termContainer['definition']=$this->actualDefinition;
        $this->termContainer['groupId']=$this->actualTermEntry;
        $this->termContainer['language']=(int)$this->actualLangId;
        $this->termContainer['collectionId']=$this->termCollection->getId();
        $this->termContainer['termEntryId']=$this->actualTermEntryIdDb;
        $this->termContainer['updated']=NOW_ISO;
        $this->termContainer['userGuid']=$this->user->getUserGuid();
        $this->termContainer['userName']=$this->user->getUserName();
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
        $collLangs=$collection->getLanguagesInTermCollections(array($this->termCollection->getId()));
        
        //disable terminology when no terms for the term collection are available
        if(empty($collLangs)){
            $this->log("Terminologie is disabled because no terms in the termcollection are found. TermcollectionId: ".$this->termCollection->getId());
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

        $this->log('For the following languages no term has been found in the tbx file: '.implode(', ', $notProcessed));
        $this->task->setTerminologie(0);
        return false;
    }
    
    /***
     * Update term collection lanugages assoc.
     */
    protected  function updateCollectionLanguage(){
        //remove old language assocs
        $assoc=ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
        /* @var $assoc editor_Models_LanguageResources_Languages */
        $assoc->removeByResourceId([$this->termCollection->getId()]);
        //add the new language assocs
        $this->termModel->updateAssocLanguages([$this->termCollection->getId()]);
        
    }
    
    /***
     * Check if the current term exist as proposal.
     * @return boolean
     */
    protected function handleCurrentTermProposal(){
        //if the user is allowed for term proposals, check if the proposals should be removed
        if(!in_array('termProposer', $this->user->getRoles())){
            return false;
        }
        $proposal=ZfExtended_Factory::get('editor_Models_Term_Proposal');
        /* @var $proposal editor_Models_Term_Proposal */
        
        $proposalInCollection=$proposal->findProposalInCollection($this->termContainer['term'], $this->termContainer['language'], $this->termCollection->getId());
        if($proposalInCollection->count()<1){
            return false;
        }
        
        $proposalTerm=$proposalInCollection->toArray();
        $proposalTerm=$proposalTerm[0];
        
        if($this->termModel->isModifiedAfter($this->termContainer['id'], $proposalTerm['termProposalCreated'])){
            return $proposal->removeTermProposal($proposalTerm['termProposalTermId'],$proposalTerm['termProposalValue']);
        }
        
        return false;
    }
    
    /***
     * Save the imported file to the disk.
     * The file location will be "trasnalte5 parh" /data/tbx-import/tbx-for-filesystem-import/tc_"collectionId"/the file"
     *
     * @param string $filepath: source file location
     * @param string $name: source file name
     */
    private function saveFileLocal($filepath,$name=null) {
        
        //if import source is not defined save it in filesystem folder
        if(!$this->importSource){
            $this->importSource="filesystem";
        }
        
        $tbxImportDirectoryPath=APPLICATION_PATH.'/../data/tbx-import/';
        $newFilePath=$tbxImportDirectoryPath.'tbx-for-'.$this->importSource.'-import/tc_'.$this->termCollection->getId();
        
        //check if the directory exist and it is writable
        if(is_dir($tbxImportDirectoryPath) && !is_writable($tbxImportDirectoryPath)){
            $this->log("Unable to save the tbx file to the tbx import path. The file is not writable. Import path: ".$tbxImportDirectoryPath." , termcollectionId: ".$this->termCollection->getId());
            return;
        }
        
        try {
            if(!file_exists($newFilePath) && !@mkdir($newFilePath, 0777, true)){
                $this->log("Unable to create directory for imported tbx files. Directory path: ".$newFilePath." , termcollectionId: ".$this->termCollection->getId());
                return;
            }
        } catch (Exception $e) {
            $this->log("Unable to create directory for imported tbx files. Directory path: ".$newFilePath." , termcollectionId: ".$this->termCollection->getId());
            return;
        }
        
        $fi = new FilesystemIterator($newFilePath, FilesystemIterator::SKIP_DOTS);
        
        $fileName=null;
        //if the name is set, use it as filename
        if($name){
            $fileName=iterator_count($fi).'-'.$name;
        }else{
            $fileName=iterator_count($fi).'-'.basename($filepath);
        }
        
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
    
    public function loadUser(string $userGuid) {
        $this->user->loadByGuid($userGuid);
    }
    
    public function getUser() {
        return $this->user;
    }
    
    /***
     * Reset the tbx hash for the tasks using the current term collection
     */
    protected function resetTaskTbxHash(){
        $taskassoc=ZfExtended_Factory::get('editor_Models_LanguageResources_Taskassoc');
        /* @var $taskassoc editor_Models_LanguageResources_Taskassoc */
        $assocs=$taskassoc->getAssocTasksByLanguageResourceId($this->termCollection->getId());
        if(empty($assocs)){
            return;
        }
        $affectedTasks = array_column($assocs, 'taskGuid');
        $meta = ZfExtended_Factory::get('editor_Models_Task_Meta');
        /* @var $meta editor_Models_Task_Meta */
        $meta->resetTbxHash($affectedTasks);
    }
    
    /***
     * Get filesystem imported collection directory
     */
    static public function getFilesystemCollectionDir(){
        return APPLICATION_PATH.'/../data/tbx-import/tbx-for-filesystem-import/';
    }
}
