<?php
/*
 START LICENSE AND COPYRIGHT

  This file is part of translate5

  Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * After importing a task a match analysis will be created based on the match rate passed in the xliff
 */
declare(strict_types=1);
class editor_Plugins_MatchAnalysis_MatchrateAnalysis
{
    /**
     * @param Closure|null $progressCallback - call to update the workerModel progress. It expects progress as argument
     *     (progress = 100 / task segment count)
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function analyse(editor_Models_Task $task, Closure $progressCallback = null): void
    {
        $taskGuid = $task->getTaskGuid();

        $analysisAssoc = new editor_Plugins_MatchAnalysis_Models_TaskAssoc();
        $analysisAssoc->setTaskGuid($taskGuid);
        $analysisAssoc->setUuid(ZfExtended_Utils::uuid());
        $analysisAssoc->setIsExternal(true);
        $analysisId = (int) $analysisAssoc->save();

        $segmentCounter = 0;
        $segmentAmount = (int) $task->getSegmentCount();

        // create a segment-iterator to get all segments of this task as a list of editor_Models_Segment objects
        $segments = new editor_Models_Segment_Iterator($taskGuid);
        foreach ($segments as $segment) {
            /* @var $segment editor_Models_Segment */

            $segmentCounter++;

            //progress to update
            $progress = $segmentCounter / $segmentAmount;

            $this->saveAnalysis($segment, $analysisId, $taskGuid);

            //report progress update
            $progressCallback && $progressCallback($progress);
        }

        $analysisAssoc->finishNow(0);
    }

    /**
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ReflectionException
     */
    protected function saveAnalysis(editor_Models_Segment $segment, int $analysisId, string $taskGuid): void
    {
        $matchAnalysis = new editor_Plugins_MatchAnalysis_Models_MatchAnalysis();

        $matchAnalysis->setSegmentId((int) $segment->getId());
        $matchAnalysis->setSegmentNrInTask((int) $segment->getSegmentNrInTask());
        $matchAnalysis->setTaskGuid($taskGuid);
        $matchAnalysis->setAnalysisId($analysisId);
        // setting LangResId to null to be able to display "From imported file" analysis later
        $matchAnalysis->setLanguageResourceid(null);
        $matchAnalysis->setWordCount((int) $segment->meta()->getSourceWordCount());
        $matchAnalysis->setCharacterCount((int) $segment->meta()->getSourceCharacterCount());
        $matchAnalysis->setMatchRate((int) $segment->getMatchRate());
        $matchAnalysis->setType($this->getLangResourceType($segment->getMatchRateType()));
        $matchAnalysis->setInternalFuzzy(0);
        $matchAnalysis->save();
    }

    private function getLangResourceType(string $matchRateType): string
    {
        $lrType = editor_Models_Segment_MatchRateType::getLangResourceType($matchRateType);

        return '' === $lrType ? editor_Models_Segment_MatchRateType::TYPE_TM : $lrType;
    }
}
