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
class editor_Models_Import_TermListParser_Tbx2 implements editor_Models_Import_IMetaDataImporter {
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
    
    protected $timer;
    
    
    protected $counterTermEntry = 0;
    protected $counterTig = 0;
    protected $counterTigInLangSet = 0;
    protected $counterTerm = 0;
    protected $counterTermInTig = 0;
    
    /**
     * internal flag
     * @var boolean
     */
    protected $forceOnImport = false;
    
    
    /***
     * Term collection id
     * 
     * @var integer
     */
    protected $termCollectionId;
    
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
     * if the current node is inside ntig
     * 
     * @var boolean
     */
    private $isInsideNtig=false;
    
    
    public function __construct() {
        if(!defined('LIBXML_VERSION') || LIBXML_VERSION < '20620') {
            //Mindestversion siehe http://www.php.net/manual/de/xmlreader.readstring.php
            throw new Zend_Exception('LIBXML_VERSION must be at least 2.6.20 (or as integer 20620).');
        }
        $this->timer = (object)array('langSet' => 0, 'tig' => 0, 'term' => 0);
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
        /* @var $importer editor_Models_Import_TermListParser_Tbx */
        foreach($tbxfiles as $file) {
            /* @var $file SplFileInfo */
            $this->importOneTbx($file, $task, $meta->getSourceLang(), $meta->getTargetLang());
            break; //we consider only one TBX file!
        }
        
        if(!empty($this->unknownStates)) {
            $this->log('TBX contains the following unknown term states: '.join(', ', $this->unknownStates));
        }
    }
    
    /**
     * Import the given TBX file
     * @param SplFileInfo $file
     * @param editor_Models_Task $task
     * @param editor_Models_Languages $sourceLang
     * @param editor_Models_Languages $targetLang
     */
    protected function importOneTbx(SplFileInfo $file, editor_Models_Task $task, editor_Models_Languages $sourceLang, editor_Models_Languages $targetLang){
        
        if(! $file->isReadable()){
            throw new ZfExtended_Exception($file.' is not Readable!');
        }
        $this->task = $task;
        
        $this->task->setTerminologie(1);
        
        //languages welche aus dem TBX importiert werden sollen
        $this->languages[$sourceLang->getId()] = $this->normalizeLanguage($sourceLang->getRfc5646());
        $this->languages[$targetLang->getId()] = $this->normalizeLanguage($targetLang->getRfc5646());

        $this->xml = new XmlReader();
        //$this->xml->open(self::getTbxPath($task));
        $this->xml->open($file->getPathname());

        
        //create the term collection
        $this->termCollectionId = $this->createTermCollection();
        
        //Bis zum ersten TermEntry springen und alle TermEntries verarbeiten.
        while($this->fastForwardTo('termEntry')) {
            $this->handleTermEntry();
        }
        

        $notProcessed = array_diff(
            array_keys($this->languages),
            array_keys($this->processedLanguages));
        if(!empty($notProcessed)) {
            $langs = array();
            foreach ($notProcessed as $value) {
                $langs[]= implode('-',$this->languages[$value]);
            }
            throw new ZfExtended_NotAcceptableException('For the following languages no term has been found in the tbx file: '.implode(', ', $langs));
        }
        $this->xml->close();
        
        $this->forceOnImport = true;
        $this->assertTbxExists($this->task, new SplFileInfo(self::getTbxPath($this->task)));
        $this->forceOnImport = false;
    }
    
