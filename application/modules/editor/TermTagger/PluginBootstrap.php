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
/**
 * Initial Class of Plugin "TermTagger"
 */
class editor_TermTagger_PluginBootstrap {
    
    /**
     * Zend_EventManager
     */
    protected $events = false;
    
    
    public function __construct()
    {
        $config = Zend_Registry::get('config');
        if (empty($config->runtimeOptions->termTagger->url->default->toArray()))
        {
            error_log("Plugin TermTagger initialized but no Zf_configuration termTagger.url.default is defined.");
            return false;
        }
        
        $this->events = Zend_EventManager_StaticEventManager::getInstance();
        $this->events->attach('Editor_IndexController', 'afterIndexAction', array($this, 'handleAfterIndex'));
        $this->events->attach('editor_Models_Segment', 'beforeSave', array($this, 'handleBeforeSegmentSave'));
        //$this->events->attach('???', 'afterTaskOpen', array($this, 'handleAfterTaskOpen'));
        
        //error_log("Plugin TermTagger initialized.");
    }
    
    /**
     * handler for event: Editor_IndexController#afterIndexAction
     * must be public for useing as callback-function in class-constructor event-binder
     */
    public function handleAfterIndex() // 
    {
        error_log("function called: ".get_class($this)."->".__FUNCTION__);
    } 
    
    /**
     * handler for event: Editor_Models_Segment#beforeSave
     * must be public for useing as callback-function in class-constructor event-binder
     */
    public function handleBeforeSegmentSave() // 
    {
        error_log("function called: ".get_class($this)."->".__FUNCTION__);
    } 
    
    /**
     * handler for event: ??#??
     * must be public for useing as callback-function in class-constructor event-binder
     */
    public function handleAfterTaskOpen() // 
    {
        error_log("function called: ".get_class($this)."->".__FUNCTION__);
    } 
}
