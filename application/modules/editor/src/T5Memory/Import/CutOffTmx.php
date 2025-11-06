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

namespace MittagQI\Translate5\T5Memory\Import;

use MittagQI\Translate5\T5Memory\Exception\TmxCutOffException;
use XMLReader;
use XMLWriter;

class CutOffTmx
{
    public static function create(): self
    {
        return new self();
    }

    public function cutOff(string $tmxFile, int $segmentToStartFrom): void
    {
        $errorLevel = error_reporting();
        error_reporting($errorLevel & ~E_WARNING);

        $reader = new XMLReader();
        if (! $reader->open($tmxFile)) {
            error_reporting($errorLevel);

            throw new TmxCutOffException('Could not open TMX file ' . $tmxFile);
        }

        $writer = new XMLWriter();

        $cutOffTmx = dirname($tmxFile) . '/' . str_replace('.tmx', '', basename($tmxFile)) . '_cutoff.tmx';

        $writer->openURI($cutOffTmx);
        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);

        /*        $writer->writeRaw('<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);*/

        $tuCount = 0;

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'header') {
                $writer->writeRaw(
                    '<tmx version="1.4">' . PHP_EOL . $reader->readOuterXML() . PHP_EOL . '<body>' . PHP_EOL
                );

                continue;
            }

            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'tu') {
                $tuCount++;

                if ($tuCount < $segmentToStartFrom) {
                    continue;
                }

                $writer->writeRaw($reader->readOuterXML() . PHP_EOL);
            }
        }

        error_reporting($errorLevel);

        $writer->writeRaw('</body>' . PHP_EOL . '</tmx>');

        $writer->flush();

        rename($cutOffTmx, $tmxFile);
    }
}
