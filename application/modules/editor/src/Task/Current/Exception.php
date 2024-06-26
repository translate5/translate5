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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

namespace MittagQI\Translate5\Task\Current;

class Exception extends \ZfExtended_ErrorCodeException
{
    /**
     * @var string
     */
    protected $domain = 'editor.currenttask';

    protected static $localErrorCodes = [
        //Development error: Some PHP code tried to load the currently opened task (identified by the taskid given in the URL)
        // but no task ID was provided in the URL.
        // So either the URL producing the request is wrongly created (no Editor.data.restpath prefix),
        // or its just the wrong context where the CurrentTask was accessed.
        'E1381' => 'Access to CurrentTask was requested but no task ID was given in the URL.',
        'E1382' => 'Access to CurrentTask was requested but it was NOT initialized yet.',
    ];
}
