<?php

namespace MittagQI\Translate5\Task\Deadline;

use DateInterval;
use DateTime;

class DeadlineDateCalculator
{
    /**
     * Cut off the deadline date by the given percentage
     * @throws \Exception
     */
    public function calculateNewDeadlineDate(string $deadlineDate, int $subPercent): string
    {
        $now = new DateTime();
        $end = new DateTime($deadlineDate);

        $interval = $now->diff($end);
        $totalDays = $interval->days;

        $daysToAdd = round($totalDays * ($subPercent / 100));

        $result = clone $now;
        $result->add(new DateInterval("P{$daysToAdd}D"));

        return $result->format('Y-m-d H:i:s');
    }
}
