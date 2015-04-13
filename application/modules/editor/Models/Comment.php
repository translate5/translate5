<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
 * Comment Entity Objekt
 */
class editor_Models_Comment extends ZfExtended_Models_Entity_Abstract {
  protected $dbInstanceClass = 'editor_Models_Db_Comments';
  protected $validatorInstanceClass = 'editor_Models_Validator_Comment';

  /**
   * renders a single comment
   * @param Zend_View $view
   * @param array $comment
   * @return string
   */
  protected function getMarkedUp(Zend_View $view, array $comment) {
      $view->comment = $comment;
      return $view->render("comment.phtml");
  }
  
  /**
   * updates the segments comments field by merging all comments to the segment, and apply HTML markup to each comment
   * @param integer $segmentId
   */
  public function updateSegment(integer $segmentId) {
      $session = new Zend_Session_Namespace();
      $comments = $this->loadBySegmentId($segmentId);
      $commentsMarkup = array();
      $view = clone Zend_Layout::getMvcInstance()->getView();
      foreach($comments as $comment) {
          $commentsMarkup[] = $this->getMarkedUp($view, $comment);
      }
      $segment = ZfExtended_Factory::get('editor_Models_Segment');
      /* @var $segment editor_Models_Segment */
      $segment->load($segmentId);
      
      $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
      /* @var $wfm editor_Workflow_Manager */
      $workflow = $wfm->getActive();
        
      if($session->taskGuid === $segment->get('taskGuid')) {
          $segment->set('comments', join("\n", $commentsMarkup));
          //@todo do this with events
          $workflow->beforeCommentedSegmentSave($segment);
          $segment->save();
      }
  }
  
  /**
   * loads all comments to the given segmentId, filter also only the session loaded taskguid
   * sorts from newest to oldest comment, does compute the isEditable flag
   * @param integer $segmentId
   * @return array
   */
  public function loadBySegmentId(integer $segmentId) {
      $session = new Zend_Session_Namespace();
      $sessionUser = new Zend_Session_Namespace('user');
      $comments = $this->loadBySegmentAndTaskPlain($segmentId, $session->taskGuid);
      $editable = true;
      //comment is editable until an other user has edited it. 
      //the following algorithm depends on correct sort (id DESC => from newest to oldest comment)
      foreach($comments as &$comment) {
          $comment['isEditable'] = ($editable && $comment['userGuid'] == $sessionUser->data->userGuid);
          $editable = $editable && $comment['isEditable'];
      }
      return $comments;
  }
  
  /**
   * Loads all comments of a segment by segmentid and taskguid, orders by creation order
   * does not provide isEditable info
   */
  public function loadBySegmentAndTaskPlain(integer $segmentId, string $taskGuid) {
      $s = $this->db->select()
      ->where('taskGuid = ?', $taskGuid)
      ->where('segmentId = ?', $segmentId)
      ->order('id DESC');
      return $this->db->getAdapter()->fetchAll($s);
  }
  
  /**
   * calculates the isEditable state of the current comment
   */
  public function isEditable() {
      $sessionUser = new Zend_Session_Namespace('user');
      //SELECT explained: 
      //if there are newer comments (id > ?) to this segment (segId=?) of another user (userGuid=?), the actual user cant edit the comment anymore
      $s = $this->db->select()
      ->where('id > ?', $this->getId())
      ->where('segmentId = ?', $this->getSegmentId())
      ->where('userGuid != ?', $sessionUser->data->userGuid);
      $res = $this->db->getAdapter()->fetchAll($s);
      return empty($res);
  }
}