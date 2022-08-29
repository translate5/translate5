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
class editor_Models_Terminology_BulkOperation_Attribute extends editor_Models_Terminology_BulkOperation_Abstract {
    const LOAD_EXISTING = 'termEntryId = ?';

    /**
     * @var editor_Models_Terminology_Models_AttributeModel
     */
    protected $model;
    /**
     * @var editor_Models_Terminology_TbxObjects_Attribute
     */
    protected $importObject;

    /***
     * Internal cache of the toBeProcessed attributes used for checking duplicates
     * @var array
     */
    protected array $duplicateCheck = [];

    public function __construct() {
        $this->model = new editor_Models_Terminology_Models_AttributeModel();
        $this->importObject = new editor_Models_Terminology_TbxObjects_Attribute();
    }

    public function add(editor_Models_Terminology_TbxObjects_Abstract $importObject) {

        // check and merge if the imported object exist in the toBeProcessed array
        if($this->checkForDuplicates($importObject)){
            return;
        }
        $this->toBeProcessed[] = $importObject;
    }

    /**
     * @throws Zend_Db_Statement_Exception
     */
    public function loadExisting(int $id) {
        $this->existing = [];
        parent::loadExisting($id);
    }

    /**
     * @param editor_Models_Terminology_TbxObjects_Attribute $elementObject
     */
    protected function fillParentIds(editor_Models_Terminology_TbxObjects_Abstract $elementObject)
    {
        $elementObject->termEntryId = $elementObject->parentEntry->id;
        $elementObject->termEntryGuid = $elementObject->parentEntry->entryGuid;
        $elementObject->langSetGuid = $elementObject->parentLangset->langSetGuid ?? null;
        $elementObject->termId = $elementObject->parentTerm->id ?? null;
        $elementObject->termTbxId = $elementObject->parentTerm->termTbxId ?? null;
        $elementObject->termGuid = $elementObject->parentTerm->guid ?? null;
    }

    /***
     * Check if in the currently imported attribute buffer there is already the same attribute. If match is found,
     * the 2 attributes will be merged into one with comma separated values
     * @param editor_Models_Terminology_TbxObjects_Abstract $importObject
     * @return bool
     */
    private function checkForDuplicates(editor_Models_Terminology_TbxObjects_Abstract $importObject): bool
    {
        if( !isset($this->duplicateCheck[$importObject->getCollectionKey()])){
            $this->duplicateCheck[$importObject->getCollectionKey()] = true;
            return false;
        }

        // duplicate exist -> find the original element and merge the values
        foreach ($this->toBeProcessed as $processed) {
            if($processed->getCollectionKey() === $importObject->getCollectionKey()){
                $processed->value .= ', '.$importObject->value;
                return true;
            }
        }

        return false;
    }

    /**
     * frees the internal storage
     */
    public function freeMemory()
    {
        parent::freeMemory();
        $this->duplicateCheck = [];
    }

    /***
     * @return void
     */
    public function resetToBeProcessed()
    {
        parent::resetToBeProcessed();
        $this->duplicateCheck = [];
    }
}
