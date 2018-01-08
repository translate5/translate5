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

/**
 * Initial Class of Plugin "XlfExportTranslateByAutostate"
 * This Plugin is for Across Connection where we have to abuse trans-units 
 * translate="yes/no" for filtering changed segments coming from translate5
 */
class editor_Plugins_XlfExportTranslateByAutostate_Init extends ZfExtended_Plugin_Abstract {
    
    /**
     * All non review states and reviewd_untouched are making translate no,
     * so the rest makes translate yes:
     * @var array
     */
    protected $translateYesStates = [
            editor_Models_Segment_AutoStates::REVIEWED,
            editor_Models_Segment_AutoStates::REVIEWED_AUTO,
            editor_Models_Segment_AutoStates::REVIEWED_UNCHANGED,
            editor_Models_Segment_AutoStates::REVIEWED_UNCHANGED_AUTO,
            editor_Models_Segment_AutoStates::REVIEWED_TRANSLATOR,
            editor_Models_Segment_AutoStates::REVIEWED_TRANSLATOR_AUTO,
            editor_Models_Segment_AutoStates::REVIEWED_PM,
            editor_Models_Segment_AutoStates::REVIEWED_PM_AUTO,
            editor_Models_Segment_AutoStates::REVIEWED_PM_UNCHANGED,
            editor_Models_Segment_AutoStates::REVIEWED_PM_UNCHANGED_AUTO,
    ];
    
    public function init() {
        $this->eventManager->attach('editor_Models_Export_FileParser_Xlf', 'writeTransUnit', array($this, 'handleWriteUnit'));
    }
    
    /**
     * Parameters are:
        'tag' => $tag,
        'key' => $key,
        'tagOpener' => $opener,
        'xmlparser' => $xmlparser,
        'segments' => $segments,
     * @param Zend_EventManager_Event $event
     */
    public function handleWriteUnit(Zend_EventManager_Event $event) {
        $segments = $event->getParam('segments');
        $autoStates = [];
        foreach($segments as $segment) {
            /* @var $segment editor_Models_Segment */
            $autoStates[] = $segment->getAutoStateId();
        }
        $autoStates = array_unique($autoStates);
        
        $foundStates = array_intersect($this->translateYesStates, $autoStates);

        //if there are some yes states its yes, so if the foundStates is empty, its no
        $translateNo = empty($foundStates);
        
        $attributes = $event->getParam('attributes');
        settype($attributes, 'array');
        $attributes['translate'] = $translateNo ? 'no' : 'yes';
        //setting back the attrbiutes in the event for further handlers, 
        // and the transunit attributes are generated from that array 
        $event->setParam('attributes', $attributes);
    }
}
