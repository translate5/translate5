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
            'editor' => array('file', 'segment', 'alikesegment', 'referencefile', 'qmstatistics', 'comment', 'task', 'user', 'taskuserassoc', 'segmentfield','workflowuserpref'),
        ));
        $this->front->getRouter()->addRoute('restDefault', $restRoute);

        //FIXME this should be an ZfExtended_Controller_RestLikeRoute, changing this here, means to rework the output in the controller
        $filemapRoute = new Zend_Controller_Router_Route(
            'editor/segment/filemap/*',
            array(
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'filemap'
            ));
        $this->front->getRouter()->addRoute('filemap', $filemapRoute);

        $authUserRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/user/authenticated/*',
            array(
                'module' => 'editor',
                'controller' => 'user',
                'action' => 'authenticated'
            ));
        $this->front->getRouter()->addRoute('authUser', $authUserRoute);

        $termsRoute = new Zend_Controller_Router_Route(
            'editor/segment/terms/*',
            array(
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'terms'
            ));
        $this->front->getRouter()->addRoute('terms', $termsRoute);

        $exportRoute = new Zend_Controller_Router_Route(
            'editor/task/export/*',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'export'
            ));
        $this->front->getRouter()->addRoute('export', $exportRoute);
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
        $this->front->getRouter()->addRoute('localizedJs', $localizedJsRoute);
    }
}