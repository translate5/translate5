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
     *   (aber nur weil tagBreakUp davor aufgerufen wurde und sonst das array nicht in der Struktur verändert wurde)
     * 
     * @param array $segment
     * @return array $segment
     */
    public function wordBreakUp($segment){
        $config = Zend_Registry::get('config');
        $regexWordBreak = $config->runtimeOptions->editor->export->wordBreakUpRegex;
        
        //by adding the count($split) and the $i++ only the array entries containing text (no tags) are parsed
        //this implies that only tagBreakUp may be called before and 
        // no other array structure manipulating method may be called between tagBreakUp and wordBreakUp!!!
        for ($i = 0; $i < count($segment); $i++) {
            $split = preg_split($regexWordBreak, $segment[$i], NULL, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            array_splice($segment, $i, 1, $split);
            $i = $i + count($split);
        }
        return $segment;
    }
    
    /**
     * splits the segment up into HTML tags / entities on one side and plain text on the other side
     * The order in the array is important for the following wordBreakUp, since there are HTML tags and entities ignored.
     * Caution: The ignoring is done by the array index calculation there!
     * So make no array structure changing things between word and tag break up! 
     * 
     * @param string $segment
     * @return array $segment
     */
    public  function tagBreakUp($segment){
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