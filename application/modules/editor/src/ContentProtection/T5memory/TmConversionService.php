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

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_LanguageResources_Languages;
use editor_Models_Segment_Whitespace as Whitespace;
use MittagQI\Translate5\ContentProtection\ConversionState;
use MittagQI\Translate5\ContentProtection\DTO\RulesHashDto;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\Model\LanguageRulesHashService;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use ZfExtended_Factory;

class TmConversionService implements TmConversionServiceInterface
{
    private ?array $languageRulesHashMap = null;

    /**
     * @var array<int, RulesHashDto>|null
     */
    private ?array $languageResourceRulesHashMap = null;

    public function __construct(
        private readonly ContentProtectionRepository $contentProtectionRepository,
        private readonly LanguageRepository $languageRepository,
        private readonly LanguageRulesHashService $languageRulesHashService,
        private readonly LanguageResourceRepository $languageResourceRepository,
    ) {
    }

    public static function create(?Whitespace $whitespace = null)
    {
        $contentProtectionRepository = ContentProtectionRepository::create();
        $languageRepository = new LanguageRepository();

        return new self(
            $contentProtectionRepository,
            $languageRepository,
            new LanguageRulesHashService($contentProtectionRepository, $languageRepository),
            new LanguageResourceRepository(),
        );
    }

    public function setRulesHash(LanguageResource $languageResource, int $sourceLanguageId, int $targetLangId): void
    {
        $languageRulesHash = $this->languageRulesHashService->findOrCreate($sourceLanguageId, $targetLangId);

        $languageResource->addSpecificData(
            LanguageResource::PROTECTION_HASH,
            $languageRulesHash->getHash()
        );
        $languageResource->save();
    }

    public function isTmConverted(int $languageResourceId): bool
    {
        if (! isset($this->getLanguageResourceRulesHashMap()[$languageResourceId])) {
            return false;
        }

        $hashDto = $this->getLanguageResourceRulesHashMap()[$languageResourceId];
        $languages = $hashDto->languages;
        $hash = $hashDto->hash;

        if (! isset($this->getLanguageRulesHashMap()[$languages['source']][$languages['target']])) {
            $lrLanguage = new editor_Models_LanguageResources_Languages();

            foreach ($lrLanguage->loadByLanguageResourceId($languageResourceId) as $languagePair) {
                $sourceLang = $this->languageRepository->find((int) $languagePair['sourceLang']);
                $targetLang = $this->languageRepository->find((int) $languagePair['targetLang']);

                if ($this->contentProtectionRepository->hasActiveRules($sourceLang, $targetLang)) {
                    return false;
                }
            }

            return null === $hash;
        }

        if (null === $hash) {
            $hash = md5('');
        }

        return $this->getLanguageRulesHashMap()[$languages['source']][$languages['target']] === $hash;
    }

    public function getConversionState(int $languageResourceId): ConversionState
    {
        return match (true) {
            $this->isConversionStarted($languageResourceId) => ConversionState::ConversionStarted,
            $this->isConversionScheduled($languageResourceId) => ConversionState::ConversionScheduled,
            $this->isTmConverted($languageResourceId) => ConversionState::Converted,
            default => ConversionState::NotConverted,
        };
    }

    private function getLanguageResourceRulesHashMap(): array
    {
        if (null === $this->languageResourceRulesHashMap) {
            $this->languageResourceRulesHashMap = $this->contentProtectionRepository->getLanguageResourceRulesHashMap();
        }

        return $this->languageResourceRulesHashMap;
    }

    private function getLanguageRulesHashMap(): array
    {
        if (null === $this->languageRulesHashMap) {
            $this->languageRulesHashMap = $this->contentProtectionRepository->getLanguageRulesHashMap();
        }

        return $this->languageRulesHashMap;
    }

    private function isConversionStarted(int $languageResourceId): bool
    {
        if (! isset($this->getLanguageResourceRulesHashMap()[$languageResourceId])) {
            return false;
        }

        return $this->getLanguageResourceRulesHashMap()[$languageResourceId]->conversionStarted;
    }

    private function isConversionScheduled(int $languageResourceId): bool
    {
        if (! isset($this->getLanguageResourceRulesHashMap()[$languageResourceId])) {
            return false;
        }

        return $this->getLanguageResourceRulesHashMap()[$languageResourceId]->conversionScheduled;
    }

    public function scheduleConversion(int $languageResourceId): void
    {
        $languageResource = $this->languageResourceRepository->get($languageResourceId);

        $languageResource->markScheduledConversion();

        $this->languageResourceRepository->save($languageResource);

        $worker = ZfExtended_Factory::get(ConverseMemoryWorker::class);
        if ($worker->init(parameters: [
            'languageResourceId' => $languageResourceId,
        ])) {
            $worker->queue();
        }
    }
}
