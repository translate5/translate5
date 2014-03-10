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

/**
 * Bundles all import-Methods around the qm subsegment flags
 */
 class editor_Models_Import_QmSubsegments implements editor_Models_Import_IMetaDataImporter {
    /**
     * Flag containing info if qm subsegment import uses a task specific xml file
     * @var boolean
     */
    protected $hasTaskSpecific = false;
    
    /**
     * imports the configured qmFlagXmlFile, store it in the internal JSON tree in qmSubsegmentFlags 
     * 
     * @param editor_Models_Task $task
     * @param string $importPath
     * @throws Zend_Exception
     */
    public function importFromXml(editor_Models_Task $task,string $importPath) {
        $config = Zend_Registry::get('config');
        if(! $config->runtimeOptions->editor->enableQmSubSegments) {
            return;
        }
        //take a task-specific qmFlagXmlFile
        $qmFlagXmlFile = $importPath.DIRECTORY_SEPARATOR.
                $config->runtimeOptions->editor->qmFlagXmlFileName;
        if(!file_exists($qmFlagXmlFile) && ! is_readable($qmFlagXmlFile)) {
            //if task-specific file does not exist, take the standard one
            $qmFlagXmlFile = APPLICATION_PATH.DIRECTORY_SEPARATOR.'..'.
                DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.
                $config->runtimeOptions->editor->qmFlagXmlFileDir.
                DIRECTORY_SEPARATOR.
                $config->runtimeOptions->editor->qmFlagXmlFileName;
            $this->hasTaskSpecific = true;
        }
        if(!file_exists($qmFlagXmlFile) && ! is_readable($qmFlagXmlFile)) {
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
     * @param unknown_type $parent
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
    
    /**
     * has to exist due to editor_Models_Import_MetaData-logic
     */
    public function cleanup() {
        return;
    }
}