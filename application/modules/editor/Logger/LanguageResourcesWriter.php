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

class editor_Logger_LanguageResourcesWriter extends ZfExtended_Logger_Writer_Abstract
{
    /**
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     */
    public function write(ZfExtended_Logger_Event $event): void
    {
        //currently we just do not write duplicates and duplicate info to the lang res log
        // → the duplicate data is kept in the main log
        if ($this->getDuplicateCount($event) > 0) {
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

    public function isAccepted(ZfExtended_Logger_Event $event): bool
    {
        $langRes = $event->extra['languageResource'] ?? null;
        if ($langRes && is_a($langRes, 'editor_Models_LanguageResources_LanguageResource')) {
            return parent::isAccepted($event);
        }

        return false;
    }
}