    /**
     * checks if the needed TBX file exists, otherwise recreate if from DB
     * @param editor_Models_Task $task
     * @param SplFileInfo $tbxPath
     */
    public function assertTbxExists(editor_Models_Task $task, SplFileInfo $tbxPath) {
        if(!$this->forceOnImport && $tbxPath->isReadable()) {
            return file_get_contents($tbxPath);
        }
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
        if(!$this->isStartTag()) {
            return; // END Tag => raus
        }
        
        // check if aktu termEntry is empty self-closing tag
        if ($this->xml->isEmptyElement) {
            return;
        }
        
        // save actual termEntryId
        $this->actualTermEntry = $this->getIdTermEntry();            
        
        //create term entry and get the id
        $this->actualTermEntryIdDb=$this->createTermEntryRecord();
        
        if(empty($this->actualTermEntry)) {
            $this->log('termEntry Tag without an ID found and ignored!');
            return;
        }
        
        // handle all inner elements of termEntry
        while($this->xml->read() && $this->xml->name !== 'termEntry') {
            switch($this->xml->name) {
                case 'langSet':
                    $start = microtime(true);
                    $this->counterTigInLangSet = 0;
                    $this->actualParentId=null;
                    $this->handleLanguage();
                    $this->timer->langSet += (microtime(true) - $start);
                    break;
                case 'descrip':
                    $this->handleDefinition(); //type="Definition"
                    $this->handleDescrip();
                    break;
                case 'transacGrp':
                    $this->handleTransacGrp();
                    break;
                case 'ntig':
                    $this->actualTermIdDb=null;
                    $this->actualParentId=null;
                    $this->isInsideNtig=$this->isStartTag();
                    break;
                case 'termGrp':
                    $this->handleTermGrp();
                    break;
            }
        }
    }

