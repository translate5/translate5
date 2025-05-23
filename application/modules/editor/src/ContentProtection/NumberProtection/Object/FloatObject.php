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

namespace MittagQI\Translate5\ContentProtection\NumberProtection\Object;

use NumberFormatter;

class FloatObject
{
    public function __construct(
        private readonly float $number,
        private readonly int $fractionDigits,
        private readonly string $sign = ''
    ) {
    }

    public static function parse(string $float): self
    {
        $formater = numfmt_create('en', NumberFormatter::DECIMAL);
        $symbols = array_filter(preg_split('/(\d+|[٠١٢٣٤٥٦٧٨٩]+)/u', $float));
        $decimalSeparator = end($symbols);

        if (false === $decimalSeparator) {
            $decimalSeparator = '.';
        }

        $firstChar = mb_substr($float, 0, 1);
        $sign = in_array($firstChar, ['-', '+']) ? $firstChar : '';
        $decimalPart = explode($decimalSeparator, $float)[1];
        // if format at the end has currency for example
        if (! preg_match('/(\d+|[٠١٢٣٤٥٦٧٨٩]+)/u', $decimalPart)) {
            array_pop($symbols);
            $decimalSeparator = end($symbols);
        }

        $regSymbol = '.' === $decimalSeparator ? '\\' . $decimalSeparator : $decimalSeparator;
        $number = preg_replace("/[^\d$regSymbol]/u", '', $float);
        $number = str_replace($decimalSeparator, '.', $number);

        return new self($formater->parse($number), mb_strlen(explode('.', $number)[1]), $sign);
    }

    public function format(string $format, ?string $locale = null): string
    {
        $formater = numfmt_create($locale ?: 'en', NumberFormatter::DECIMAL);
        if (! empty($format)) {
            $this->setFormat($format, $formater);
        }

        $formater->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $this->fractionDigits);
        $formater->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $this->fractionDigits);

        $firstChar = mb_substr($format, 0, 1);
        $signOfFormat = in_array($firstChar, ['-', '+']) ? $firstChar : '';

        return ($signOfFormat ? '' : $this->sign) . $formater->format($this->number);
    }

    private function setFormat(string $format, NumberFormatter $formater): void
    {
        $formater->setPattern($format);
        preg_match('/#+0*([^#0])((#+\1)*#*0*([^#0]))?#*0*/u', $format, $symbols);

        if (empty($symbols)) {
            return;
        }

        // Symbols are predefined by locale, so we have to enforce our desired ones
        $formater->setSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, $symbols[4] ?? $symbols[1]);

        if (! isset($symbols[4])) {
            $formater->setSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL, '');

            return;
        }

        // replace non-standard symbols in patter with standard
        $format = str_replace([$symbols[1], $symbols[4]], ['COMMA', 'DOT'], $format);
        $formater->setPattern(str_replace(['COMMA', 'DOT'], [',', '.'], $format));
        $formater->setSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL, $symbols[1]);
    }
}
