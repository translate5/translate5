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

class MissingMrkSpacesFixer
{
    private editor_Models_Import_FileParser_XmlParser $parser;

    private string $fixedData = '';

    private bool $hasFixedData = false;

    private array $sourceStartsWithSpace = [];

    private array $sourceEndsWithSpace = [];

    private bool $hasMissingSpaces = false;

    public function __construct(
        private readonly bool $fix,
    ) {
        $this->parser = new editor_Models_Import_FileParser_XmlParser();

        $this->parser->registerElement(
            'trans-unit > seg-source > mrk[mtype=seg]',
            null,
            function ($tag, $endIdx, $opener) {
                // speed up analysis
                if (! $this->fix && $this->hasMissingSpaces) {
                    return;
                }

                $tagContent = $this->parser->getRange($opener['openerKey'], $endIdx, true);

                $transUnitId = $this->parser->getAttribute(
                    $this->parser->getParent('trans-unit')['attributes'],
                    'id'
                );
                $mid = $this->parser->getAttribute($opener['attributes'], 'mid');

                if (preg_match('#">(\s+)[^\s<]#', $tagContent, $match)) {
                    settype($this->sourceStartsWithSpace[$transUnitId], 'array');
                    $this->sourceStartsWithSpace[$transUnitId][$mid] = $match[1];
                }
                if (preg_match('#[^\s>](\s+)</mrk>#', $tagContent, $match)) {
                    settype($this->sourceEndsWithSpace[$transUnitId], 'array');
                    $this->sourceEndsWithSpace[$transUnitId][$mid] = $match[1];
                }
            }
        );
        $this->parser->registerElement(
            'trans-unit > target > mrk[mtype=seg]',
            null,
            function ($tag, $endIdx, $opener) {
                // speed up analysis
                if (! $this->fix && $this->hasMissingSpaces) {
                    return;
                }
                $tagContent = $this->parser->getRange($opener['openerKey'], $endIdx, true);
                $transUnitId = $this->parser->getAttribute(
                    $this->parser->getParent('trans-unit')['attributes'],
                    'id'
                );
                $mid = $this->parser->getAttribute($opener['attributes'], 'mid');
                if (isset($this->sourceStartsWithSpace[$transUnitId][$mid]) && ! preg_match('#">\s#', $tagContent)) {
                    if ($this->fix) {
                        $chunk = $this->parser->getChunk($opener['openerKey'] + 1);
                        $this->parser->replaceChunk($opener['openerKey'] + 1, $this->sourceStartsWithSpace[$transUnitId][$mid] . $chunk);
                        $this->hasFixedData = true;
                    } else {
                        $this->hasMissingSpaces = true;
                    }
                }
                if (isset($this->sourceEndsWithSpace[$transUnitId][$mid]) && ! preg_match('#\s</mrk>#', $tagContent)) {
                    if ($this->fix) {
                        $chunk = $this->parser->getChunk($endIdx - 1);
                        $this->parser->replaceChunk($endIdx - 1, $chunk . $this->sourceEndsWithSpace[$transUnitId][$mid]);
                        $this->hasFixedData = true;
                    } else {
                        $this->hasMissingSpaces = true;
                    }
                }
            }
        );
    }

    public function hasMissingSpaces(string $data): bool
    {
        $this->hasFixedData = $this->hasMissingSpaces = false;
        $this->sourceStartsWithSpace = $this->sourceEndsWithSpace = [];
        $this->fixedData = $this->parser->parse($data);
        if ($this->fix) {
            return false;
        }

        // @phpstan-ignore-next-line
        return $this->hasMissingSpaces;
    }

    public function getFixedData(): string
    {
        return $this->hasFixedData ? $this->fixedData : '';
    }
}
