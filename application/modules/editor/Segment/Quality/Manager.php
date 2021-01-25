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
 *
 */
final class editor_Segment_Quality_Manager {
    
    private static $_provider = [];
    /**
     * @var editor_Segment_Quality_Manager
     */
    private static $_instance = null;
    
    private static $_locked = false;
    
    /**
     * Adds a Provider to the Quality manager
     * @param string $className
     * @throws ZfExtended_Exception
     */
    public static function registerProvider($className){
        if(self::$_locked){
            throw new ZfExtended_Exception('Adding Quality Provider after app bootstrapping is not allowed.');
        }
        self::$_provider[] = $className;
    }
    /**
     *
     * @return editor_Segment_Quality_Manager
     */
    public static function instance(){
        if(self::$_instance == null){
            self::$_instance = new editor_Segment_Quality_Manager();
            self::$_locked = true;
        }
        return self::$_instance;
    }
    /**
     * 
     * @var editor_Segment_Quality_Provider[]
     */
    private $registry;
    /**
     * To prevent any changes during import
     * @var boolean
     */
    private $locked = false;
    /**
     * The constructor instantiates all providers and locks the registry
     * @throws ZfExtended_Exception
     */
    private function __construct(){
        $this->registry = [];
        foreach(self::$_provider as $providerClass){
            try {
                $provider = new $providerClass();
                /* @var $provider editor_Segment_Quality_Provider */
                $this->registry[$provider->getType()] = $provider;
            } catch (Exception $e) {
                throw new ZfExtended_Exception('Quality Provider '.$providerClass.' does not exist');
            }
        }
    }
    /**
     * 
     * @param string $type
     * @return boolean
     */
    public function hasProvider(string $type){
        return array_key_exists($type, $this->registry);
    }
    /**
     * 
     * @param string $type
     * @return editor_Segment_Quality_Provider|NULL
     */
    public function getProvider(string $type){
        if(array_key_exists($type, $this->registry)){
            return $this->registry[$type];
        }
        return NULL;
    }
    /**
     * Prepares the import: adds all Import Workers that exist for Quality tags
     * @param editor_Models_Task $task
     * @param int $parentWorkerId
     */
    public function prepareImport(editor_Models_Task $task, int $parentWorkerId){
        
        // TODO REMOVE
        error_log("editor_Segment_Quality_Manager::prepareImport");
        
        foreach($this->registry as $type => $provider){
            /* @var $provider editor_Segment_Quality_Provider */
            if($provider->hasImportWorker()){
                $provider->addImportWorker($task, $parentWorkerId);
            }
        }
    }
    /**
     * Finishes the import: processes all non-worker providers & saves the processed tags-model back to the segments
     * @param editor_Models_Task $task
     */
    public function finishImport(editor_Models_Task $task){
        
        // TODO REMOVE
        error_log("editor_Segment_Quality_Manager::finishImport");
        
        $db = ZfExtended_Factory::get('editor_Models_Db_Segments');
        /* @var $db editor_Models_Db_Segments */
        $db->getAdapter()->beginTransaction();
        $sql = $db->select()
            ->from($db, ['id'])
            ->where('taskGuid = ?', $task->getTaskGuid())
            ->order('id')
            ->forUpdate(true);
        $segmentIds = $db->fetchAll($sql)->toArray();
        $segmentIds = array_column($segmentIds, 'id');
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        foreach($segmentIds as $segmentId){
            $segment->load($segmentId);
            $tags = editor_Segment_Tags::fromSegment($task, false, $segment);
            // process all quality providers that do not have an import worker
            foreach($this->registry as $type => $provider){
                /* @var $provider editor_Segment_Quality_Provider */
                if(!$provider->hasImportWorker()){
                    $tags = $provider->processSegment($task, $tags, true);
                }
            }
            // flush the segment tags content back to the segment
            $tags->flush();
        }
        $db->getAdapter()->commit();
    }
    /**
     * @param editor_Models_Segment $segment
     * @param editor_Models_Task $task
     */
    public function processEditing(editor_Models_Segment $segment, editor_Models_Task $task){
        
        // TODO REMOVE
        error_log("editor_Segment_Quality_Manager::processEditing");
        
        $tags = editor_Segment_Tags::fromSegment($task, true, $segment, false);
        foreach($this->registry as $type => $provider){
            /* @var $provider editor_Segment_Quality_Provider */
            $tags = $provider->processSegment($task, $tags, false);
        }
        $tags->flush();
    }
    /**
     * The central API to identify the needed Tag class by classnames and attributes
     * @param string $type
     * @param string $nodeName
     * @param string[] $classNames
     * @param string[] $attributes
     * @param int $startIndex
     * @param int $endIndex
     * @return editor_Segment_InternalTag | NULL
     */
    public function evaluateInternalTag(string $type, string $nodeName, array $classNames, array $attributes, int $startIndex, int $endIndex){
        if(!empty($type) && array_key_exists($type, $this->registry)){
            if($this->registry[$type]->isInternalTag($type, $nodeName, $classNames, $attributes)){
                return $this->registry[$type]->createInternalTag($startIndex, $endIndex, $nodeName);
            }            
            return NULL;
        }
        foreach($this->registry as $type => $provider){
            /* @var $provider editor_Segment_Quality_Provider */
            if($provider->isInternalTag($type, $nodeName, $classNames, $attributes)){
                return $provider->createInternalTag($startIndex, $endIndex, $nodeName);
            }
        }
        return NULL;
    }
    
    /*
    public function createStatisticsData(){
       
    }
    
    public function createGridFilterData(){
        
    }
    */
}
