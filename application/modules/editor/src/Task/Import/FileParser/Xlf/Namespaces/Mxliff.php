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

namespace MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces;

use editor_Models_Import_FileParser_XmlParser as XmlParser;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Comments;

/**
 * Custom import namespace handler for mxlif filles. This xlff file contain non-standard placeholders(markups) which will be
 * surrounded by the placeholder tags. On export the placeholders(ph) tags will be removed and the original contend will be
 * returned.
 */
class Mxliff extends AbstractNamespace
{
    public const MXLIFF_XLIFF_NAMESPACE = 'http://www.memsource.com';

    public function __construct(
        protected XmlParser $xmlparser,
        protected Comments $comments,
    ) {
        parent::__construct($xmlparser, $comments);
    }

    public function preProcessFile(string $xliff): string
    {
        return $this->processSegmentChunk($xliff);
    }

    private function processSegmentChunk(string $segmentChunk): string
    {
        $pattern1 = '/\{([a-zA-Z]+)&gt;/';
        $pattern2 = '/&lt;([a-zA-Z]+)\}/';

        $callback = function ($matches) {
            return "<ph>{$matches[0]}</ph>";
        };

        return preg_replace_callback([$pattern1, $pattern2], $callback, $segmentChunk);
    }

    public static function isApplicable(string $xliff): bool
    {
        return str_contains($xliff, self::MXLIFF_XLIFF_NAMESPACE);
    }

    public static function getExportCls(): ?string
    {
        return \MittagQI\Translate5\Task\Export\FileParser\Xlf\Namespaces\Mxliff::class;
    }

    /**
     * Translate5 uses x,g and bx ex tags only. So the whole content of the tags incl. the tags must be used.
     * {@inheritDoc}
     * @see AbstractNamespace::useTagContentOnly()
     */
    public function useTagContentOnly(): ?bool
    {
        return false;
    }
}
