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
        /* @var $diff ZfExtended_Diff */
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
            if($addition === ''){
                return;
            }
            return $this->surroundWithIns($addition);
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
            if($deletion === ''){
                return;
            }
            return $this->surroundWithDel($deletion);
        }
        return '';
    }
}