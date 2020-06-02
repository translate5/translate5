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
 * Helper
 */
class Editor_Controller_Helper_Api extends Zend_Controller_Action_Helper_Abstract {
    /**
     * Since numeric IDs aren't really sexy to be used for languages in API,
     *  this method can also deal with rfc5646 strings and LCID numbers. The LCID numbers must be prefixed with 'lcid-' for example lcid-123
     * Not found / invalid languages are converted to 0, this should then be handled afterwards
     * 
     * @param mixed $languageParameter IN: the given language ID/rfc/lcid, OUT: the numeric language DB ID
     * @return editor_Models_Languages
     */
    public function convertLanguageParameters(&$languageParameter) {
        //ignoring if already integer like value or empty
        $language = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $language editor_Models_Languages */
        try {
            //if empty a notFound is triggered
            if(empty($languageParameter) || (int)$languageParameter > 0) {
                $language->load($languageParameter);
                return $language;
            }
            $matches = [];
            if(preg_match('/^lcid-([0-9]+)$/i', $languageParameter, $matches)) {
                $language->loadByLcid($matches[1]);
            }else {
                $language->loadByRfc5646($languageParameter);
            }
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            $languageParameter = 0;
            return null;
        }
        $languageParameter = $language->getId();
        return $language;
    }
}
