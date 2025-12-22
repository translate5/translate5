<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace Translate5\MaintenanceCli\L10n;

use editor_Models_Import_FileParser_XmlParser;

/**
 * Helper class to load translations from existing xliff files
 */
class XliffParser extends editor_Models_Import_FileParser_XmlParser
{
    private array $translations;

    private string $_source;

    private string $_target;

    public function __construct(
        private readonly string $absoluteFilePath
    ) {
    }

    /**
     * Retrieves the translation as array source => target
     * @return array<string, string>
     * @throws \ZfExtended_Exception
     */
    public function getTranslations(): array
    {
        $this->translations = [];
        $xmlString = @file_get_contents($this->absoluteFilePath);
        if (! $xmlString) {
            throw new \ZfExtended_Exception(
                'L10N XliffParser: Could not get contents of file ' . $this->absoluteFilePath
            );
        }
        // parse the xml, replaces the targets with the source-segments with the segmentation-markers added
        $this->registerElement('xliff trans-unit', [$this, 'startTransUnit'], [$this, 'endTransUnit']);
        $this->registerElement('xliff trans-unit > source', null, [$this, 'endSource']);
        $this->registerElement('xliff trans-unit > target', null, [$this, 'endTarget']);

        try {
            $this->parse($xmlString);
        } catch (\Throwable $e) {
            $add = '';
            if (
                is_a($e, \ZfExtended_ErrorCodeException::class) &&
                array_key_exists('parseStack', $e->getErrors())
            ) {
                $add = ' [ stack: ' . $e->getErrors()['parseStack'] . ']';
            }

            throw new \ZfExtended_Exception(
                'L10N XliffParser - Error parsing file ' . $this->absoluteFilePath . ': ' .
                \ZfExtended_Logger::renderException($e) . $add
            );
        }

        return $this->translations;
    }

    public function startTransUnit(string $tag, array $attributes, int $key, bool $isSingle): void
    {
        $this->_source = '';
        $this->_target = '';
    }

    public function endTransUnit(string $tag, int $key, array $opener): void
    {
        if ($this->_source !== '') {
            $this->translations[$this->_source] = $this->_target;
        }
    }

    public function endSource(string $tag, int $key, array $opener): void
    {
        $this->_source = $this->getRange($opener['openerKey'] + 1, $key - 1, true);
    }

    public function endTarget(string $tag, int $key, array $opener): void
    {
        $this->_target = $this->getRange($opener['openerKey'] + 1, $key - 1, true);
    }
}
