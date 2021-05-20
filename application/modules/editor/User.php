<?php
/*
 START LICENSE AND COPYRIGHT
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
 
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/gpl.html
 http://www.translate5.net/plugin-exception.txt
 
 END LICENSE AND COPYRIGHT
 */

/**
 * 
 * Represents the session based User and provides a convenience API accessing it
 */
class editor_User {
    
    /**
     * @var editor_User
     */
    private static $_instance = NULL;
    
    /**
     * 
     * @return editor_User
     */
    public static function instance() : editor_User {
        if(self::$_instance == NULL){
            self::$_instance = new editor_User();
        }
        return self::$_instance;
    }
    /**
     * 
     * @var Zend_Session_Namespace
     */
    private $session;
    
    private function __construct(){
        $this->session = new Zend_Session_Namespace('user');
        // TODO FIXME: add some validation to catch an inexisting session or an invalid user
    }
    /**
     * 
     * @return int
     */
    public function getId() : int {
        return $this->session->data->id;
    }
    /**
     * 
     * @return string
     */
    public function getGuid() : string {
        return $this->session->data->userGuid;
    }
    /**
     * 
     * @return string
     */
    public function getLogin() : string {
        return $this->session->data->login;
    }
    /**
     * 
     * @return string
     */
    public function getRoles() : string {
        return $this->session->data->roles;
    }
    /**
     *
     * @return stdClass
     */
    public function getData() {
        return $this->session->data;
    }
    /**
     * 
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role) : bool {
        return (!empty($role) && in_array($role, $this->session->data->roles));
    }
    /**
     * 
     * @param string[] $roles
     * @return bool
     */
    public function hasRoles(array $roles) : bool {
        foreach($roles as $role){
            if(!$this->hasRole($role)){
                return false;
            }
        }
        return true;
    }
    /**
     * 
     * @param string|string[] $role
     * @return bool
     */
    public function isA($role) : bool {
        if(is_array($role)){
            return $this->hasRoles($role);
        }
        return $this->hasRole($role);
    }
    /**
     *
     * @return bool
     */
    public function isProjectManager() : bool {
        return $this->hasRole(ACL_ROLE_PM);
    }
    /**
     *
     * @return bool
     */
    public function isAdmin() : bool {
        return $this->hasRole(ACL_ROLE_ADMIN);
    }
    /**
     *
     * @return bool
     */
    public function isPM() : bool {
        return $this->isProjectManager();
    }
}