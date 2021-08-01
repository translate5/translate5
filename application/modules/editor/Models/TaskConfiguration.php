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
 * TaskConfiguration Entity Objekt
 * 
 * @method void setId() setId(int $id)
 * @method integer getId() getId()
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method string getName() getName()
 * @method void setName() setName(string $name)
 * @method boolean getConfirmed() getConfirmed()
 * @method void setConfirmed() setConfirmed(bool $confirmed)
 * @method string getModule() getModule()
 * @method void setModule() setModule(string $module)
 * @method string getCategory() getCategory()
 * @method void setCategory() setCategory(string $category)
 * @method string getValue() getValue()
 * @method void setValue() setValue(string $value)
 * @method string getDefault() getDefault()
 * @method void setDefault() setDefault(string $default)
 * @method string getDefaults() getDefaults()
 * @method void setDefaults() setDefaults(string $defaults) comma seperated values!
 * @method string getType() getType()
 * @method void setType() setType(string $type)
 * @method string getDescription() getDescription()
 * @method void setDescription() setDescription(string $desc)
 * 
 * 
*/
class editor_Models_TaskConfiguration extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_TaskConfiguration';
    protected $validatorInstanceClass   = 'editor_Models_Validator_TaskConfiguration';
}
