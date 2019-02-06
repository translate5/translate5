<?php
/*
 START LICENSE AND COPYRIGHT
 
 This file is part of ZfExtended library
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 https://www.gnu.org/licenses/lgpl-3.0.txt
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
 https://www.gnu.org/licenses/lgpl-3.0.txt
 
 END LICENSE AND COPYRIGHT
 */

/**
 * Extended Logger for special task and workflow logging
 * sets the origin to workflow
 */
class editor_Logger_Workflow extends ZfExtended_Logger {
    /**
     * @var editor_Models_Task
     */
    protected $task; 
    
    public function __construct(editor_Models_Task $task) {
        $this->task = $task;
        //do not init logger instance here, since we pass everything to the default logger
    }
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Logger::request()
     */
    public function request(array $additionalData = []) {
        //FIXME remove the following config from DB and migrate request log table
        // $config->runtimeOptions->requestLogging
        parent::request(['task' => $this->task]);
    }
    
    /**
     * Just pass everything to the default logger, expect the here defined functions
     * {@inheritDoc}
     * @see ZfExtended_Logger::__call()
     */
    public function __call($name, $arguments) {
        $logger = Zend_Registry::get('logger');
        /* @var $logger ZfExtended_Logger */
        $origDomain = $logger->domain;
        $logger->domain = 'workflow';
        call_user_func_array([$logger, $name], $arguments);
        $logger->domain = $origDomain;
    }
}