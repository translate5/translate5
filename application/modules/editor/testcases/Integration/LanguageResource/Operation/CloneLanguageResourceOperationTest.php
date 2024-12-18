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

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Integration\LanguageResource\Operation;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Manager as ServiceManager;
use editor_Services_OpenTM2_Service as OpenTM2Service;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\CustomerAssocService;
use MittagQI\Translate5\LanguageResource\Operation\CloneLanguageResourceOperation;
use MittagQI\Translate5\LanguageResource\Operation\CreateLanguagePairOperation;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use PHPUnit\Framework\TestCase;

class CloneLanguageResourceOperationTest extends TestCase
{
    private function prepareData(): LanguageResource
    {
        $languageResource = new LanguageResource();
        $languageResource->init([
            'resourceId' => ServiceManager::SERVICE_OPENTM2 . '_1',
            'resourceType' => 'tm',
            'serviceType' => ServiceManager::SERVICE_OPENTM2,
            'serviceName' => OpenTM2Service::NAME,
            'color' => '000000',
            'name' => 'oldName',
        ]);
        $languageResource->createLangResUuid();
        $languageResource->save();

        $customerAssocService = CustomerAssocService::create();

        foreach ([1, 2] as $customerId) {
            $customerAssocService->associate((int) $languageResource->getId(), $customerId);
        }

        $createLanguagePairOperation = CreateLanguagePairOperation::create();
        $createLanguagePairOperation->createLanguagePair(
            (int) $languageResource->getId(),
            4,
            5
        );

        return $languageResource;
    }

    public function testClone(): void
    {
        $oldLanguageResource = $this->prepareData();

        $connectorMock = $this->createMock(\editor_Services_OpenTM2_Connector::class);
        $connectorMock->expects(self::once())->method('addTm');

        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->method('getConnector')
            ->willReturn($connectorMock);

        $newName = 'newName';

        $cloneLanguageResourceOperation = new CloneLanguageResourceOperation(
            CreateLanguagePairOperation::create(),
            CustomerAssocService::create(),
            new LanguageResourceRepository(),
            $serviceManager
        );

        $newLanguageResource = $cloneLanguageResourceOperation->clone($oldLanguageResource, $newName);

        self::assertNotEquals($oldLanguageResource->getId(), $newLanguageResource->getId());
        self::assertEquals($newName, $newLanguageResource->getName());
        self::assertEquals([1, 2], $newLanguageResource->getCustomers());
        self::assertEquals(4, $newLanguageResource->getSourceLang());
        self::assertEquals(5, $newLanguageResource->getTargetLang());
    }

    protected function tearDown(): void
    {
        $db = \Zend_Registry::get('db');
        $db->query('TRUNCATE TABLE LEK_languageresources_customerassoc');
        $db->query('TRUNCATE TABLE LEK_languageresources_languages');
        $db->query('DELETE FROM LEK_languageresources');
    }
}
