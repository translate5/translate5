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

/** #@+
 * @author Marc Mittag
 * @package translate5
 * @version 0.7
 *
 */

/**
 * Stellt Methoden bereit, die translate5 grundsätzlich als Stand Alone-Anwendung verfügbar machen
 */
class Erp_IndexController extends ZfExtended_Controllers_Action {
    
    public function indexAction(){
        $this->_helper->layout->disableLayout();
        
        $config = Zend_Registry::get('config');
        $this->view->buildType = $config->runtimeOptions->buildType;
        $this->view->buildVersion = $this->getAppVersion();
        $this->view->publicModulePath = APPLICATION_RUNDIR.'/modules/'.Zend_Registry::get('module');
        $this->setPhp2JsVars();
    }

    /**
     * To prevent LFI attacks load existing Plugin JS filenames and use them as whitelist
     * Currently this Method is not reusable, its only for JS.
     */
    public function pluginpublicAction()
    {
        $types = array(
            'js' => 'text/javascript',
            'css' => 'text/css',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg',
            'woff' => 'application/woff',
            'woff2' => 'application/woff2',
            'ttf' => 'application/ttf',
            'eot' => 'application/eot',
            'mp3' => 'audio/mp3',
            'mp4' => 'video/mp4',
            'html' => 'text/html'
        );
        $slash = '/';
        // get requested file from router
        $requestedType = $this->getParam(1);
        $requestedFile = $this->getParam(2);
        $requestedFileParts = explode($slash, $requestedFile);
        $extension = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));

        //pluginname is alpha characters only so check this for security reasons
        //ucfirst is needed, since in JS packages start per convention with lowercase, Plugins in PHP with uppercase!
        $plugin = ucfirst(preg_replace('/[^a-zA-Z0-9]/', '', array_shift($requestedFileParts)));

        // DEBUG
        // error_log("INDEXCONTROLLER: pluginpublicAction: plugin: ".$plugin." / requestedType: ".$requestedType." / requestedFile: ".$requestedFile." / extension: ".$extension);

        if (empty($plugin)) {
            throw new ZfExtended_NotFoundException();
        }

        //get the plugin instance to the key
        $pm = Zend_Registry::get('PluginManager');
        /* @var $pm ZfExtended_Plugin_Manager */
        $plugin = $pm->get($plugin);
        /* @var $plugin ZfExtended_Plugin_Abstract */
        if (empty($plugin)) {
            throw new ZfExtended_NotFoundException();
        }

        // check if requested "fileType" is allowed
        if (!$plugin->isPublicSubFolder($requestedType)) {
            throw new ZfExtended_NotFoundException();
        }

        $publicFile = $plugin->getPublicFile($requestedType, $requestedFileParts);
        if (empty($publicFile) || !$publicFile->isFile()) {
            throw new ZfExtended_NotFoundException();
        }
        if (array_key_exists($extension, $types)) {
            header('Content-Type: ' . $types[$extension]);
        } else {
            // TODO FIXME: it seems by default the content-type text/html is set by apache instead of no content-type
            // this leads to problems with files without extensions as is often the case with wget downloaded websites
            header('Content-Type: ');
        }
        //FIXME add version URL suffix to plugin.css inclusion
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', $publicFile->getMTime()));
        //with etags we would have to use the values of $_SERVER['HTTP_IF_NONE_MATCH'] too!
        //makes sense to do so!
        //header('ETag: '.md5(of file content));

        header_remove('Cache-Control');
        header_remove('Expires');
        header_remove('Pragma');
        header_remove('X-Powered-By');

        /*
        header('Pragma: public');
        header('Cache-Control: max-age=86400');
        header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
        header('Content-Type: image/png');
        */


        readfile($publicFile);
        //FIXME: Optimierung bei den Plugin Assets: public Dateien die durch die Plugins geroutet werden, sollten chachebar sein und B keine Plugin Inits triggern. Geht letzteres überhaupt wg. VisualReview welches die Dateien ebenfalls hier durchschiebt?
        exit;
    }
    
    protected function setPhp2JsVars() {
        $config = Zend_Registry::get('config');
        $vars = $this->view->Php2JsVars();
        
        $restPath = APPLICATION_RUNDIR.'/'.Zend_Registry::get('module').'/';
        $vars->set('restpath', $restPath);
        
        $vars->set('app.buildType', $this->view->buildType);
        $vars->set('app.version', $this->view->buildVersion);
        $vars->set('app.baseCurrency', '€');
        
        //base currency presented with text value
        $vars->set('app.baseCurrencyText', 'EUR');

        $keyaccount = ZfExtended_Factory::get('erp_Models_Keyaccount');
        /* @var $keyaccount erp_Models_Keyaccount */
        $vars->set('customers.keyaccounts', $keyaccount->loadAll());
        $vars->set('customers.taxsets', $config->runtimeOptions->taxsets->toArray());
        
        $customersModel=ZfExtended_Factory::get('erp_Models_Customer');
        /* @var $customersModel erp_Models_Customer */
        $vars->set('customers.customers', $customersModel->loadAll());
        
        $order = ZfExtended_Factory::get('erp_Models_Order');
        /* @var $order erp_Models_Order */
        $states = $order->getStatesList();
        
        $states['viewfilters'] = $config->runtimeOptions->viewfilters->toArray();
        $vars->set('project', $states);

        $this->orderAndSetLanguages('sourceLanguages');
        $this->orderAndSetLanguages('targetLanguages');
        
        $vars->set('transmissionPath',$config->runtimeOptions->po->transferMethod->toArray());
        
        $vars->set('moduleFolder', $this->view->publicModulePath.'/');
        $vars->set('appFolder', $this->view->publicModulePath.'/js/app');
        $vars->set('pluginFolder', APPLICATION_RUNDIR.'/'.Zend_Registry::get('module').'/plugins/js');

        $po = ZfExtended_Factory::get('erp_Models_PurchaseOrder');
        $vars->set('purchaseOrder', $po->getStatesList());

        $userSession = new Zend_Session_Namespace('user');
        $userSession->data->passwd = '********';
        $vars->set('app.user', $userSession->data);
        
        $vars->set('messageBox.delayFactor', $config->runtimeOptions->messageBox->delayFactor);
        $vars->set('loginUrl', APPLICATION_RUNDIR.$config->runtimeOptions->loginUrl);
        $vars->set('editorUrl', APPLICATION_RUNDIR.'/editor');
        $vars->set('useradminUrl', APPLICATION_RUNDIR.'/editor#initialView/adminUserGrid');
        
        $userRoles = $userSession->data->roles;
        
        $acl = ZfExtended_Acl::getInstance();
        $registeredViews=ZfExtended_Factory::get('erp_CustomView_Manager');
        /* @var $registeredViews erp_CustomView_Manager */
        
        $views=$registeredViews->getAll();
        
        $final=[];
        $finalLabeL=[];
        foreach($views as $view=>$class) {
            if($acl->isInAllowedRoles($userRoles, "customerview", $view)){
                $final[]=$view;
                $viewInstance=ZfExtended_Factory::get($class);
                $finalLabeL[$view]=$viewInstance->getLabel();
            }
        }
        //which views are available for the user
        $vars->set('viewslist',$final);
        $vars->set('viewslistLabels',$finalLabeL);
        
        $this->setJsAppData();
    }

    /**
     * Set the several data needed vor authentication / user handling in frontend
     */
    protected function setJsAppData() {
        $userSession = new Zend_Session_Namespace('user');
        $userRoles = $userSession->data->roles;
        
        $php2js = $this->view->Php2JsVars();
        $php2js->set('app.controllers', $this->getFrontendControllers());
        
        $acl = ZfExtended_Acl::getInstance();
        $php2js->set('app.userRights', $acl->getFrontendRights($userRoles));
    }

    /**
     * reorders language list as given in config and sets in the GUI
     * @param string $languageType
     */
    protected function orderAndSetLanguages($languageType) {
        $vars = $this->view->Php2JsVars();
        $config = Zend_Registry::get('config')->runtimeOptions;
        
        $langModel = ZfExtended_Factory::get('erp_Models_Languages');
        /* @var $langModel erp_Models_Languages */
        $langs = $langModel->getAvailableLanguages();
        
        if(isset($config->{$languageType})){
            $langs = $langModel->orderLanguages($langs, $config->{$languageType}->toArray());
        }
        $vars->set($languageType, $langs);
    }


    /**
     * returns a list with used JS frontend controllers
     * @return array
     */
    protected function getFrontendControllers() {
        $controllers = array('ServerException','Global');
        $pm = Zend_Registry::get('PluginManager');
        /* @var $pm ZfExtended_Plugin_Manager */
        $pluginFrontendControllers = $pm->getActiveFrontendControllers();
        if(!empty($pluginFrontendControllers)) {
            $controllers = array_merge($controllers, $pluginFrontendControllers);
        }
        return $controllers;
    }
}