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
use editor_Models_Languages as Language;
use editor_Models_Segment_Whitespace as Whitespace;
use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\Model\LanguageRulesHashService;
use MittagQI\Translate5\ContentProtection\NumberProtector;
use MittagQI\Translate5\Repository\LanguageRepository;
use RuntimeException;
use XMLReader;
use XMLWriter;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Logger;

class TmConversionService implements TmConversionServiceInterface
{
    public const T5MEMORY_NUMBER_TAG = 't5:n';

    private array $languageRulesHashMap;

    private array $languageResourceRulesHashMap;

    private ZfExtended_Logger $logger;

    public function __construct(
        private readonly ContentProtectionRepository $contentProtectionRepository,
        private readonly ContentProtector $contentProtector,
        private readonly LanguageRepository $languageRepository,
        private readonly LanguageRulesHashService $languageRulesHashService
    ) {
        $this->languageRulesHashMap = $contentProtectionRepository->getLanguageRulesHashMap();
        $this->languageResourceRulesHashMap = $contentProtectionRepository->getLanguageResourceRulesHashMap();
        $this->logger = Zend_Registry::get('logger')->cloneMe('translate5.content_protection');
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

    public static function fullTagRegex(): string
    {
        return sprintf('/<%s id="(\d+)" r="(.+)" n="(.+)"\s?\/>/Uu', self::T5MEMORY_NUMBER_TAG);
    }

    public function isTmConverted(int $languageResourceId): bool
    {
        if (! isset($this->languageResourceRulesHashMap[$languageResourceId])) {
            return false;
        }

        ['languages' => $languages, 'hash' => $hash] = $this->languageResourceRulesHashMap[$languageResourceId];

        if (! isset($this->languageRulesHashMap[$languages['source']][$languages['target']])) {
            $lrLanguage = ZfExtended_Factory::get(\editor_Models_LanguageResources_Languages::class);

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

    public function isConversionInProgress(int $languageResourceId): bool
    {
        if (! isset($this->languageResourceRulesHashMap[$languageResourceId])) {
            return false;
        }

        if (! empty($this->languageResourceRulesHashMap[$languageResourceId]['conversionStarted'])) {
            return true;
        }

        return false;
    }

    public function startConversion(int $languageResourceId): void
    {
        $languageResource = ZfExtended_Factory::get(LanguageResource::class);
        $languageResource->load($languageResourceId);

        $languageResource->addSpecificData(LanguageResource::PROTECTION_CONVERSION_STARTED, date('Y-m-d H:i:s'));
        $languageResource->save();

        $worker = ZfExtended_Factory::get(ConverseMemoryWorker::class);
        if ($worker->init(parameters: [
            'languageResourceId' => $languageResourceId,
        ])) {
            $worker->queue();
        }
    }

    public function convertT5MemoryTagToContent(string $string): string
    {
        preg_match_all(self::fullTagRegex(), $string, $tags, PREG_SET_ORDER);

        if (empty($tags)) {
            return $string;
        }

        foreach ($tags as $tagWithProps) {
            $tag = $tagWithProps[0];
            $protectedContent = html_entity_decode($tagWithProps[3], ENT_XML1);
            // return < and > from special chars that was used to avoid error in t5memory
            $protectedContent = str_replace(['*≺*', '*≻*'], ['<', '>'], $protectedContent);
            // make sure not escape already escaped HTML entities
            $protectedContent = $this->protectHtmlEntities($protectedContent);

            $string = str_replace($tag, htmlentities($protectedContent, ENT_XML1), $string);
        }

        return $this->unprotectHtmlEntities($string);
    }

    private function protectHtmlEntities(string $text): string
    {
        return preg_replace('/&(\w{2,8});/', '**\1**', $text);
    }

    private function unprotectHtmlEntities(string $text): string
    {
        return preg_replace('/\*\*(\w{2,8})\*\*/', '&\1;', $text);
    }

    public function convertContentTagToT5MemoryTag(string $queryString, bool $isSource, &$numberTagMap = []): string
    {
        $queryString = $this->contentProtector->unprotect($queryString, false, NumberProtector::alias());
        $regex = NumberProtector::fullTagRegex();

        if (! preg_match_all($regex, $queryString, $tags, PREG_SET_ORDER)) {
            return $queryString;
        }

        $currentId = 1;
        foreach ($tags as $tagProps) {
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
            // replace < and > with special chars to avoid error in t5memory
            // simple htmlentities or rawurlencode would not work
            $protectedContent = str_replace(['<', '>'], ['*≺*', '*≻*'], $protectedContent);
            $protectedContent = htmlentities($protectedContent, ENT_XML1);

            $t5nTag = sprintf(
                '<%s id="%s" r="%s" n="%s"/>',
                self::T5MEMORY_NUMBER_TAG,
                $currentId,
                $tagProps['regex'],
                $protectedContent
            );

            $numberTagMap[$tagProps['regex']][] = $tag;

            $queryString = str_replace($tag, $t5nTag, $queryString);
            $currentId++;
        }

        return $queryString;
    }

    public function convertTMXForImport(string $filenameWithPath, int $sourceLangId, int $targetLangId): string
    {
        $sourceLang = $sourceLangId ? $this->languageRepository->find($sourceLangId) : null;
        $targetLang = $targetLangId ? $this->languageRepository->find($targetLangId) : null;

        if (! $this->contentProtectionRepository->hasActiveRules($sourceLang, $targetLang)) {
            return $filenameWithPath;
        }

        $exportDir = APPLICATION_PATH . '/../data/TMConversion/';
        @mkdir($exportDir, recursive: true);

        $resultFilename = $exportDir . str_replace('.tmx', '', basename($filenameWithPath)) . '_converted.tmx';

        $writer = new XMLWriter();

        if (! $writer->openURI($resultFilename)) {
            throw new RuntimeException('File for TMX conversion was not created. Filename: ' . $resultFilename);
        }

        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);

        $reader = new XMLReader();
        $reader->open($filenameWithPath);
        $writtenElements = 0;
        $brokenTus = 0;

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'header') {
                $writer->writeRaw($reader->readOuterXML());
            }

            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'tu') {
                $writtenElements++;
                $writer->writeRaw(
                    $this->convertTransUnit(
                        $reader->readOuterXML(),
                        $sourceLang,
                        $targetLang,
                        $brokenTus
                    )
                );
            }

            if (! in_array($reader->name, ['tmx', 'body'], true)) {
                continue;
            }

            if ($reader->nodeType == XMLReader::ELEMENT) {
                $writer->startElement($reader->name);

                if ($reader->hasAttributes) {
                    while ($reader->moveToNextAttribute()) {
                        $writer->writeAttribute($reader->name, $reader->value);
                    }
                }

                if ($reader->isEmptyElement) {
                    $writer->endElement();
                }
            }
        }

