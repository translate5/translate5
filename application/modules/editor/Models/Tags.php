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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Tags Object Instance as needed in the application
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method string getOrigin() getOrigin()
 * @method void setOrigin() setOrigin(string $origin)
 * @method string getLabel() getLabel()
 * @method void setLabel() setLabel(string $label)
 * @method string getOriginalTagId() getOriginalTagId()
 * @method void setOriginalTagId() setOriginalTagId(string $originalTagId)
 * 
 * see ZfExtended_Models_Entity_Abstract:
 * - string getSpecificData() getSpecificData()
 * - void setSpecificData() setSpecificData(string $specificData)
 */
class editor_Models_Tags extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Tags';
    protected $validatorInstanceClass = 'editor_Models_Validator_Tag';
    
    /**
     * All tags for the given origin.
     * @param string $origin
     * @return array
     */
    public function loadByOrigin(string $origin) {
        $s = $this->db->select();
        $s->where('origin = ?', $origin);
        return parent::loadFilterdCustom($s);  // ??????????????
    }
}
