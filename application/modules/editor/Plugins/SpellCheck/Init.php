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
 * Initial Class of Plugin "SpellCheck"
 * Hint: class must be named NOT Bootstrap, otherwise we will get a strange Zend Error
 */
class editor_Plugins_SpellCheck_Init extends ZfExtended_Plugin_Abstract {
    protected static $description = 'Provides the languagetool spell-checker.';
    
    /**
     * @var array
     */
    protected $frontendControllers = array(
        'pluginSpellCheck' => 'Editor.plugins.SpellCheck.controller.Editor'
    );
    
    protected $localePath = 'locales';
    
    public function getFrontendControllers() {
        return $this->getFrontendControllersFromAcl();
    }
    
    public function init() {
        $this->initEvents();
        $this->addController('SpellCheckQueryController');
        $this->initRoutes();
        editor_Segment_Quality_Manager::registerProvider('editor_Plugins_SpellCheck_QualityProvider');
    }
    
    protected function initEvents() {
        $this->eventManager->attach('Editor_IndexController', 'afterIndexAction', array($this, 'injectFrontendConfig'));
        $this->eventManager->attach('Editor_IndexController', 'afterLocalizedjsstringsAction', array($this, 'initJsTranslations'));

        // Adds needed data to the Segment-Editor
        $this->eventManager->attach('Editor_SegmentController', 'afterIndexAction', [$this, 'handleAfterSegmentIndex']);
        $this->eventManager->attach('Editor_SegmentController', 'afterPutAction',   [$this, 'handleAfterSegmentPut']);

        // Checks spell checkers availability.
        $this->eventManager->attach('ZfExtended_Resource_GarbageCollector', 'cleanUp', array($this, 'handleSpellCheckerCheck'));
    }

    /**
     * Is called periodically to check the LanguageTool instances
     */
    public function handleSpellCheckerCheck() {

        // Get spellchecker state
        $status = $this->spellcheckerState();
        $serverList = [];
        $offline = [];

        // Foreach spellchecker
        foreach ($status->running as $url => $stat) {

            // Get text line
            $serverList []= "\n" . $url . ': ' . ($stat ? 'ONLINE': 'OFFLINE!');

            // If offline - append to separate array
            if (!$stat) {
                $offline[] = $url;
            }
        }

        // Save offline instances list to memcache
        (new editor_Plugins_SpellCheck_Configuration)->saveDownListToMemCache($offline);

        // If not all spellcheckers available
        if (!$status->runningAll) {

            // Log error
            Zend_Registry::get('logger')
                ->cloneMe('editor.spellcheck')
                ->error('E1417', 'SpellCheck DOWN: one or more configured LanguageTool instances are not available: {serverList}', $_ = [
                'serverList' => join('; ', $serverList),
                'serverStatus' => $status,
            ]);
        }
    }

    /**
     * Checks if the configured spellcheckers are available and returns the result as stdClass
     *
     * @return stdClass
     */
    private function spellcheckerState() {

        //
        $spellchecker = new stdClass();

        // Get SpellCheck-plugin's LanguageTool-adapter
        $scAdapter = ZfExtended_Factory::get('editor_Plugins_SpellCheck_Adapter_LanguageTool_Adapter');

        /* @var $scAdapter editor_Plugins_SpellCheck_Adapter_LanguageTool_Adapter */
        $spellchecker->configured = $scAdapter->getConfiguredUrls();

        // Get all unique LanguageTool unique url endpoints
        $allUrls = array_unique(call_user_func_array('array_merge', array_values((array) $spellchecker->configured)));

        // TODO next 18 lines of code are copypasted from term tagger Bootstrap.php
        // Prepare variables
        $running = []; $version = []; $spellchecker->runningAll = true;

        // Foreach unique endpoint
        foreach($allUrls as $url) {

            // Check whether it's running
            $running[$url] = $scAdapter->testServerUrl($url, $version[$url]);

            // Update $this->runningAll flag
            $spellchecker->runningAll = $running[$url] && $spellchecker->runningAll;
        }

        // Assign variables to object to be returned
        $spellchecker->running = $running;
        $spellchecker->version = $version;

        // Return object containing the info
        return $spellchecker;
    }

    public function injectFrontendConfig(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        /* @var $view Zend_View_Interface */
        //To set config values:
        $view->Php2JsVars()->set('plugins.SpellCheck.cssMap', editor_Plugins_SpellCheck_Check::$css);
        $view->headLink()->appendStylesheet($this->getResourcePath('plugin.css'));
        $view->Php2JsVars()->get('editor')->htmleditorCss[] = $this->getResourcePath('htmleditor.css');
        $view->headLink()->appendStylesheet($this->getResourcePath('htmleditor.css'));
    }
    
    public function initJsTranslations(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $view->pluginLocale()->add($this, 'views/localizedjsstrings.phtml');
    }
    
    /**
     * defines all URL routes of this plug-in
     */
    protected function initRoutes() {
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        $r = $f->getRouter();
        
        $restRoute = new Zend_Rest_Route($f, array(), array(
                'editor' => array('plugins_spellcheck_spellcheckquery',
                ),
        ));
        $r->addRoute('plugins_spellcheck_restdefault', $restRoute);
        
        $languagesRoute = new ZfExtended_Controller_RestLikeRoute(
                'editor/plugins_spellcheck_spellcheckquery/languages',
                array(
                        'module' => 'editor',
                        'controller' => 'plugins_spellcheck_spellcheckquery',
                        'action' => 'languages'
                ));
        $r->addRoute('plugins_spellcheck_languages', $languagesRoute);
        
        
        $matchesRoute = new ZfExtended_Controller_RestLikeRoute(
                'editor/plugins_spellcheck_spellcheckquery/matches',
                array(
                        'module' => 'editor',
                        'controller' => 'plugins_spellcheck_spellcheckquery',
                        'action' => 'matches'
                ));
        $r->addRoute('plugins_spellcheck_matches', $matchesRoute);
    }

    /**
     * Append spellcheck data for each segment within segments store data
     *
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterSegmentIndex(Zend_EventManager_Event $event) {

        // Get array of segment ids
        $view = $event->getParam('view');
        $segmentIds = array_column($view->rows, 'id');

        // Get [segmentId => spellCheckData] pairs
        $segmentSpellCheckDataById = ZfExtended_Factory
            ::get('editor_Models_SegmentQuality')
            ->getSpellCheckData($segmentIds);

        // Apply to response
        foreach ($view->rows as &$row) {
            $row['spellCheck'] = $segmentSpellCheckDataById[$row['id']];
        }
    }

    /**
     * Append spellcheck data for updated segment
     *
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterSegmentPut(Zend_EventManager_Event $event) {

        $view = $event->getParam('view');
        // Get [segmentId => spellCheckData] pairs
        $segmentSpellCheckDataByIds = ZfExtended_Factory
            ::get('editor_Models_SegmentQuality')
            ->getSpellCheckData([$view->rows['id']]);

        // Apply spellCheck prop
        $view->rows['spellCheck'] = $segmentSpellCheckDataByIds[$view->rows['id']];
    }
}
