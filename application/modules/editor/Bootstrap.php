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
 * @package editor
 * @version 1.0
 *
 */

/**
 * Klasse zur Portalinitialisierung
 *
 * - In initApplication können Dinge zur Portalinitialisierung aufgerufen werden
 * - Alles für das Portal nötige ist jedoch in Resource-Plugins ausgelagert und
 *   wird über die application.ini definiert und dann über Zend_Application
 *   automatisch initialisert
 *
 */
class Editor_Bootstrap extends Zend_Application_Module_Bootstrap
{
    protected $front;

    public function __construct($application) {
        require_once APPLICATION_PATH . '/../library/querypath/src/qp.php';
        parent::__construct($application);
        
        //Binding the worker clean up to the after import event, since import
        // is currently the main use case for workers
        $eventManager = Zend_EventManager_StaticEventManager::getInstance();
        /* @var $eventManager Zend_EventManager_StaticEventManager */
        $eventManager->attach('editor_Models_Import', 'afterImport', function(){
            $worker = ZfExtended_Factory::get('ZfExtended_Worker_GarbageCleaner');
            /* @var $worker ZfExtended_Worker_GarbageCleaner */
            $worker->init();
            $worker->queue();
        }, 0);
        
    }
    
    public function _initController()
    {
        $this->front = Zend_Controller_Front::getInstance();
    }
    
    
    public function _initREST()
    {
        $this->front->setRequest(new REST_Controller_Request_Http);

        // register the RestHandler plugin
        $this->front->registerPlugin(new ZfExtended_Controllers_Plugins_RegisterRestControllerPluginRestHandler());

        // add REST contextSwitch helper
        $contextSwitch = new REST_Controller_Action_Helper_ContextSwitch();
        Zend_Controller_Action_HelperBroker::addHelper($contextSwitch);

        // add restContexts helper
        $restContexts = new REST_Controller_Action_Helper_RestContexts();
        Zend_Controller_Action_HelperBroker::addHelper($restContexts);
    }
    
    
    public function _initRestRoutes()
    {
        $restRoute = new Zend_Rest_Route($this->front, array(), array(
            'editor' => array(  'file', 'segment', 'alikesegment', 'referencefile', 'qmstatistics', 'comment',
                                'task', 'user', 'taskuserassoc', 'segmentfield', 'workflowuserpref', 'worker', 'taskmeta'),
        ));
        $this->front->getRouter()->addRoute('editorRestDefault', $restRoute);

        $filemapRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/segment/filemap/*',
            array(
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'filemap'
            ));
        $this->front->getRouter()->addRoute('editorFilemap', $filemapRoute);

        $authUserRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/user/authenticated/*',
            array(
                'module' => 'editor',
                'controller' => 'user',
                'action' => 'authenticated'
            ));
        $this->front->getRouter()->addRoute('editorAuthUser', $authUserRoute);

        $termsRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/segment/terms/*',
            array(
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'terms'
            ));
        $this->front->getRouter()->addRoute('editorTerms', $termsRoute);

        $exportRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/task/export/*',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'export'
            ));
        $this->front->getRouter()->addRoute('editorExport', $exportRoute);
        
        $taskStat = new ZfExtended_Controller_RestLikeRoute(
            'editor/task/statistics/*',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'statistics'
            ));
        $this->front->getRouter()->addRoute('editorTaskStat', $taskStat);
        
        $workerRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/worker/queue/*',
            array(
                'module' => 'editor',
                'controller' => 'worker',
                'action' => 'queue'
            ));
        $this->front->getRouter()->addRoute('editorQueue', $workerRoute);
    }
    
    
    public function _initOtherRoutes()
    {
        $localizedJsRoute = new Zend_Controller_Router_Route(
            'editor/js/app-localized.js',
            array(
                'module' => 'editor',
                'controller' => 'index',
                'action' => 'localizedjsstrings'
            ));
        $this->front->getRouter()->addRoute('editorLocalizedJs', $localizedJsRoute);
        
        $pluginJs = new Zend_Controller_Router_Route_Regex(
            'editor/plugins/js/([a-z0-9_\-./]*)',
            array(
                'module' => 'editor',
                'controller' => 'index',
                'action' => 'pluginjs'
            ));
        $this->front->getRouter()->addRoute('editorPluginJs', $pluginJs);
    }
}