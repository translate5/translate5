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

namespace MittagQI\Translate5\Task\Export\FileParser\Xlf\Namespaces;

use editor_Models_Export_FileParser_Xlf_Namespaces_Abstract;
use editor_Models_Import_FileParser_XmlParser;
use MittagQI\Translate5\Task\Export\FileParser\Xlf\Comments;

/**
 * This mxlif namespace handler will remove the custom added placeholder tags (ph) from the mxliff markup
 */
class Mxliff extends editor_Models_Export_FileParser_Xlf_Namespaces_Abstract
{
    public function __construct(editor_Models_Import_FileParser_XmlParser $xmlParser, Comments $comments)
    {
        parent::__construct($xmlParser, $comments);
        if ($comments->isEnabled()) {
            $this->registerParserHandler();
        }
    }

    public function registerParserHandler(): void
    {
        $this->xmlparser->registerElement('ph', null, function ($tag, $key, $opener) {
            $chunk = $this->xmlparser->getChunk($key - 1);
            $openerPatern = '/\{([a-zA-Z0-9]+)&gt;/';
            $closerPatern = '/&lt;([a-zA-Z0-9]+)\}/';

            if (preg_match($openerPatern, $chunk)) {
                $this->xmlparser->replaceChunk($opener['openerKey'], '');
                $this->xmlparser->replaceChunk($key, '');
            }

            if (preg_match($closerPatern, $chunk)) {
                $this->xmlparser->replaceChunk($opener['openerKey'], '');
                $this->xmlparser->replaceChunk($key, '');
            }
        });
    }

    public function postProcessFile(string $xml): string
    {
        // will remove the ph tags from the converted content on import
        $pattern = '/<ph>\{([a-z]+)&gt;<\/ph>|<ph>&lt;([a-z]+)\}<\/ph>/';

        return preg_replace_callback($pattern, function ($matches) {
            return ! empty($matches[1]) ? '{' . $matches[1] . '&gt;' : '&lt;' . $matches[2] . '}';
        }, $xml);
    }
}
