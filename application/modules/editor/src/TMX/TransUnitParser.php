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

namespace MittagQI\Translate5\TMX;

use editor_Models_Languages as Language;
use MittagQI\Translate5\T5Memory\Exception\BrokenTranslationUnitException;
use XMLReader;

class TransUnitParser
{
    public function extractStructure(
        string $transUnit,
        Language $sourceLang,
        Language $targetLang,
    ): TransUnitStructure {
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
                $replacement = str_replace($segment, TransUnitStructure::SOURCE_PLACEHOLDER, $xml->readInnerXml());
            } else {
                $targetSegment = $segment;
                $replacement = str_replace($segment, TransUnitStructure::TARGET_PLACEHOLDER, $xml->readInnerXml());
            }

            $transUnit = str_replace(
                $tuv,
                str_replace($xml->readInnerXml(), $replacement, $tuv),
                $transUnit
            );

            if ('' !== $sourceSegment && '' !== $targetSegment) {
                break;
            }
        }

        // if there is no source or target tuv, then we assume that tu is broken
        if ('' === $sourceSegment || '' === $targetSegment) {
            throw new BrokenTranslationUnitException($transUnit);
        }

        return new TransUnitStructure(
            $transUnit,
            $sourceSegment,
            $targetSegment
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
