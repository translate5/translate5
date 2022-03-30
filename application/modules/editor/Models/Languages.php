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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Editor specific language class
 */
class editor_Models_Languages extends ZfExtended_Languages {
    protected $dbInstanceClass = 'editor_Models_Db_Languages';
    protected $validatorInstanceClass   = 'editor_Models_Validator_Language';

    /**
     * Since numeric IDs aren't really sexy to be used for languages in API,
     *  this method can also deal with rfc5646 strings and LCID numbers. The LCID numbers must be prefixed with 'lcid-' for example lcid-123
     * Not found / invalid languages are converted to 0, this should then be handled afterwards
     *
     * @param mixed $languageParameter IN: the given language ID/rfc/lcid, OUT: the numeric language DB ID
     * @return editor_Models_Languages
     */
    public function convertLanguage(&$languageParameter) {
        //ignoring if already integer like value or empty
        try {
            //if empty a notFound is triggered
            if(empty($languageParameter) || (int)$languageParameter > 0) {
                $this->load($languageParameter);
                return $this;
            }
            $matches = [];
            if(preg_match('/^lcid-([0-9]+)$/i', $languageParameter, $matches)) {
                $this->loadByLcid($matches[1]);
            }else {
                $this->loadByRfc5646($languageParameter);
            }
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            $languageParameter = 0;
            return null;
        }
        $languageParameter = $this->getId();
        return $this;
    }
}