        $reader->close();

        $writer->flush();

        if (0 !== $writtenElements) {
            // Finalizing document with $writer->endDocument() adds closing tags for all bpt-ept tags
            // so add body and tmx closing tags manually
            file_put_contents($resultFilename, PHP_EOL . '</body>', FILE_APPEND);
        }

        file_put_contents($resultFilename, PHP_EOL . '</tmx>', FILE_APPEND);

        if (0 !== $brokenTus) {
            $this->logger->error(
                'E1593',
                'Trans unit has unexpected structure and was excluded from TMX import',
                [
                    'count' => $brokenTus,
                ]
            );
        }

        return $resultFilename;
    }

    private function convertTransUnit(
        string $transUnit,
        Language $sourceLang,
        Language $targetLang,
        int &$brokenTus
    ): string {
        $transUnit = $this->convertT5MemoryTagToContent($transUnit);
        preg_match_all(
            '/<tuv xml:lang="((\w|-)+)">((\n|\r|\r\n)?\s*<seg>.+<\/seg>(\n|\r|\r\n)*)+\s*<\/tuv>/Uum',
            $transUnit,
            $matches,
            PREG_SET_ORDER
        );

        $numberTagMap = [];

        // if there is no source or target tuv, then we assume that tu is broken
        if (empty($matches[0][0]) || empty($matches[1][0])) {
            $brokenTus++;

            return '';
        }

        $sourceMatchId = str_contains(strtolower($matches[0][1]), $sourceLang->getMajorRfc5646()) ? 0 : 1;
        $targetMatchId = $sourceMatchId === 0 ? 1 : 0;

        [$source, $target] = $this->contentProtector->filterTags(
            $this->contentProtector->protect(
                $matches[$sourceMatchId][0],
                true,
                (int) $sourceLang->getId(),
                (int) $targetLang->getId(),
                ContentProtector::ENTITY_MODE_OFF
            ),
            $this->contentProtector->protect(
                $matches[$targetMatchId][0],
                false,
                (int) $sourceLang->getId(),
                (int) $targetLang->getId(),
                ContentProtector::ENTITY_MODE_OFF
            )
        );

        return str_replace(
            [$matches[$sourceMatchId][0], $matches[$targetMatchId][0]],
            [
                $this->convertContentTagToT5MemoryTag($source, true, $numberTagMap),
                $this->convertContentTagToT5MemoryTag($target, false, $numberTagMap),
            ],
            $transUnit
        );
    }
}
