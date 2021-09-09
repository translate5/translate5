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
class editor_Models_Terminology_BulkOperation_TransacGrp extends editor_Models_Terminology_BulkOperation_Abstract {


    /**
     * @var editor_Models_Terminology_Models_TransacgrpModel
     */
    protected $model;
    /**
     * @var editor_Models_Terminology_TbxObjects_TransacGrp
     */
    protected $importObject;

    public function __construct() {
        $this->model = new editor_Models_Terminology_Models_TransacgrpModel();
        $this->importObject = new editor_Models_Terminology_TbxObjects_TransacGrp();
    }

    /**
     * @param editor_Models_Terminology_TbxObjects_TransacGrp $elementObject
     */
    protected function fillParentIds(editor_Models_Terminology_TbxObjects_Abstract $elementObject)
    {
        $elementObject->termEntryId = $elementObject->parentEntry->id;
        $elementObject->termEntryGuid = $elementObject->parentEntry->entryGuid;
        $elementObject->termId = $elementObject->parentTerm->id ?? null;
        $elementObject->termTbxId = $elementObject->parentTerm->termTbxId ?? null;
        $elementObject->termGuid = $elementObject->parentTerm->guid ?? null;
        $elementObject->langSetGuid = $elementObject->parentLangset->langSetGuid ?? null;
    }
}
