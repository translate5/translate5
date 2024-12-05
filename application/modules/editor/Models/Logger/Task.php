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
 * Logger Task Entity Object
 *
 * @method string getId()
 * @method void setId(int $id)
 * @method string getTaskGuid()
 * @method void setTaskGuid(string $taskGuid)
 * @method string getLevel()
 * @method void setLevel(int $level)
 * @method string getState()
 * @method void setState(string $state)
 * @method string getEventCode()
 * @method void setEventCode(string $eventCode)
 * @method string getDomain()
 * @method void setDomain(string $domain)
 * @method string getWorker()
 * @method void setWorker(string $worker)
 * @method string getMessage()
 * @method void setMessage(string $message)
 * @method string getExtra()
 * @method void setExtra(string $extra)
 * @method string getAuthUserGuid()
 * @method void setAuthUserGuid(string $authUserGuid)
 * @method string getAuthUser()
 * @method void setAuthUser(string $authUser)
 * @method string getCreated()
 * @method void setCreated(string $created)
 */
class editor_Models_Logger_Task extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = 'editor_Models_Db_Logger_Task';

    /**
     * Sets the internal data from the given Event class
     */
    public function setFromEventAndTask(ZfExtended_Logger_Event $event, editor_Models_Task $task): void
    {
        $this->setTaskGuid($task->getTaskGuid());
        $this->setState($task->getState());
        $this->setEventCode($event->eventCode);
        $this->setLevel($event->level);
        $this->setDomain($event->domain);
        $this->setWorker($event->worker);
        $this->setMessage($event->message);
        $this->setAuthUserGuid($event->userGuid);
        $this->setAuthUser($event->userLogin);
        $this->setCreated($event->created);
    }

    /**
     * loads all events to the given taskGuid
     * sorts from newest to oldest
     */
    public function loadByTaskGuid(string $taskGuid): array
    {
        $s = $this->db->select();
        $s->where('taskGuid = ?', $taskGuid);

        return array_map(function ($item) {
            $item['message'] = $item['message'];

            return $item;
        }, $this->loadFilterdCustom($s));
    }

    /**
     * loads last 5 fatals / errors / warnings for given taskGuid
     */
    public function loadLastErrors(string $taskGuid): array
    {
        $errornousLevels = [
            ZfExtended_Logger::LEVEL_FATAL,
            ZfExtended_Logger::LEVEL_ERROR,
            ZfExtended_Logger::LEVEL_WARN,
        ];
        $s = $this->db->select();
        $s->where('taskGuid = ?', $taskGuid);
        $s->where('level in (?)', [$errornousLevels]);
        $s->order('id DESC');
        $s->limit('5');

        return array_map(function ($item) {
            $item['message'] = htmlspecialchars($item['message']);

            return $item;
        }, $this->db->fetchAll($s)->toArray());
    }

    /**
     * Loads task logs by level
     */
    public function loadByLevel(string $taskGuid, array $levels): array
    {
        $s = $this->db->select();
        $s->where('taskGuid = ?', $taskGuid);
        $s->where('level in (?)', [$levels]);
        $s->order('id DESC');

        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * loads all events to the given taskGuid
     * sorts from newest to oldest
     */
    public function getTotalByTaskGuid(string $taskGuid): int
    {
        $s = $this->db->select();
        $s->where('taskGuid = ?', $taskGuid);

        return $this->computeTotalCount($s);
    }

    /**
     * @return array|null : a single row as assoc array
     */
    public function getLastByTaskGuidAndEventCodes(string $taskGuid, array $eventCodes): ?array
    {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid);
        if (count($eventCodes) == 0) {
            $s->where('1 = 2');
        } elseif (count($eventCodes) == 1) {
            $s->where('eventCode = ?', $eventCodes[0]);
        } else {
            $s->where('eventCode IN (?)', $eventCodes);
        }
        $s->order('created DESC');
        $row = $this->db->fetchRow($s);
        if (empty($row)) {
            return null;
        }
        $item = $row->toArray();
        $item['message'] = htmlspecialchars($item['message']);

        return $item;
    }

    /**
     * @return array: a single row as assoc array
     */
    public function getByTaskGuidAndEventCodes(string $taskGuid, array $eventCodes): array
    {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->where('eventCode IN (?)', $eventCodes)
            ->order('created DESC');

        return array_map(function ($item) {
            $item['message'] = htmlspecialchars($item['message']);

            return $item;
        }, $this->db->fetchAll($s)->toArray());
    }

    public function getErrorsByTaskGuidAndDomain(string $taskGuid, string $domain): array
    {
        $s = $this->db->select();
        $s->where('taskGuid = ?', $taskGuid);
        $s->where('domain = ?', $domain);
        $s->where('level in (?)', [
            ZfExtended_Logger::LEVEL_FATAL,
            ZfExtended_Logger::LEVEL_ERROR,
            ZfExtended_Logger::LEVEL_WARN,
        ]);

        return array_map(
            static function ($item) {
                return htmlspecialchars($item['message']);
            },
            $this->loadFilterdCustom($s)
        );
    }
}
