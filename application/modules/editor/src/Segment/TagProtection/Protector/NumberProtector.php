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

namespace MittagQI\Translate5\Segment\TagProtection\Protector;

use DOMCharacterData;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use editor_Models_Languages;
use editor_Models_Segment_Number_LanguageFormat as LanguageFormat;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\Segment\TagProtection\Protector\Number\NumberParsingException;
use MittagQI\Translate5\Segment\TagProtection\Protector\Number\NumberProtectorInterface;
use MittagQI\Translate5\Repository\LanguageNumberFormatRepository;

class NumberProtector implements ProtectorInterface
{
    /**
     * @var array<string, NumberProtectorInterface>
     */
    private array $protectors;

    private array $protectedNumbers = [];

    private DOMDocument $document;

    /**
     * @param array<NumberProtectorInterface> $protectors
     */
    public function __construct(
        array $protectors,
        private LanguageNumberFormatRepository $numberFormatRepository,
        private LanguageRepository $languageRepository
    ) {
        foreach ($protectors as $protector) {
            $this->protectors[$protector::getType()] = $protector;
        }
        $this->document = new DOMDocument();
    }

    public function hasEntityToProtect(string $textNode, ?int $sourceLang = null): bool
    {
        return (bool) preg_match('/(\d|[[:xdigit:]][-:]+)/u', $textNode);
    }

    public function protect(string $textNode, ?int $sourceLangId, ?int $targetLangId): string
    {
        $sourceLang = $sourceLangId ? $this->languageRepository->find($sourceLangId) : null;
        $targetLang = $targetLangId ? $this->languageRepository->find($targetLangId) : null;

        $this->loadXML("<node>$textNode</node>");

        foreach ($this->numberFormatRepository->getAll($sourceLang) as $langFormat) {
            if (!preg_match($langFormat->getRegex(), $this->document->textContent)) {
                continue;
            }

            $this->processElement($this->document->documentElement, $langFormat, $sourceLang, $targetLang);

            // reloading document with potential new tags
            $this->loadXML($this->getCurrentTextNode());
        }

        preg_match('/<node>(.+)<\/node>/', $this->getCurrentTextNode(), $matches);

        return $matches[1];
    }

    private function processElement(
        DOMNode $element,
        LanguageFormat $langFormat,
        ?editor_Models_Languages $sourceLang,
        ?editor_Models_Languages $targetLang
    ): void {
        // we can't remove them in protectNumbers() because it will break `$element->childNodes` iterator,
        // so we remove them after and replace original text node with a couple of generated nodes in protectNumbers()
        $nodesToRemove = [];

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $this->processElement($child, $langFormat, $sourceLang, $targetLang);

                continue;
            }

            if (
                $child instanceof DOMCharacterData
                && $this->protectNumbers($child, $langFormat, $sourceLang, $targetLang)
            ) {
                $nodesToRemove[] = $child;
            }
        }

        foreach ($nodesToRemove as $node) {
            $node->remove();
        }
    }

    private function protectNumbers(
        DOMCharacterData $text,
        LanguageFormat $langFormat,
        ?editor_Models_Languages $sourceLang,
        ?editor_Models_Languages $targetLang
    ): bool {
        if (!preg_match_all($langFormat->getRegex(), $text->textContent, $matches)) {
            return false;
        }

        $numbers = $matches[0];
        $parts = preg_split($langFormat->getRegex(), $text->textContent);

        $matchCount = count($numbers);

        /** @var DOMNode $parentNode */
        $parentNode = $text->parentNode;

        for ($i = 0; $i <= $matchCount; $i++) {
            if (!empty($parts[$i])) {
                $parentNode->insertBefore(new DOMText($parts[$i]), $text);
            }

            if (!isset($numbers[$i])) {
                continue;
            }

            $parentNode->insertBefore(
                $this->protectNumber($numbers[$i], $langFormat, $sourceLang, $targetLang),
                $text
            );
        }

        return true;
    }

    private function protectNumber(
        string $number,
        LanguageFormat $langFormat,
        ?editor_Models_Languages $sourceLang,
        ?editor_Models_Languages $targetLang
    ): DOMNode {
        if (!isset($this->protectedNumbers[$number])) {
            try {
                $protectedNumber = $this
                    ->protectors[$langFormat->getType()]
                    ->protect($number, $langFormat, $sourceLang, $targetLang);
            } catch (NumberParsingException) {
                return new DOMText($number);
            }

            $dom = new DOMDocument();
            $dom->loadXML($protectedNumber);
            $this->protectedNumbers[$number] = $this->document->importNode($dom->firstChild);
        }

        return $this->protectedNumbers[$number]->cloneNode();
    }

    private function loadXML(string $textNode): void
    {
        $this->document->loadXML($textNode);
        // loadXML resets encoding so we setting it here at each iteration
        $this->document->encoding = 'utf-8';
    }

    private function getCurrentTextNode(): string
    {
        return $this->document->saveXML($this->document->documentElement);
    }
}