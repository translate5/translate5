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

class editor_Models_Terminology_Models_AttributeDataType extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_AttributeDatatype';
    protected $validatorInstanceClass   = 'editor_Models_Validator_Term_AttributeDatatype';

    /**
     * Loads the label with given name and type, if it does not exist, the label will be created
     * @param string $labelName
     * @param string $labelType
     */
    public function loadOrCreate(string $labelName, string $labelType = '')
    {
        $s = $this->db->select()
        ->from($this->db)
        ->where('label = ?', $labelName);
        if (!empty($labelType)) {
            $this->setType($labelType);
            $s->where('type = ?', $labelType);
        }
        $row = $this->db->fetchRow($s);
        if ($row) {
            $this->row = $row;
            return;
        }
        $this->setLabel($labelName);
        $this->save();
    }

    /**
     * Return all labels with translated labelText
     * @return array
     */
    public function loadAllTranslated(): array
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
