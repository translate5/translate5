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
 * Foldertree Helper Klasse zum Verschieben von Nodes im Tree
 */
class editor_Models_Foldertree_Mover {
  /**
   * @var editor_Models_Foldertree
   */
  protected $actualTree;
  
  /**
   * object Tree of loaded Foldertree
   * @var array
   */
  protected $objectTree;
  
  /**
   * maps the id to the Object, for faster processing
   * @var array
   */
  protected $idMap;
  
  /**
   * stores the index of a item in the parent children array 
   * @var array
   */
  protected $nodeIndex = array();
  
  /**
   * needs the actual Foldertree to move the Files in
   * @param editor_Models_Foldertree $actualTree
   */
  public function __construct(editor_Models_Foldertree $actualTree){
    $this->actualTree = $actualTree;
    $this->createObjectTreeAndMap();
  }
  
  /**
   * creates $this->idMap and $this->objectMap out of the string representation $this->actualTree->tree
   */
  protected function createObjectTreeAndMap() {
    $this->objectTree = $this->actualTree->getTree();
    $this->nodeVisitor($this->objectTree);
  }
  
  /**
   * visits every child in the tree, and adds the data to $this->idMap
   * @param array $children
   */
  protected function nodeVisitor(array $children) {
    foreach($children as $index => $child){
      $this->idMap[$child->id] = $child;
      $this->nodeIndex[$child->id] = $index;
      if(!empty($child->children)){
        $this->nodeVisitor($child->children);
      }
    }
  }
  
  /**
   * returns the File Node with the given ID
   * @param int $id
   * @return stdClass
   */
  public function getById($id) {
    return $this->idMap[$id];
  }
  
  /**
   * moves the File/Directory with the given id to the given parentid and index
   * @param int $id
   * @param int $parentId
   * @param int $index
   */
  public function moveNode($id, $parentId, $index) {
    $this->removeFromOldParent($id);
    $this->insertIntoNewParent($id, $parentId, $index);
    $this->actualTree->setTree($this->objectTree);
    $this->actualTree->save();
  }
  
  protected function removeFromOldParent($id) {
    $oldIndex = $this->nodeIndex[$id];
    $oldParentid = $this->idMap[$id]->parentId;
    if($oldParentid == 0){
      unset($this->objectTree[$oldIndex]);
      //das folgende muss sein, da ansonsten json_encode ein Object anstatt einem Array draus macht
      $this->objectTree = array_values($this->objectTree);
    } else {      
      unset($this->idMap[$oldParentid]->children[$oldIndex]);
      //das folgende muss sein, da ansonsten json_encode ein Object anstatt einem Array draus macht
      $this->idMap[$oldParentid]->children = array_values($this->idMap[$oldParentid]->children);
    }
  }
  
  protected function insertIntoNewParent($id, $parentId, $index) {
    $newchildren = array();
    $i = 0;
    if($parentId == 0){
      $children = $this->objectTree;
    }
    else {
      $children = $this->idMap[$parentId]->children;
    }
    foreach($children as $child){
      if($index == $i++){
        $newchildren[] = $this->idMap[$id];
      }
      $newchildren[] = $child;
    }
    //Am Ende einfügen
    if($index >= $i){
      $newchildren[] = $this->idMap[$id];
    }
    if($parentId == 0){
      $this->objectTree = $newchildren;
    } else { 
      $this->idMap[$parentId]->children = $newchildren;
    }
    $this->idMap[$id]->parentId = $parentId;
  }
}