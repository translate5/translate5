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
 * @author Angel Naydenov
 * @package editor
 * @version 1.0
 *
 */
/**
 * SegmentUserAssoc Object Instance as needed in the application
 * @method integer getId() getId()
 * @method string getSegmentId() getSegmentId()
 * @method string getUserGuid() getUserGuid()
 * @method void setId() setId(integer $id)
 * @method void setSegmentId() setSegmentId(string $segment_id)
 * @method void setUserGuid() setUserGuid(string $userGuid)
 */
class editor_Models_SegmentUserAssoc extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_SegmentUserAssoc';
    protected $validatorInstanceClass = 'editor_Models_Validator_SegmentUserAssoc';

    
    /**
     * returns all users to the segment_id of the given SegmentUserAssoc
     * @param integer $segment_id
     * @return [array] list with user arrays
     */
    public function getUsersOfSegment($segment_id){
        $this->setSegmentId($segment_id);
        return $this->loadAllUsers();
    }
    
    /**
     * loads all segments to the given user guid
     * @param guid $userGuid
     * @return array|null
     */
    public function loadByUserGuid(string $userGuid){
        try {
            $s = $this->db->select()->where('userGuid = ?', $userGuid);
            return $this->db->fetchAll($s)->toArray();
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        return null;
    }
    
    /**
     * loads all users to the given segment_id
     * @param guid $segment_id
     * @return array|null
     */
    public function loadBySegmentId(string $segment_id){
        try {
            $s = $this->db->select()->where('segment_id = ?', $segment_id);
            return $this->db->fetchAll($s)->toArray();
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        return null;
    }
    
    /**
     * loads the assocs
     * @param array $list
     * @return array
     */
    public function loadByUserGuidList(array $list) {
        try {
            if(count($list)===0)
                return array();
            $s = $this->db->select()->where('userGuid in (?)', $list);
            return $this->db->fetchAll($s)->toArray();
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        return null;
    }
    
     /**
     * loads the assocs
     * @param array $list
     * @return array
     */
    public function loadBySegmentIdList(array $list) {
        try {
            if(count($list)===0)
                return array();
            $s = $this->db->select()->where('segment_id in (?)', $list);
            return $this->db->fetchAll($s)->toArray();
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        return null;
    }
    
    /**
     * loads one SegmentUserAssoc Instance by given params.
     * 
     * @param string $userGuid
     * @param string $segment_id
     * @return array
     */
    public function loadByParams(string $userGuid, $segment_id) {
        try {
            $s = $this->db->select()
                ->where('userGuid = ?', $userGuid)
                ->where('segment_id = ?', $segment_id);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if (!$row) {
            $this->notFound(__CLASS__ . '#segment_id + userGuid', $segment_id.' + '.$userGuid);
        }
        //load implies loading one Row, so use only the first row
        $this->row = $row;
        return $this->row->toArray();
    }
    
    /**
     * @param string $userGuid
     * @return int
     */
    public function getTotalCountByUserGuid($userGuid) {
        $s = $this->db->select();
        $s->where('userGuid = ?', $userGuid);
        return parent::computeTotalCount($s);
    }
    
    /**
     * @param int $segment_id
     * @return int
     */
    public function getTotalCountBySegmentId($segment_id) {
        $s = $this->db->select();
        $s->where('segment_id = ?', $segment_id);
        return parent::computeTotalCount($s);
    }
    
    /**
     * returns a list with users to the actually loaded segment_id
     * @param string $segment_id
     * @return array
     */
    public function loadAllUsers() {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $db = $this->db;
        $s = $user->db->select()
        ->from(array('u' => $user->db->info($db::NAME)))
        ->join(array('tua' => $db->info($db::NAME)), 'tua.userGuid = u.userGuid', array())
        ->where('tua.segment_id = ?', $this->getSegmentId());
        return $user->db->fetchAll($s)->toArray();
    }
    
    /**
     * loads the SegmentUserAssoc Content joined with userinfos (currently only login)
     * @return array
     */
    public function loadAllWithUserInfo() {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $db = $this->db;
        $s = $db->select()
        ->setIntegrityCheck(false)
        ->from(array('tua' => $db->info($db::NAME)))
        ->join(array('u' => $user->db->info($db::NAME)), 'tua.userGuid = u.userGuid', array('login', 'surName', 'firstName'));
        
        //default sort: 
        if(!$this->filter->hasSort()) {
            $this->filter->addSort('surName');
            $this->filter->addSort('firstName');
            $this->filter->addSort('login');
        }
        return $this->loadFilterdCustom($s);
    }
    

    /**
     * deletes the actual loaded assoc
     */
    public function delete() {
        $this->db->delete(array(
            'id = ?' => $this->getId(),
            'segment_id = ?' => $this->getSegmentId(),
            'userGuid = ?' => $this->getUserGuid(),
        ));
        $this->init();
    }

    /**
     * deletes all assoc entries for this userGuid
     * @param string $userGuid
     */
    public function deleteByUserGuid($userGuid) {
        $list = $this->loadByUserGuid($userGuid);
        foreach($list as $assoc) {
            $this->init($assoc);
            $this->delete();
        }
    }
    
    /**
     * deletes all assoc entries for this segment_id
     * @param string $segment_id
     */
    public function deleteBySegmentId($segment_id) {
        $list = $this->loadBySegmentId($segment_id);
        foreach($list as $assoc) {
            $this->init($assoc);
            $this->delete();
        }
    }
    
}