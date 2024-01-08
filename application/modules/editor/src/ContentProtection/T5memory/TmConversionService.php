<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\ContentProtection\T5memory;

use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\Model\LanguageResourceRulesHash;
use MittagQI\Translate5\ContentProtection\NumberProtector;
use RuntimeException;
use XMLReader;
use XMLWriter;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class TmConversionService
{
    public const T5MEMORY_NUMBER_TAG = 't5:n';

    private array $languageRulesHashMap;
    private array $languageResourceRulesHashMap;

    public function __construct(
        private ContentProtectionRepository $contentProtectionRepository,
        private ContentProtector $contentProtector
    ) {
        $this->languageRulesHashMap = $contentProtectionRepository->getLanguageRulesHashMap();
        $this->languageResourceRulesHashMap = $contentProtectionRepository->getLanguageResourceRulesHashMap();
    }

    public static function fullTagRegex(): string
    {
        return sprintf('/<%s id="(\d+)" r="(.+)" n="(.+)"\s?\/>/Uu', self::T5MEMORY_NUMBER_TAG);
    }

    public function isTmConverted(int $languageResourceId): bool
    {
        if (!isset($this->languageResourceRulesHashMap[$languageResourceId])) {
            return false;
        }

        foreach ($this->languageResourceRulesHashMap[$languageResourceId] as ['languageId' => $id, 'hash' => $hash]) {
            if ($this->languageRulesHashMap[(int)$id] !== $hash) {
                return false;
            }
        }

        return true;
    }

    public function isConversionInProgress(int $languageResourceId): bool
    {
        if (!isset($this->languageResourceRulesHashMap[$languageResourceId])) {
            return false;
        }

        foreach ($this->languageResourceRulesHashMap[$languageResourceId] as ['conversionStarted' => $started]) {
            if (!empty($started)) {
                return true;
            }
        }

        return false;
    }

    public function startConversion(int $languageResourceId, int $languageId): void
    {
        $languageResourceRulesHash = ZfExtended_Factory::get(LanguageResourceRulesHash::class);

        try {
            $languageResourceRulesHash->loadByLanguageResourceIdAndLanguageId($languageResourceId, $languageId);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            // if not found we simply create new
            $languageResourceRulesHash->init();
            $languageResourceRulesHash->setLanguageResourceId($languageResourceId);
            $languageResourceRulesHash->setLanguageId($languageId);
        }

        $languageResourceRulesHash->setConversionStarted(date('Y-m-d H:i:s'));
        $languageResourceRulesHash->save();

        $worker = ZfExtended_Factory::get(ConverseMemoryWorker::class);
        $worker->init(parameters: ['languageResourceId' => $languageResourceId, 'languageId' => $languageId]);
        $worker->queue();
    }

    public function convertT5MemoryTagToNumber(string $string): string
    {
        return preg_replace(self::fullTagRegex(), '\3', $string);
    }

    public function convertContentTagToT5MemoryTag(string $queryString, bool $isSource, &$numberTagMap = []): string
    {
        $queryString = $this->contentProtector->unprotect($queryString, false, NumberProtector::alias());
        $regex = NumberProtector::fullTagRegex();

        if (!preg_match_all($regex, $queryString, $tags, PREG_SET_ORDER)) {
            return $queryString;
        }

        $currentId = 1;
        foreach ($tags as $tagProps) {
            $tag = array_shift($tagProps);
            $tagProps = array_combine(['type', 'name', 'source', 'iso', 'target'], $tagProps);

            $contentRecognition = $this->contentProtectionRepository->getContentRecognition(
                $tagProps['type'],
                $tagProps['name']
            );

            $encodedRegex = base64_encode(gzdeflate($contentRecognition->getRegex()));
            $t5nTag = sprintf(
                '<%s id="%s" r="%s" n="%s"/>',
                self::T5MEMORY_NUMBER_TAG,
                $currentId,
                $encodedRegex,
                $isSource ? $tagProps['source'] : $tagProps['target']
            );

            $numberTagMap[$encodedRegex][] = $tag;

            $queryString = str_replace($tag, $t5nTag, $queryString);
            $currentId++;
        }

        return $queryString;
    }

    public function convertTMXForImport(string $filenameWithPath, int $sourceLang, int $targetLang): string
    {
        $exportDir = APPLICATION_PATH . '/../data/TMConversion/';
        @mkdir($exportDir, recursive: true);

        $resultFilename = $exportDir . str_replace('.tmx', '', basename($filenameWithPath)) . '_converted.tmx';

        $writer = new XMLWriter();

        if (!$writer->openURI($resultFilename)) {
            throw new RuntimeException('File for TMX conversion was not created. Filename: ' . $resultFilename);
        }

        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);

        $reader = new XMLReader();
        $reader->open($filenameWithPath);

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'header') {
                $writer->writeRaw($reader->readOuterXML());
            }

            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'tu') {
                $writer->writeRaw($this->convertTransUnit($reader->readOuterXML(), $sourceLang, $targetLang));
            }

            if (!in_array($reader->name, ['tmx', 'body'], true)) {
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

        // Finalizing document with $writer->endDocument() adds closing tags for all bpt-ept tags
        // so add body and tmx closing tags manually
        file_put_contents($resultFilename, PHP_EOL . '</body>' . PHP_EOL . '</tmx>', FILE_APPEND);

        return $resultFilename;
    }

    private function convertTransUnit(string $transUnit, int $sourceLang, int $targetLang): string
    {
        $transUnit = $this->convertT5MemoryTagToNumber($transUnit);
        preg_match_all(
            '/<tuv xml:lang="((\w|-)+)">((\n|\r|\r\n)?.+)+<\/tuv>/Uum',
            $transUnit,
            $matches,
            PREG_SET_ORDER
        );

        $numberTagMap = [];

        [$source, $target] = $this->contentProtector->filterTags(
            $this->contentProtector->protect(
                $matches[0][0],
                true,
                $sourceLang,
                $targetLang,
                ContentProtector::ENTITY_MODE_OFF
            ),
            $this->contentProtector->protect(
                $matches[1][0],
                false,
                $sourceLang,
                $targetLang,
                ContentProtector::ENTITY_MODE_OFF
            )
        );

        return str_replace(
            [$matches[0][0], $matches[1][0]],
            [
                $this->convertContentTagToT5MemoryTag($source, true, $numberTagMap),
                $this->convertContentTagToT5MemoryTag($target, false, $numberTagMap),
            ],
            $transUnit
        );
    }

}
