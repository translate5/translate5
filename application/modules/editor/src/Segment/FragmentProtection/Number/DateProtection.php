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

namespace MittagQI\Translate5\Segment\FragmentProtection\Number;

use DateTime;
use MittagQI\Translate5\Repository\LanguageNumberFormatRepository;
use MittagQI\Translate5\Segment\FragmentProtection\NumberProtection;
use MittagQI\Translate5\Segment\FragmentProtection\RatingInterface;
use Traversable;

class DateProtection implements NumberProtectionInterface, RatingInterface
{
    private array $dateFormats = [
        [
            'regex' => '\d\d\d\d\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\/(0[1-9]|1[0-2]|[1-9])',
            'format' => 'Y/d/m'
        ],
        [
            'regex' => '\d\d\d\d-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9])',
            'format' => 'Y-d-m'
        ],
        [
            'regex' => '\d\d\d\d\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\.(0[1-9]|1[0-2]|[1-9])',
            'format' => 'Y.d.m'
        ],
        [
            'regex' => '\d\d\d\d (0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) (0[1-9]|1[0-2]|[1-9])',
            'format' => 'Y d m'
        ],

        [
            'regex' => '(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\/(0[1-9]|1[0-2]|[1-9])\/\d\d\d\d',
            'format' => 'd/m/Y'
        ],
        [
            'regex' => '(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9])-\d\d\d\d',
            'format' => 'd-m-Y'
        ],
        [
            'regex' => '(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\.(0[1-9]|1[0-2]|[1-9])\.\d\d\d\d',
            'format' => 'd.m.Y'
        ],
        [
            'regex' => '(0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) (0[1-9]|1[0-2]|[1-9]) \d\d\d\d',
            'format' => 'd m Y'
        ],

        [
            'regex' => '(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\/(0[1-9]|1[0-2]|[1-9])\/\d\d',
            'format' => 'd/m/y'
        ],
        [
            'regex' => '(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9])-\d\d',
            'format' => 'd-m-y'
        ],
        [
            'regex' => '(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\.(0[1-9]|1[0-2]|[1-9])\.\d\d',
            'format' => 'd.m.y'
        ],

        [
            'regex' => '\d\d\d\d\/(0[1-9]|1[0-2]|[1-9])\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])',
            'format' => 'Y/m/d'
        ],
        [
            'regex' => '\d\d\d\d-(0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])',
            'format' => 'Y-m-d'
        ],
        [
            'regex' => '\d\d\d\d\.(0[1-9]|1[0-2]|[1-9])\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])',
            'format' => 'Y.m.d'
        ],
        [
            'regex' => '\d\d\d\d (0[1-9]|1[0-2]|[1-9]) (0[1-9]|[1-2][0-9]|3[0-1]|[1-9])',
            'format' => 'Y m d'
        ],

        [
            'regex' => '(0[1-9]|1[0-2]|[1-9])\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\/\d\d\d\d',
            'format' => 'm/d/Y'
        ],
        [
            'regex' => '(0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-\d\d\d\d',
            'format' => 'm-d-Y'
        ],
        [
            'regex' => '(0[1-9]|1[0-2]|[1-9])\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\.\d\d\d\d',
            'format' => 'm.d.Y'
        ],
        [
            'regex' => '(0[1-9]|1[0-2]|[1-9]) (0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) \d\d\d\d',
            'format' => 'm d Y'
        ],

        [
            'regex' => '(0[1-9]|1[0-2]|[1-9])\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\/\d\d',
            'format' => 'm/d/y'
        ],
        [
            'regex' => '(0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-\d\d',
            'format' => 'm-d-y'
        ],
        [
            'regex' => '(0[1-9]|1[0-2]|[1-9])\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\.\d\d',
            'format' => 'm.d.y'
        ],

        [
            'regex' => '\d\d\/(0[1-9]|1[0-2]|[1-9])\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])',
            'format' => 'y/m/d'
        ],

        [
            'regex' => '\d\d\d\d(0[1-9]|1[0-2])(0[1-9]|[1-2][0-9]|3[0-1])',
            'format' => 'Ymd'
        ],
    ];

    public function __construct(private LanguageNumberFormatRepository $formatRepository)
    {
    }

    public function hasEntityToProtect(string $textNode, ?int $sourceLang): bool
    {
        return (bool) preg_match($this->getDateRegex($sourceLang), $textNode);
    }

    public function rating(): int
    {
        return 500;
    }

