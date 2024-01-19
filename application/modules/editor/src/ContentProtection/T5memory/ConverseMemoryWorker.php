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

declare(strict_types=1);

namespace MittagQI\Translate5\ContentProtection\T5memory;

use editor_Models_Segment_Whitespace as Whitespace;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Manager;
use editor_Services_OpenTM2_Connector as Connector;
use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\Model\LanguageResourceRulesHash;
use MittagQI\Translate5\ContentProtection\Model\LanguageRulesHash;
use MittagQI\Translate5\LanguageResource\Status;
use ZfExtended_Factory;
use ZfExtended_Worker_Abstract;

class ConverseMemoryWorker extends ZfExtended_Worker_Abstract
{
    private int $languageResourceId;
    private int $languageId;
    private LanguageResource $languageResource;
    private array $memoriesBackup;
    private LanguageResourceRulesHash $languageResourceRulesHash;

    public function __construct()
    {
        parent::__construct();
        $this->log = \Zend_Registry::get('logger')
            ->cloneMe('editor.content-protection.opentm2.conversion');
        $this->tmConversionService = new TmConversionService(
            new ContentProtectionRepository(),
            ContentProtector::create(ZfExtended_Factory::get(Whitespace::class))
        );
    }

    private function restoreLangResourceMemories(): void
    {
        $this->languageResource->addSpecificData('memories', $this->memoriesBackup);
        $this->languageResource->save();
    }

    protected function validateParameters($parameters = [])
    {
        if (!array_key_exists('languageResourceId', $parameters)) {
            return false;
        }

        if (!array_key_exists('languageId', $parameters)) {
            return false;
        }

        $this->languageResourceId = (int) $parameters['languageResourceId'];
        $this->languageId = (int) $parameters['languageId'];

        $this->languageResource = ZfExtended_Factory::get(LanguageResource::class);
        $this->languageResource->load($this->languageResourceId);

        if (editor_Services_Manager::SERVICE_OPENTM2 !== $this->languageResource->getServiceType()) {
            return false;
        }

        $this->memoriesBackup = $this->languageResource->getSpecificData('memories', parseAsArray: true) ?? [];

        $this->languageResourceRulesHash = ZfExtended_Factory::get(LanguageResourceRulesHash::class);
        try {
            $this->languageResourceRulesHash->loadByLanguageResourceIdAndLanguageId(
                $this->languageResourceId,
                $this->languageId
            );
        } catch (\ZfExtended_Models_Entity_NotFoundException) {
            $this->languageResourceRulesHash->init([
                'languageResourceId' => $this->languageResourceId,
                'languageId' => $this->languageId
            ]);
        }

        return true;
    }

    protected function handleWorkerException(\Throwable $workException) {
        $this->workerException = $workException;

        $this->restoreLangResourceMemories();
        $this->resetConversionStarted();
    }
    
    protected function work(): bool
    {
        $languageRulesHash = ZfExtended_Factory::get(LanguageRulesHash::class);
        $languageRulesHash->loadByLanguageId($this->languageId);

        if ($this->tmConversionService->isTmConverted($this->languageResourceId)) {
            return true;
        }

        if (!$this->tmConversionService->isConversionInProgress($this->languageResourceId)) {
            return false;
        }

        $connector = new Connector();

        $sourceLang = (int)$this->languageResource->getSourceLang();
        $targetLang = (int)$this->languageResource->getTargetLang();

        $connector->connectTo($this->languageResource, $sourceLang, $targetLang);
        $status = $connector->getStatus($this->languageResource->getResource(), $this->languageResource);

        if (Status::AVAILABLE !== $status) {
            $this->log->error(
                'E1377',
                'OpenTM2: Unable to use the memory because of the memory status {status}.',
                [
                    'languageResource' => $this->languageResource,
                    'status' => $status,
                ]
            );
            $this->resetConversionStarted();

            return false;
        }

        $exportFilename = $connector->export($connector->getValidExportTypes()['TMX']);

        if (!file_exists($exportFilename)) {
            $this->log->error(
                'E1587',
                'Conversion: TM was not exported. TMX file does not exists: {filename}',
                [
                    'filename' => $exportFilename,
                    'languageResource' => $this->languageResource
                ]
            );

            $this->resetConversionStarted();

            return false;
        }

        $fileinfo = [
            'tmp_name' => $exportFilename,
            'type' => $connector->getValidExportTypes()['TMX'],
            'name' => basename($exportFilename),
        ];

        if (!$connector->addTm($fileinfo, ['createNewMemory' => true])) {
            $this->log->error(
                'E1588',
                'Conversion: Failed to import file: {filename}',
                [
                    'filename' => $exportFilename,
                    'languageResource' => $this->languageResource
                ]
            );

            $this->restoreLangResourceMemories();
            $this->resetConversionStarted();

            return false;
        }

        unlink($exportFilename);

        $onMemoryDeleted = fn ($filename) =>
            fn () => $this->languageResource->addSpecificData(
                'memories',
                array_values(
                    array_filter(
                        $this->languageResource->getSpecificData('memories', parseAsArray: true),
                        fn ($memory) => $memory['filename'] !== $filename
                    )
                )
            );

        foreach ($this->memoriesBackup as $memory) {
            if (!$connector->deleteMemory($memory['filename'], $onMemoryDeleted($memory['filename']))) {
                $this->log->error(
                    'E1589',
                    'Conversion: Memory [{filename}] was not deleted in process of conversion',
                    array_merge($memory, ['languageResource' => $this->languageResource])
                );
            }
        }

        // Language Resource was possibly changed in $onMemoryDeleted call
        $this->languageResource->save();

        $this->languageResourceRulesHash->setHash($languageRulesHash->getHash());
        $this->resetConversionStarted();

        return true;
    }

    private function resetConversionStarted(): void
    {
        $this->languageResourceRulesHash->setConversionStarted(null);
        $this->languageResourceRulesHash->save();
    }
}