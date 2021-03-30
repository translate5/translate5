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

use Doctrine\DBAL\Exception;

/**
 * Class editor_Models_Terms_Images
 * TermsImage Instance
 *
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getType() getType()
 * @method string setType() setType(string $type)
 * @method string getTarget() getTarget()
 * @method string setTarget() setTarget(string $target)
 * @method string getValue() getValue()
 * @method string setValue() setValue(string $value)
 * @method string getXbase() getXbase()
 * @method string setXbase() setXbase(string $xbase)
 * @method integer getCollectionId() getCollectionId()
 * @method integer setCollectionId() setCollectionId(integer $collectionId)
 * @method string getEntryId() getEntryId()
 * @method string setEntryId() setEntryId(string $entryId)
 * @method string getTermEntryUniqueId() getTermEntryUniqueId()
 * @method string setTermEntryUniqueId() setTermEntryUniqueId(string $termEntryUniqueId)
 * @method string getElementUniqueId() getElementUniqueId()
 * @method string setElementUniqueId() setElementUniqueId(string $elementUniqueId)
 * @method string getUniqueId() getUniqueId()
 * @method string setUniqueId() setUniqueId(string $uniqueId)
 */
class editor_Models_Terminology_Models_ImagesModel extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_Images';

    /**
     * editor_Models_Terms_Images constructor.
     */
    public function __construct() {
        parent::__construct();
    }
}
