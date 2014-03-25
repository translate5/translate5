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
 * Contains Methods for Fileparsing on the Import
 * 
 * - Child Classes must implement and use the abstract methods
 */
abstract class editor_Models_Import_FileParser {
    /**
     * @var string
     */
    protected $_origFile = NULL;
    /**
     * @var string
     */
    protected $_skeletonFile = NULL;
    /**
     * @var string
     */
    protected $_fileName = NULL;
    /**
     * @var integer
     */
    protected $_fileId = NULL;
    
    /**
     * array containing all segment data parsed
     * @var [array] 2D array, first level has keys which map to the segment field names. Second Level array must be compliant to editor_Models_Db_SegmentDataRow
     */
    protected $segmentData = array();
    
    /**
     * @var string mid des aktuellen Segments
     */
    protected $_mid = NULL;
    /**
     * @var array legt fest, ob das aktuell geparste segment editierbar sein soll
     *            Struktur: array(Segment-ID => boolean)
     */
    protected $_editSegment = array();
    /**
     * @var array setzt die autoStateId für die betreffenden Segmente
     *            Struktur: array(Segment-ID => boolean)
     */
    protected $_autoStateId = array();
    /**
     * @var array shows if the segment had been autopropagated
     *            Struktur: array(Segment-ID => boolean)
     */
    protected $_autopropagated = array();
    /**
     * @var array Matchwert des aktuell geparsten Segments
     *            Struktur: array(Segment-ID => Integer)
     */
    protected $_matchRateSegment = array();
    /**
     * @var array Segment vorübersetzt (true) oder nicht (false)
     *            Struktur: array(Segment-ID => boolean)
     */
    protected $_pretransSegment = array();
    /**
     * @var boolean legt für die aktuelle Fileparser-Instanz fest, ob 100-Matches
     *              editiert werden dürfen (true) oder nicht (false)
     */
    public $_edit100PercentMatches = false;
    /**
     * @var editor_ImageTag_Left
     */

    protected $_leftTag = NULL;
    /**
     * @var editor_ImageTag_Right
     */

    protected $_rightTag = NULL;
    /**
     * @var editor_ImageTag_Single
     */

    protected $_singleTag = NULL;

    /**
     * @var int innerhalb eines Importprojekts für jede Terminstanz (ein
     *          ausgezeichneter Term in einem Segment) eindeutige ID, die bei
     *          jedem Term hochgezählt werden muss
     *
     */
    protected $_projectTerminstanceId = 0;

    /**
     * @var array enthält alle bereits während des Imports aus der DB ausgelesenen Terme
     *            der im Dokument enthaltenen Terme im Format array(mid => editor_Models_Term)
     *            die mid entspricht der mid des terms aus dem sdlxliff
     *
     */
    protected $_terms = array();

    /**
     * @var editor_Models_Segment_TermTag
     */
    protected $segmentTermTag;
    
    /**
     * @var array terms2save ein Array mit editor_Models_TermTagData-Objekten als Werten, das
     *           während des Parsings eines Segments befüllt, danach genutzt und wieder geleert wird usw.
     */
    public $_terms2save = array();

    /**
     * @var editor_Models_Languages Entity Instanz der Sprache
     */
    protected $_sourceLang = NULL;
    
    /**
     * @var editor_Models_Languages Entity Instanz der Sprache
     */
    protected $_targetLang = NULL;
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var string taskGuid
     */
    protected $_taskGuid = NULL;

    /**
     * Contains a list of processors of the parsed segmentdata
     * @var [editor_Models_Import_SegmentProcessor]
     */
    protected $segmentProcessor = array();
    
    /**
     * all files with extensions listet here are converted to utf8. For details see method convert2utf8
     * @var array
     */
    protected $_convert2utf8 = array('csv');
    /**
     * @var string $path path to the file in the encoding of the filesystem (runtimeOptions.fileSystemEncoding)
     */
    protected $_path;
    
    /**
     * Auto State Definer
     * @var editor_Models_SegmentAutoStates
     */
    protected $autoStates;
    
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager;
    
    /**
     * @param string $path pfad zur Datei in der Kodierung des Filesystems (also runtimeOptions.fileSystemEncoding)
     * @param string $fileName Dateiname utf-8 kodiert
     * @param integer $fileId
     * @param boolean $edit100PercentMatches
     * @param editor_Models_Languages $sourceLang
     * @param editor_Models_Languages $targetLang
     * @param editor_Models_Task $targetLang
     */
    public function __construct(string $path, string $fileName,integer $fileId,
            boolean $edit100PercentMatches, editor_Models_Languages $sourceLang, editor_Models_Languages $targetLang, editor_Models_Task $task){
        $this->_origFile = file_get_contents($path);
        $this->_path = $path;
        $this->_fileName = $fileName;
        $this->_fileId = $fileId;
        $this->_edit100PercentMatches = $edit100PercentMatches;
        $this->_leftTag = ZfExtended_Factory::get('editor_ImageTag_Left');
        $this->_rightTag = ZfExtended_Factory::get('editor_ImageTag_Right');
        $this->_singleTag = ZfExtended_Factory::get('editor_ImageTag_Single');
        $this->segmentTermTag = ZfExtended_Factory::get('editor_Models_Segment_TermTag');
        $this->_sourceLang = $sourceLang;
        $this->_targetLang = $targetLang;
        $this->task = $task;
        $this->_taskGuid = $task->getTaskGuid();
        $this->autoStates = ZfExtended_Factory::get('editor_Models_SegmentAutoStates');
        $this->handleEncoding();
    }
    
