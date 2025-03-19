<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace Translate5\MaintenanceCli\FixScript;

use editor_Models_Import_FileParser_XmlParser;

class MissingMrkSegmentsFixer
{
    private const EMPTY_MRK_REGEXP = '/<mrk[^>]*>\s+<\/mrk>/';

    private editor_Models_Import_FileParser_XmlParser $parser;

    private int $mrkSourceCount;

    private int $mrkTargetCount;

    private string $fixedData = '';

    private bool $hasFixedData = false;

    private array $emptyMrkInSource = [];

    public function __construct(
        private readonly bool $fix,
    ) {
        $this->parser = new editor_Models_Import_FileParser_XmlParser();

        $this->parser->registerElement(
            'trans-unit > seg-source > mrk[mtype=seg]',
            null,
            function ($tag, $endIdx, $opener) {
                $tagContent = $this->parser->getRange($opener['openerKey'], $endIdx, true);
                if (preg_match(self::EMPTY_MRK_REGEXP, $tagContent)) {
                    $this->mrkSourceCount++;
                    if ($this->fix) {
                        $transUnitId = $this->parser->getAttribute(
                            $this->parser->getParent('trans-unit')['attributes'],
                            'id'
                        );
                        settype($this->emptyMrkInSource[$transUnitId], 'array');
                        $mid = $this->parser->getAttribute($opener['attributes'], 'mid');
                        $this->emptyMrkInSource[$transUnitId][$mid] = $tagContent; // <mrk mid="1" mtype="seg"> </mrk>
                    }
                }
            }
        );
        $this->parser->registerElement(
            'trans-unit > target',
            null,
            function ($tag, $endIdx, $opener) {
                if ($this->fix) {
                    $transUnitId = $this->parser->getAttribute(
                        $this->parser->getParent('trans-unit')['attributes'],
                        'id'
                    );
                    if (! empty($this->emptyMrkInSource[$transUnitId])) {
                        $foundMrkInTarget = [];
                        // add all missing MRKs
                        for ($i = $opener['openerKey'] + 1; $i < $endIdx; $i++) {
                            $chunk = $this->parser->getChunk($i);
                            if (! empty($chunk) && stripos($chunk, '<mrk ') === 0 && preg_match(
                                '/mid=[\'"]?(\d+)"/U',
                                $chunk,
                                $matches
                            )) {
                                $mid = (int) $matches[1];
                                $foundMrkInTarget[$mid] = 1;
                                $mid--;
                                while ($mid > 0 && ! isset($foundMrkInTarget[$mid]) && isset($this->emptyMrkInSource[$transUnitId][$mid])) {
                                    $chunk = $this->emptyMrkInSource[$transUnitId][$mid] . $chunk;
                                    $this->parser->replaceChunk($i, $chunk);
                                    unset($this->emptyMrkInSource[$transUnitId][$mid]);
                                    $mid--;
                                    $this->hasFixedData = true;
                                }
                            }
                        }
                    }
                } else {
                    $tagInnerXML = $this->parser->getRange($opener['openerKey'] + 1, $endIdx - 1, true);
                    if ($mrkTargetCount = preg_match_all(self::EMPTY_MRK_REGEXP, $tagInnerXML)) {
                        $this->mrkTargetCount += $mrkTargetCount;
                    }
                }
            }
        );
    }

    public function hasMissingMrkSegments(string $data): bool
    {
        $this->hasFixedData = false;
        $this->mrkSourceCount = $this->mrkTargetCount = 0;
        $this->emptyMrkInSource = [];
        $this->fixedData = $this->parser->parse($data);
        if ($this->fix) {
            return false;
        }

        // @phpstan-ignore-next-line
        return $this->mrkSourceCount !== $this->mrkTargetCount;
    }

    public function getFixedData(): string
    {
        return $this->hasFixedData ? $this->fixedData : '';
    }
}
