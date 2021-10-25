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

class erp_Plugins_Moravia_Init extends ZfExtended_Plugin_Abstract {
       
     /**
     * Contains the Plugin Path relativ to APPLICATION_PATH or absolut if not under APPLICATION_PATH
     * @var array
     */
    protected $frontendControllers = array(
        'pluginMoraviaCustomerView' => 'Erp.plugins.Moravia.controller.CustomerView',
    );
    
    protected $localePath = 'locales';
    
    
    /***
     * 
     * @var erp_Models_Customer
     */
    protected $customer;

    public function getFrontendControllers() {
        return $this->getFrontendControllersFromAcl();
    }
    
    /**
     * Initialize the plugn "Moravia"
     * {@inheritDoc}
     * @see ZfExtended_Plugin_Abstract::init()
     */
    public function init() {
        //load the moravia customer
        $this->customer=ZfExtended_Factory::get('erp_Models_Customer');
        /* @var $customer erp_Models_Customer */
        $this->customer->findCustomerByNumber($this->config->moravianumber);
        $this->initEvents();
        $this->addController('PmcustomersController');
        $this->addController('TypeController');
        $this->addController('ProductionController');
        
        $this->initRoutes();
        if(ZfExtended_Debug::hasLevel('plugin', 'Moravia')) {
            ZfExtended_Factory::addOverwrite('Zend_Http_Client', 'ZfExtended_Zendoverwrites_Http_DebugClient');
        }
        $viewManager=ZfExtended_Factory::get('erp_CustomView_Manager');
        /* @var $viewManager erp_CustomView_Manager */
        $viewManager->addView(new erp_Plugins_Moravia_ProductionView());
        
        $oreder=ZfExtended_Factory::get('erp_Models_Order');
        /* @var $oreder erp_Models_Order */
        
        //add moravia specific states and states translations
        $oreder->addAdditionalState('prcreated');
        $oreder->addAdditionalState('prapproved');
        $oreder->addAdditionalState('popublished');
        
        $oreder->addAdditionalStateTranslation('prcreated','PR created');
        $oreder->addAdditionalStateTranslation('prapproved', 'PR approved');
        $oreder->addAdditionalStateTranslation('popublished', 'PO published');
    }
    
    protected function initEvents() {
        $this->eventManager->attach('erp_PurchaseorderController', 'beforeIndexAction', array($this, 'beforePurchaseorderIndexAction'));
        $this->eventManager->attach('erp_OrderController', 'afterPostAction', array($this, 'afterOrderPostAction'));
        $this->eventManager->attach('erp_OrderController', 'afterPutAction', array($this, 'afterOrderPutAction'));
        $this->eventManager->attach('Erp_IndexController', 'afterIndexAction', array($this, 'injectFrontendConfig'));
    }
    
    /**
     * defines all URL routes of this plug-in
     */
    protected function initRoutes() {
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        $r = $f->getRouter();
        
        $restRoute = new Zend_Rest_Route($f, array(), array(
            'erp' => array(
                'plugins_moravia_pmcustomers',
                'plugins_moravia_type',
                'plugins_moravia_production'
            ),
        ));
        $r->addRoute('plugins_moravia_restdefault', $restRoute);
        
        
        $billCollectionRoute = new ZfExtended_Controller_RestLikeRoute(
            'erp/plugins_moravia_production/billcollection',
            array(
                'module' => 'erp',
                'controller' => 'plugins_moravia_production',
                'action' => 'billcollection'
            ));
        $r->addRoute('plugins_moravia_production/billcollection', $billCollectionRoute);
        
        $balancevaluecheckRoute = new ZfExtended_Controller_RestLikeRoute(
            'erp/plugins_moravia_production/balancevaluecheck',
            array(
                'module' => 'erp',
                'controller' => 'plugins_moravia_production',
                'action' => 'balancevaluecheck'
            ));
        $r->addRoute('plugins_moravia_production/balancevaluecheck', $balancevaluecheckRoute);
    }
    
    public function beforePurchaseorderIndexAction(Zend_EventManager_Event $event){
        //is the user allowed more than production
        if(!$this->isProductionOnly()){
            return;
        }
        
        $entity=$event->getParam('entity');
        /* @var $entity ZfExtended_Models_Entity_Abstract */
        
        /* @var $tmpFilter ZfExtended_Models_Filter_ExtJs */
        $this->addEntityFilter($entity,(object)[
            'field' => 'customerName',
            'value' => [$this->customer->getName()],
            'type' => 'list',
        ]);
    }
    
    public function afterOrderPostAction(Zend_EventManager_Event $event) {
        $entity=$event->getParam('entity');
        $data=$this->isValidDataForProduction($event->getParam('request'),$entity);
        if(!$data){
            return;
        }
        
        $model=ZfExtended_Factory::get('erp_Plugins_Moravia_Models_ProductionData');
        /* @var $model erp_Plugins_Moravia_Models_ProductionData */
        
        //set the production data fields, the setData method will fill only the valid data parametars for production data entity
        $model->setData($data);
        $model->setOrderId($entity->getId());

        //if it is not save and new line item, generate new handof number
        if($model->getHandoffNumber()==null || $model->getHandoffNumber()==''){
            $model->setHandoffNumber($model->findNextHandoffNumber());
        }
        try {
            $model->save();
            //set the view rows with the lates changes
            $event->getParam('view')->rows=$model->getDataObject();
        } catch (Exception $e) {
            //the exception happens when production data is saved -> delete the parent entity, and throw an exception
            $order=ZfExtended_Factory::get('erp_Models_Order');
            /* @var $order erp_Models_Order */
            $order->load($entity->getId());
            $order->delete();
            throw new ZfExtended_Exception('Error on production data save. Error was:'.$e->getMessage().'\n'.$e->getTraceAsString());
        }
    }
    
