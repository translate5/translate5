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
class editor_Models_Import_TermListParser_Tbx extends editor_Models_Import_TermListParser {
    /**
     * @var XmlReader
     */
    protected $xml;

    /**
     * @var string
     */
    protected $taskGuid;

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
     * Liste mit temporären Dateien die nach dem Import gelöscht werden sollen.
     * @var array
     */
    protected $tempFilesToRemove = array();

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

    const TERM_INSERT_BLOCKSIZE = 15;

    public function __construct() {
        if(!defined('LIBXML_VERSION') || LIBXML_VERSION < '20620') {
            //Mindestversion siehe http://www.php.net/manual/de/xmlreader.readstring.php
            throw new Zend_Exception('LIBXML_VERSION must be at least 2.6.20 (or as integer 20620).');
        }
        $this->timer = (object)array('langSet' => 0, 'tig' => 0, 'term' => 0);
    }

    /**
     * Die als SplFileInfo übergebenen TBX Datei importieren
     * @see editor_Models_Import_TermListParser::import()
     */
    public function import(SplFileInfo $file, string $taskGuid, editor_Models_Languages $sourceLang, editor_Models_Languages $targetLang){
        if(! $file->isReadable()){
            throw new Zend_Exception($file.' is not Readable!');
        }
        $start = microtime(true);
        $this->insertIdsInTbx($file->getPathname());
        $after_insert = microtime(true);

        //languages welche aus dem TBX importiert werden sollen
        $this->languages[$sourceLang->getId()] = $this->normalizeLanguage($sourceLang->getRfc5646());
        $this->languages[$targetLang->getId()] = $this->normalizeLanguage($targetLang->getRfc5646());

        $this->xml = new XmlReader();
        $this->xml->open($file->getPathname());

        $this->taskGuid = $taskGuid;

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
    }

    /**
     * Fügt mittels der openTMS-Java-Bibliothek bei termEntry-, tig- und term-Tags id-Attribute mit eindeutigen Werten hinzu, sofern noch nicht vorhanden
     *
     * - wird eine Metadatendatei vor dem eigentlichen Import noch durch ein
     *   externes Tool verändert, wird die Originaldatei erweitert um die Endung
     *   ".orig" abgelegt und die veränderte Metadatei unter ihrem ursprünglichen
     *   Namen gespeichert. Diese Methode löscht alle veränderten Metadateien
     *   und benennt die ".orig"-Dateien um zu ihrem ursprünglichen Namen.
     *
     * @param string $filePath
     * @return boolean
     */
    protected function insertIdsInTbx($filePath) {
        $tagger = ZfExtended_Factory::get('editor_Models_Import_InvokeTermTagger');
        /* @var $tagger editor_Models_Import_InvokeTermTagger */
        $tagger->tagTbx($filePath, $filePath.'.withIds');

        $tempOrig = $filePath.'.orig';
        $this->tempFilesToRemove[$tempOrig] = $filePath;
        rename($filePath,$tempOrig);
        rename($filePath.'.withIds',$filePath);
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
     * termEntry Element verarbeiten
     */
    protected function handleTermEntry() {
        if(!$this->isStartTag()) {
            return; // END Tag => raus
        }

        //Term Entry ID ablegen
        $this->actualTermEntry = $this->xml->getAttribute('id');
        if(empty($this->actualTermEntry)) {
            $this->log('termEntry Tag without an ID found and ignored!');
            return;
        }

        //alles was kein termEntry ist verarbeiten.
        //Wenn ein termEntry erreicht wird, ist das das EndTag des aktuellen Term Entries
        while($this->xml->read() && $this->xml->name !== 'termEntry') {
            switch($this->xml->name) {
                case 'langSet':
                    $start = microtime(true);
                    $this->handleLanguage();
                    $this->timer->langSet += (microtime(true) - $start);
                    break;
                case 'tig':
                    $start = microtime(true);
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
            $termData['taskGuid'] = $this->taskGuid;
            //term; mid; status in $termData
            $termData['definition'] = $this->actualDefinition;
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
        /* @var $termTable editor_Models_Db_Terms */
        $termTable = ZfExtended_Factory::get('editor_Models_Db_Terms');

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
            $this->actualTig = array('mid' => null, 'term' => null, 'status' => null);
            return;
        }
        if(!$this->isEndTag()){
            return;
        }
        if(empty($this->actualTig) || empty($this->actualTig['mid'])){
            $this->log('tig Tag ohne relevanten Inhalt oder ohne term tag id Attribut! Wird ignoriert.');
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
        $this->actualTig['mid'] = $this->xml->getAttribute('id');
        $this->actualTig['term'] = $this->xml->readString();
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
     * Extrahiert die Term Definition
     */
    protected function handleDefinition() {
        if(!$this->isStartTag() || $this->xml->getAttribute('type') !== 'Definition'){
          return;
        }
        $this->actualDefinition = $this->xml->readString();
    }

    protected function isEndTag() {
        return ($this->xml->nodeType === XmlReader::END_ELEMENT);
    }

    protected function isStartTag() {
        return ($this->xml->nodeType === XmlReader::ELEMENT);
    }

    protected function log($logMessage) {
        $msg = $logMessage.'. Task: '.$this->taskGuid;
        /* @var $log ZfExtended_Log */
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        $log->logError($msg);
    }

    /**
     * entfernt die tmp Dateien
     * @see editor_Models_Import_TermListParser::cleanup()
     */
    public function cleanup() {
        foreach($this->tempFilesToRemove as $torestore => $toremove) {
            if(file_exists($torestore) && file_exists($toremove)) {
                unlink($toremove);
                rename($torestore, $toremove);
            }
        }
    }
}
