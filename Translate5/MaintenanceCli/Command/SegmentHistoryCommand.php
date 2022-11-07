<?php
/*
 START LICENSE AND COPYRIGHT
 
 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
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
namespace Translate5\MaintenanceCli\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\WebAppBridge\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Exception\RuntimeException;


class SegmentHistoryCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'segment:history';
    
    protected $versionCounter = 1;
    
    protected $autoStateMap;
    
    /**
     * @var \editor_Models_Segment_UtilityBroker
     */
    protected $segmentUtilities;
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Shows the segment editing history, from the oldest to the current version.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Shows the segment editing history, from the oldest to the current version.
The segment is identified by id or by taskGuid + segment number in task.
The single versions are showing only the values different to the current one! This could be confusing first.');
        
        $this->addArgument('segment', InputArgument::REQUIRED, 'Either a instance wide unique segment ID, or with -t|--task the segment number in the given task.');
        $this->addOption(
            'task',
            't',
            InputOption::VALUE_REQUIRED,
            'Give a task ID or taskGuid here, then the argument "segment" is interpreted as segment nr in that task instead as a unique segment id.');
            
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        Application::$startSession = true;
        $this->initTranslate5AppOrTest();
        $this->writeTitle('Segment history');
        
        $this->segmentUtilities = new \editor_Models_Segment_UtilityBroker;
        
        $ref = new \ReflectionClass('editor_Models_Segment_AutoStates');
        $this->autoStateMap = array_flip($ref->getConstants());
        
        $taskGuid = $this->findTaskGuid();
        
        $segment = $this->findSegment($taskGuid);
        
        $history = \ZfExtended_Factory::get('editor_Models_SegmentHistory');
        /* @var $history \editor_Models_SegmentHistory */
        $historyEntries = array_reverse($history->loadBySegmentId($segment->getId()));
        $historyData = \ZfExtended_Factory::get('editor_Models_SegmentHistoryData');
        /* @var $historyData \editor_Models_SegmentHistoryData */
        $historyDataEntries = $historyData->loadBySegmentId($segment->getId(), \editor_Models_SegmentField::TYPE_TARGET);
        
        $ids = array_column($historyDataEntries, 'segmentHistoryId');
        $historyDataEntries = array_combine($ids, $historyDataEntries);

        $this->io->section("General segment information");
        $this->io->text([
            '<info>Segment ID:</info> <options=bold>'.$segment->getId().'</>',
            '<info>Nr in Task:</info> <options=bold>'.$segment->getSegmentNrInTask().'</>',
            ' <info>Task GUID:</info> <options=bold>'.$segment->getTaskGuid().'</>',
        ]);
        foreach($historyEntries as $entry) {
            $this->showSegment((object) $entry, $segment);
            if(!empty($historyDataEntries[$entry['id']])) {
                $this->showSegmentContent($historyDataEntries[$entry['id']]);
            }
        }
        $this->showLatestSegment($segment);
        $data = $segment->getEditableFieldData();
        foreach($data as $field => $content) {
            $this->showSegmentContent(['name' => $field, 'edited' => $content]);
        }
        return 0;
    }
    
    /**
     * returns the autostate key
     * @param int $autoState
     * @return string
     */
    protected function getAutoState(int $autoState): string
    {
        return $this->autoStateMap[$autoState] ?? 'unknown';
    }
    
    protected function showSegment(\stdClass $segmentVersion, \editor_Models_Segment $segment) {
        $this->io->section('Version '.$this->versionCounter++.':');
        $result = [
            '     <info>history ID:</info> <options=bold>'.$segmentVersion->id.'</>',
            '        <info>created:</info> <options=bold>'.$segmentVersion->created.'</>',
        ];
//         if(!empty($segmentVersion->timestamp) && $segmentVersion->timestamp != $segment->getTimestamp()) {
//             $result[] = '<info>modified before:</info> '.$segmentVersion->timestamp;
//         }
        if($segmentVersion->userGuid != $segment->getUserGuid()) {
            $result[] = '           <info>user:</info> '.$segmentVersion->userName.' ('.$segmentVersion->userGuid.')';
        }
        if($segmentVersion->autoStateId != $segment->getAutoStateId()) {
            $result[] = '  <info>process state:</info> '.$this->getAutoState($segmentVersion->autoStateId);
        }
        if($segmentVersion->editable != $segment->getEditable()) {
            $result[] = '       <info>editable:</info> '.$segmentVersion->editable;
        }
        if($segmentVersion->pretrans != $segment->getPretrans()) {
            $result[] = '       <info>pretrans:</info> '.$segmentVersion->pretrans;
        }
        if($segmentVersion->workflowStepNr != $segment->getWorkflowStepNr() || $segmentVersion->workflowStep != $segment->getWorkflowStep()) {
            if(empty($segmentVersion->workflowStepNr) && empty($segmentVersion->workflowStep)) {
                $result[] = '       <info>workflow:</info> -na-';
            }
            else {
                $result[] = '       <info>workflow:</info> '.$segmentVersion->workflowStep.' ('.$segmentVersion->workflowStepNr.')';
            }
        }
        if($segmentVersion->matchRate != $segment->getMatchRate()) {
            $result[] = '      <info>matchRate:</info> '.$segmentVersion->matchRate;
        }
        if(mb_stripos($segment->getMatchRateType(), $segmentVersion->matchRateType) !== 0) {
            $result[] = ' <info>matchRate type:</info> '.$segmentVersion->matchRateType;
        }
        if(!empty($segmentVersion->stateId) && $segmentVersion->stateId != $segment->getStateId()) {
            $result[] = '       <info>state id:</info> '.$segmentVersion->stateId;
        }
        $this->io->text($result);
    }
    
    protected function showSegmentContent(array $segment) {
        // TODO track changes: make del red and add green
        $content = $this->segmentUtilities->internalTag->toExcel($segment['edited']);
        $label = str_pad('<info>'.$segment['name'].':</info> ', 30, ' ', STR_PAD_LEFT);
        $this->io->text($label.$content);
    }
    
    protected function showLatestSegment(\editor_Models_Segment $segment) {
        $this->io->section('Used/latest version:');
        $result = [
            '  <info>last modified:</info> <options=bold>'.$segment->getTimestamp().'</>',
            '           <info>user:</info> <options=bold>'.$segment->getUserName().' ('.$segment->getUserGuid().')</>',
            '       <info>editable:</info> <options=bold>'.$segment->getEditable().'</>',
            '       <info>pretrans:</info> <options=bold>'.$segment->getPretrans().'</>',
            '  <info>process state:</info> <options=bold>'.$this->getAutoState($segment->getAutoStateId()).'</>',
        ];
        if(empty($segment->getWorkflowStep()) && empty($segment->getWorkflowStepNr())) {
            $result[] = '       <info>workflow:</info> <options=bold>-na-</>';
        }
        else {
            $result[] = '       <info>workflow:</info> <options=bold>'.$segment->getWorkflowStep().' ('.$segment->getWorkflowStepNr().')</>';
        }
        $result[] = '      <info>matchRate:</info> <options=bold>'.$segment->getMatchRate().'</>';
        $result[] = '  <info>matchRateType:</info> <options=bold>'.$segment->getMatchRateType().'</>';
        if(!empty($segment->getStateId())) {
            $result[] = '        <info>stateId:</info> <options=bold>'.$segment->getStateId().'</>';
        }
        
        $this->io->text($result);
    }
    
    /**
     * returns the taskGuid to given option --task, null if option was not given
     * @throws RuntimeException
     * @return string|NULL
     */
    protected function findTaskGuid(): ?string
    {
        $taskId = $this->input->getOption('task');
        
        if(empty($taskId)) {
            return null;
        }
        $task = \ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task \editor_Models_Task */
        try {
            if(is_numeric($taskId)) {
                $task->load($taskId);
            }
            else {
                $task->loadByTaskGuid($taskId);
            }
        }
        catch(\ZfExtended_Models_Entity_NotFoundException $e) {
            throw new RuntimeException('No task with ID | taskGuid '.$taskId.' could be found!');
        }
        return $task->getTaskGuid();
    }
    
    /**
     * returns the found segment
     * @param string $taskGuid optional, of given segmentId is interpreted as segment nr in task
     * @throws RuntimeException
     * @return \editor_Models_Segment
     */
    protected function findSegment(string $taskGuid = null): \editor_Models_Segment
    {
        $segmentId = $this->input->getArgument('segment');
        
        $segment = \ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment \editor_Models_Segment */
        
        try {
            if(empty($taskGuid)) {
                $segment->load($segmentId);
            } else {
                $segment->loadBySegmentNrInTask($segmentId, $taskGuid);
            }
            return $segment;
        }
        catch(\ZfExtended_Models_Entity_NotFoundException $e) {
            throw new RuntimeException('No segment with nr|id '.$segmentId.' could be found!');
        }
    }
}
