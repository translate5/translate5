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
 * Entity Model for comment meta data
 * @method string getId()
 * @method void setId(int $id)
 * @method string getCommentId()
 * @method void setCommentId(integer $id)
 * @method string getOriginalId()
 * @method void setOriginalId(integer $id)
 * @method string getSeverity()
 * @method void setSeverity(string $severity)
 * @method string getVersion()
 * @method void setVersion(string $version)
 * @method string getAffectedField()
 * @method void setAffectedField(string $field)
 */
class editor_Models_Comment_Meta extends ZfExtended_Models_Entity_MetaAbstract
{
    protected $dbInstanceClass = 'editor_Models_Db_CommentMeta';

    /**
     * @return Zend_Db_Table_Row_Abstract
     */
    public function loadByCommentId(int $commentId)
    {
        return $this->loadRow('commentId = ?', $commentId);
    }

    /**
     * Adds an empty meta data rowset to the DB.
     */
    public function initEmptyRowset()
    {
        $db = new $this->dbInstanceClass();

        /* @var $db Zend_Db_Table_Abstract */
        try {
            $db->insert([
                'commentId' => $this->getCommentId(),
            ]);
        } catch (Zend_Db_Statement_Exception $e) {
            try {
                $this->handleIntegrityConstraintException($e);
            } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
                //"duplicate entry" errors are ignored.
                return;
            }
        }
    }
}
