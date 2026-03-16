<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * User Preselection Entity Object — file translation defaults per user (TRANSLATE-5268)
 * @method string getId()
 * @method void setId(int $id)
 * @method string getUserId()
 * @method void setUserId(int $userId)
 * @method string getSourceLangFileDefault()
 * @method void setSourceLangFileDefault(?int $sourceLangFileDefault)
 * @method string getTargetLangFileDefault()
 * @method void setTargetLangFileDefault(?int $targetLangFileDefault)
 * @method string getTargetLangFileDefaultMulti()
 * @method void setTargetLangFileDefaultMulti(?string $targetLangFileDefaultMulti)
 * @method string getFileCustomerDefault()
 * @method void setFileCustomerDefault(?int $fileCustomerDefault)
 */
class editor_Models_UserPreselection extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = 'editor_Models_Db_UserPreselection';

    protected $validatorInstanceClass = 'editor_Models_Validator_UserPreselection';

    /***
     * Load or init user preselection model
     */
    public function loadOrSet(int $userId)
    {
        try {
            $this->loadByUser($userId);
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            parent::init();
            $this->setUserId($userId);
        }
    }

    /***
     * Load model for the given user
     */
    public function loadByUser(int $userId)
    {
        $this->loadRow('userId=?', $userId);
    }

    /***
     * Get the multi-target selections as an array
     */
    public function getTargetLangFileDefaultMultiArray(): array
    {
        $json = $this->getTargetLangFileDefaultMulti();
        if (empty($json)) {
            return [];
        }
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }
}