    public function afterOrderPutAction(Zend_EventManager_Event $event) {
        $entity=$event->getParam('entity');
        $data=$this->isValidDataForProduction($event->getParam('request'),$entity);
        if(!$data){
            return;
        }
        
        $model=ZfExtended_Factory::get('erp_Plugins_Moravia_Models_ProductionData');
        /* @var $model erp_Plugins_Moravia_Models_ProductionData */
        $model->loadByOrderId($entity->getId());
        
        //set the production data fields, the setData method will fill only the valid data parametars for production data entity
        $model->setData($data);
        $model->save();
        $this->resetBalanceValueCheckFromStatus($model);
    }
    
    public function injectFrontendConfig(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        /* @var $view Zend_View_Interface */
        $view->Php2JsVars()->set('plugins.Moravia.customer',$this->customer->getDataObject());
        
        $view->Php2JsVars()->set('plugins.Moravia.endCustomers',$this->getConfig()->endCustomers->toArray());
        
        $view->Php2JsVars()->set('plugins.Moravia.handoffNumberCustomerConfig', $this->getConfig()->handoffNumberCustomerConfig->toArray());
        
        $view->Php2JsVars()->set('plugins.Moravia.preliminaryWeightedWordsEndCustomerConfig', $this->getConfig()->preliminaryWeightedWordsEndCustomerConfig->toArray());
    }

    /***
     * Check the current request data is valid for production view.
     * If yes, the request data as array will be returned
     * @param Zend_Controller_Request_Abstract $request
     * @param erp_Models_Order $entity
     * @return NULL|array
     */
    protected function isValidDataForProduction(Zend_Controller_Request_Abstract $request,erp_Models_Order $entity){
        
        $view=$request->getParam('customerview');
        $manager=ZfExtended_Factory::get('erp_CustomView_Manager');
        /* @var $manager erp_CustomView_Manager */
        try {
            //validate the requested view for the user
            $view=$manager->checkUserView($view);
        } catch (ZfExtended_Exception $e) {
            return null;
        }
        
        //validate the entity customer
        $productionView=ZfExtended_Factory::get('erp_Plugins_Moravia_ProductionView');
        /* @var $productionView erp_Plugins_Moravia_ProductionView */
        if($entity->getCustomerNumber()!= $this->config->moravianumber || $productionView->getName()!=$view->getName()){
            return null;
        }
        
        //decote the request data
        /*@var $request Zend_Controller_Request_Abstract */
        $data=$request->getParam('data');
        $data=json_decode($data,true);
        //unset the order id so it is not initialized by the ProductionData entity
        unset($data['id']);
        
        return $data;
    }
    
    /***
     * Reset the balance value check when the order status is changed to offered,ordered or proforma
     * @param erp_Plugins_Moravia_Models_ProductionData $productionData
     */
    protected function resetBalanceValueCheckFromStatus(erp_Plugins_Moravia_Models_ProductionData $productionData){
        $invalidStates=[
            'offered',
            'ordered',
            'proforma'
        ];
        $model=ZfExtended_Factory::get('erp_Models_Order');
        /* @var $model erp_Models_Order */
        $model->load($productionData->getOrderId());
        if(in_array($model->getState(), $invalidStates)){
            $productionData->updateBalanceValueCheck($productionData->getHandoffNumber());
        }
    }

    /***
     * Check if the current user has production only role
     * @return boolean
     */
    protected function isProductionOnly(){
        $userSession = new Zend_Session_Namespace('user');
        $userRoles = $userSession->data->roles;
        $ret=array_diff($userRoles,['production','noRights','basic']);
        return empty($ret);
    }
    
    /***
     * Check if the given customerview is of type production
     * @param string $view
     * @return boolean
     */
    protected function isProductionView(string $view){
        $productionView=ZfExtended_Factory::get('erp_Plugins_Moravia_ProductionView');
        /* @var $productionView erp_Plugins_Moravia_ProductionView */
        return $view==$productionView->getName();
    }
    
    /***
     * Add filter to the entity
     * 
     * @param ZfExtended_Models_Entity_Abstract $entity
     * @param stdClass $filter
     */
    protected function addEntityFilter(ZfExtended_Models_Entity_Abstract $entity,stdClass $filter){
        //no filter set for the entity, init the filter
        if($entity->getFilter()==null){
            $tmpFilter=ZfExtended_Factory::get('ZfExtended_Models_Filter_ExtJs',[
                $entity
            ]);
            //set the model filter from the frontend filter
            $entity->filterAndSort($tmpFilter);
        }
        
        /* @var $tmpFilter ZfExtended_Models_Filter_ExtJs */
        $entity->getFilter()->addFilter($filter);
    }
}
