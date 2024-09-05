<?php

namespace MittagQI\Translate5\Task\Deadline;

use DateInterval;
use DateTime;
use editor_Models_Task;

class DeadlineDateCalculator
{
    /**
     * Cut off the deadline date by the given percentage
     * @throws \Exception
     */
    public function calculateNewDeadlineDate(editor_Models_Task $task): string
    {
        $start = new DateTime($task->getCreated());
        $end = new DateTime($task->getDeadlineDate());
        $percentage = $task->getConfig()->runtimeOptions->import->projectDeadline->jobDeadlineFraction;

        $interval = $start->diff($end);
        $totalDays = $interval->days;
        $daysToAdd = ceil($totalDays * ($percentage / 100));
        $start->setTime(21, 0); // concept is 21:00 is deadline always

        if ($daysToAdd <= 0) {
            return $start->format('Y-m-d H:i:s');
        }
        $start->add(new DateInterval("P{$daysToAdd}D"));

        return $start->format('Y-m-d H:i:s');
    }
}
