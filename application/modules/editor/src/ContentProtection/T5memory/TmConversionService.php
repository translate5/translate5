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
use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\ContentProtection\ConversionState;
use MittagQI\Translate5\ContentProtection\DTO\RulesHashDto;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\Model\LanguageRulesHashService;
use MittagQI\Translate5\ContentProtection\NumberProtector;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Segment\EntityHandlingMode;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Logger;

class TmConversionService implements TmConversionServiceInterface
{
    private array $languageRulesHashMap;

    /**
     * @var array<int, RulesHashDto>
     */
    private array $languageResourceRulesHashMap;

    public function __construct(
        private readonly ContentProtectionRepository $contentProtectionRepository,
        private readonly ContentProtector $contentProtector,
        private readonly LanguageRepository $languageRepository,
        private readonly LanguageRulesHashService $languageRulesHashService,
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly ZfExtended_Logger $logger,
    ) {
        $this->languageRulesHashMap = $contentProtectionRepository->getLanguageRulesHashMap();
        $this->languageResourceRulesHashMap = $contentProtectionRepository->getLanguageResourceRulesHashMap();
    }

    public static function create(?Whitespace $whitespace = null)
    {
        $contentProtectionRepository = ContentProtectionRepository::create();
        $languageRepository = new LanguageRepository();

        return new self(
            $contentProtectionRepository,
            ContentProtector::create($whitespace ?: ZfExtended_Factory::get(Whitespace::class)),
            $languageRepository,
            new LanguageRulesHashService($contentProtectionRepository, $languageRepository),
            new LanguageResourceRepository(),
            Zend_Registry::get('logger')->cloneMe('editor.content_protection'),
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
        if (! isset($this->languageResourceRulesHashMap[$languageResourceId])) {
            return false;
        }

        $hashDto = $this->languageResourceRulesHashMap[$languageResourceId];
        $languages = $hashDto->languages;
        $hash = $hashDto->hash;

        if (! isset($this->languageRulesHashMap[$languages['source']][$languages['target']])) {
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

        return $this->languageRulesHashMap[$languages['source']][$languages['target']] === $hash;
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

    private function isConversionStarted(int $languageResourceId): bool
    {
        if (! isset($this->languageResourceRulesHashMap[$languageResourceId])) {
            return false;
        }

        return $this->languageResourceRulesHashMap[$languageResourceId]->conversionStarted;
    }

    private function isConversionScheduled(int $languageResourceId): bool
    {
        if (! isset($this->languageResourceRulesHashMap[$languageResourceId])) {
            return false;
        }

        return $this->languageResourceRulesHashMap[$languageResourceId]->conversionScheduled;
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

    public function convertT5MemoryTagToContent(string $string): string
    {
        preg_match_all(T5NTag::fullTagRegex(), $string, $tags, PREG_SET_ORDER);

        if (empty($tags)) {
            return $string;
        }

        foreach ($tags as $tagWithProps) {
            $tag = $tagWithProps[0];
            // make sure not escape already escaped HTML entities
            $protectedContent = $this->protectHtmlEntities(T5NTag::fromMatch($tagWithProps)->content);

            $string = str_replace($tag, htmlentities($protectedContent, ENT_XML1), $string);
        }

        return $this->unprotectHtmlEntities($string);
    }

    private function protectHtmlEntities(string $text): string
    {
        return preg_replace('/&(\w{2,8});/', '¿¿¿\1¿¿¿', $text);
    }

    private function unprotectHtmlEntities(string $text): string
    {
        return preg_replace('/¿¿¿(\w{2,8})¿¿¿/', '&\1;', $text);
    }

    public function convertContentTagToT5MemoryTag(string $queryString, bool $isSource, &$numberTagMap = []): string
    {
        $queryString = $this->contentProtector->unprotect($queryString, $isSource, NumberProtector::alias());
        $regex = NumberProtector::fullTagRegex();

        if (! preg_match($regex, $queryString)) {
            return $queryString;
        }

        $currentId = 1;
        $queryString = preg_replace_callback(
            $regex,
            function (array $tagProps) use (&$currentId, &$numberTagMap, $isSource) {
                $tag = array_shift($tagProps);

                if (count($tagProps) < 7) {
                    $this->logger->warn(
                        'E1625',
                        "Protection Tag doesn't has required meta info. Fuzzy searches may return worse match rate"
                    );
                    $tagProps = array_pad($tagProps, 7, '');
                }

                unset($tagProps[5]);

                $tagProps = array_combine(['type', 'name', 'source', 'iso', 'target', 'regex'], $tagProps);

                if (empty($tagProps['regex'])) {
                    // for BC reasons, we use the name as regex
                    $tagProps['regex'] = base64_encode($tagProps['name']);
                }

                $protectedContent = $isSource ? $tagProps['source'] : $tagProps['target'];

                $protectedContent = html_entity_decode($protectedContent);

                if ($isSource) {
                    if (! isset($numberTagMap[$tagProps['regex']][$tag])) {
                        $numberTagMap[$tagProps['regex']][$tag] = new \SplQueue();
                    }
                    $numberTagMap[$tagProps['regex']][$tag]->enqueue($currentId);
                } else {
                    $ids = $numberTagMap[$tagProps['regex']][$tag] ?? null;

                    $currentId = null !== $ids && ! $ids->isEmpty() ? $ids->dequeue() : $currentId;
                }

                $t5nTag = new T5NTag($currentId, $tagProps['regex'], $protectedContent);

                $currentId++;

                return $t5nTag->toString();
            },
            $queryString
        );

        return $queryString;
    }

    public function convertPair(string $source, string $target, int $sourceLang, int $targetLang): array
    {
        $source = $this->convertT5MemoryTagToContent($source);
        $target = $this->convertT5MemoryTagToContent($target);

        $source = $this->collapseTmxTags($source);
        $target = $this->collapseTmxTags($target);

        $protectedSource = $this->contentProtector->protect(
            $source,
            true,
            $sourceLang,
            $targetLang,
            EntityHandlingMode::Off
        );

        $protectedTarget = $this->contentProtector->protect(
            $target,
            false,
            $sourceLang,
            $targetLang,
            EntityHandlingMode::Off
        );

        [$source, $target] = $this->contentProtector->filterTags($protectedSource, $protectedTarget);

        $tagMap = [];

        return [
            $this->convertContentTagToT5MemoryTag($source, true, $tagMap),
            $this->convertContentTagToT5MemoryTag($target, false, $tagMap),
        ];
    }

    private function collapseTmxTags(string $segment): string
    {
        return preg_replace('#<(ph|bpt|ept) ([^>]*)>(.*)</\1>#U', '<$1 $2/>', $segment);
    }

    private function convertTransUnit(
        string $transUnit,
        Language $sourceLang,
        Language $targetLang,
        int &$brokenTus,
    ): string {
        $sourceSegment = '';
        $targetSegment = '';

        $xml = XMLReader::XML($transUnit);

        while ($xml->read()) {
            if ($xml->nodeType !== XMLReader::ELEMENT) {
                continue;
            }

            if ($xml->name !== 'tuv') {
                continue;
            }

            $tuv = $xml->readOuterXML();
            $lang = strtolower($xml->getAttribute('xml:lang'));

            $segment = str_replace(['<seg>', '</seg>'], '', trim($xml->readInnerXml()));

            if ($this->isSourceTuv($lang, $sourceLang, $targetLang)) {
                $sourceSegment = $segment;
                $transUnit = str_replace($tuv, str_replace($sourceSegment, '*source*', $tuv), $transUnit);
            } else {
                $targetSegment = $segment;
                $transUnit = str_replace($tuv, str_replace($targetSegment, '*target*', $tuv), $transUnit);
            }

            if ('' !== $sourceSegment && '' !== $targetSegment) {
                break;
            }
        }

        // if there is no source or target tuv, then we assume that tu is broken
        if ('' === $sourceSegment || '' === $targetSegment) {
            $brokenTus++;

            return '';
        }

        return str_replace(
            [
                '*source*',
                '*target*',
            ],
            $this->convertPair(
                $sourceSegment,
                $targetSegment,
                (int) $sourceLang->getId(),
                (int) $targetLang->getId()
            ),
            $transUnit
        );
    }

    private function isSourceTuv(string $tuvLang, Language $sourceLang, Language $targetLang): bool
    {
        if (strtolower($sourceLang->getRfc5646()) === $tuvLang) {
            return true;
        }

        if (strtolower($targetLang->getRfc5646()) === $tuvLang) {
            return false;
        }

        return str_contains($tuvLang, strtolower($sourceLang->getMajorRfc5646()));
    }
}
