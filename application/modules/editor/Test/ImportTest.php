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

use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\Import\Task;

/**
 * Base Class for all API Tests that are importing tasks & resources
 */
abstract class editor_Test_ImportTest extends editor_Test_ApiTest
{
    /**
     * @var Config
     */
    private static Config $_config;

    /**
     * This is the central method to setup an ImportTest
     * Here all tasks and resources have to be added to the config
     * @param Config $config
     */
    protected static function setupImport(Config $config): void
    {

    }

    /**
     * Retrieves the test Config
     * @return Config
     */
    protected static function getConfig(): Config
    {
        return static::$_config;
    }

    /**
     * Retrieves the imported task (if eactly one was setup)
     * @return Task
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    protected static function getTask(): Task
    {
        return static::$_config->getTaskAt(0);
    }

    /**
     * internal setup for the base-classes
     * Do not override in concrete test-classes, use beforeTests there
     */
    protected static function testSpecificSetup()
    {
        // add a test-customer if setup-option set
        if (static::$setupOwnCustomer) {
            static::$ownCustomer = static::api()->addCustomer('API Testing::' . static::class);
        }

        // evaluate & process the import-setup
        static::$_config = new Config(static::api(), static::class, static::getTestLogin());
        static::setupImport(static::$_config);
        static::$_config->setup();

        // log the user in that is setup as the needed test-user, this must always be the last step
        if (static::api()->login(static::$setupUserLogin)) {
            static::assertLogin(static::$setupUserLogin);
        }
    }

    /**
     * internal teardown for the base-classes
     * Do not override in concrete test-classes, use afterTests there
     */
    protected static function testSpecificTeardown()
    {
        // teardown the configured stuff
        static::$_config->teardown();

        // as a final thing, remove the test-coustomer if setup-option set
        if (static::$setupOwnCustomer) {
            static::api()->deleteCustomer(static::$ownCustomer->id);
        }
    }
}
