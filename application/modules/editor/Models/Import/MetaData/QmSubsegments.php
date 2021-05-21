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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Bundles all import-Methods around the qm subsegment flags
 */
 class editor_Models_Import_MetaData_QmSubsegments implements editor_Models_Import_MetaData_IMetaDataImporter {
    /**
     * Flag containing info if qm subsegment import uses a task specific xml file
     * @var boolean
     */
    protected $hasTaskSpecific = true;
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_MetaData_IMetaDataImporter::import()
     */
    public function import(editor_Models_Task $task, editor_Models_Import_MetaData $meta) {
        $this->importFromXml($task, $meta->getImportPath());
    }
    
    /**
     * imports the configured qmFlagXmlFile, store it in the internal JSON tree in qmSubsegmentFlags 
     * 
     * @param editor_Models_Task $task
     * @param string $importPath
     * @throws Zend_Exception
     */
    public function importFromXml(editor_Models_Task $task,string $importPath) {
        $config = $task->getConfig();
        if(! $config->runtimeOptions->autoQA->enableMqmTags) {
            return;
        }
        //take a task-specific qmFlagXmlFile
        $qmFlagXmlFile = $importPath.DIRECTORY_SEPARATOR.
                $config->runtimeOptions->editor->qmFlagXmlFileName;
        if(!file_exists($qmFlagXmlFile) || ! is_readable($qmFlagXmlFile)) {
            //if task-specific file does not exist, take the standard one
            $appDir = APPLICATION_PATH.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
            $publicPartialPath = 'public'.DIRECTORY_SEPARATOR.
                $config->runtimeOptions->editor->qmFlagXmlFileDir.
                DIRECTORY_SEPARATOR.
                $config->runtimeOptions->editor->qmFlagXmlFileName;
            $publicPath = $appDir.$publicPartialPath;
            $clientSpecificPath = $appDir.'client-specific'.DIRECTORY_SEPARATOR.$publicPartialPath;
            $qmFlagXmlFile = $publicPath;
            if(file_exists($clientSpecificPath) &&  is_readable($clientSpecificPath)) {
                $qmFlagXmlFile = $clientSpecificPath;
            }
            $this->hasTaskSpecific = false;
        }
        if(!file_exists($qmFlagXmlFile) || ! is_readable($qmFlagXmlFile)) {
            throw new Zend_Exception('qmFlagXmlFile not found or not readable! runtimeOptions.editor.qmFlagXmlFile was: "'.$qmFlagXmlFile.'"');
        }
        
        $xml = new SimpleXMLIterator($qmFlagXmlFile, 0, true);
        $this->qmFlagId = 1;
        $root = new stdClass();
        $this->iterateQmXML($root, $xml);
        $root->qmSubsegmentFlags = $root->children;
        unset($root->children);
        $root->severities = $config->runtimeOptions->editor->qmSeverity->toArray();
        
        $task->setQmSubsegmentFlags(json_encode($root));
    }

    /**
     * iterates over and manipulates the QM Sub Segments to prepare them for storage in DB
     * @param stdClass $parent
     * @param SimpleXMLIterator $nodeList
     * @throws Zend_Exception
     */
    protected function iterateQmXML($parent, SimpleXMLIterator $nodeList) {
        foreach($nodeList as $xmlNode) {
            if($xmlNode->getName() != 'issue') {
                throw new Zend_Exception('Unknown tag '.$xmlNode->getName().' in QmXML!');
            }
            $type = $xmlNode->attributes()->type;
            if(empty($type)||$type=='') {
                throw new Zend_Exception('Issue-Tag has no type-attribute set!');
            }
            $node = new stdClass();
            $node->text = (string)$type;
            $node->id = $this->qmFlagId++;
            if(!isset($parent->children)){
                $parent->children = array();
            }
            $parent->children[] = $node;
        	if($nodeList->hasChildren()) {
            	$this->iterateQmXML($node, $nodeList->getChildren());
        	}
        }
    }
    
    /**
     * returns boolean if imported qm subsegments were task specific or general
     * @return boolean
     */
    public function hasTaskSpecific() {
        return $this->hasTaskSpecific;
    }
}