    public function protect(iterable $textNodes, ?int $sourceLang, ?int $targetLang): Traversable
    {
        $regex = $this->getDateRegex($sourceLang);

        foreach ($textNodes as $textNode) {
            if ($textNode['protected']) {
                yield $textNode;

                continue;
            }

            preg_match_all($regex, $textNode['text'], $matches, PREG_PATTERN_ORDER);
            $splitText = preg_split($regex, $textNode['text']);
            $dates = $matches[1];

            // put protected dates on the places where they belong
            foreach ($this->processSplitNode($splitText, $dates, $sourceLang, $targetLang) as $text => $protected) {
                if (!empty($text)) {
                    yield ['text' => $text, 'protected' => $protected];
                }
            }
        }
    }

    private function processSplitNode(
        array $splitText,
        array $datesToProtect,
        ?int $sourceLang,
        ?int $targetLang
    ): iterable {
        $matchCount = count($datesToProtect);
        for ($i = 0; $i <= $matchCount; $i++) {
            // yield not date part of text node
            yield $splitText[$i] => false;

            if (!isset($datesToProtect[$i])) {
                continue;
            }

            yield $this->protectDate($datesToProtect[$i], $sourceLang, $targetLang) => true;
        }
    }

    private function protectDate(string $dateToProtect, ?int $sourceLang, ?int $targetLang): string
    {
        $targetFormat = null;

        // if source lang provided we'll firstly try to check existing user provided formats
        if (null !== $sourceLang) {
            $protectedDate = $this->protectDateOfCustomFormats($dateToProtect, $sourceLang, $targetLang);

            if (null !== $protectedDate) {
                // yield protected date of format provided by user and continue with next text part
                return $protectedDate;
            }
        }

        if ($targetLang) {
            $targetFormat = $this->formatRepository->getDateFormat($targetLang, 'default')?->getFormat();
        }

        foreach ($this->dateFormats as $sourceFormat) {
            if (!$this->dateMatchesRegex($sourceFormat['regex'], $dateToProtect)) {
                continue;
            }

            return $this->composeNumberDateTag($dateToProtect, 'default', $sourceFormat['format'], $targetFormat);
        }

        throw new \LogicException(
            sprintf('None of regex matches current date "%s" that should not be possible', $dateToProtect)
        );
    }

    private function composeNumberDateTag(
        string $date,
        string $name,
        ?string $sourceFormat,
        ?string $targetFormat
    ): string {
        $datetime = null;

        if (null !== $sourceFormat) {
            $datetime = DateTime::createFromFormat($sourceFormat, $date);
        }

        return sprintf(
            '<number type="%s" name="%s" source="%s" iso="%s" target="%s" />',
            NumberProtection::DATE_TYPE,
            $name,
            $date,
            $datetime ? $datetime->format('Y-m-d') : '',
            $datetime && $targetFormat ? $datetime->format($targetFormat) : ''
        );
    }

    private function composeDateRegex(string ...$parts): string
    {
        return sprintf('/\b(%s)\b/', implode('|', $parts));
    }

    private function getDateRegex(?int $sourceLang): string
    {
        return $this->composeDateRegex(
            ...array_merge(
                array_column($this->dateFormats, 'regex'),
                array_column($this->getFormatsByLangId($sourceLang), 'regex')
            )
        );
    }

    private function getFormatsByLangId(?int $sourceLang): array
    {
        return null === $sourceLang
            ? []
            : $this->formatRepository->findByLanguageIdAndType($sourceLang, NumberProtection::DATE_TYPE);
    }

    private function protectDateOfCustomFormats(
        string $dateToProtect,
        int $sourceLang,
        ?int $targetLang,
    ): ?string {
        $formats = $this->getFormatsByLangId($sourceLang);

        if (!preg_match($this->composeDateRegex(...array_column($formats, 'regex')), $dateToProtect)) {
            return null;
        }

        $targetFormat = null;

        foreach ($formats as $sourceFormat) {
            if ($targetLang) {
                $targetFormat = $this->formatRepository
                    ->getDateFormat($targetLang, $sourceFormat['name'])
                    ?->getFormat();
            }

            if (!$this->dateMatchesRegex($sourceFormat['regex'], $dateToProtect)) {
                continue;
            }

            return $this->composeNumberDateTag(
                $dateToProtect,
                $sourceFormat['name'] ?? 'default',
                $sourceFormat['format'] ?? null,
                $targetFormat
            );
        }

        return null;
    }

    private function dateMatchesRegex(string $regex, string $dateToProtect): bool
    {
        return (bool) preg_match($this->composeDateRegex($regex), $dateToProtect);
    }
}