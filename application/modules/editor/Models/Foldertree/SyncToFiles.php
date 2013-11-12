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
 * Class provides sync between Foldertree and File Entities
 */
class editor_Models_Foldertree_SyncToFiles {
  /**
   * object Tree of loaded Foldertree
   * @var array
   */
  protected $objectTree;

  /**
   * actual Foldertree
   * @var editor_Models_Foldertree
   */
  protected $tree;

  /**
   * File Instanz to set and save data in DB
   * @var editor_Models_File
   */
  protected $file;

  /**
   * internal counter to set the order from
   * @var integer
   */
  protected $orderCounter;

  /**
   * @var mixed
   *
   */
  protected $_sourceLang = NULL;

  /**
   * @var mixed
   *
   */
  protected $_targetLang = NULL;

  /**
   * @var mixed
   *
   */
  protected $_relaisLang = NULL;

  /**
   * Liste mit Directory IDs welche aus der Files Tabelle wieder entfernt werden müssen
   * @var array
   */
  protected $directoryNodeIdsToDelete = array();

  /**
   * needs the actual Foldertree to move the Files in
   * @param editor_Models_Foldertree $actualTree
   * @param mixed $sourceLang
   * @param mixed $targetLang
   * @param mixed $relaisLang
   */
  public function __construct(editor_Models_Foldertree $actualTree,$sourceLang = NULL, $targetLang = NULL, $relaisLang = null){
    $this->tree = $actualTree;
    $this->objectTree = $this->tree->getTree();
    $this->file = ZfExtended_Factory::get('editor_Models_File');
    $this->_sourceLang = $sourceLang;
    $this->_targetLang = $targetLang;
    $this->_relaisLang = $relaisLang;
  }

  /**
   * starts the sync Process from Foldertree to Files
   */
  public function recursiveSync() {
    $this->directoryNodeIdsToDelete = array();
    $this->orderCounter = 0;
    $this->nodeVisitor($this->objectTree);
    $this->tree->setTree($this->objectTree);
    $this->tree->save();
    if(count($this->directoryNodeIdsToDelete)>0){
        $this->file->cleanupDirectoryIncrements($this->directoryNodeIdsToDelete);
    }
  }

    /**
     * visits every child in the tree, and syncs the Tree Data to a File Entity
     * @param array $children
     */
    protected function nodeVisitor(array $children, $parentId = 0) {
        foreach ($children as $index => $child) {
            $child->parentId = $parentId;
            if (isset($child->isFile) && $child->isFile) {
                $this->syncNodeToFile($child);
                continue;
            }
            $this->incrementFileIdForDirectory($child);
            if (!empty($child->children)) {
                $this->nodeVisitor($child->children, $child->id);
            }
        }
    }

  /**
   * syncs one Tree Node to the File Entity
   * @param stdClass $node
   */
  protected function syncNodeToFile(stdClass $node){
    if($node->id > 0){
      $this->file->load($node->id);
    }
    else {
      $this->file->init();
      $this->file->setTaskGuid($this->tree->getTaskGuid());
    }

    $this->file->setFileOrder($this->orderCounter++);
    $this->file->setFileName($node->filename);
    if(!is_null($this->_sourceLang)){
        $this->file->setSourceLang($this->_sourceLang);
    }
    if(!is_null($this->_targetLang)){
        $this->file->setTargetLang($this->_targetLang);
    }
    if(!is_null($this->_relaisLang)){
        $this->file->setRelaisLang($this->_relaisLang);
    }
    $this->file->save();

    $node->id = $this->file->getId();
  }

  /**
   * Verzeichnisse werden nur temporär in der File Tabelle gespeichert um eine ID zu bekommen
   * @param stdClass $node
   */
  protected function incrementFileIdForDirectory(stdClass $node){
    if($node->id > 0){
        //id is already set, nothing to do
        return;
    }
    $this->file->init();
    $this->file->setTaskGuid($this->tree->getTaskGuid());
    $this->file->setSourceLang(1);
    $this->file->setTargetLang(1);
    $this->file->setRelaisLang(1);
    $this->file->setFileOrder(1);
    $this->file->save();

    $node->id = $this->file->getId();
    $this->directoryNodeIdsToDelete[] = $node->id;
  }
}