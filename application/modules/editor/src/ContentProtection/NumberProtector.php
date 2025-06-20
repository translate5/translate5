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

namespace MittagQI\Translate5\ContentProtection;

use editor_Models_Import_FileParser_XmlParser as XmlParser;
use editor_Models_Languages;
use LogicException;
use MittagQI\Translate5\ContentProtection\DTO\ConversionToInternalTagResult;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionDto;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\NumberProtection\NumberParsingException;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\DateProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\FloatProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\IntegerProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\IPAddressProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\KeepContentProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\MacAddressProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\NumberProtectorInterface;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\ReplaceContentProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Tag\NumberTag;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\Segment\EntityHandlingMode;
use Zend_Registry;
use ZfExtended_Logger;

class NumberProtector implements ProtectorInterface
{
    public const TAG_NAME = 'number';

    private const PLACEHOLDER_FORMAT = '¿¿¿%s¿¿¿';

    /**
     * @var array<string, NumberProtectorInterface>
     */
    private array $protectors;

    private array $protectedNumbers = [];

    /**
     * @var array<string, true>
     */
    private array $invalidRules = [];

    /**
     * @var array<int, bool>
     */
    private array $hasTextRules = [];

    /**
     * @param array<NumberProtectorInterface> $protectors
     */
    public function __construct(
        array $protectors,
        private readonly ContentProtectionRepository $numberRepository,
        private readonly LanguageRepository $languageRepository,
        private readonly ZfExtended_Logger $logger,
    ) {
        foreach ($protectors as $protector) {
            $this->protectors[$protector::getType()] = $protector;
        }
    }

    public function validateFormat(string $type, string $format): bool
    {
        return $this->protectors[$type]->validateFormat($format);
    }

    public function getFormatedExample(string $type, string $format): string
    {
        return $this->protectors[$type]->getFormatedExample($format);
    }

    /**
     * @return string[]
     */
    public static function keepAsIsTypes(): array
    {
        return [
            MacAddressProtector::getType(),
            IPAddressProtector::getType(),
            KeepContentProtector::getType(),
        ];
    }

    /**
     * @return string[]
     */
    public static function nonKeepAsIsTypes(): array
    {
        return [
            ReplaceContentProtector::getType(),
        ];
    }

    public static function alias(): string
    {
        return self::TAG_NAME;
    }

    public static function fullTagRegex(): string
    {
        return sprintf(
            '/<%s type="(.+)" name="(.+)" source="(.+)" iso="(.+)" target="(.+)"\s*(regex="(.+)")?\s?\/>/U',
            self::TAG_NAME
        );
    }

    public static function create(
        ?ContentProtectionRepository $numberRepository = null,
        ?ZfExtended_Logger $logger = null,
    ): self {
        $numberRepository = $numberRepository ?: ContentProtectionRepository::create();
        $logger = $logger ?: Zend_Registry::get('logger')->cloneMe('translate5.content_protection');

        return new self(
            [
                new DateProtector($numberRepository),
                new FloatProtector($numberRepository),
                new IntegerProtector($numberRepository),
                new IPAddressProtector($numberRepository),
                new MacAddressProtector($numberRepository),
                new KeepContentProtector($numberRepository),
                new ReplaceContentProtector($numberRepository),
            ],
            $numberRepository,
            new LanguageRepository(),
            $logger,
        );
    }

    public static function isNumberTag(string $tag): bool
    {
        return (bool) preg_match(self::fullTagRegex(), $tag);
    }

    public static function getIsoFromTag(string $tag): string
    {
        preg_match('/iso="(.+)"/U', $tag, $matches);

        return 'iso:' . $matches[1];
    }

    public function types(): array
    {
        return array_keys($this->protectors);
    }

    public function priority(): int
    {
        return 200;
    }

    public function hasEntityToProtect(string $textNode, int $sourceLang = null): bool
    {
        $has = (bool) preg_match('/(\d|[[:xdigit:]][-:]+)/u', $textNode);

        if ($has) {
            return true;
        }

        if (! array_key_exists($sourceLang, $this->hasTextRules)) {
            $this->hasTextRules[$sourceLang] = $this->numberRepository->hasActiveTextRules(
                $sourceLang ? $this->languageRepository->find($sourceLang) : null
            );
        }

        return $this->hasTextRules[$sourceLang];
    }

    public function hasTagsToConvert(string $textNode): bool
    {
        return str_contains($textNode, '<' . self::TAG_NAME . ' ');
    }

    public function tagList(): array
    {
        return [self::TAG_NAME];
    }

