<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\T5Memory;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Segment as Segment;
use editor_Services_Manager;
use MittagQI\Translate5\Integration\Contract\SegmentUpdateDtoFactoryInterface;
use MittagQI\Translate5\Integration\Contract\UpdateSegmentInterface;
use MittagQI\Translate5\Integration\SegmentUpdate\UpdateSegmentDTO;
use MittagQI\Translate5\LanguageResource\Adapter\Exception\SegmentUpdateException;
use MittagQI\Translate5\T5Memory\Api\Exception\SegmentTooLongException;
use MittagQI\Translate5\T5Memory\DTO\UpdateOptions;
use MittagQI\Translate5\T5Memory\Exception\SegmentUpdateCheckException;
use Zend_Config;
use ZfExtended_Logger;

class UpdateSegmentService implements UpdateSegmentInterface
{
    public function __construct(
        private readonly ZfExtended_Logger $logger,
        private readonly SegmentUpdateDtoFactoryInterface $segmentUpdateDtoFactory,
        private readonly UpdateRetryService $updateRetryService,
    ) {
    }

    public static function create(): self
    {
        return new self(
            \Zend_Registry::get('logger')->cloneMe('editor.t5memory.update'),
            SegmentUpdateDtoFactory::create(),
            UpdateRetryService::create(),
        );
    }

    public function supports(LanguageResource $languageResource): bool
    {
        return \editor_Services_OpenTM2_Service::NAME === $languageResource->getServiceName();
    }

    public function update(
        LanguageResource $languageResource,
        Segment $segment,
        Zend_Config $config,
        ?UpdateOptions $updateOptions = null,
    ): void {
        if (null === $updateOptions) {
            $updateOptions = new UpdateOptions(
                useSegmentTimestamp: false,
                saveToDisk: true,
                saveDifferentTargetsForSameSource: (bool) $config
                    ->runtimeOptions
                    ->LanguageResources
                    ->t5memory
                    ->saveDifferentTargetsForSameSource,
                recheckOnUpdate: false,
            );
        }

        $this->updateWithDTO(
            $languageResource,
            $segment,
            $this->segmentUpdateDtoFactory->getUpdateDTO(
                $languageResource,
                $segment,
                $config,
                $updateOptions,
            ),
            $config,
            $updateOptions,
        );
    }

    public function updateWithDTO(
        LanguageResource $languageResource,
        Segment $segment,
        UpdateSegmentDTO $dto,
        Zend_Config $config,
        UpdateOptions $updateOptions,
    ): void {
        if ('' === trim($dto->target)) {
            return;
        }

        try {
            $this->updateRetryService->updateWithRetry(
                $languageResource,
                $dto,
                $updateOptions,
                $config,
            );
        } catch (SegmentUpdateException $e) {
            editor_Services_Manager::reportTMUpdateError(errorMsg: $e->getMessage());

            if ($e->getPrevious() instanceof SegmentTooLongException) {
                $this->logger->info('E1306', 't5memory: could not save segment to TM', [
                    'languageResource' => $languageResource,
                    'segment' => $segment,
                    'apiError' => $e->getMessage(),
                ]);
            } else {
                $this->logger->error('E1306', 't5memory: could not save segment to TM', [
                    'languageResource' => $languageResource,
                    'segment' => $segment,
                    'apiError' => $e->getMessage(),
                ]);
            }

            throw $e;
        } catch (SegmentUpdateCheckException $e) {
            $this->logger->error(
                'E1586',
                $e->getMessage(),
                [
                    'languageResource' => $languageResource,
                    'segment' => $segment,
                    'response' => $e->apiResponse,
                    'target' => $dto->target,
                ]
            );
        }
    }
}
