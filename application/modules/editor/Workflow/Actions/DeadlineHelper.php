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
 * Workflow Helper to find out the affected task user associations
 */
class editor_Workflow_Actions_DeadlineHelper extends editor_Workflow_Actions_Abstract {
    /***
     * Task log message for deadline notificiation
     * @var string
     */
    const DEADLINE_NOTIFICATION_LOG_MESSAGE = 'Deadline notification send.';

    /***
     * How frequent do cron periodical action is triggered. This is required
     * for the deadline periodical notification so we adjast the deadline date select arount
     * this periond
     * @var integer
     */
    const CRON_PERIODICAL_CALL_FREQUENCY_MIN = 30;

    CONST LOG_EVENT_CODE = 'E1012';
    CONST STR_APPROACH = 'approaching';
    CONST STR_OVERDUE = 'overdue';

    /**
     * Get the deadline not notified assocs
     *
     * @param int $daysOffset
     * @param bool $isApproaching
     * @param $role
     * @return array|array
     */
    public function getDeadlineUnnotifiedAssocs(stdClass $triggerConfig, bool $isApproaching): array {

        //if no receiverRole is defined, all roles will be used
        $role = null;
        if(isset($triggerConfig->receiverRole)){
            $role = $triggerConfig->receiverRole;
        }
        $daysOffset=$triggerConfig->daysOffset ?? 1;

        $symbol = $isApproaching ? '+' : '-';

        if($this->config->trigger == 'doCronPeriodical'){
            //the deadline check date will be between: "days offset date" +/- "cron periodical call frequency"
            $dateSelect = 'tua.deadlineDate BETWEEN '.
                ' DATE_SUB(NOW() '.$symbol.' INTERVAL ? DAY,INTERVAL '.Zend_Db_Table::getDefaultAdapter()->quote(self::CRON_PERIODICAL_CALL_FREQUENCY_MIN).' MINUTE)'.
                ' AND '.
                ' DATE_ADD(NOW() '.$symbol.' INTERVAL ? DAY,INTERVAL '.Zend_Db_Table::getDefaultAdapter()->quote(self::CRON_PERIODICAL_CALL_FREQUENCY_MIN).' MINUTE)';
        }
        else {
            //date select when this is triggered from the daily action
            $dateSelect = 'DATE(tua.deadlineDate) = DATE(CURRENT_DATE) '.$symbol.' INTERVAL ? DAY';
        }

        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */

        $db = Zend_Registry::get('db');
        /* @var $db Zend_Db_Table */
        $s = $db->select()
            ->from(array('tua' => 'LEK_taskUserAssoc'))
            ->join(array('u' => $user->db->info($user->db::NAME)),'tua.userGuid=u.userGuid',['userGuid', 'firstName', 'surName', 'gender', 'login', 'email', 'roles', 'locale'])
            ->join(array('t' => 'LEK_task'), 'tua.taskGuid = t.taskGuid',[]);
        if(!empty($role)){
            $s->where('tua.role = ?', $role);
        }
        $s->where('tua.state != ?', $this->config->workflow::STATE_FINISH)
            ->where('t.state = ?', editor_Models_Task::STATE_OPEN)
            ->where('t.workflow = ?', $this->config->workflow->getName())
            ->where($dateSelect, $daysOffset);

        $tuas = $db->fetchAll($s);

        if(empty($tuas)){
            return [];
        }
        //filter out the notified assoc
        return $this->filterAlreadyNotified($tuas, $isApproaching);
    }

    /**
     * Filter out the task assocs from $assocs for which deadline notification is already send
     * @param array $assocs
     * @param bool $isApproaching
     * @return array
     * @throws Zend_Exception
     */
    protected function filterAlreadyNotified(array $assocs, bool $isApproaching): array {
        if(empty($assocs)){
            return $assocs;
        }
        $taskGuids = array_unique(array_column($assocs, 'taskGuid'));

        $db = Zend_Registry::get('db');
        /* @var $db Zend_Db_Table */

        $s = $db->select()
            ->from('LEK_task_log',['json_value(extra, "$.jobId") as jobId'])
            ->where('taskGuid IN (?)',$taskGuids)
            ->where('eventCode = ?', self::LOG_EVENT_CODE)
            ->where('json_value(extra, "$.type") = ?', $isApproaching ? self::STR_APPROACH : self::STR_OVERDUE)
            ->where('message like ?', self::DEADLINE_NOTIFICATION_LOG_MESSAGE.'%')
            ->distinct();
        $tuas = $db->fetchAll($s);

        if(empty($tuas)){
            //no notifications send so far
            return $assocs;
        }

        // get the jobIDs from the task log table.
        $jobIds = array_column($tuas, 'jobId');

        //filter out already notified users
        return array_filter($assocs, function($assoc) use ($jobIds){
           return !in_array($assoc['id'], $jobIds);
        });
    }

    /***
     * Write deadline notifiead log entry into task log table
     * @param array $tua
     * @param bool $isApproaching
     */
    public function logDeadlineNotified(array $tua, bool $isApproaching) {
        $this->log->info(self::LOG_EVENT_CODE, self::DEADLINE_NOTIFICATION_LOG_MESSAGE.' Deadline {type}', [
            'jobId' => $tua['id'],
            'type' => $isApproaching ? self::STR_APPROACH : self::STR_OVERDUE,
            'task'=>$this->config->task
        ]);
    }
}