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

namespace MittagQI\Translate5\LanguageResource\Adapter;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_LanguageResources_Languages;
use MittagQI\Translate5\Repository\LanguageRepository;
use ReflectionException;
use Zend_Cache_Exception;
use ZfExtended_Models_Entity_NotFoundException;

/**
 * Used to normalize the languages (normally from task) given to connectTo method of connector
 */
class LanguageResolver
{
    public function __construct(
        private readonly LanguageRepository $languages,
        private readonly editor_Models_LanguageResources_Languages $languageResourceLanguages,
    ) {
    }

    public static function create(): LanguageResolver
    {
        return new self(
            LanguageRepository::create(),
            new editor_Models_LanguageResources_Languages(),
        );
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ReflectionException
     * @throws Zend_Cache_Exception
     */
    public function resolve(LanguageResource $languageResource, LanguagePairDTO $languagePairDTO): ?LanguagePairDTO
    {
        if ($languagePairDTO->languageResolutionType === LanguageResolutionType::Strict) {
            $sourceFuzzy = [$languagePairDTO->sourceLanguageId];
            $targetFuzzy = [$languagePairDTO->targetLanguageId];
        } else {
            if ($languagePairDTO->languageResolutionType === LanguageResolutionType::IncludeMajorAndSubLanguages) {
                //to include all sublanguages we have to resolve to the major first
                $sourceLanguageId = $this->languages->findMajorLanguageById($languagePairDTO->sourceLanguageId);
                $targetLanguageId = $this->languages->findMajorLanguageById($languagePairDTO->targetLanguageId);
            } else {
                $sourceLanguageId = $languagePairDTO->sourceLanguageId;
                $targetLanguageId = $languagePairDTO->targetLanguageId;
            }
            $sourceFuzzy = $this->languages->findFuzzyLanguages($sourceLanguageId, includeMajor: true);
            $targetFuzzy = $this->languages->findFuzzyLanguages($targetLanguageId, includeMajor: true);
        }

        // load only the required languages
        $languagePair = $this->languageResourceLanguages->loadFilteredPairs(
            (int) $languageResource->getId(),
            $sourceFuzzy,
            $targetFuzzy
        );

        // if only 1 language combination is available for the langauge resource, use it.
        if (count($languagePair) === 1) {
            $languagePair = $languagePair[0];

            return new LanguagePairDTO((int) $languagePair['sourceLang'], (int) $languagePair['targetLang']);
        }

        // check for direct match
        foreach ($languagePair as $item) {
            if (($item['sourceLang'] === $languagePairDTO->sourceLanguageId)
                && ($item['targetLang'] === $languagePairDTO->targetLanguageId)) {
                return new LanguagePairDTO($item['sourceLang'], $item['targetLang']);
            }
        }

        // check for fuzzy match
        foreach ($languagePair as $item) {
            // find the langauges using fuzzy matching
            if (in_array($item['sourceLang'], $sourceFuzzy, true)
                && in_array($item['targetLang'], $targetFuzzy, true)) {
                return new LanguagePairDTO($item['sourceLang'], $item['targetLang']);
            }
        }

        return null;
    }
}
