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
use MittagQI\Translate5\LanguageResource\Status;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use RuntimeException;
use XMLReader;
use XMLWriter;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Logger;

class TmConversionService implements TmConversionServiceInterface
{
    private array $languageRulesHashMap;

    private array $languageResourceRulesHashMap;

    private ZfExtended_Logger $logger;

    public function __construct(
        private readonly ContentProtectionRepository $contentProtectionRepository,
        private readonly ContentProtector $contentProtector,
        private readonly LanguageRepository $languageRepository,
        private readonly LanguageRulesHashService $languageRulesHashService,
        private readonly LanguageResourceRepository $languageResourceRepository,
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
        $languageResource = $this->languageResourceRepository->get($languageResourceId);

        $languageResource->addSpecificData(LanguageResource::PROTECTION_CONVERSION_STARTED, date('Y-m-d H:i:s'));
        // set status to import to block interactions with the language resource
        $languageResource->setStatus(Status::IMPORT);

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

                $t5nTag = new T5NTag($currentId, $tagProps['regex'], $protectedContent);

                $numberTagMap[$tagProps['regex']][$currentId] = $tag;

                $currentId++;

                return $t5nTag->toString();
            },
            $queryString
        );

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

        // suppress: namespace error : Namespace prefix t5 on n is not defined
        $errorLevel = error_reporting();
        error_reporting($errorLevel & ~E_WARNING);

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

        error_reporting($errorLevel);

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
        int &$brokenTus,
    ): string {
        $transUnit = $this->convertT5MemoryTagToContent($transUnit);

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

        $protectedSource = $this->contentProtector->protect(
            $sourceSegment,
            true,
            (int) $sourceLang->getId(),
            (int) $targetLang->getId(),
            ContentProtector::ENTITY_MODE_OFF
        );

        $protectedTarget = $this->contentProtector->protect(
            $targetSegment,
            false,
            (int) $sourceLang->getId(),
            (int) $targetLang->getId(),
            ContentProtector::ENTITY_MODE_OFF
        );

        [$source, $target] = $this->contentProtector->filterTags($protectedSource, $protectedTarget);

        return str_replace(
            [
                '*source*',
                '*target*',
            ],
            [
                $this->convertContentTagToT5MemoryTag($source, true),
                $this->convertContentTagToT5MemoryTag($target, false),
            ],
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
