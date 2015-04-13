<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
 * Formats a Segment List as a HTML table to be send as an E-Mail. 
 */
class View_Helper_WorkflowNotifyHtmlMailUserList extends Zend_View_Helper_Abstract
{
    /**
     * @var array
     */
    protected $users;
    
    /**
     * render the HTML Segment Table
     * @return string
     */
    protected function render() {
        $t = $this->view->translate;
        /* @var $task editor_Models_Task */
        $result = array('<table cellpadding="4">');
        $th = '<th align="left">';
        $result[] = '<tr>';
        $result[] = $th.$t->_('Nachname').'</th>';
        $result[] = $th.$t->_('Vorname').'</th>';
        $result[] = $th.$t->_('Login').'</th>';
        $result[] = $th.$t->_('E-Mail Adresse').'</th>';
        $result[] = '</tr>';
        
        foreach($this->users as $user) {
            $result[] = "\n".'<tr>';
            $result[] = '<td>'.$user['surName'].'</td>';
            $result[] = '<td>'.$user['firstName'].'</td>';
            $result[] = '<td>'.$user['login'].'</td>';
            $result[] = '<td>'.$user['email'].'</td>';
            $result[] = '</tr>';
        }
        $result[] = '</table>';
        return join('', $result);
    }
    
    /**
     * @return string
     */
    public function __toString(){
        return $this->render();
    }

    /**
     * Helper Initiator, renders a list of users as html table
     * @param array $users
     */
    public function workflowNotifyHtmlMailUserList(array $users) {
        $this->users = $users;
        return $this;
    }
}