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
 * FIXME must be moved to ZfExtended since there is the authenticated user used too!!!
 * Represents the session based User and provides a convenience API accessing it
 */
class editor_User {
    
    /**
     * @var editor_User
     */
    private static $_instance = NULL;

    /**
     * @var ZfExtended_Models_User
     */
    private static $modelInstance = NULL;

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

    /**
     * @throws ZfExtended_NotAuthenticatedException
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    private function __construct(){
        $this->session = new Zend_Session_Namespace('user');
        // TODO FIXME: add some validation to catch an inexisting session or an invalid user
        self::$modelInstance = ZfExtended_Factory::get('ZfExtended_Models_User');
        if($this->getId() === 0) {
            throw new ZfExtended_NotAuthenticatedException();
        }
        self::$modelInstance->load($this->getId());
    }
    /**
     * 
     * @return int
     */
    public function getId() : int {
        return $this->session->data->id ?? 0;
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
     * @return array
     */
    public function getRoles() : array {
        return $this->session->data->roles;
    }

    /***
     * @return string
     */
    public function getUserName(): string
    {
        return $this->session->data->userName;
    }

    /**
     * Check if currently logged in user is allowed to access the given ressource and right
     *
     * @param string $resource
     * @param string $right
     *
     * @return boolean
     */
    public function isAllowed(string $resource, string $right): bool {
        try {
            return ZfExtended_Acl::getInstance()->isInAllowedRoles($this->getRoles(), $resource, $right);
        }
        catch (Zend_Acl_Exception) {
            return false;
        }
    }

    /**
     *
     * @return stdClass
     */
    public function getData() {
        return $this->session->data;
    }

    public function getModel(): ZfExtended_Models_User {
        return self::$modelInstance;
    }

    /**
     * @deprecated should not be used, ACL checks must be used instead
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role) : bool {
        return (!empty($role) && in_array($role, $this->session->data->roles));
    }
    /**
     * @deprecated should not be used, ACL checks must be used instead
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
     * @deprecated should not be used, ACL checks must be used instead
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
     * @deprecated should not be used, ACL checks must be used instead
     * @return bool
     */
    public function isProjectManager() : bool {
        return $this->hasRole(ACL_ROLE_PM);
    }
    /**
     * @deprecated should not be used, ACL checks must be used instead
     * @return bool
     */
    public function isAdmin() : bool {
        return $this->hasRole(ACL_ROLE_ADMIN);
    }
    /**
     * @deprecated should not be used, ACL checks must be used instead
     * @return bool
     */
    public function isPM() : bool {
        return $this->isProjectManager();
    }
}