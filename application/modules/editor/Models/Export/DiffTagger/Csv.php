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
 * tags the changes between original target and edited target - <ins>-Tags for inserted contents
 * and <del>-tags for deleted contents
 */

class editor_Models_Export_DiffTagger_Csv extends editor_Models_Export_DiffTagger {

    /**
     * @var string
     */
    protected $_changeTimestamp = NULL;
    /**
     * @var string
     */
    protected $_userName = NULL;

    public function __construct() {
        parent::__construct();
    }
    
    /**
     * @var string Regex zur Tagerkennung, bereits mit Delimitern und Modifikatoren
     */
    protected $_regexTag = '"(<[^<>]*>)"';
    /**
     * @var array Regexes which define the opening and closing add changemarks
     */
    protected $_regexChangeMark = array(
        'OpeningAdd'=>'<ins>',
        'ClosingAdd'=>'</ins>'
        );
    
    /**
     * zeichnet ein einzelnes Segment aus
     *
     * @param string $target bereits in die Ursprungssyntax zurückgebautes target-Segment
     * @param string $edited bereits in die Ursprungssyntax zurückgebautes editiertes target-Segment (edited-Spalte)
     * @param string $changeTimestamp Zeitpunkt der letzten Änderung des Segments
     * @param string $userName Benutzername des Lektors
     * @return string $edited mit diff-Syntax fertig ausgezeichnet
     */
    public function diffSegment($target, $edited, $changeTimestamp, $userName) {
        $targetArr = $this->tagBreakUp($target);
        $editedArr = $this->tagBreakUp($edited);

        $targetArr = $this->wordBreakUp($targetArr);
        $editedArr = $this->wordBreakUp($editedArr);

        $diff = ZfExtended_Factory::get('ZfExtended_Diff');
        $diffRes = $diff->process($targetArr, $editedArr);
        
        
        foreach ($diffRes as $key => &$val) {
            if (is_array($val)) {
                $val['i'] = $this->markAddition($val['i']);
                $val['d'] = $this->markDeletion($val['d']);
                $val = implode('', $val);
            }
        }
        return $this->removeChangeMarksFromXliffQmTags(implode('', $diffRes));
    }
    
    /**
     * add the sourounding <ins>-Tags for the change-markers to the i-subarray
     * of a specific changed part of a segment in the return-array of ZfExtended_Diff
     *
     * @param array $i Array('added')
     * @return string additions inclosed by '<ins>'addition'</ins>' or empty string if no Addition
     */
    protected function markAddition($i) {
        if (count($i) > 0) {
            
            $addition = implode('', $i);
            if($addition === '')return;
            return '<ins>' . $addition . '</ins>';
        }
        return '';
    }
    /**
     * add the sourounding <ins>-Tags for the change-markers to the i-subarray
     * of a specific changed part of a segment in the return-array of ZfExtended_Diff
     *
     * @param array $i Array('added')
     * @return string additions inclosed by '<ins>'addition'</ins>' or empty string if no Addition
     */
    protected function markDeletion($d) {
        if (count($d) > 0) {
            
            $deletion = implode('', $d);
            if($deletion === '')return;
            return '<del>' . $deletion . '</del>';
        }
        return '';
    }
}