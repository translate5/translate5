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
 * @package portal
 * @version 2.0
 *
 */
/**
 * @deprecated
 */
class Models_Installer_PreconditionCheck {
    public function checkUsers() {
        $config = Zend_Registry::get('config');
        $db = Zend_Db::factory($config->resources->db);
        $result = $db->query('SELECT count(*) active FROM session where modified + lifetime > unix_timestamp()');
        $activeSessions = $result->fetchObject()->active;
        
        $result = $db->query('SELECT count(*) active FROM session where modified + 3600 > unix_timestamp()');
        $lastHourSessions = $result->fetchObject()->active;
        
        echo "Session Summary:\n";
        echo "Active Sessions:               ".$activeSessions."\n";
        echo "Active Sessions (last hour):   ".$lastHourSessions."\n";
    }
    
    public function checkWorkers() {
        $config = Zend_Registry::get('config');
        $db = Zend_Db::factory($config->resources->db);
        $result = $db->query('SELECT count(*) cnt, state FROM Zf_worker group by state');
        echo "Workers:\n";
        while($row = $result->fetchObject()) {
            echo "        ".str_pad($row->state, 23).$row->cnt."\n";
        }
    }
}