    public function convertToInternalTagsWithShortcutNumberMapCollecting(
        string $segment,
        int $shortTagIdent,
    ): ConversionToInternalTagResult {
        $shortcutNumberMap = [];

        $converted = $this->convertToInternalTags($segment, $shortTagIdent, true, $shortcutNumberMap);

        return new ConversionToInternalTagResult($converted, $shortTagIdent, $shortcutNumberMap);
    }

    public function convertToInternalTags(
        string $segment,
        int &$shortTagIdent,
        bool $collectTagNumbers = false,
        array &$shortcutNumberMap = [],
    ): string {
        $xml = $this->getXmlParser($shortTagIdent, $collectTagNumbers, $shortcutNumberMap);

        return $xml->parse($segment, true, [self::TAG_NAME]);
    }

    public function convertToInternalTagsInChunks(
        string $segment,
        int &$shortTagIdent,
        bool $collectTagNumbers,
        array &$shortcutNumberMap = [],
    ): array {
        $xml = $this->getXmlParser($shortTagIdent, $collectTagNumbers, $shortcutNumberMap);
        $xml->parse($segment, true, [self::TAG_NAME]);

        return $xml->getAllChunks();
    }

    private function getXmlParser(
        int &$shortTagIdent,
        bool $collectTagNumbers = false,
        array &$shortcutNumberMap = [],
    ): XmlParser {
        $xml = new XmlParser([
            'normalizeTags' => false,
        ]);
        $xml->registerElement(
            self::TAG_NAME,
            null,
            function ($tagName, $key, $opener) use ($xml, $collectTagNumbers, &$shortTagIdent, &$shortcutNumberMap) {
                $xml->replaceChunk(
                    $key,
                    $this->handleNumberTags($xml, $key, $opener, $collectTagNumbers, $shortTagIdent, $shortcutNumberMap)
                );
            }
        );

        return $xml;
    }

    public function convertToInternalTagsWithShortcutNumberMap(
        string $segment,
        int $shortTagIdent,
        array $shortcutNumberMap,
    ): ConversionToInternalTagResult {
        $converted = $this->convertToInternalTags($segment, $shortTagIdent, shortcutNumberMap: $shortcutNumberMap);

        return new ConversionToInternalTagResult($converted, $shortTagIdent, $shortcutNumberMap);
    }

    public function protect(
        string $textNode,
        bool $isSource,
        int $sourceLangId,
        int $targetLangId,
        EntityHandlingMode $entityHandling = EntityHandlingMode::Restore,
    ): string {
        if (empty($textNode)) {
            return $textNode;
        }

        // Reset document else it will be compromised between method calls
        $sourceLang = $sourceLangId ? $this->languageRepository->find($sourceLangId) : null;
        $targetLang = $targetLangId ? $this->languageRepository->find($targetLangId) : null;

        if (null === $sourceLang || null === $targetLang) {
            throw new LogicException("Provided langs are not present in DB: $sourceLangId, $targetLangId");
        }

        if (! $this->hasEntityToProtect($textNode, $sourceLangId)) {
            return $textNode;
        }

        $tries = 0;

        $dtos = $isSource
            ? $this->numberRepository->getAllForSource($sourceLang, $targetLang)
            : $this->numberRepository->getAllForTarget($sourceLang, $targetLang);

        $placeholders = [];

        $textNode = $this->replaceTagsWithPlaceholders($textNode, $placeholders);

        foreach ($dtos as $protectionDto) {
            // if we'll try to protect for example integers in a row like "string 12 45 67 string"
            // then we'll need to do that in a couple of tries because in current case will get result as:
            // string <number ... source="12" ... /> 145 <number ... source="67" ... /> string
            while ($tries < 2) {
                if (0 === $tries && ! $this->hasProcessableMatches($textNode, $placeholders, $protectionDto)) {
                    continue 2;
                }

                $textNode = $this->protectInText($textNode, $placeholders, $protectionDto, $sourceLang, $targetLang);

                if (0 === $tries && $this->fullSegmentMatch($textNode, $protectionDto)) {
                    break 2;
                }

                $tries++;
            }

            $tries = 0;
        }

        return str_replace(array_keys($placeholders), array_values($placeholders), $textNode);
    }

    // In case we got text like: "string &lt;goba&gt;string"
    // and user expects us to protect <goba> in: "string <goba> string"
    // entity encoding is part of upper processing logic
    private function decodeTextNode(string $textNode): string
    {
        return html_entity_decode($textNode, ENT_XML1);
    }

