<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfInvalidException;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\FilterEntity;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\OkapiFilterInventory;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\T5FilterInventory;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;

/**
 * REST Endpoint Controller to serve the Default Bconfs Filter List for the Bconf-Management in the Preferences
 * This controller is not bound to an entity
 */
class editor_Plugins_Okapi_BconfDefaultFilterController extends ZfExtended_RestController
{
    /**
     * copied the init method, parent can not be used, since no real entity is used here
     */
    public function init(): void
    {
        $this->initRestControllerSpecific();
    }

    /**
     * sends all default bconf filters as JSON, Translate5 adjusted and okapi defaults
     *
     * @throws Zend_Db_Table_Exception
     */
    public function getallAction(): void
    {
        $bconf = new FilterEntity();
        $startIndex = $bconf->getHighestId() + 1000000;
        $t5Rows = T5FilterInventory::instance()->getGridRows($startIndex);
        $this->view->rows = array_merge($t5Rows, OkapiFilterInventory::instance()->getGridRows(count($t5Rows) + $startIndex));
        $this->view->total = count($this->view->rows);
    }

    /**
     * Special set the extensions for a non-custom default filter (a filter without database-entry)
     *
     * @throws Throwable
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws BconfInvalidException
     * @throws OkapiException
     */
    public function setextensionsAction(): void
    {
        $identifier = $this->getParam('identifier');
        $extensions = explode(',', $this->getParam('extensions', ''));
        $extensions = array_values(array_filter($extensions));

        $bconf = new BconfEntity();
        $bconf->load($this->getParam('bconfId'));
        $extensionMapping = $bconf->getExtensionMapping();
        if (count($extensions) > 0) {
            $extensionMapping->changeFilter($identifier, $extensions);
        } else {
            // no extensions means, we remove the filter
            $extensionMapping->removeFilter($identifier);
        }
        // must be reflected in the bconf as well ...
        $bconf->pack();
    }
}
