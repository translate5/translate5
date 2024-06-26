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

/**
 * SegmentUserAssoc Object Instance as needed in the application
 * @method string getId()
 * @method string getSegmentId()
 * @method string getUserGuid()
 * @method string getTaskGuid()
 * @method string getCreated()
 * @method string getModified()
 *
 * @method void setSegmentId(string $segmentId)
 * @method void setUserGuid(string $userGuid)
 * @method void setTaskGuid(string $taskGuid)
 * @method void setCreated(string $timestamp)
 * @method void setModified(string $timestamp)
 */
class editor_Models_SegmentUserAssoc extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = 'editor_Models_Db_SegmentUserAssoc';

    protected $validatorInstanceClass = 'editor_Models_Validator_SegmentUserAssoc';

    #region direct calls

    /**
     * directly deletes the segment user association by given ID
     */
    public function directDelete(int $segmentId, string $userGuid): void
    {
        $this->db->delete([
            'userGuid = ?' => $userGuid,
            'segmentId = ?' => $segmentId,
        ]);
    }

    /**
     * @throws ZfExtended_ValidateException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     */
    public function createAndSave(string $taskGuid, int $segmentId, string $userGuid)
    {
        $now = date('Y-m-d H:i:s');
        $this->init();
        $this->setModified($now);
        $this->setCreated($now);
        $this->setTaskGuid($taskGuid);
        $this->setUserGuid($userGuid);
        $this->setSegmentId($segmentId);
        $this->validate();

        try {
            $this->save();
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            // on duplicate key everything is ok, the entry is already existing
        }
    }

    #endregion

    /**
     * returns all users to the segmentId of the given SegmentUserAssoc
     * @param int $segmentId
     * @return [array] list with user arrays
     */
    public function getUsersOfSegment($segmentId)
    {
        $this->setSegmentId($segmentId);

        return $this->loadAllUsers();
    }

    /**
     * loads all segments to the given user guid
     * @return array|null
     */
    public function loadByUserGuid(string $userGuid)
    {
        try {
            $s = $this->db->select()->where('userGuid = ?', $userGuid);

            return $this->db->fetchAll($s)->toArray();
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }

        return null;
    }

    /**
     * loads all users to the given segmentId
     * @return array|null
     */
    public function loadBySegmentId(string $segmentId)
    {
        try {
            $s = $this->db->select()->where('segmentId = ?', $segmentId);

            return $this->db->fetchAll($s)->toArray();
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }

        return null;
    }

    /**
     * loads the assocs
     * @return array
     */
    public function loadByUserGuidList(array $list)
    {
        try {
            if (count($list) === 0) {
                return [];
            }
            $s = $this->db->select()->where('userGuid in (?)', $list);

            return $this->db->fetchAll($s)->toArray();
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }

        return null;
    }

    /**
     * loads the assocs
     * @return array
     */
    public function loadBySegmentIdList(array $list)
    {
        if (empty($list)) {
            return [];
        }

        try {
            $s = $this->db->select()
                ->where('segmentId in (?)', $list);

            return $this->db->fetchAll($s)->toArray();
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }

        return null;
    }

    /**
     * Loads all association entries to the given segmentIds to the given user which are watched
     * @param string $userGuid
     */
    public function loadIsWatched(array $segmentIds, $userGuid)
    {
        if (empty($segmentIds)) {
            return [];
        }

        try {
            $s = $this->db->select()
                ->where('segmentId in (?)', $segmentIds)
                ->where('isWatched = 1')
                ->where('userGuid = ?', $userGuid);

            return $this->db->fetchAll($s)->toArray();
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }

        return null;
    }

    /**
     * loads one SegmentUserAssoc Instance by given params.
     *
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function loadByParams(string $userGuid, int|string $segmentId): array
    {
        try {
            $s = $this->db->select()
                ->where('userGuid = ?', $userGuid)
                ->where('segmentId = ?', $segmentId);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if (! $row) {
            $this->notFound(__CLASS__ . '#segmentId + userGuid', $segmentId . ' + ' . $userGuid);
        }
        //load implies loading one Row, so use only the first row
        $this->row = $row;

        return $this->row->toArray();
    }

    /**
     * @param string $userGuid
     * @return int
     */
    public function getTotalCountByUserGuid($userGuid)
    {
        $s = $this->db->select();
        $s->where('userGuid = ?', $userGuid);

        return parent::computeTotalCount($s);
    }

    /**
     * @param int $segmentId
     * @return int
     */
    public function getTotalCountBySegmentId($segmentId)
    {
        $s = $this->db->select();
        $s->where('segmentId = ?', $segmentId);

        return parent::computeTotalCount($s);
    }

    /**
     * returns a list with users to the actually loaded segmentId
     * @return array
     */
    public function loadAllUsers()
    {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $db = $this->db;
        $s = $user->db->select()
            ->from([
                'u' => $user->db->info($db::NAME),
            ])
            ->join([
                'tua' => $db->info($db::NAME),
            ], 'tua.userGuid = u.userGuid', [])
            ->where('tua.segmentId = ?', $this->getSegmentId());

        return $user->db->fetchAll($s)->toArray();
    }

    /**
     * loads the SegmentUserAssoc Content joined with userinfos (currently only login)
     * @return array
     */
    public function loadAllWithUserInfo()
    {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $db = $this->db;
        $s = $db->select()
            ->setIntegrityCheck(false)
            ->from([
                'tua' => $db->info($db::NAME),
            ])
            ->join([
                'u' => $user->db->info($db::NAME),
            ], 'tua.userGuid = u.userGuid', ['login', 'surName', 'firstName']);

        //default sort:
        if (! $this->filter->hasSort()) {
            $this->filter->addSort('surName');
            $this->filter->addSort('firstName');
            $this->filter->addSort('login');
        }

        return $this->loadFilterdCustom($s);
    }
}
