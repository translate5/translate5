<?php
/*
 START LICENSE AND COPYRIGHT
 
 This file is part of ZfExtended library
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 https://www.gnu.org/licenses/lgpl-3.0.txt
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
 https://www.gnu.org/licenses/lgpl-3.0.txt
 
 END LICENSE AND COPYRIGHT
 */

/**
 */
class editor_Logger_LanguageResourcesWriter extends ZfExtended_Logger_Writer_Abstract {
    public function write(ZfExtended_Logger_Event $event) {
        //currently we just do not write duplicates and duplicate info to the lang res log → the duplicate data is kept in the main log
        if($this->getDuplicateCount($event) > 0) {
            return;
        }
        $languageResource = $event->extra['languageResource'];
        /* @var $languageResource editor_Models_LanguageResources_LanguageResource */
        
        $log = ZfExtended_Factory::get('editor_Models_Logger_LanguageResources');
        /* @var $log editor_Models_Logger_LanguageResources */
        $log->setFromEventAndLanguageResource($event, $languageResource);
        $log->setLanguageResourceId($languageResource->getId());
        
        $event->getExtraFlattenendAndSanitized();
        $log->setExtra($event->getExtraAsJson());
        $log->save();
        //then we just unset the task in both
        unset($event->extra['languageResource']);
        unset($event->extraFlat['languageResource']);
    }
    
    public function isAccepted(ZfExtended_Logger_Event $event) {
        if(empty($event->extra) || empty($event->extra['languageResource']) || !is_a($event->extra['languageResource'], 'editor_Models_LanguageResources_LanguageResource')) {
            return false;
        }
        return parent::isAccepted($event);
    }
}