    private function fullSegmentMatch(string $textNode, ContentProtectionDto $protectionDto): bool
    {
        if (preg_match('#<seg>(.*)</seg>#', $textNode, $segMatches)) {
            $textNode = $segMatches[1];
        }

        $decoded = $this->decodeTextNode($textNode);

        if (
            ! preg_match_all($protectionDto->regex, $textNode, $matches)
            && ! preg_match_all($protectionDto->regex, $decoded, $matches)
        ) {
            return false;
        }

        // more then one match shows that regex don't match the whole text
        if (count($matches[0]) > 1) {
            return false;
        }

        return $textNode === $matches[$protectionDto->matchId][0];
    }

    private function hasProcessableMatches(string $textNode, array $placeholders, ContentProtectionDto $protectionDto): bool
    {
        $textToTest = str_replace(array_keys($placeholders), ' ', $textNode);

        if (
            ! preg_match($protectionDto->regex, $textToTest)
            && ! preg_match($protectionDto->regex, $this->decodeTextNode($textNode))
        ) {
            return false;
        }

        if ($protectionDto->keepAsIs || ! empty($protectionDto->outputFormat)) {
            return true;
        }

        if (! isset($this->invalidRules["{$protectionDto->type}:{$protectionDto->name}"])) {
            $this->invalidRules["{$protectionDto->type}:{$protectionDto->name}"] = true;
            $this->logger->warn(
                'E1585',
                'Input rule of type "{type}" and name "{name}" does not have appropriate output rule',
                [
                    'type' => $protectionDto->type,
                    'name' => $protectionDto->name,
                ]
            );
        }

        return false;
    }

    public function unprotect(string $content, bool $isSource): string
    {
        return preg_replace_callback(
            sprintf('/<%s.+source="(.+)".+target="(.+)?".*\/>/U', self::TAG_NAME),
            fn (array $match): string => html_entity_decode($isSource ? $match[1] : ($match[2] ?? $match[1])),
            $content
        );
    }

    public function convertForSorting(string $content, bool $isSource): string
    {
        return $this->unprotect($content, $isSource);
    }

    private function handleNumberTags(
        XmlParser $xml,
        int $key,
        array $opener,
        bool $collectTagNumbers,
        int &$shortTagIdent,
        array &$shortcutNumberMap = [],
    ): NumberTag {
        $source = $xml->getAttribute($opener['attributes'], 'source', null);
        $target = $xml->getAttribute($opener['attributes'], 'target', null);

        $wholeTag = $xml->getChunk($key);
        $shortTagNumber = $shortTagIdent;

        $iso = self::getIsoFromTag($wholeTag);

        if ($collectTagNumbers) {
            $shortcutNumberMap[$iso][] = $shortTagNumber;
            $shortTagIdent++;
        }
        //either we get a reusable shortcut number in the map, or we have to increment one
        elseif (! empty($shortcutNumberMap) && ! empty($shortcutNumberMap[$iso])) {
            $shortTagNumber = array_shift($shortcutNumberMap[$iso]);
        } else {
            $shortTagIdent++;
        }

        $tagObj = new NumberTag();
        $tagObj->originalContent = $wholeTag;
        $tagObj->tagNr = $shortTagNumber;
        $tagObj->id = self::TAG_NAME;
        $tagObj->tag = self::TAG_NAME;
        $tagObj->text = json_encode([
            'source' => $source,
            'target' => $target,
        ]);
        $tagObj->iso = $iso;
        $tagObj->source = $source;
        //title: Only translatable with using ExtJS QTips in the frontend, as title attribute not possible
        $tagObj->renderTag(title: '&lt;' . $shortTagNumber . '/&gt;: Number', cls: ' ' . self::TAG_NAME);

        return $tagObj;
    }

    private function protectInText(
        string $text,
        array &$placeholders,
        ContentProtectionDto $langFormat,
        ?editor_Models_Languages $sourceLang,
        ?editor_Models_Languages $targetLang,
    ): string {
        $textToTest = str_replace(array_keys($placeholders), '', $text);

        if (
            ! preg_match($langFormat->regex, $textToTest)
            && ! preg_match($langFormat->regex, $this->decodeTextNode($text))
        ) {
            return $text;
        }

        $result = '';

        foreach ($this->splitTextByProtections($text) as $part => $isProtected) {
            if ($isProtected) {
                $result .= $part;

                continue;
            }

            foreach ($this->protectTextPart($part, $placeholders, $langFormat, $sourceLang, $targetLang) as $textPart) {
                $result .= $textPart;
            }
        }

        return $result;
    }

