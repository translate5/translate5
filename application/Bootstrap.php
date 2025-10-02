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

/**#@+
 * @author Marc Mittag
 * @package portal
 * @version 2.0
 *
 */

/**
 * Klasse zur Portalinitialisierung
 *
 * - In initApplication können Dinge zur Portalinitialisierung aufgerufen werden
 * - Alles für das Portal nötige ist jedoch in Resource-Plugins ausgelagert und
 *   wird über die application.ini definiert und dann über Zend_Application
 *   automatisch initialisert
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    public function _initApplication()
    {

    }

    /**
     * Initialize Integration Segment Update container and dto factory
     */
    protected function _initIntegrationSegmentUpdateAndDtoFactory()
    {
        if (! \Zend_Registry::isRegistered('integration.segment.update')) {
            error_log("In");
            \Zend_Registry::set(
                'integration.segment.update',
                \MittagQI\Translate5\Integration\UpdateSegmentService::create()
            );
        }

        if (! \Zend_Registry::isRegistered('integration.segment.update.dto_factory')) {
            \Zend_Registry::set(
                'integration.segment.update.dto_factory',
                \MittagQI\Translate5\Integration\SegmentUpdateDtoFactory::create()
            );
        }
    }
}
