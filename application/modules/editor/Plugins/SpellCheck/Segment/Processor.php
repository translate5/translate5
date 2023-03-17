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

namespace MittagQI\Translate5\Plugins\SpellCheck\Segment;

use editor_Models_Segment;
use editor_Models_SegmentFieldManager;
use editor_Models_Task;
use editor_Plugins_SpellCheck_QualityProvider;
use editor_Segment_Tags;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\DownException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\MalfunctionException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\RequestException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\TimeOutException;
use MittagQI\Translate5\Plugins\SpellCheck\LanguageTool\Service;
use MittagQI\Translate5\Plugins\SpellCheck\LanguageTool\Adapter;
use MittagQI\Translate5\Segment\AbstractProcessor;
use MittagQI\Translate5\Service\DockerServiceAbstract;
use MittagQI\Translate5\Segment\Db\Processing;
use MittagQI\Translate5\Segment\Processing\State;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;

/**
 * Encapsulates the processing of segments with the languagetool-service
 * @property Service $service;
 */
class Processor extends AbstractProcessor
{
    /**
     * Reports the defect segments that occurred during spellchecking if any
     * @param editor_Models_Task $task
     * @param string $processingMode
     * @throws Zend_Exception
     */
    public static function reportDefectSegments(editor_Models_Task $task, string $processingMode) {

        $processingState = new Processing();
        $segmentIds = $processingState->getSegmentsForState($task->getTaskGuid(), Service::SERVICE_ID, State::UNPROCESSABLE);
        if (!empty($segmentIds)) {

            $logger = Zend_Registry::get('logger')->cloneMe(Configuration::getLoggerDomain($processingMode));
            $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
            $fieldManager = ZfExtended_Factory::get(editor_Models_SegmentFieldManager::class);
            $fieldManager->initFields($task->getTaskGuid());

            foreach ($segmentIds as $segmentId) {

                $segment->load($segmentId);
                $segmentsToLog[] = $segment->getSegmentNrInTask().'; Source-Text: '.strip_tags($segment->get($fieldManager->getFirstSourceName()));
            }
            $logger->warn('E1465', 'Some segments could not be checked by the spellchecker', [
                'task' => $task,
                'untaggableSegments' => $segmentsToLog,
            ]);
        }
    }

    /**
     * Is used as interval between the batches in the looped processing
     * This reduces the risk of deadlocks
     * @var int
     */
    protected int $loopingPause = 150;

    /**
     * @var string
     */
    private string $language;

    /**
     * @var Adapter 
     */
    private Adapter $adapter;

    /**
     * @var string
     */
    private string $qualityType;


    public function __construct(editor_Models_Task $task, DockerServiceAbstract $service, string $processingMode, string $serviceUrl = null, bool $isWorkerContext = true)
    {
        parent::__construct($task, $service, $processingMode, $serviceUrl, $isWorkerContext);
        $this->adapter = $this->service->getAdapter();
        $this->language = $this->adapter->getSpellCheckLangByTaskTargetLangId($task->getTargetLang());
        $this->qualityType = editor_Plugins_SpellCheck_QualityProvider::qualityType();
    }

    /**
     * Retrieves the spellcheck language to use (what may differs from the task's target language)
     * @return string|bool
     */
    public function getSpellcheckLanguage(): string|bool
    {
        return $this->language;
    }

    /**
     * batch-size when spellchecking
     * @return int
     */
    public function getBatchSize(): int
    {
        return Configuration::OPERATION_BATCH_SIZE;
    }


    /**
     * @param editor_Segment_Tags[] $segmentsTags
     * @throws DownException
     * @throws MalfunctionException
     * @throws RequestException
     * @throws TimeOutException
     * @throws Zend_Exception
     */
    public function processBatch(array $segmentsTags)
    {
        foreach ($segmentsTags as $tags) {
            $this->process($tags);
        }
    }

    /**
     * @param editor_Segment_Tags $segmentTags
     * @param bool $saveTags
     * @throws DownException
     * @throws MalfunctionException
     * @throws RequestException
     * @throws TimeOutException
     * @throws Zend_Exception
     */
    public function process(editor_Segment_Tags $segmentTags, bool $saveTags = true)
    {
        // Fetch segment
        $segment = $segmentTags->getSegment();

        // Foreach target
        foreach ($segmentTags->getTargets() as $target) {

            // Do check
            $check = new Check($segment, $target->getField(), $this->adapter, $this->language);

            // Process check results
            foreach ($check->getStates() as $category => $qualities) {
                foreach ($qualities as $quality) {
                    $segmentTags->addQuality(
                        field: $target->getField(),
                        type: $this->qualityType,
                        category: $category,
                        additionalData: $quality
                    );
                }
            }
            // Prevent other target-columns from being processed as this is not fully supported yet
            break;
        }
        if($saveTags){
            // Save qualities if the tags-model should be saved - we do not add any tags to the segment's markup, only quality entries
            $segmentTags->save(true, false);
        }
    }
}
