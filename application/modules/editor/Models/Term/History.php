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

/**
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getTerm() getTerm()
 * @method void setTerm() setTerm(string $term)
 * @method string getDefinition() getDefinition()
 * @method void setDefinition() setDefinition(string $term)
 * @method integer getTermId() getTermId()
 * @method void setTermId() setTermId(integer $id)
 * @method integer getCollectionId() getCollectionId()
 * @method void setCollectionId() setCollectionId(integer $id)
 * @method string getCreated() getCreated()
 * @method void setCreated() setCreated(string $date)
 * @method string getHistoryCreated() getHistoryCreated()
 * @method void setHistoryCreated() setHistoryCreated(string $date)
 * @method string getCreated() getCreated()
 * @method void setCreated() setCreated(string $date)
 * @method string getUpdated() getUpdated()
 * @method void setUpdated() setUpdated(string $date)
 * @method string getStatus() getStatus()
 * @method void setStatus() setStatus(string $status)
 * @method string getProcessStatus() getProcessStatus()
 * @method void setProcessStatus() setProcessStatus(string $status)
 * @method string getUserGuid() getUserGuid()
 * @method void setUserGuid() setUserGuid(string $status)
 * @method string getUserName() getUserName()
 * @method void setUserName() setUserName(string $status)
 */
class editor_Models_Term_History extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Term_History';
    
    public function getFieldsToUpdate() {
        return [
            'collectionId',
            'term',
            'status',
            'processStatus',
            'definition',
            'userGuid',
            'userName',
            'created',
            'updated',
        ];
    }
}