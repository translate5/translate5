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

use MittagQI\Translate5\LanguageResource\QueryDurationLogger;

class editor_Plugins_MatchAnalysis_Worker extends editor_Models_Task_AbstractWorker
{
    protected editor_Plugins_MatchAnalysis_Analysis $analysis;

    /**
     * @throws Zend_Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->log = Zend_Registry::get('logger')->cloneMe('plugin.matchanalysis');
    }

    protected function validateParameters(array $parameters): bool
    {
        $neededEntries = [
            'internalFuzzy',
            'pretranslateMatchrate',
            'pretranslateTmAndTerm',
            'pretranslateMt',
            'isTaskImport',
            'pretranslate',
        ];

        $foundEntries = array_keys($parameters);
        $keyDiff = array_diff($neededEntries, $foundEntries);

        //if there is not keyDiff all needed were found
        return empty($keyDiff);
    }

    public function work(): bool
    {
        try {
            return $this->doWork();
        } catch (Throwable $e) {
            if (isset($this->analysis)) {
                //clean after analysis exception
                $this->analysis->clean();
            }
            // when error happens unlock the task
            $this->task->unlock();
            $this->log->error(
                'E1100',
                'MatchAnalysis Plug-In: analysis and pre-translation cannot be run. '
                    . 'See additional errors for more Information.',
                [
                    'task' => $this->task,
                ]
            );
            $this->log->exception($e, [
                'extra' => [
                    'task' => $this->task,
                ],
            ]);

            return false;
        }
    }

    /**
     * @return boolean
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Db_Exceptions_DeadLockHandler
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Exception
     */
    protected function doWork(): bool
    {
        $params = $this->workerModel->getParameters();

        $analysisAssoc = ZfExtended_Factory::get(editor_Plugins_MatchAnalysis_Models_TaskAssoc::class);
        $analysisAssoc->setTaskGuid($this->task->getTaskGuid());

        //set flag for internal fuzzy usage
        $analysisAssoc->setInternalFuzzy($params['internalFuzzy']);
        //set pretranslation matchrate used for the anlysis
        $analysisAssoc->setPretranslateMatchrate($params['pretranslateMatchrate']);
        $analysisAssoc->setUuid(ZfExtended_Utils::uuid());

        $analysisId = $analysisAssoc->save();

        $this->analysis = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Analysis', [$this->task, $analysisId]);

        $this->analysis->setPretranslate($params['pretranslate']);
        $this->analysis->setInternalFuzzy($params['internalFuzzy']);
        $this->analysis->setUserGuid($params['userGuid']);
        $this->analysis->setUserName($params['userName']);
        $this->analysis->setPretranslateMatchrate($params['pretranslateMatchrate']);
        $this->analysis->setPretranslateMt($params['pretranslateMt']);
        $this->analysis->setPretranslateTmAndTerm($params['pretranslateTmAndTerm']);
        $this->analysis->setBatchQuery($params['batchQuery']);

        $updateCounter = 0;
        $lastProgress = 0;
        $return = $this->analysis->analyseAndPretranslate(function ($progress) use (&$updateCounter, &$lastProgress) {
            $updateCounter++;
            $lastProgress = $progress;
            //update the progress on each 10 segments (to prevent from possible deadlocks in worker table).
            if ($updateCounter % 10 == 0) {
                $this->updateProgress($progress);
            }
        });

        QueryDurationLogger::logFromWorker(
            'MatchAnalysis query duration sum {workerId} {resource} - '
                . '{queryCount} ({queryCountFromCache}) queries in {sum} ({sumFromCache})',
            [
                'task' => $this->task,
                'workerId' => (int) $this->workerModel->getId(),
                'analysisId' => $analysisId,
            ]
        );

        if (! empty($lastProgress)) {
            $this->updateProgress($lastProgress);
        }

        //setting null takes the current date from DB
        $analysisAssoc->finishNow($this->analysis->getErrorCount());

        $this->task->unlock();

        return $return;
    }

    /***
     * Match analysis and pretranslation takes 92 % of the import time
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::getWeight()
     */
    public function getWeight(): int
    {
        return 92;
    }
}
