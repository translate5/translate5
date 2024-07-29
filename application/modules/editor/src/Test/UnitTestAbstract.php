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

namespace MittagQI\Translate5\Test;

use PHPUnit\Framework\TestCase;
use Throwable;
use Zend_Config;
use Zend_Registry;

/**
 * Base Class for all Unit Tests
 * Adds capabilities to save/resore the global Zend_Config to enable overwriting the config for a test
 */
abstract class UnitTestAbstract extends TestCase
{
    public const TYPE = 'unit';

    private static Zend_Config $_config;

    final public static function setUpBeforeClass(): void
    {
        try {
            self::$_config = Zend_Registry::get('config');
            static::beforeTests();
        } catch (Throwable $e) {
            Zend_Registry::set('config', self::$_config);
            static::afterTests();

            throw $e;
        }
    }

    final public static function tearDownAfterClass(): void
    {
        Zend_Registry::set('config', self::$_config);
        static::afterTests();
    }

    /**
     * This API can be used overwrite the config for the lifespan of the test
     */
    final public static function setConfig(object $config): void
    {
        Zend_Registry::set('config', $config);
    }

    /**
     * Use this method to add setting up additional stuff before the tests are performed
     */
    public static function beforeTests(): void
    {
    }

    /**
     * Use this method to clean up additional stuff after the tests have been performed
     */
    public static function afterTests(): void
    {
    }
}