    public function addSegmentProcessor(editor_Models_Import_SegmentProcessor $proc){
        $this->segmentProcessor[] = $proc;
    }
    
    /**
     * set the shared instance of the segmentFieldManager
     * @param $sfm editor_Models_SegmentFieldManager
     */
    public function setSegmentFieldManager(editor_Models_SegmentFieldManager $sfm) {
        $this->segmentFieldManager = $sfm;
        $this->initDefaultSegmentFields();
    }
    
    /**
     * protects whitespace inside a segment with a tag
     *
     * @param string $segment
     * @return string $segment
     */
    protected function parseSegmentProtectWhitespace($segment) {
        $search = array(
          "\r\n",  
          "\n",  
          "\r"
        );
        $replace = array(
          '<hardReturn />',
          '<softReturn />',
          '<macReturn />'
        );
        $segment =  str_replace($search, $replace, $segment);
        
        return preg_replace_callback(
                array(
                    '" ( +)"'), //protect multispaces
                        function ($match) {
                            return ' <space ts="' . implode(',', unpack('H*', $match[1])) . '"/>';
                        }, 
            $segment);
    }
    
    /**
     * returns the internally used SegmentFieldManager
     * @return editor_Models_SegmentFieldManager
     */
    public function getSegmentFieldManager() {
        return $this->segmentFieldManager;
    }
    
    /**
     * @return array array('tagImageName1.png','tagImageName2.png',...)
     */
    public function getTagImageNames() {
        return array_merge(
            $this->_leftTag->_imagesInObject,
            $this->_rightTag->_imagesInObject,
            $this->_singleTag->_imagesInObject
            );
    }
    
    /**
     * Gibt den Inhalt das erzeugte Skeleton File zurück 
     * @return string
     */
    public function getSkeletonFile() {
        return $this->_skeletonFile;
    }
    
    /**
     * übernimmt das eigentliche FileParsing
     *
     * - ruft untergeordnete Methoden für das Fileparsing auf, wie extractSegment, setSegmentAttribs
     *
     */
    abstract protected function parse();

    /**
     * initiates the default fields source and target, should be overwritten if fields differ.
     */
    protected function initDefaultSegmentFields() {
        $sfm = $this->segmentFieldManager;
        $sourceEdit = (boolean) $this->task->getEnableSourceEditing();
        $sfm->addField($sfm::LABEL_SOURCE, editor_Models_SegmentField::TYPE_SOURCE, $sourceEdit);
        $sfm->addField($sfm::LABEL_TARGET, editor_Models_SegmentField::TYPE_TARGET);
    }
    
    /**
     * Does the fileparsing
     */
    public function parseFile() {
        foreach($this->segmentProcessor as $p) {
            $p->preParseHandler($this);
        }
        $this->parse();
        foreach($this->segmentProcessor as $p) {
            $p->postParseHandler($this);
        }
    }
    
    /**
     * Speichert das Segment und die zugehörigen Terme in die Datenbank
     *
     * @param mixed transunit
     * @return integer segmentId
     */
    protected function setAndSaveSegmentValues(){
        $this->calculateLocalSegmentAttribs();
        foreach($this->segmentProcessor as $p) {
            $r = $p->process($this);
            if($r !== false) {
                $result = $r;
            }
        }
        foreach($this->segmentProcessor as $p) {
            $result = $p->postProcessHandler($this, $result);
        }
        return $result;
    }

    /**
     * returns a placeholder for reexport the edited content
     * @param integer $segmentId
     * @param string $name
     */
    protected function getFieldPlaceholder($segmentId, $name) {
        return '<lekTargetSeg id="'.$segmentId.'" field="'.$name.'" />';
    }
    
    /**
     * checks the encoding of the file and saves the encoding to the file-table
     * - only saves encoding for formats listed in this->_convert2utf8
     * @triggers error if encoding could not be detected; in this case does not save anything to db
     */
    protected function handleEncoding() {
        $convert = false;
        foreach ($this->_convert2utf8 as $format){
            if(preg_match('"\.'.$format.'$"i', $this->_fileName)===1){
                $convert = TRUE;
            }
        }
        if(!$convert)return;
        $enc = $this->checkAndConvert2utf8();
        if(!$enc){
            trigger_error('The encoding of the file "'.
                    $this->_fileName.
                    '" is none of the encodings utf-8, iso-8859-1 and win-1252.',
                    E_USER_ERROR);
        }
        $file = ZfExtended_Factory::get('editor_Models_File');
        $file->load($this->_fileId);
        $file->setEncoding($enc);
        $file->save();
    }

