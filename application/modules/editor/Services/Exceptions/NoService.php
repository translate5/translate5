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

/**
 * is thrown if a service is accessed, which does not exist any more, or where the corresponding plug-in was disabled.
 */
class editor_Services_Exceptions_NoService extends ZfExtended_ErrorCodeException {
    /**
     * @var string
     */
    protected $domain = 'editor.languageresource.service';
    
    static protected $localErrorCodes = [
        'E1106' => 'Given Language-Resource-Service "{serviceType}." is not registered in the Language-Resource-Service-Manager!',
        'E1257' => 'The LanguageResource-Service "{service}" is not configured. Please check this confluence-page for more details: "{helpPage}"',
        'E1316' => 'The previously configured LanguageResource-Service "{service}" is not available anymore.',
    ];
}