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

namespace MittagQI\Translate5\Plugins\SpellCheck\Segment;

use editor_Segment_Processing;

/**
 * Separate holder of certain configurations to accompany the SegmentProcessor & Worker
 */
class Configuration
{
    /**
     * Defines, how much segments can be processed per one worker call
     *
     * @var integer
     */
    const OPERATION_BATCH_SIZE = 5;

    /**
     * Defines the timeout in seconds how long a spell-check call with multiple segments may need
     *
     * @var integer
     */
    const TIMEOUT_REQUEST = 300;

    /**
     * Logger Domain Import
     * @var string
     */
    const OPERATION_LOGGER_DOMAIN = 'editor.spellcheck.operation';

    /**
     * Logger Domain Editing
     * @var string
     */
    const EDITOR_LOGGER_DOMAIN = 'editor.spellcheck.segmentediting';

    /**
     * @param string $processingType
     * @return string
     */
    public static function getLoggerDomain(string $processingType): string
    {
        if(editor_Segment_Processing::isOperation($processingType)){
            return self::OPERATION_LOGGER_DOMAIN;
        }
        return self::EDITOR_LOGGER_DOMAIN;
    }

    /**
     * Retrieves the request timeouts for languagetool-service calls
     * @param bool $isWorkerContext
     * @return int
     */
    public static function getRequestTimeout(bool $isWorkerContext): int
    {
        return self::TIMEOUT_REQUEST;
    }
}
