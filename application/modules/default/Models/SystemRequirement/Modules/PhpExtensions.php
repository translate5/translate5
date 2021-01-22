<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * @package portal
 * @version 2.0
 *
 */
/**
 */
class Models_SystemRequirement_Modules_PhpExtensions extends ZfExtended_Models_SystemRequirement_Modules_Abstract {
    
    protected $installationBootstrap = true;
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Models_SystemRequirement_Modules_Abstract::validate()
     */
    function validate(): ZfExtended_Models_SystemRequirement_Result {
        $this->result->id = 'phpmodules';
        $this->result->name = 'PHP Extensions';
        $this->checkPhpExtensions();
        return $this->result;
    }

    /**
     * Checks the needed PHP extensions
     */
    protected function checkPhpExtensions() {
        $loaded = get_loaded_extensions();
        $needed = [
            'dom',
            'fileinfo',
            'gd',
            'iconv',
            'intl',
            'mbstring',
            'pdo_mysql',
            'zip',
            'curl'
        ];
        $missing = array_diff($needed, $loaded);
        if(empty($missing)) {
            return;
        }
        $this->result->error[] = 'The following PHP extensions are not loaded or not installed, but are needed by translate5: '."\n    ".join(", ", $missing);
        
        if(extension_loaded('gd')) {
            $gdinfo = gd_info();
            if(!$gdinfo['FreeType Support']) {
                $this->result->error[] = 'The PHP extension GD needs to be installed with freetype support!';
            }
        }
    }
}