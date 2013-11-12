<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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