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

namespace MittagQI\Translate5\Task\Reimport;

class Exception extends \ZfExtended_ErrorCodeException
{

    /**
     * @var string
     */
    protected $domain = 'editor.task.reimport';

    protected static array $localErrorCodes = [
        'E1427' => 'Reimport DataProvider: Error on file upload.',
        'E1428' => 'Reimport DataProvider: Unable to move the uploaded file to {file}',
        'E1429' => 'Reimport DataProvider: No upload files found for task reimport.',
        'E1430' => 'Reimport DataProvider: This file type is not supported {file}',
        'E1431' => 'Reimport DataProvider: Unable to create a temporary folder for the re-import',
        'E1434' => 'Reimport Segment processor: No matching segment was found for the given mid.',
        'E1441' => 'Reimport Segment processor: No content parser found for the file {file}',
        'E1442' => 'Reimport Zip: zip file could not be opened: {zipError}',
        'E1462' => 'Reimport Zip: The provided zip package did not contain any file matching the files in the task.',
    ];
}