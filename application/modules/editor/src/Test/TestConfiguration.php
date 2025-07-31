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

namespace MittagQI\Translate5\Test;

use MittagQI\Translate5\Service\Services;
use Zend_Exception;
use ZfExtended_Exception;
use ZfExtended_Factory;
use ZfExtended_Plugin_Manager;

/**
 * Holds important configs that need to be set up for the API-tests
 * These Configs are either defined here (value NOT NULL) or copied from the application DB to the test DB (value IS NULL)
 */
final class TestConfiguration
{
    /**
     * The folder task-specific data etc. will be stored when using the test-DB
     */
    public const DATA_DIRECTORY = 'testdata';

    /**
     * Represents a config-value that can be present in test-configs that must be replaced with the installations base-url
     */
    public const BASE_URL = '{BASE_URL}';

    /**
     * The configs that define USERDATA pathes//null checks for no concrete value but if not empty
     */
    public const DATA_CONFIGS = [
        /* Configs that reference pathes in the USERDATA dir */

        'runtimeOptions.dir.tmp' => '../testdata/tmp',
        'runtimeOptions.dir.logs' => '../testdata/cache',
        /* 'runtimeOptions.dir.locales' => '../testdata/locales', Will not be relocated as it containes git-controlled files */
        'runtimeOptions.dir.taskData' => '../testdata/editorImportedTasks',
        'runtimeOptions.dir.languageResourceData' => '../testdata/editorLanguageResources',
        'runtimeOptions.plugins.Okapi.dataDir' => '../testdata/editorOkapiBconf',
        'runtimeOptions.plugins.VisualReview.fontsDataDir' => '../testdata/editorVisualReviewFonts',
    ];

    /**
     * The configs that either need a fixed value or will be fetched from the application database
     * If the value is null, the value will be fetched from the application-database
     * all values will be saved to the translate5_test database
     */
    public const CONFIGS = [
        /* Configs that need to be of fixed value */
        'runtimeOptions.customers.anonymizeUsers' => 1,
        'runtimeOptions.editor.notification.userListColumns' => '["surName","firstName","email","role","state","deadlineDate"]',
        'runtimeOptions.import.enableSourceEditing' => 1,
        'runtimeOptions.import.sdlxliff.importComments' => 1,
        'runtimeOptions.import.xlf.preserveWhitespace' => 0,
        'runtimeOptions.tbx.termLabelMap' => '{"legalTerm": "permitted", "admittedTerm": "permitted", "preferredTerm": "preferred", "regulatedTerm": "permitted", "deprecatedTerm": "forbidden", "supersededTerm": "forbidden", "standardizedTerm": "permitted"}',

        /* Configs that need to be taken from the application database */
        'runtimeOptions.server.internalURL' => null,
        'runtimeOptions.server.name' => null,
        'runtimeOptions.server.protocol' => null,
        'runtimeOptions.errorCodesUrl' => null,
        'runtimeOptions.LanguageResources.moses.server' => null, // TODO FIXME: this should come from a proper ExternalService
        'runtimeOptions.LanguageResources.sdllanguagecloud.server' => null, // TODO FIXME: this should come from a proper ExternalService
        'runtimeOptions.LanguageResources.microsoft.apiUrl' => null, // TODO FIXME: this should come from a proper ExternalService
        'runtimeOptions.LanguageResources.microsoft.apiKey' => null, // TODO FIXME: this should come from a proper ExternalService
    ];

    /**
     * The name of the test-db will follow a fixed scheme
     * @throws ZfExtended_Exception
     */
    public static function createTestDatabaseName(string $applicationDatabaseName): string
    {
        // we have to be really picky here ...
        if (empty($applicationDatabaseName)) {
            throw new ZfExtended_Exception('Empty applicationDatabaseName provided!');
        }
        // trying to respect camel-case vs underscore naming-schemes here, of course just an attempt
        if (strtolower($applicationDatabaseName) === $applicationDatabaseName) {
            return $applicationDatabaseName . '_test';
        }

        return $applicationDatabaseName . 'Test';
    }

    /**
     * Retrieves the configs for a test-database
     */
    public static function getTestConfigs(): array
    {
        return array_merge(self::DATA_CONFIGS, self::CONFIGS, Services::getTestConfigs(), self::getPluginConfigs());
    }

    /**
     * Retrieves the configs for a productive/application database
     */
    public static function getApplicationConfigs(string $dataFolder = 'data'): array
    {
        $configs = [];
        foreach (self::DATA_CONFIGS as $name => $value) {
            $configs[$name] = str_replace('/' . self::DATA_DIRECTORY . '/', '/' . $dataFolder . '/', $value);
        }

        return array_merge($configs, self::CONFIGS, Services::getTestConfigs(), self::getPluginConfigs());
    }

    /**
     * Retrieves the folders inside the USERDATA directory that needs to be cleaned when the database is recreated
     * @return string[]
     */
    public static function getUserDataFolders(): array
    {
        $folders = [];
        foreach (self::DATA_CONFIGS as $name => $path) {
            $parts = explode('/', $path);
            $folders[] = array_pop($parts);
        }

        return $folders;
    }

    /**
     * Retrieves the configs coming from plugins
     */
    /**
     * @throws Zend_Exception
     */
    private static function getPluginConfigs(): array
    {
        $pluginmanager = ZfExtended_Factory::get(ZfExtended_Plugin_Manager::class);

        return $pluginmanager->getTestConfigs();
    }
}
