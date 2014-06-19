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