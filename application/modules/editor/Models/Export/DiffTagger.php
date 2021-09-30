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

    /***
     * Insert tag string
     * @var string
     */
    const INSERT_TAG="ins";

    /***
     * Delete tag string
     * @var string
     */
    const DELETE_TAG="del";

    /***
     * Delete tag attributes. Defined as key value, where the key is the attribute name, and the value is the attribute value
     * @var array
     */
    public  $deleteTagAttributes=array();

    /***
     * Insert tag attributes. Defined as key value, where the key is the attribute name, and the value is the attribute value
     * @var array
     */
    public  $insertTagAttributes=array();

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
    protected function wordBreakUp($segment){
        return editor_Utils::wordBreakUp($segment);
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
    protected  function tagBreakUp($segment){
        return editor_Utils::tagBreakUp($segment);
    }

    /**
     * generates an UUID and returns it
     * saves the changed data in internal arrays
     *
     * @param string $changeTimestamp
     * @param string $userName
     * @param boolean $addition true → addition, false → deletion
     * @return string
     */
    protected function addRevision($changeTimestamp, $userName, $addition = true): string{
        $revision = [
            'guid'=>ZfExtended_Utils::uuid(),
            'timestamp'=>$changeTimestamp,
            'username'=>$userName
        ];

        if($addition) {
            $this->_additions[] = $revision;
        }
        else {
            $this->_deletions[] = $revision;
        }

        return $revision['guid'];
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

    /***
     * Surround the text content with given tag.
     * @param string $tag
     * @param string $content
     * @return string
     */
    protected function surroundWith($tag,$content){
        //check which attribute array should be used
        $attributes=null;
        if($tag==self::INSERT_TAG){
            $attributes=$this->insertTagAttributes;
        }else{
            $attributes=$this->deleteTagAttributes;
        }

        //build the opening tag and add the attributes
        $tagcontent=[];
        $tagcontent[]='<'.$tag;
        foreach ($attributes as $att=>$value) {
            $tagcontent[]=' '.$att.'="'.$value.'" ';
        }
        $tagcontent[]='>';
        //apply the content
        $tagcontent[]=$content;
        $tagcontent[]='</'.$tag.'>';

        //merge the content as string
        return implode('', $tagcontent);
    }

    /***
     * Surround the content as ins tag
     * @param string $content
     * @return string
     */
    protected function surroundWithIns($content){
        return $this->surroundWith(self::INSERT_TAG, $content);
    }

    /***
     * Surround the content as del tag
     * @param string $content
     * @return string
     */
    protected function surroundWithDel($content){
        return $this->surroundWith(self::DELETE_TAG, $content);
    }
}