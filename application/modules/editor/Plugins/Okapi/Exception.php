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
        'E1390' => 'Okapi Plug-In: The uploaded SRX file is not valid ({details})',
        'E1404' => 'Okapi Plug-In: The filter/fprm "{filter}" from the imported bconf "{bconf}" is not valid',
        'E1405' => 'Okapi Plug-In: Invalid extension-mapping found in the bconf "{bconf}" to import',
        'E1406' => 'Okapi Plug-In: The extension mapping of the bconf "{bconf}" contains an invalid filter identifier "{identifier}"',
        'E1407' => 'Okapi Plug-In: The extension mapping of the bconf "{bconf}" contains an invalid filter identifier "{identifier}" which has been removed',
        'E1408' => 'Okapi Plug-In: The bconf "{bconf}" to import is not valid ({details})',
        'E1409' => 'Okapi Plug-In: The edited filter file "{filterfile}" is not valid ({details})',
        'E1414' => 'Okapi Plug-In: The fprm file "{file}" is not valid ({details})',
        'E1415' => 'Okapi Plug-In: Error unpacking the bconf {bconf} ({details})',
        'E1416' => 'Okapi Plug-In: Error packing the bconf {bconf} ({details})',
        'E1410' => 'Okapi Plug-In: No configuration found for okapi server(s)',
        'E1411' => 'Okapi Plug-In: No configuration found for okapi server used',
        'E1412' => 'Okapi Plug-In: The server used can not be found in all available configured servers',
        'E1474' => 'Okapi Plug-In: The Okapi plug-in is disabled so no export into the original import format can be done at the moment',
    ];
}