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

class editor_Models_Terminology_Models_AttributeDataType extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_AttributeDatatype';
    protected $validatorInstanceClass   = 'editor_Models_Validator_Term_AttributeDatatype';


    /**
     * Load the label with given name,type and level, if it does not exist, the label will be created
     * @param string $labelName
     * @param string $labelType
     */
    public function loadOrCreate(string $labelName, string $labelType = '',array $level = [
        editor_Models_Terminology_TbxObjects_Attribute::ATTRIBUTE_LEVEL_ENTRY,
        editor_Models_Terminology_TbxObjects_Attribute::ATTRIBUTE_LEVEL_LANGUAGE,
        editor_Models_Terminology_TbxObjects_Attribute::ATTRIBUTE_LEVEL_TERM])
    {
        $s = $this->db->select()
        ->from($this->db)
        ->where('label = ?', $labelName);
        if (!empty($labelType)) {
            $this->setType($labelType);
            $s->where('type = ?', $labelType);
        }
        $levelSql = [];
        // for each level, add like search
        foreach ($level as $l) {
            $levelSql[] = 'level LIKE "%'.$l.'%"';
        }
        $s->where(implode(' OR ',$levelSql));
        $row = $this->db->fetchRow($s);
        if ($row) {
            $this->row = $row;
            return;
        }
        $this->setLabel($labelName);
        $this->setDataType(editor_Models_Terminology_TbxObjects_Attribute::ATTRIBUTE_DEFAULT_DATATYPE);
        $this->setLevel(implode(',',$level));
        $this->save();
    }

    /***
     * Return all labels with translated labelText
     * @return array
     */
    public function loadAllTranslated(bool $addUniqueKey = false): array
    {
        $labels = $this->loadAll();
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        foreach ($labels as &$label){
            if (empty($label['labelText'])) {
                continue;
            }
            $label['labelText'] = $translate->_($label['labelText']);
        }

        return $labels;
    }
}
