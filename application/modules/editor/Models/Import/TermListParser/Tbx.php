<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Kapselt den Import der Meta Daten zu einem Projekt.
 * - sucht selbstständig nach MetaDaten im Projekt
 * - importiert die gefundenen MetaDaten
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
     * autoincrement-IDd zu den aktuell bearbeiteten langSet Tags aus der Tabelle LEK_languages im Format $this->actualLangIDs[$this->actualLang] = (int)ID
     * @var array
     */
    protected $actualLangIDs = array();

    /**
     * Term Definition des aktuellen langSet Tags
     * @var string
     */
    protected $actualDefinition='';

    /**
     * Liste mit Termen im aktuell offenen Lang Set
     * @var array
     */
    protected $actualTermsInLangSet = array();

    /**
     * @var array
     */
    protected $languages = array();

    /**
     * @var editor_Models_Languages
     */
    protected $sourceLang;

    /**
     * @var editor_Models_Languages
     */
    protected $targetLang;

    /**
     * @var array
     */
    protected $processedLanguages = array();

    /**
     * @var string Definiert, welcher Pfadtrenner bei java-Aufruf auf der command-line innerhalb des Parameters -cp gesetzt wird (Linux ":" Windows ";")
     */
    protected $javaPathSep = ':';

    /**
     * Um den Durchsatz beim Speichern der Terme zu erhöhen, werde diese zwischengespeichert und en block in die DB gelegt.
     * @var array
     */
    protected $termInsertBuffer = array();

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
    );
    
    protected $timer;
    
    
    /**
     * If set to true, all IDs (termEntry, tig, term) are set automatically
     * If set to false (not recomended at this moment) IDs will be quessed from the submitted tbx-file 
     * @var boolean
     */
    protected $autoIds = true;
    
    /**
     * Will be set in first <termEntry> of the tbx-file.
     * Detects if ids should be added to the termEntries or not 
     * @var boolean
     */
    protected $addTermEntryIds = true;
    
    /**
     * Will be set in first <tig> of the tbx-file.
     * Detects if ids should be added to the terms or not 
     * @var boolean
     */
    protected $addTigIds = true;
    
    /**
     * Will be set in first <term> of the tbx-file.
     * Detects if ids should be added to the terms or not 
     * @var boolean
     */
    protected $addTermIds = true;
    
    
    protected $counterTermEntry = 0;
    protected $counterTig = 0;
    protected $counterTigInLangSet = 0;
    protected $counterTerm = 0;
    protected $counterTermInTig = 0;
    
    
    const TERM_INSERT_BLOCKSIZE = 15;

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
            throw new Zend_Exception($file.' is not Readable!');
        }
        $this->task = $task;
        
        $this->task->setTerminologie(1);
        
        //languages welche aus dem TBX importiert werden sollen
        $this->languages[$sourceLang->getId()] = $this->normalizeLanguage($sourceLang->getRfc5646());
        $this->languages[$targetLang->getId()] = $this->normalizeLanguage($targetLang->getRfc5646());

        $this->xml = new XmlReader();
        //$this->xml->open(self::getTbxPath($task));
        $this->xml->open($file->getPathname());

        //Bis zum ersten TermEntry springen und alle TermEntries verarbeiten.
        while($this->fastForwardTo('termEntry')) {
            $this->handleTermEntry();
        }
        $this->saveTermEntityToDb();
        $end = microtime(true);

        $notProcessed = array_diff(
            array_keys($this->languages),
            array_keys($this->processedLanguages));
        if(!empty($notProcessed)) {
            foreach ($notProcessed as $value) {
                $this->log('Zur folgenden Sprache wurde kein Terminologie Eintrag aus der TBX Datei gefunden: '.implode('-',$this->languages[$value]));
            }
        }
        $this->xml->close();
        
        $this->assertTbxExists($this->task, new SplFileInfo(self::getTbxPath($this->task)));
    }
    
    /**
     * checks if the needed TBX file exists, otherwise recreate if from DB
     * @param editor_Models_Task $task
     * @param SplFileInfo $tbxPath
     */
    public function assertTbxExists(editor_Models_Task $task, SplFileInfo $tbxPath) {
        if($tbxPath->isReadable()) {
            return file_get_contents($tbxPath);
        }
        //after recreation we need to fetch the IDs!
        //$this->data['fetchIds'] = true;
        
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
                    $this->handleLanguage();
                    $this->timer->langSet += (microtime(true) - $start);
                    break;
                case 'tig':
                    $start = microtime(true);
                    $this->counterTermInTig = 0;
                    $this->handleTig();
                    $this->timer->tig += (microtime(true) - $start);
                    break;
                case 'term':
                    $start = microtime(true);
                    $this->handleTerm();
                    $this->timer->term += (microtime(true) - $start);
                    break;
                case 'termNote':
                    $this->handleTermNote(); //type="normativeAuthorization"
                    break;
                //ich gehe davon aus, dass descrips mit dem type Definition immer der Term Description entsprechen
                case 'descrip':
                    $this->handleDefinition(); //type="Definition"
                    break;
            }
        }
    }

    /**
     * Sprach Tag verarbeiten
     */
    protected function handleLanguage() {
        if($this->isEndTag()){
            $this->saveTermEntity();
            $this->actualDefinition = '';
            $this->actualLang = null;
            $this->actualTermsInLangSet = array();
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

    protected function saveTermEntity() {
        $config = Zend_Registry::get('config');
        foreach ($this->actualTermsInLangSet as $mid => $termData) {
            $termData['taskGuid'] = $this->task->getTaskGuid();
            //term; mid; status in $termData
            if (!empty($termData['definition']) && !empty($this->actualDefinition)) {
                $termData['definition'] = $this->actualDefinition." ".$termData['definition'];
            }
            if (empty($termData['definition'])) {
                $termData['definition'] = $this->actualDefinition;
            }
            $termData['groupId'] = $this->actualTermEntry;
            $termData['language'] = $this->actualLangID;
            if(empty($termData['status'])){
                $termData['status'] = $config->runtimeOptions->tbx->defaultTermStatus;
            }
            $this->termInsertBuffer[] = $termData;
        }
        if(count($this->termInsertBuffer) > self::TERM_INSERT_BLOCKSIZE){
            $this->saveTermEntityToDb();
        }
    }

    /**
     * Um den Durchsatz zuz erhöhen werden die Terme Blockweise gesammelt und dann eingefügt.
     */
    protected function saveTermEntityToDb() {
        if(empty($this->termInsertBuffer)) {
            return;
        }
        
        $termTable = ZfExtended_Factory::get('editor_Models_Db_Terms');
        /* @var $termTable editor_Models_Db_Terms */
        
        $firstTerm = reset($this->termInsertBuffer);
        $sql = $termTable->getInsertSql(array_keys($firstTerm));
        $db = $termTable->getAdapter();

        $query = $termTable->getInsertSql(array_keys($firstTerm));
        $queryVals = array();
        foreach ($this->termInsertBuffer as $row) {
          foreach($row as &$col) {
            $col = $db->quote($col);
          }
          $queryVals[] = '(' . implode(',', $row) . ')';
        }
        
        $db->query($query . implode(',', $queryVals));
        $this->termInsertBuffer = array();
    }

    /**
     * wenn das Ende eines Tigs erreicht wird,
     * dessen Daten unter der ID des Tigs zum aktuellen langSet fügen
     */
    protected function handleTig() {
        if($this->isStartTag()){
            $this->actualTig = array('mid' => null, 'term' => null, 'status' => null, 'definition' => null);
            $this->actualTig['tigId'] = $this->getIdTig();
            return;
        }
        if(!$this->isEndTag()){
            return;
        }
        // check if aktu tig is empty self-closing tag
        if ($this->xml->isEmptyElement) {
            return;
        }
        
        if(empty($this->actualTig) || empty($this->actualTig['mid'])){
            //$this->log('tig Tag ohne relevanten Inhalt oder ohne term tag id Attribut! Wird ignoriert.');
            $this->log('tig-tag without relevant content or without attribut id. tip-tag will be ignored.');
            return;
        }
        $this->actualTermsInLangSet[$this->actualTig['mid']] = $this->actualTig;
    }

    /**
     * Extrahiert die Daten eines Term Tags
     */
    protected function handleTerm() {
        if(!$this->isStartTag()){
            return;
        }
        // check if aktu term is empty self-closing tag
        if ($this->xml->isEmptyElement) {
            return;
        }
        
        
        $this->actualTig['mid'] = $this->getIdTerm();
        $this->actualTig['term'] = $this->xml->readInnerXml();
    }

    /**
     * Extrahiert die Daten eines TermNote Tags mit Type Defintion (=> Status)
     */
    protected function handleTermNote() {
        if(!$this->isStartTag() || $this->xml->getAttribute('type') !== 'normativeAuthorization'){
          return;
        }
        $this->actualTig['status'] = $this->getMappedStatus($this->xml->readString());
    }

    /**
     * Gibt den im Editor verwendeten Status zum im TBX gemappten Status zurück
     * @param string $tbxStatus
     * @return string
     */
    protected function getMappedStatus($tbxStatus) {
        if(empty($this->statusMap[$tbxStatus])){
            return editor_Models_Term::STAT_NOT_FOUND;
        }
        return $this->statusMap[$tbxStatus];
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
            $this->actualTig['definition'] = $this->xml->readString();
            return;
        }
        
        if ($this->xml->getAttribute('type') == 'Definition') {
            $this->actualDefinition = $this->xml->readString();
        }
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
    
    public function cleanup() {
        //nothing to do
    }
    
    /**
     * Generates a unic id for a termEntry-element.
     * If autoIds is set to false and there is an id in the tbx-file this id is used
     * 
     * @return string
     */
    private function getIdTermEntry () {
        // detect on first call if IDs should be added
        if ($this->counterTermEntry == 0 && $this->addTermEntryIds) {
            if (!$this->autoIds && !empty($this->xml->getAttribute('id'))) {
                $this->addTermEntryIds = false;
            }
        }
        
        if ($this->addTermEntryIds == false) {
            return $this->xml->getAttribute('id');
        }
        
        $this->counterTermEntry += 1;
        
        return 'termEntry_'.str_pad($this->counterTermEntry, 7, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generates a unic id for a tig-element.
     * If autoIds is set to false and there is an id in the tbx-file this id is used
     * 
     * @return string
     */
    private function getIdTig () {
        // detect on first call if IDs should be added
        if ($this->counterTig == 0 && $this->addTigIds) {
            if (!$this->autoIds && !empty($this->xml->getAttribute('id'))) {
                $this->addTigIds = false;
            }
        }
        
        if ($this->addTigIds == false) {
            return $this->xml->getAttribute('id');
        }
        
        $this->counterTigInLangSet += 1;
        $this->counterTig += 1;
        
        $tempReturn =   'tig_'.str_pad($this->counterTermEntry, 7, '0', STR_PAD_LEFT)
                        .'_'.str_pad($this->counterTigInLangSet, 3, '0', STR_PAD_LEFT)
                        .'_'.str_pad($this->counterTig, 7, '0', STR_PAD_LEFT)
                        .'_'.$this->actualLang;
        return $tempReturn;
    }
    
    /**
     * Generates a unic id for a term-element.
     * If autoIds is set to false and there is an id in the tbx-file this id is used
     * 
     * @return string
     */
    private function getIdTerm () {
        // detect on first call if IDs should be added
        if ($this->counterTerm == 0 && $this->addTermIds) {
            if (!$this->autoIds && !empty($this->xml->getAttribute('id'))) {
                $this->addTermIds = false;
            }
        }
        
        if ($this->addTermIds == false) {
            return $this->xml->getAttribute('id');
        }
        
        $this->counterTermInTig += 1;
        $this->counterTerm += 1;
        
        $tempReturn =   'term_'.str_pad($this->counterTermEntry, 7, '0', STR_PAD_LEFT)
                        .'_'.str_pad($this->counterTigInLangSet, 3, '0', STR_PAD_LEFT)
                        .'_'.$this->actualLang
                        .'_'.str_pad($this->counterTermInTig, 3, '0', STR_PAD_LEFT)
                        .'_'.str_pad($this->counterTerm, 7, '0', STR_PAD_LEFT);
        return $tempReturn;
    }
    
}
