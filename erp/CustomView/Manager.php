<?php
class erp_CustomView_Manager {
    
    /**
     */
    static protected $registeredViews = array(
        'offer'=>'erp_CustomView_Offer',
        'project'=>'erp_CustomView_Project',
        'bill'=>'erp_CustomView_Bill'
    );
    
    public function getAll() {
        return self::$registeredViews;
    }
    
    /**
     */
    public function addView(erp_CustomView_Abstract $view) {
        self::$registeredViews[$view->getName()] =get_class($view);
        self::$registeredViews = array_unique(self::$registeredViews);
        return self::$registeredViews;
    }
    
    /**
     */
    public function hasView(string $viewname) {
        return isset(self::$registeredViews[$viewname]) ? self::$registeredViews[$viewname] : false;
    }
    
    /***
     * Check if the given view is registered in the manager and exist for the current loged user
     * @param string $view
     * @throws ZfExtended_Exception
     * @return erp_CustomView_Abstract
     */
    public function checkUserView($view){
        if(empty($view)){
            throw new ZfExtended_Exception("View is not defined");
        }
        
        //check if the view class is registered for the view
        $viewClass=$this->hasView($view);
        
        if(empty($viewClass)){
            throw new ZfExtended_Exception("The provided view is not defined in the view manager.");
        }
        
        //the manager has the view
        $userSession = new Zend_Session_Namespace('user');
        $userRoles = $userSession->data->roles;
        if(!ZfExtended_Acl::getInstance()->isInAllowedRoles($userRoles, "customerview", $view)){
            throw new ZfExtended_Exception("The user is not allowed to see this view. ViewName->".$view);
        }
        return ZfExtended_Factory::get($viewClass);
    }
}