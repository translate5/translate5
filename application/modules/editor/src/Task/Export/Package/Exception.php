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

namespace MittagQI\Translate5\Task\Export\Package;

class Exception extends \ZfExtended_ErrorCodeException
{

    /**
     * @var string
     */
    protected $domain = 'editor.task.export.package';

    protected static array $localErrorCodes = [
        'E1452' => 'Export package: Task contains not supported files for package export',
        'E1453' => 'Export package: Source package validation fail',
        'E1454' => 'Export package: Unable to create resource export folder',
        'E1501' => 'Export package: General problem with package export. Check the error log for more info.',
        'E1502' => 'Export package: The export package does not exist anymore.',
        'E1504' => 'Export package: The provided download link is not valid.'
    ];
}