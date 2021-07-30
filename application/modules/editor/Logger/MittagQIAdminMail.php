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
 * This writer can be used to send log mails to @translat5.net and @mittagqi.com admin users to log specific info messages via email
 */
class editor_Logger_MittagQIAdminMail extends ZfExtended_Logger_Writer_DirectMail {
    /**
     * @var boolean
     */
    protected $enabled = true;
    
    public function __construct(array $options) {
        parent::__construct($options);
        $users = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $users ZfExtended_Models_User */
        $s = $users->db->select();
        $s->where('roles like "%,admin,%" or roles like "%,admin" or roles like "admin,%"')
        ->where('email like "%@translate5.net" or email like "%mittagqi.com"');
        $adminUsers = $users->db->fetchAll($s)->toArray();
        if(empty($adminUsers)) {
            $this->enabled = false;
            return;
        }
        $this->options['receiver'] = array_unique(array_column($adminUsers, 'email'));
    }
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Logger_Writer_Abstract::isEnabled()
     */
    public function isEnabled(): bool {
        return parent::isEnabled() && $this->enabled;
    }
    
}