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

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 * 

  /**
 * Enthält Methoden zum Auszeichnen der Änderungen zwischen ursprünglichem target und dem edited-Feld aus
 *
 */

abstract class editor_Models_Export_DiffTagger {
    /**
     * @var string Regex zur Tagerkennung, bereits mit Delimitern und Modifikatoren
     */
    protected $_regexTag;
    
    /**
     * @var array Regexes which define the opening and closing add changemarks
     */
    protected $_regexChangeMark = array('OpeningAdd'=>null,'ClosingAdd'=>null);
    
    /**
     * 
     * timestamp is a Unix-Timestamp
     * @var array $_additions array(array('guid'=>(string)'','timestamp'=>(string)'','username'=>''),...)
     */
    public $_additions = array();
    /**
     * 
     * timestamp is a Unix-Timestamp
     * @var array $_deletions array(array('guid'=>(string)'','timestamp'=>(string)'','username'=>''),...)
     */
    public $_deletions = array();
    /**
     * 
     * @var ZfExtended_Controller_Helper_Guid
     */
    protected $_guidHelper = array();
    
    public function __construct() {
        $this->_guidHelper = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'Guid'
        );
    }
    /**
     * zeichnet ein einzelnes Segment aus
     * 
     * @param array $target bereits in die Ursprungssyntax zurückgebautes target-Segment
     * @param array $edited bereits in die Ursprungssyntax zurückgebautes editiertes target-Segment (edited-Spalte)
     * @param string $changeTimestamp Zeitpunkt der letzten Änderung des Segments
     * @param string $userName Benutzername des Lektors
     * @return string $edited mit diff-Syntax fertig ausgezeichnet
     * 
     */
    abstract public function diffSegment($target, $edited,$changeTimestamp,$userName);
    /**
     * Zerlegt die Wortteile des segment-Arrays anhand der Wortgrenzen in ein Array,
     * welches auch die Worttrenner als jeweils eigene Arrayelemente enthält
     * 
     * - parst nur die geraden Arrayelemente, denn dies sind die Wortbestandteile
     * 
     * @param array $segment
     * @return array $segment
     */
    protected function wordBreakUp($segment){
        $config = Zend_Registry::get('config');
        $regexWordBreak = $config->runtimeOptions->editor->export->wordBreakUpRegex;
        
		 //$splitCharaceters = "\s+|(\s[.*()[]:;,'#+=?!$%&\"{}¡]+)|([.*()[]:;,'#+=?!$%&\"{}¡]+\s)|([.*()[]:;,'#+=?!$%&\"{}¡)]+$)|(^[.*()[]:;,'#+=?!$%&\"{}¡]+)";
        //parse nur die geraden Arrayelemente, denn dies sind die Wortbestandteile
        for ($i = 0; $i < count($segment); $i++) {
            $split = preg_split($regexWordBreak, $segment[$i], NULL, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            array_splice($segment, $i, 1, $split);
            $i = $i + count($split);
        }
        return $segment;
    }
    /**
     * Zerlegt Segmentstring anhand der Wortgrenzen in ein Array,
     * welches auch die Worttrenner als jeweils eigene Arrayelemente enthält
     * 
     * @param string $segment
     * @return array $segment
     */
    protected function tagBreakUp($segment){
        if(is_null($this->_regexTag)){
            throw new Zend_Exception('Regex zur Tagerkennung ist NULL');
        }
        return preg_split($this->_regexTag, $segment, NULL,  PREG_SPLIT_DELIM_CAPTURE);
    }
    /**
     * Generiert ein UUID gibt diese zurück
     * speichert in einem assoc Array $this->additions die UUID (Key), das Änderungsdatum (timestamp) sowie den username des Lektors. 
     * 
     * @param string $segment
     * @return array $segment
     */
    protected function addAdditionRevision($changeTimestamp,$userName){
        $guid = $this->_guidHelper->create();
        $this->_additions[] = array('guid'=>$guid,'timestamp'=>$changeTimestamp,'username'=>$userName);
        return $guid;
    }
    /**
     * Generiert ein UUID gibt diese zurück
     * speichert in einem assoc Array $this->additions die UUID (Key), das Änderungsdatum (timestamp) sowie den username des Lektors. 
     * 
     * @param string $segment
     * @return array $segment
     */
    protected function addDeleteRevision($changeTimestamp,$userName){
        $guid = $this->_guidHelper->create();
        $this->_deletions[] = array('guid'=>$guid,'timestamp'=>$changeTimestamp,'username'=>$userName);
        return $guid;
    }
    /**
     * removes the change-marks from xliff-qm-tags, because they are no changes
     * @param string $segment 
     * @return string $segment
     * array('OpeningAdd'=>null,'ClosingAdd'=>null,'OpeningDel'=>null,'ClosingDel'=>null);
     */
    //stand: tagremoval funzt noch nicht, wenn changes daneben und diff zeichnet neben tag auch nicht verändertes mit aus
    protected function removeChangeMarksFromXliffQmTags($segment){
        if(in_array(null, $this->_regexChangeMark, true)){
            throw new Zend_Exception('Regex for removal of changemarks is NULL');
        }
        $mqm = array();
        $callback = function ($matches) use(&$mqm){
            $nr = count($mqm);
            $mqm[] = $matches[0];
            return '~'.$nr.'~';
        };
        $segment = str_replace('~', '______tilde_translate5_____', $segment);
        $segment = preg_replace_callback('"(<mqm:[^>]*>)"',$callback,$segment);
        $search = array(
            '"'.$this->_regexChangeMark['OpeningAdd'].
                '([~\d]+)'.$this->_regexChangeMark['ClosingAdd'].
                '"',
            '"([~\d]+)('.$this->_regexChangeMark['ClosingAdd'].
                ')"',
            '"('.$this->_regexChangeMark['OpeningAdd'].
                ')([~\d]+)"'
            );
        $segment = preg_replace($search, array('\\1','\\2\\1','\\2\\1'), $segment);
        $count = count($mqm);
        for($i=0; $i<$count; $i++){
            $segment = str_replace('~'.$i.'~', $mqm[$i], $segment);
        }
        return str_replace('______tilde_translate5_____','~', $segment);
    }
}