    private function protectTextPart(
        string $part,
        array &$placeholders,
        ContentProtectionDto $langFormat,
        ?editor_Models_Languages $sourceLang,
        ?editor_Models_Languages $targetLang,
    ): iterable {
        preg_match_all($langFormat->regex, $part, $matches);

        $decodedPart = null;
        if (empty($matches[$langFormat->matchId])) {
            $decodedPart = $this->decodeTextNode($part);

            preg_match_all($langFormat->regex, $decodedPart, $matches);
        }

        if (empty($matches[$langFormat->matchId])) {
            return yield $part;
        }

        // if we have decoded part - match was found in decoded entity
        $part = $decodedPart ?? $part;

        $parts = preg_split($langFormat->regex, $part);

        $wholeMatches = $matches[0];
        $numbers = $matches[$langFormat->matchId];

        $count = count($parts);
        $ruleHash = md5($langFormat->regex);

        for ($i = 0; $i <= $count; $i++) {
            yield $parts[$i];

            if (! isset($numbers[$i])) {
                break;
            }

            $protected = $this->protectNumber(
                $numbers[$i],
                $ruleHash,
                $placeholders,
                $langFormat,
                $sourceLang,
                $targetLang
            );

            yield str_replace($numbers[$i], $protected, $wholeMatches[$i]);
        }
    }

    /**
     * true - for protected text, false - for text to protect
     *
     * @return iterable<string, bool>
     */
    private function splitTextByProtections(string $text): iterable
    {
        preg_match_all('/¿¿¿.+¿¿¿/Uu', $text, $protectedMatches);
        $protectedMatches = $protectedMatches[0];

        $parts = preg_split('/¿¿¿.+¿¿¿/Uu', $text);

        $partsCount = count($parts);

        for ($p = 0; $p <= $partsCount; $p++) {
            if (! isset($parts[$p])) {
                break;
            }

            yield $parts[$p] => false;

            if (isset($protectedMatches[$p])) {
                yield $protectedMatches[$p] => true;
            }
        }
    }

    private function protectNumber(
        string $number,
        string $wholePartHash,
        array &$placeholders,
        ContentProtectionDto $langFormat,
        ?editor_Models_Languages $sourceLang,
        ?editor_Models_Languages $targetLang,
    ): string {
        $numberKey = $number . ':' . $wholePartHash;
        if (! isset($this->protectedNumbers[$numberKey])) {
            try {
                $protectedNumber = $this
                    ->protectors[$langFormat->type]
                    ->protect($number, $langFormat, $sourceLang, $targetLang);
            } catch (NumberParsingException) {
                // if match was not actually a number - return it as is
                return $number;
            }

            $this->protectedNumbers[$numberKey] = $protectedNumber;
        }

        $numberPlaceholder = sprintf(self::PLACEHOLDER_FORMAT, base64_encode($this->protectedNumbers[$numberKey]));

        $placeholders[$numberPlaceholder] = $this->protectedNumbers[$numberKey];

        return $numberPlaceholder;
    }

    /**
     * @param array<string, string> $placeholders
     */
    public function replaceTagsWithPlaceholders(string $textNode, array &$placeholders): string
    {
        $textNode = preg_replace_callback(
            \editor_Models_Segment_InternalTag::REGEX_INTERNAL_TAGS,
            function ($match) use (&$placeholders) {
                $placeholder = sprintf(self::PLACEHOLDER_FORMAT, base64_encode($match[0]));
                $placeholders[$placeholder] = $match[0];

                return $placeholder;
            },
            $textNode,
        );

        $xml = new XmlParser([
            'normalizeTags' => false,
        ]);
        $xml->registerElement(
            '*',
            null,
            function ($tagName, $key, $opener) use ($xml, &$placeholders) {
                $openTag = $xml->getChunk($opener['openerKey']);
                $openTagPlaceholder = sprintf(self::PLACEHOLDER_FORMAT, base64_encode($openTag));

                $placeholders[$openTagPlaceholder] = $openTag;

                $xml->replaceChunk(
                    $opener['openerKey'],
                    $openTagPlaceholder
                );

                if ($opener['openerKey'] === $key) {
                    return;
                }

                $closeTag = $xml->getChunk($key);
                $closeTagPlaceholder = sprintf(self::PLACEHOLDER_FORMAT, base64_encode($closeTag));

                $placeholders[$closeTagPlaceholder] = $closeTag;

                $xml->replaceChunk(
                    $key,
                    $closeTagPlaceholder
                );
            }
        );

        return $xml->parse($textNode, true);
    }
}
