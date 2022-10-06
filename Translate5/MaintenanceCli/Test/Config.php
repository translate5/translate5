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
namespace Translate5\MaintenanceCli\Test;

/**
 * Holds important configs that need to be set up for the API-tests
 */
class Config {

    /**
     * The folder task-specific data etc. will be stored
     */
    const DATA_DIRECTORY = 'testdata';

    /**
     * The configs that define USERDATA pathes
     */
    const DATA_CONFIGS = [

        /* Configs that reference pathes in the USERDATA dir */

        'runtimeOptions.dir.tmp' => '../testdata/tmp',
        'runtimeOptions.dir.logs' => '../testdata/cache',
        /* 'runtimeOptions.dir.locales' => '../testdata/locales', Will not be relocated as it containes git-controlled files */
        'runtimeOptions.dir.taskData' => '../testdata/editorImportedTasks',
        'runtimeOptions.plugins.Okapi.dataDir' => '../testdata/editorOkapiBconf',
        'runtimeOptions.plugins.VisualReview.fontsDataDir' => '../testdata/editorVisualReviewFonts'
    ];

    /**
     * The configs that either need a fixed value or will be fetched from the application database
     * If the value is null, the value will be fetched from the application-database
     * all values will be saved to the translate5_test database
     */
    const CONFIGS = [

        /* Configs that need to be of fixed value */

        'runtimeOptions.customers.anonymizeUsers' => 1,
        'runtimeOptions.editor.notification.userListColumns' => '["surName","firstName","email","role","state","deadlineDate"]',
        'runtimeOptions.import.enableSourceEditing' => 1,
        'runtimeOptions.import.sdlxliff.importComments' => 1,
        'runtimeOptions.import.xlf.preserveWhitespace' => 0,
        'runtimeOptions.InstantTranslate.minMatchRateBorder' => 70,
        'runtimeOptions.plugins.SpellCheck.liveCheckOnEditing' => 1,
        'runtimeOptions.plugins.VisualReview.directPublicAccess' => 1,
        'runtimeOptions.tbx.termLabelMap' => '{"legalTerm": "permitted", "admittedTerm": "permitted", "preferredTerm": "preferred", "regulatedTerm": "permitted", "deprecatedTerm": "forbidden", "supersededTerm": "forbidden", "standardizedTerm": "permitted"}',

        /* Configs that need to be taken from the application database */

        'runtimeOptions.server.name' => null,
        'runtimeOptions.server.protocol' => null,
        'runtimeOptions.errorCodesUrl' => null,

        'runtimeOptions.LanguageResources.opentm2.server' => null,
        'runtimeOptions.LanguageResources.opentm2.tmprefix' => null,
        'runtimeOptions.LanguageResources.moses.server' => null,
        'runtimeOptions.LanguageResources.sdllanguagecloud.server' => null,

        'runtimeOptions.termTagger.url.gui' => null,
        'runtimeOptions.termTagger.url.import' => null,
        'runtimeOptions.termTagger.url.default' => null,

        'runtimeOptions.plugins.DeepL.authkey' => null,
        'runtimeOptions.plugins.DeepL.server' => null,
        'runtimeOptions.plugins.FrontEndMessageBus.socketServer.route' => null,
        'runtimeOptions.plugins.FrontEndMessageBus.socketServer.port' => null,
        'runtimeOptions.plugins.FrontEndMessageBus.socketServer.httpHost' => null,
        'runtimeOptions.plugins.FrontEndMessageBus.socketServer.schema' => null,
        'runtimeOptions.plugins.FrontEndMessageBus.messageBusURI' => null,
        'runtimeOptions.plugins.GlobalesePreTranslation.api.url' => null,
        'runtimeOptions.plugins.Okapi.server' => null,
        'runtimeOptions.plugins.Okapi.serverUsed' => null,
        'runtimeOptions.plugins.PangeaMt.server' => null,
        'runtimeOptions.plugins.SpellCheck.languagetool.url.default' => null,
        'runtimeOptions.plugins.SpellCheck.languagetool.url.import' => null,
        'runtimeOptions.plugins.SpellCheck.languagetool.url.gui' => null,
        'runtimeOptions.plugins.VisualReview.googleCloudApiKey' => null,
        'runtimeOptions.plugins.VisualReview.shellCommandGoogleChrome' => null,
        'runtimeOptions.plugins.VisualReview.shellCommandPdf2Html' => null,
        'runtimeOptions.plugins.VisualReview.shellCommandPdfMerge' => null,
        'runtimeOptions.plugins.VisualReview.shellCommandPdfOptimizer' => null,
        'runtimeOptions.plugins.VisualReview.shellCommandWget' => null
    ];

    /**
     * The name of the test-db will follow a fixed scheme
     * @param string $applicationDatabaseName
     * @return string
     * @throws \ZfExtended_Exception
     */
    public static function createTestDatabaseName(string $applicationDatabaseName) : string{
        // we have to be really picky here ...
        if(empty($applicationDatabaseName)){
            throw new \ZfExtended_Exception('Empty applicationDatabaseName provided!');
        }
        // trying to respect camel-case vs underscore naming-schemes here, of course just an attempt
        if(strtolower($applicationDatabaseName) === $applicationDatabaseName){
            return $applicationDatabaseName.'_test';
        }
        return $applicationDatabaseName.'Test';
    }

    /**
     * Retrieves the configs for a test-database
     * @return array
     */
    public static function getTestConfigs() : array {
        return array_merge(self::DATA_CONFIGS, self::CONFIGS);
    }

    /**
     * Retrieves the configs for a productive/application database
     * @param string $dataFolder
     * @return array
     */
    public static function getApplicationConfigs(string $dataFolder='data') : array {
        $configs = [];
        foreach(self::DATA_CONFIGS as $name => $value){
            $configs[$name] = str_replace('/'.self::DATA_DIRECTORY.'/', '/'.$dataFolder.'/', $value);
        }
        return array_merge($configs, self::CONFIGS);
    }

    /**
     * Retrieves the folders inside the USERDATA directory that needs to be cleaned when the database is recrteated
     * @return string[]
     */
    public static function getUserDataFolders() : array {
        $folders = [];
        foreach(self::DATA_CONFIGS as $name => $path){
            $parts = explode('/', $path);
            $folders[] = array_pop($parts);
        }
        return $folders;
    }
}