    /**
     * Sprach Tag verarbeiten
     */
    protected function handleLanguage() {
        if($this->isEndTag()){
            $this->actualDefinition = '';
            $this->actualLang = null;
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
        if(!$this->isLanguageToProcess()) {
            //bis zum Ende des aktuellen LangTags gehen.
            while($this->xml->read() && $this->xml->name !== 'langSet'){}
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
        $this->actualLangID = 0;
        $matched = false;
        foreach($this->languages as $langString => $langAllowed) {
            if($matched) {
                $this->processedLanguages[$lastLangId] = 1;
                $this->actualLangID = $lastLangId;
                return true;
            }
            $compareFirstOnly = count($langToImport) == 1 || count($langAllowed) < 2;
            $matched = ($compareFirstOnly && $langAllowed[0] === $langToImport[0] || $langAllowed === $langToImport);
            $lastLangId = $langString;
        }
        if($matched) {
            $this->processedLanguages[$lastLangId] = 1;
            $this->actualLangID = $lastLangId;
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
            return;
        }
        
        // check if aktu term is empty self-closing tag
        if ($this->xml->isEmptyElement) {
            return;
        }
        
        $term=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        
        $term->setTaskGuid($this->task->getTaskGuid());
        $term->setTerm($this->xml->readInnerXml());
        $term->setMid($this->getIdTerm());
        //the status will be updated when is found from the termNote
        $term->setDefinition($this->actualDefinition);
        $term->setGroupId($this->actualTermEntry);
        $term->setLanguage((integer)$this->actualLangId);
        $term->setTigId($this->getIdTig());
        $term->setCollectionId($this->termCollectionId);
        $term->setTermEntryId($this->actualTermEntryIdDb);
        $this->actualTermIdDb=$term->save();
    }

    /**
     * Check if the termNote is of a type normativeAuthorization.
     * Update the statuso to the current term in the database.
     */
    protected function checkTermStatus() {
        if(!$this->isStartTag() || $this->xml->getAttribute('type') !== 'normativeAuthorization'){
          return;
        }
        $actualTermNoteStatus= $this->getMappedStatus($this->xml->readString());

        //update the term with the status
        $term=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        $term->init($this->actualTermIdDb);
        $term->setStatus($actualTermNoteStatus);
        $term->save();
    }

    /**
     * Gibt den im Editor verwendeten Status zum im TBX gemappten Status zurück
     * @param string $tbxStatus
     * @return string
     */
    protected function getMappedStatus($tbxStatus) {
        if(!empty($this->statusMap[$tbxStatus])){
            return $this->statusMap[$tbxStatus];
        }
        if(!in_array($tbxStatus, $this->unknownStates)) {
            $this->unknownStates[] = $tbxStatus;
        }
        return editor_Models_Term::STAT_NOT_FOUND;
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
        //insert descript
        $entry=$this->saveEntryAttribute(null);
        $this->actualParentId=$entry->getId();
    }
    
    /***
     * transacGrp attribute handler.
     * TransacGrp attribute can be saved in the term attributes or in the term entry attributes table depends on if the transacGrp is in nTig tag
     * 
     */
    protected function handleTransacGrp(){
        //internal parentId pointer
        $tmpParrentId=null;
        while($this->xml->read() && $this->xml->name !== 'transacGrp') {
            switch($this->xml->name) {
                case 'transac':
                    if($this->isInsideNtig){
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
                    $this->isInsideNtig ? $this->saveTermAttribute($tmpParrentId) : $this->saveEntryAttribute($tmpParrentId);
                    break;
                    
            }
        }
    }
    
    /***
     * Handle the term group element.
     * Save all needed child inside the termGrp tag
     */
    protected function handleTermGrp(){
        if(!$this->isStartTag()){
            return;
        }
        // handle all inner elements of termGrp
        while($this->xml->read() && $this->xml->name !== 'termGrp') {
            switch($this->xml->name) {
                case 'term':
                    $this->handleTerm();
                    break;
                case 'termNote':
                    $this->checkTermStatus();
                    $this->saveTermAttribute(null);
                    break;
                case 'admin':
                    $this->saveTermAttribute(null);
                    break;
            }
        }
    }
    
    /***
     * Save term entry attribute in the database
     * 
     * @param unknown $parentId
     * @return boolean|editor_Models_TermCollection_TermEntryAttributes
     */
    protected function saveEntryAttribute($parentId){
        if(!$this->isStartTag()){
            return false;
        }
        $attribute=$this->getAttributeObject(false,$parentId);
        $attribute->setTermEntryId($this->actualTermEntryIdDb);
        $attribute->save();
        return $attribute;
    }
    
    /***
     * Save term attribute in the database
     * 
     * @param unknown $parentId
     * @return void|editor_Models_TermCollection_TermEntryAttributes
     */
    protected function saveTermAttribute($parentId){
        if(!$this->isStartTag()){
            return;
        }
        $attribute=$this->getAttributeObject(true,$parentId);
        $attribute->setTermId($this->actualTermIdDb);
        $attribute->save();
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
        
        $label=ZfExtended_Factory::get('editor_Models_TermCollection_TermAttributesLabel');
        /* @var $label editor_Models_TermCollection_TermAttributesLabel */
        $labelResult=$label->getLabelByName($this->xml->name);
        if(empty($labelResult)){
            $label->setLabel($this->xml->name);
            $labelResult=$label->save();
            $attribute->setLabelId($labelResult);
        }else{
            $attribute->setLabelId($labelResult[0]['id']);
        }
        
        $attribute->setLanguage($this->actualLang);
        if(!$parentId){
            $parentId=$this->actualParentId;
        }
        $attribute->setParentId($parentId);
        $attribute->setName($this->xml->name);
        $attribute->setAttrType($this->xml->getAttribute('type'));
        $attribute->setAttrTarget($this->xml->getAttribute('target'));
        $attribute->setAttrId($this->xml->getAttribute('id'));
        $attribute->setAttrLang($this->xml->getAttribute('xml:lang'));
        $attribute->setValue($this->xml->readInnerXml());
        return $attribute;
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
    
    /**
     * Get the actual term entry id
     * 
     * @return string
     */
    private function getIdTermEntry() {
        $this->counterTermEntry++;
        return $this->xml->getAttribute('id');
    }

    /**
     * Generates a unic id for a tig-element.
     *
     * @return string
     */
    private function getIdTig() {
        $this->counterTigInLangSet++;
        $this->counterTig++;
        $tempId = 'tig'
                  .'_'.str_pad($this->counterTermEntry, 7, '0', STR_PAD_LEFT)
                  .'_'.str_pad($this->counterTigInLangSet, 3, '0', STR_PAD_LEFT)
                  .'_'.str_pad($this->counterTig, 7, '0', STR_PAD_LEFT)
                  .'_'.$this->actualLang;
        return $tempId;
    }


    /**
     * Get the term id from the xml tag
     * 
     * @return string
     */
    private function getIdTerm() {
        $this->counterTermInTig++;
        $this->counterTerm++;
        return $this->xml->getAttribute('id');
    }
    
    /***
     * Create the term collection and return the id
     * TODO: add name as parametar ?
     */
    private function createTermCollection(){
        $termCollection=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $termCollection editor_Models_TermCollection_TermCollection */
        $termCollection->setName("Term collection name");
        $termCollection->save();
        return $termCollection->getId();
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
}
