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

    //fire event after the file is saved
    $eventManager = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
    /* @var $eventManager ZfExtended_EventManager */
    $eventManager->trigger('afterImportfileSave', $this, array(
            'node' => $node,
            'file'=>$this->file
    ));
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