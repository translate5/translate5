<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 * Okapi Exception
 */
class editor_Plugins_Okapi_Exception extends ZfExtended_ErrorCodeException {
    /**
     * @var string
     */
    protected $domain = 'plugin.okapi';
    
    static protected array $localErrorCodes = [
        'E1055' => 'Okapi Plug-In: Bconf not given or not found: {bconfFile}',
        'E1056' => 'Okapi Plug-In: tikal fallback can not be used, workfile does not contain the XLF suffix: {workfile}',
        'E1057' => 'Okapi Plug-In: Data dir not writeable: {okapiDataDir}',
        'E1058' => 'Okapi Plug-In: Error in converting a file: "{message}" on converting file {file}',
        'E1059' => 'Okapi Plug-In: Configuration error - no Okapi server URL is configured!',
        'E1150' => 'Okapi Plug-In: The exported XLIFF contains empty targets, the Okapi process will probably fail then.',
        'E1340' => 'Okapi Plug-In: The default bconf configuration file-name is not set.',
        'E1387' => 'Okapi Plug-In: Providing the BCONF to use in the import ZIP is deprecated',
        'E1390' => 'Okapi Plug-In: The SRX file is not valid. {details}',
        'E4400' => 'Okapi Plug-In: The filter "{filter}" from the imported bconf "{bconf}" is not valid',
        'E4401' => 'Okapi Plug-In: The okapi filter "{filter}" from the imported bconf "{bconf}" is not valid',
        'E4402' => 'Okapi Plug-In: Invalid extension-mapping found in the bconf "{bconf}" to import',
        'E4403' => 'Okapi Plug-In: The extension mapping of the bconf "{bconf}" containes an invalid filter-mapping identifier "{identifier}"'
    ];
}