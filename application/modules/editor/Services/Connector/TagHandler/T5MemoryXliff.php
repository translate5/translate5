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

use MittagQI\Translate5\ContentProtection\Model\ContentProtectionDto;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\NumberProtection\NumberProtectorProvider;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\NumberProtectorInterface;
use MittagQI\Translate5\ContentProtection\NumberProtector;
use MittagQI\Translate5\ContentProtection\T5memory\ConvertT5MemoryTagService;
use MittagQI\Translate5\ContentProtection\T5memory\ConvertT5MemoryTagServiceInterface;
use MittagQI\Translate5\ContentProtection\T5memory\T5NTag;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\T5Memory\ContentProtection\DiffProtector;

class editor_Services_Connector_TagHandler_T5MemoryXliff extends editor_Services_Connector_TagHandler_Xliff
{
    protected const ALLOWED_TAGS = '<x><x/><bx><bx/><ex><ex/><g><number>';

    private NumberProtector $numberProtector;

    private readonly ConvertT5MemoryTagServiceInterface $conversionService;

    /**
     * @var array<string, array<string, \SplQueue<int>>>
     */
    private array $contentProtectionTagMap = [];

    private readonly ContentProtectionRepository $contentProtectionRepository;

    private readonly NumberProtectorProvider $numberProtectorProvider;

    private readonly DiffProtector $diffProtector;

    private readonly LanguageRepository $languageRepository;

    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->conversionService = ConvertT5MemoryTagService::create();
        $this->numberProtector = NumberProtector::create();
        $this->contentProtectionRepository = ContentProtectionRepository::create();
        $this->numberProtectorProvider = NumberProtectorProvider::create();
        $this->diffProtector = DiffProtector::create();
        $this->languageRepository = LanguageRepository::create();
    }

    /**
     * @param array<string> $skippedTags
     * @throws \MittagQI\Translate5\ContentProtection\NumberProtection\NumberParsingException
     */
    public function restoreInFuzzyResult(string $resultString, array $skippedTags, bool $isSource = true): string
    {
        $sourceLang = $this->languageRepository->find($this->sourceLang);
        $targetLang = $this->languageRepository->find($this->targetLang);

        $resultString = $this->restoreProtectionTags(
            $resultString,
            $sourceLang,
            $targetLang,
            $skippedTags,
        );

        $resultString = parent::restoreInResult($resultString, $isSource);

        $dto = $this->numberProtector->convertToInternalTagsWithShortcutNumberMap(
            $resultString,
            $this->shortTagIdent,
            $this->shortcutNumberMap
        );

        return $dto->segment;
    }

    /**
     * @throws \MittagQI\Translate5\ContentProtection\NumberProtection\NumberParsingException
     */
    public function restoreInResult(string $resultString, bool $isSource = true): ?string
    {
        return $this->restoreInFuzzyResult($resultString, [], $isSource);
    }

    private function getProtector(string $type): NumberProtectorInterface
    {
        if (DiffProtector::getType() === $type) {
            return $this->diffProtector;
        }

        return $this->numberProtectorProvider->getByType($type);
    }

    /**
     * @param array<string> $skippedTags
     * @throws \MittagQI\Translate5\ContentProtection\NumberProtection\NumberParsingException
     */
    public function restoreProtectionTags(
        string $resultString,
        ?editor_Models_Languages $sourceLang,
        ?editor_Models_Languages $targetLang,
        array $skippedTags = [],
    ): string {
        $t5nTagRegex = T5NTag::fullTagRegex();

        if (! preg_match_all($t5nTagRegex, $resultString, $matches, PREG_SET_ORDER)) {
            return $resultString;
        }

        $protectionDtos = [];
        $contentProtectionTags = [];

        foreach ($matches as $match) {
            $tag = T5NTag::fromMatch($match);
            // $contentProtectionTags[*rule*][*id*] = ['tag' => *T5NTag*, 'render' => *wholeTag*];
            $contentProtectionTags[$tag->rule][$tag->id] = [
                'render' => $match[0],
                'tag' => $tag,
            ];
        }

        foreach ($contentProtectionTags as $rule => $tags) {
            // sort tags by their ids
            ksort($tags);
            // get rid of the keys
            $tags = array_values($tags);

            if (isset($this->contentProtectionTagMap[$rule])) {
                $taskSegmentTags = [];

                foreach ($this->contentProtectionTagMap[$rule] as $tag => ['ids' => $ids, 'protectedContent' => $protectedContent]) {
                    $skipIds = [];
                    foreach ($protectedContent as $content => $protectedIds) {
                        if (in_array($content, $skippedTags)) {
                            // count content occurrences to skipped tags
                            $occurrences = count(array_filter($skippedTags, fn($skipped) => $skipped === $content));

                            for ($i = 0; $i < $occurrences; $i++) {
                                $protectedId = array_shift($protectedIds);
                                $skipIds[$protectedId] = true;
                            }
                        }
                    }

                    foreach ($ids as $id) {
                        if (isset($skipIds[$id])) {
                            continue;
                        }

                        $taskSegmentTags[$id] = $tag;
                    }
                }

                ksort($taskSegmentTags);
                $taskSegmentTags = array_values($taskSegmentTags);

                foreach ($taskSegmentTags as $id => $segmentTag) {
                    if (! isset($tags[$id])) {
                        break;
                    }

                    $resultString = str_replace($tags[$id]['render'], $segmentTag, $resultString);

                    unset($tags[$id]);
                }

                if (empty($tags)) {
                    continue;
                }
            }

            foreach ($tags as ['tag' => $tag, 'render' => $render]) {
                if (null === $sourceLang || null === $targetLang) {
                    // no language found, so we just unprotect the content
                    $resultString = str_replace($render, $tag->content, $resultString);

                    continue;
                }

                if (! isset($protectionDtos[$rule])) {
                    $recognition = $this->contentProtectionRepository->findRecognitionByRegex($tag->getRegex());

                    $protectionDtos[$rule] = null === $recognition
                        ? ContentProtectionDto::fake(
                            DiffProtector::getType(),
                            'Missing rule - in TM only',
                            $tag->getRegex(),
                        )
                        : ContentProtectionDto::fake(
                            $recognition->getType(),
                            $recognition->getName() . ' - in TM only',
                            $recognition->getRegex(),
                        );
                }

                $dto = $protectionDtos[$rule];

                $resultString = str_replace(
                    $render,
                    $this->getProtector($dto->type)->protect(
                        $tag->content,
                        $dto,
                        $sourceLang,
                        $targetLang,
                    ),
                    $resultString
                );
            }
        }

        return $resultString;
    }

    protected function convertQuery(string $queryString, bool $isSource): string
    {
        $this->contentProtectionTagMap = [];

        return $this->conversionService->convertContentTagToT5MemoryTag($queryString, $isSource, $this->contentProtectionTagMap);
    }

    protected function processXliffTags(string $queryString): string
    {
        if ($this->gTagPairing) {
            return $this->utilities->internalTag->toXliffPaired(
                $queryString,
                replaceMap: $this->map,
                dontRemoveTags: ['<' . T5NTag::TAG . '>']
            );
        }

        return $this->utilities->internalTag->toXliff(
            $queryString,
            replaceMap: $this->map,
            dontRemoveTags: ['<' . T5NTag::TAG . '>']
        );
    }
}
