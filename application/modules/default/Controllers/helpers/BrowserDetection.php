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

/**
 * Just a simple helper to detect Internet Explorer via the user agent
 */
final class BrowserDetection {
    
    /**
     * simple check to detect IE 11 and below. This is hacky, but using a library like browsercap e.g. is overkill for just detecting IE and always involves regulary maintaining the library / ubdate to the latest data
     * @return boolean
     */
    public static function isInternetExplorer(){
        $userAgent = array_key_exists('HTTP_USER_AGENT', $_SERVER) ? htmlentities($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8') : null;
        if (!empty($userAgent) && (preg_match('~MSIE|Internet Explorer~i', $userAgent) || (strpos($userAgent, 'Trident/7.0') !== false && strpos($userAgent, 'rv:11.0') !== false))) {
            return true;
        }
        return false;
    }
}
