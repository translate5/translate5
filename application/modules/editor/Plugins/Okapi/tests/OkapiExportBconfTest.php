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
use MittagQI\Translate5\Plugins\Okapi\OkapiService;
use MittagQI\Translate5\Test\Import\Bconf;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\JsonTestAbstract;

class OkapiExportBconfTest extends JsonTestAbstract
{
    protected static array $requiredPlugins = [
        editor_Plugins_Okapi_Init::class,
    ];

    private static Bconf $testBconf;

    private static BconfEntity $bconf;

    /**
     * Just imports a bconf to test with
     */
    protected static function setupImport(Config $config): void
    {
        // TODO FIXME: still neccessary ?
        if (! Zend_Registry::isRegistered('Zend_Locale')) {
            Zend_Registry::set('Zend_Locale', new Zend_Locale('en'));
        }

        self::$testBconf = $config->addBconf('TestBconf', 'batchConfiguration.t5.bconf');
    }

    public function test10_ConfigurationAndApi()
    {
        $conf = Zend_Registry::get('config');
        $okapiConf = $conf->runtimeOptions->plugins->Okapi;
        $service = editor_Plugins_Okapi_Init::createService(OkapiService::ID, $conf);

        self::assertNotEmpty($okapiConf->dataDir, 'runtimeOptions.plugins.Okapi.dataDir not set');
        self::assertNotEmpty($service->getConfiguredServiceUrl(false), 'runtimeOptions.plugins.Okapi.api.url not set');

        self::$bconf = new BconfEntity();
        self::$bconf->load(self::$testBconf->getId());
        static::assertStringStartsWith(
            'TestBconf',
            self::$bconf->getName(),
            'Imported bconf\'s name is not like ' . 'TestBconf' . ' but ' . self::$bconf->getName()
        );

        $input = static::api()->getFile('batchConfiguration.t5.bconf');
        $output = self::$bconf->getPath();
        $failureMsg = "Original and repackaged Bconfs do not match\nInput was '$input', Output was '$output";

        self::assertFileEquals($input, $output, $failureMsg);

        self::assertTrue($service->check());
    }

    /*public function test40_OkapiTaskImport()
    {
        $config = static::getConfig();
        $config->import(
            $config
                ->addTask('en', 'de')
                //->setImportBconfId(self::$bconf->getDefaultBconfId())
                ->addUploadFile('workfiles/export-contentelements-14104-EN.xliff.typo3')
        );
    }*/
}
