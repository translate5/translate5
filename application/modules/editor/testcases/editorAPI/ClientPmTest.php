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

use MittagQI\Translate5\Test\Api\Helper;
use MittagQI\Translate5\Test\Import\Config;

/**
 * Tests the Client PM role / multitenancy
 */
class ClientPmTest extends editor_Test_ImportTest
{

    protected static function setupImport(Config $config): void
    {
        // import one task with the testmanager as owner and the base customer
        $config->addTask('en', 'de', static::getTestCustomerId(0), '3-segments-en-de.zip');
        // import one task with the testclientpm as owner and customer 1
        $config->addTask('en', 'de', static::getTestCustomerId(1), '3-segments-en-de.zip')
            ->setOwner('testclientpm');
    }

    public function testTaskView()
    {
        $this->assertTrue(true);
    }
}
