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

/**
 * Saving an existing Segment contains a lot of different steps in the business logic, not only just saving the content to the DB
 * Therefore this updater class exists, which provides some functions to update a segment 
 *  in the correct way from the business logic view point
 */
class editor_Models_Segment_Updater {
    use editor_Models_Import_FileParser_TagTrait;
    
    /**
     * @var ZfExtended_EventManager
     */
    protected $events = false;
    
    /**
     * @var editor_Models_Segment
     */
    protected $segment;
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTag;
    
    public function __construct(editor_Models_Task $task) {
        $this->task = $task;
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(get_class($this)));
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->initHelper();
    }
    
    /**
     * Updates the segment with all dependencies
     * @param editor_Models_Segment $segment
     * @throws editor_Models_Segment_UnprocessableException | ZfExtended_ValidateException
     */
    public function update(editor_Models_Segment $segment, editor_Models_SegmentHistory $history) {
        $this->segment = $segment;
        $this->segment->setConfig($this->task->getConfig());
        
        $allowedAlternatesToChange = $this->segment->getEditableDataIndexList();
        $updateSearchAndSort = array_intersect(array_keys($this->segment->getModifiedValues()), $allowedAlternatesToChange);
        
//HERE sanitizeEditedContent check (ob aufgerufen!) 
// Sinnvoll, ja nein? selbes Problem mit dem ENT_XML1 stuff, bei replace all und excel nötig. Wie ists mit der Pretranslation?
// Wie ist das mit den TMs und en ENT_XML1??

        foreach($updateSearchAndSort as $field) {
            $this->segment->updateToSort($field);
        }
        
        //if no content changed, restore the original content (which contains terms, so segment may not be retagged)
        $this->segment->restoreNotModfied();
        
        //@todo do this with events
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wfm editor_Workflow_Manager */
        $workflow=$wfm->getActive($this->segment->getTaskGuid());
        $workflow->beforeSegmentSave($this->segment, $this->task);
        
        $this->segment->validate();
        
        //TODO: Introduced with TRANSLATE-885, but is more a hack as a solution. See Issue comments for more information!
        $this->updateTargetHashAndOriginal($this->task); 
        
        foreach($allowedAlternatesToChange as $field) {
            if($this->segment->isModified($field)) {
                $this->segment->updateQmSubSegments($field);
            }
        }
        
        $this->events->trigger("beforeSegmentUpdate", $this, array(
            'entity' => $this->segment,
            'history' => $history
        ));
        
        $this->updateMatchRateType(); 
        
        //saving history directly before normal saving,
        // so no exception between can lead to history entries without changing the master segment
        $history->save();
        $this->segment->setTimestamp(NOW_ISO); //see TRANSLATE-922
        $this->segment->save();
        //call after segment put handler
        $this->updateLanguageResources();
        
        //update the segment finish count for the current workflow step
        $this->task->changeSegmentFinishCount($this->task, $segment->getAutoStateId(), $history->getAutoStateId());
    }
    
    /**
     * Updates the target original and targetMd5 hash for repetition calculation
     * Can be done only in Workflow Step 1 and if all targets were empty on import
     * This is more a hack as a right solution. See TRANSLATE-885 comments for more information!
     * See also in AlikesegmenController!
     */
    protected function updateTargetHashAndOriginal() {
        //TODO: also a check is missing, if task has alternate targets or not.
        // With alternates no recalc is needed at all, since no repetition editor can be used
        
        if($this->task->getWorkflowStep() == 1 && (bool) $this->task->getEmptyTargets()){
            $hasher = ZfExtended_Factory::get('editor_Models_Segment_RepetitionHash', [$this->task]);
            /* @var $hasher editor_Models_Segment_RepetitionHash */
            $this->segment->setTargetMd5($hasher->hashTarget($this->segment->getTargetEdit(), $this->segment->getSource()));
            $this->segment->setTarget($this->segment->getTargetEdit());
            $this->segment->updateToSort('target');
        }
    }
    
    /**
     * Before a segment is saved, the matchrate type has to be fixed to valid value
     */
    protected function updateMatchRateType() {
        $segment = $this->segment;
        /* @var $segment editor_Models_Segment */
        $givenType = $segment->getMatchRateType();
        
        //if it was a normal segment edit, without overtaking the match we have to do nothing here
        if(!$segment->isModified('matchRateType') || strpos($givenType, editor_Models_LanguageResources_LanguageResource::MATCH_RATE_TYPE_EDITED) !== 0) {
            return;
        }
        
        $matchrateType = ZfExtended_Factory::get('editor_Models_Segment_MatchRateType');
        /* @var $matchrateType editor_Models_Segment_MatchRateType */
        
        $unknown = function() use ($matchrateType, $givenType, $segment){
            $matchrateType->initEdited($matchrateType::TYPE_UNKNOWN, $givenType);
            $segment->setMatchRateType((string) $matchrateType);
        };
        
        $matches = [];
        //if it was an invalid type set it to unknown
        if(! preg_match('/'.editor_Models_LanguageResources_LanguageResource::MATCH_RATE_TYPE_EDITED.';languageResourceid=([0-9]+)/', $givenType, $matches)) {
            $unknown();
            return;
        }
        
        //load the used languageResource to get more information about it (TM or MT)
        $languageResourceid = $matches[1];
        $languageresource = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $languageresource editor_Models_LanguageResources_LanguageResource */
        try {
            $languageresource->load($languageResourceid);
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $unknown();
            return;
        }
        
        //just to display the TM name too, we add it here to the type
        $type = $languageresource->getServiceName().' - '.$languageresource->getName();
        
        //set the type
        $matchrateType->initEdited($languageresource->getResource()->getType(),$type);
        
        //REMINDER: this would be possible if we would know if the user edited the segment after using the TM
        //$matchrateType->add($matchrateType::TYPE_INTERACTIVE);
        
        //save the type
        $segment->setMatchRateType((string) $matchrateType);

        //if it is tm and the matchrate is >=100, log the usage
        if($languageresource->isTm() && $segment->getMatchRate() >= editor_Services_Connector_FilebasedAbstract::EXACT_MATCH_VALUE){
            $this->logAdapterUsageOnSegmentEdit($languageresource);
        }
    }
    
    /**
     * After a segment is changed we inform the language resource services about that. What they do with this information is the service's problem.
     */
    protected function updateLanguageResources(): void {
        /* @var $segment editor_Models_Segment */
        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */
        if(editor_Models_Segment_MatchRateType::isUpdateable($this->segment->getMatchRateType())) {
            $manager->updateSegment($this->segment);
        }
    }
    
    /**
     * Applies the import whitespace replacing to the edited user by the content
     * @param string $content the content to be sanitized, the value is modified directly via reference!
     * @return bool
     */
    public function sanitizeEditedContent(string &$content): string {
        $nbsp = json_decode('"\u00a0"');
        
        //some browsers create nbsp instead of normal whitespaces, since nbsp are removed by the protectWhitespace code below
        // we convert it to usual whitespaces. If there are multiple ones, they are reduced to one then.
        // This is so far the desired behavior. No characters escaped as tag by the import should be addable through the editor.
        // Empty spaces at the very beginning/end are only allowed during editing and now removed for saving.
        $content = trim(str_replace($nbsp, ' ', $content));
        
        //if there are tags to be ignored, we remove them here
        $oldContent = $content = $this->internalTag->removeIgnoredTags($content);
        
        //since our internal tags are a div span construct with plain content in between, we have to replace them first
        $content = $this->internalTag->protect($content);
        
        //the following call splits the content at tag boundaries, and sanitizes the textNodes only
        // In the textnode additional / new protected characters (whitespace) is converted to internal tags and then removed
        // This is because the user is not allowed to add new internal tags by adding plain special characters directly (only via adding it as tag in the frontend)
        $content = $this->parseSegmentProtectWhitespace($content, 'strip_tags');
        
        //revoke the internaltag replacement
        $content = $this->internalTag->unprotect($content);
        
        //return true if some whitespace content was changed
        return $this->whitespaceHelper->entityCleanup($content) !== $this->whitespaceHelper->entityCleanup($oldContent);
    }
    
    /***
     * This will write a log entry of how many characters are send to the adapter for translation.
     * 
     * @param editor_Models_LanguageResources_LanguageResource $adapter
     */
    protected function logAdapterUsageOnSegmentEdit(editor_Models_LanguageResources_LanguageResource $adapter) {
        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */
        $connector = $manager->getConnector($adapter,$this->task->getSourceLang(),$this->task->getTargetLang(),$this->task->getConfig());
        /* @var $connector editor_Services_Connector */
        $connector->logAdapterUsage($this->segment, editor_Services_Connector::REQUEST_SOURCE_EDITOR);
    }
}