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
 * 
  `term` varchar(19000) NOT NULL DEFAULT '' COMMENT 'the proposed term',
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
 * 
 * 
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getTerm() getTerm()
 * @method void setTerm() setTerm(string $term)
 * @method integer getTermId() getTermId()
 * @method void setTermId() setTermId(integer $id)
 * @method integer getCollectionId() getCollectionId()
 * @method void setCollectionId() setCollectionId(integer $id)
 * @method string getUserGuid() getUserGuid()
 * @method void setUserGuid() setUserGuid(string $userGuid)
 * @method string getUserName() getUserName()
 * @method void setUserName() setUserName(string $userName)
 * @method string getCreated() getCreated()
 * @method void setCreated() setCreated(string $date)
 */
class editor_Models_Term_Proposal extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Term_Proposal';
    protected $validatorInstanceClass = 'editor_Models_Validator_Term_Proposal';
    
    /**
     * Loads a proposal by termId
     * @param integer $termId
     * @return Zend_Db_Table_Row_Abstract
     */
    public function loadByTermId(int $termId): Zend_Db_Table_Row_Abstract {
        return $this->loadRow('termId = ?', $termId);
    }
}