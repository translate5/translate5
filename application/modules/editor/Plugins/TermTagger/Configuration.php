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

namespace MittagQI\Translate5\Plugins\TermTagger;

use editor_Segment_Processing;

/**
 * Seperate Holder of certain configurations regarding the termtagging
 * to accompany Tagger, Remover and editor_Plugins_TermTagger_Worker
 */
class Configuration
{

    /**
     * Defines, how much segments can be processed in one processor call
     * @var integer
     */
    const OPERATION_BATCH_SIZE = 5;
    /**
     * Defines, how much tags can be removed in one processor call
     * Be aware that this may affects the deadlock probability as other workers work on the same table at the same time
     * @var integer
     */
    const REMOVAL_BATCH_SIZE = 10;
    /**
     * Defines the timeout in seconds how long a termtag call with multiple segments may need
     * @var integer
     */
    const OPERATION_TIMEOUT_REQUEST = 300;
    /**
     * Defines the timeout in seconds how long a single segment needs to be tagged
     * @var integer
     */
    const EDITOR_TIMEOUT_REQUEST = 180;
    /**
     * Defines the timeout in seconds how long the upload and parse request of a TBX may need
     * @var integer
     */
    const TIMEOUT_TBXIMPORT = 600;
    /**
     * Logger Domain Import
     * @var string
     */
    const IMPORT_LOGGER_DOMAIN = 'editor.terminology.import';
    /**
     * Logger Domain Editing
     * @var string
     */
    const EDITOR_LOGGER_DOMAIN = 'editor.terminology.segmentediting';
    /**
     * Logger Domain Manual Analysis
     * @var string
     */
    const ANALYSIS_LOGGER_DOMAIN = 'editor.terminology.analysis';

    /**
     * Defines the logger-domain for all termtagger code
     * @param string $processingType
     * @return string
     */
    public static function getLoggerDomain(string $processingType): string
    {
        switch ($processingType) {

            case editor_Segment_Processing::IMPORT:
                return self::IMPORT_LOGGER_DOMAIN;

            case editor_Segment_Processing::ANALYSIS:
            case editor_Segment_Processing::RETAG:
            case editor_Segment_Processing::TAGTERMS:
                return self::ANALYSIS_LOGGER_DOMAIN;

            case editor_Segment_Processing::EDIT:
            default:
                return self::EDITOR_LOGGER_DOMAIN;
        }
    }

    /**
     * Retrieves the request timeouts for termtagger-service calls
     * @param bool $isWorkerContext
     * @return int
     */
    public static function getRequestTimeout(bool $isWorkerContext): int
    {
        if ($isWorkerContext) {
            return self::OPERATION_TIMEOUT_REQUEST;
        }
        return self::EDITOR_TIMEOUT_REQUEST;
    }
}