    /**
     * converts all fileformats listed in $this->_convert2utf8 to utf8, if possible
     * - calls saveEncoding, catches exeption
     * - converts Latin1 and windows-1252-encoded file to utf-8, even if part of
     *   file is in utf8 already. In any other case a user_error is triggered
     * - if exception is caught, saveEncoding is called after conversion
     *   again and this time a caught exception triggers a user_error
     * @return string encoding | false if encoding is none of utf-8, 'iso-8859-1', 'windows-1251'
     */
    protected function checkAndConvert2utf8() {
        if(mb_detect_encoding($this->_origFile, 'UTF-8', true))return 'UTF-8';
        
        $list = array('iso-8859-1', 'windows-1251');
     
        foreach ($list as $item) {
            $sample = iconv($item, $item, $this->_origFile);
            if (md5($sample) == md5($this->_origFile)) { 
                 $this->_origFile = iconv($item, 'UTF-8', $this->_origFile);
                 return $item;
            }
        }
        return false;
    }

    /**
     * Gibt die Terme des aktuellen Segments zurück und leert die zurückgegebene interne Liste
     */
    public function getAndCleanTerms() {
        $result = $this->_terms2save;
        $this->_terms2save = array();
        return $result;
    }
    
    /**
     * Sets $this->_editSegment, $this->_matchRateSegment and $this->_autopropagated
     * and $this->_pretransSegment and $this->_autoStateId for the segment currently worked on
     * @param mixed transunit
     */
    abstract protected function setSegmentAttribs($transunit);
    
    /**
     * calculates and sets segment attributes needed by us, this info doesnt exist directly in the segment. 
     * These are currently: pretransSegment, editSegment, autoStateId
     * Parameters are given by the current segment
     */
    protected function calculateLocalSegmentAttribs() {
        $matchRate = $this->_matchRateSegment[$this->_mid];
        $isAutoprop = $this->_autopropagated[$this->_mid];
        $isFullMatch = ($matchRate === 100);
        $isEditable  = !$isFullMatch || $this->_edit100PercentMatches || $isAutoprop;
        $isTranslated = $this->isTranslated();
        $this->_editSegment[$this->_mid] = $isEditable;
        $this->_pretransSegment[$this->_mid] = $isFullMatch && !$isAutoprop;
        $this->_autoStateId[$this->_mid] = $this->autoStates->calculateImportState($isEditable, $isTranslated);
    }
    
    /**
     * returns true if at least one target has a translation set
     */
    protected function isTranslated() {
        foreach($this->segmentData as $name => $data) {
            $field = $this->segmentFieldManager->getByName($name);
            if($field->type !== editor_Models_SegmentField::TYPE_TARGET) {
                continue;
            }
            if(!empty($data['original'])) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Extrahiert aus einer trans-unit Quell-
     * und Zielsegmente
     *
     * - speichert die Segmente in der Datenbank
     * @param mixed $transUnit
     * @return mixed $transUnit
     */
    abstract protected function extractSegment($transUnit);
    
    /**
     * Extrahiert aus einem Segment (bereits ohne umschließende Tags) die Tags
     *
     * - speichert die Tags in der Datenbank
     *
     * @param mixed $segment
     * @param boolean isSource
     * @return mixed $segment enthält anstelle der Tags die vom JS benötigten Replacement-Tags
     *         wobei die id die ID des Segments in der Tabelle Segments darstellt
     */
    abstract protected function parseSegment($segment,$isSource);

    /**
     * Returns if current segment had been autopropagated
     * @return boolean
     */
    public function getIfAutopropagated() {
        return $this->_autopropagated[$this->_mid];
    }

    /**
     * Gibt die Matchrate des aktuellen Segments zurück
     * @return integer
     */
    public function getMatchRate() {
        return $this->_matchRateSegment[$this->_mid];
    }
    
    /**
     * Gibt den EditSegment Status des aktuellen Segments zurück
     * @return boolean
     */
    public function getEditable() {
        return $this->_editSegment[$this->_mid];
    }
    
    /**
     * Gibt den EditSegment Status des aktuellen Segments zurück
     * @return integer
     */
    public function getAutoStateId() {
        return $this->_autoStateId[$this->_mid];
    }
    
    /**
     * Gibt den EditSegment Status des aktuellen Segments zurück
     * @return boolean
     */
    public function getPretrans() {
        return (int)$this->_pretransSegment[$this->_mid];
    }
    
    /**
     * Gibt die MID des aktuellen Segments zurück
     * @return string
     */
    public function getMid(){
        return $this->_mid;
    }

    /**
     * returns a reference to the array with alle parsed data fields
     * The reference enables the ability to manipulate the parsed data in the segmentprocessors (see MqmParser)
     * @see editor_Models_Import_SegmentProcessor_MqmParser
     */
    public function & getFieldContents() {
        return $this->segmentData;
    }
}
