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
 * Encapsulates the tagging of groups of segment-tags
 * This enables to not "misuse" the import/analysis worker for processing a single tag when editing
 */
class editor_Plugins_TermTagger_SegmentProcessor {
    
    /**
     * Filters a quality state out of an array of term tag css-classes (=states)
     * Returns an empty string if any found
     * @param array $cssClasses
     * @param bool $isTargetField
     * @return string
     */
    public static function getQualityState(array $cssClasses, bool $isSourceField) : string {
        foreach($cssClasses as $cssClass){
            switch($cssClass){                
                case editor_Models_Term::TRANSSTAT_NOT_FOUND:
                    if($isSourceField){
                        return editor_Plugins_TermTagger_QualityProvider::NOT_FOUND_IN_TARGET;
                    }
                    break;
                    
                case editor_Models_Term::TRANSSTAT_NOT_DEFINED:
                    if($isSourceField){
                        return editor_Plugins_TermTagger_QualityProvider::NOT_DEFINED_IN_TARGET;
                    }
                    break;
                    
                case editor_Models_Term::STAT_SUPERSEDED:
                case editor_Models_Term::STAT_DEPRECATED:
                    if($isSourceField){
                        return editor_Plugins_TermTagger_QualityProvider::FORBIDDEN_IN_SOURCE;
                    } else {
                        return editor_Plugins_TermTagger_QualityProvider::FORBIDDEN_IN_TARGET;
                    }                    
                    break;
            }
        }
        return '';
    }
    /**
     * Finds term tags of certain classes (= certain term stati) in the tags that represent a problem
     * @param editor_Segment_Tags $tags
     */
    public static function findAndAddQualitiesInTags(editor_Segment_Tags $tags){
        $type = editor_Plugins_TermTagger_Tag::TYPE;
        foreach($tags->getTagsByTypeForField($type) as $field => $termTags){
            /* @var $termTags editor_Plugins_TermTagger_Tag[] */
            foreach($termTags as $termTag){
                if($termTag->hasCategory()){
                    $tags->addQualityByTag($termTag, $field);
                }
            }
        }
    }
    /**
     * @var editor_Models_Task
     */
    private $task;
    /**
     * @var editor_Plugins_TermTagger_Configuration
     */
    private $config;
    /**
     * @var string
     */
    private $processingMode;
    /**
     * @var boolean
     */
    private $isWorkerThread;
    /**
     * @var editor_Plugins_TermTagger_Service_ServerCommunication
     */
    private $communicationService;
    
    public function __construct(editor_Models_Task $task, editor_Plugins_TermTagger_Configuration $config, string $processingMode, bool $isWorkerThread){
        $this->task = $task;
        $this->config = $config;
        $this->processingMode = $processingMode;
        $this->isWorkerThread = $isWorkerThread;
    }
    /**
     * 
     * @param editor_Segment_Tags[] $segmentsTags
     * @param string $slot
     * @param bool $doSaveTags
     * @throws ZfExtended_Exception
     */
    public function process(array $segmentsTags, string $slot, bool $doSaveTags) {
        foreach($segmentsTags as $tags){            
            $tags->removeTagsByType(editor_Plugins_TermTagger_Tag::TYPE);
        }
        // creating the communication service which passes the tags to a temporary model sent to the tagger
        $this->communicationService = $this->config->createServerCommunicationServiceFromTags($segmentsTags);
        $termTagger = ZfExtended_Factory::get(
            'editor_Plugins_TermTagger_Service',
            [$this->config->getLoggerDomain($this->processingMode),
                $this->config->getRequestTimeout($this->isWorkerThread),
                editor_Plugins_TermTagger_Configuration::TIMEOUT_TBXIMPORT]);
        /* @var $termTagger editor_Plugins_TermTagger_Service */
        $this->config->checkTermTaggerTbx($termTagger, $slot, $this->communicationService->tbxFile);
        $result = $termTagger->tagterms($slot, $this->communicationService);
        $taggedSegments = $this->config->markTransFound($result->segments);
        $taggedSegmentsById = $this->groupResponseById($taggedSegments);
        foreach ($segmentsTags as $tags) { /* @var $tags editor_Segment_Tags */
            $segmentId = $tags->getSegmentId();
            if(array_key_exists($segmentId, $taggedSegmentsById)){
                // bring the tagged segment content back to the tags model
                $this->applyResponseToTags($taggedSegmentsById[$segmentId], $tags);
                $tags->termtaggerProcessed = true;
                // add qualities if found in the target tags
                if($this->task->getConfig()->runtimeOptions->termTagger->enableAutoQA){
                    self::findAndAddQualitiesInTags($tags);
                }
                // save the tags, either to the tags-model or back to the segment if configured
                if($doSaveTags){
                    if($this->processingMode == editor_Segment_Processing::IMPORT){
                        $tags->save(editor_Plugins_TermTagger_Tag::TYPE);
                    } else {
                        $tags->flush();
                    }
                }
            } else {
                // TODO FIXME: proper exception
                throw new ZfExtended_Exception('Response of termtagger did not contain the sent segment with ID '.$segmentId);
            }
        }
    }
    /**
     * Can only be called after ::process was called ...
     * @return editor_Plugins_TermTagger_Service_ServerCommunication
     */
    public function getCommunicationsService(){
        return $this->communicationService;
    }
    /**
     * Transfers a single termtagger response to the corresponding tags-model
     * @param array $responseGroup
     * @param editor_Segment_Tags $tags
     * @throws ZfExtended_Exception
     */
    private function applyResponseToTags(array $responseGroup, editor_Segment_Tags $tags) {
        // UGLY: this should better be done by adding real tag-objects instead of setting the tags via text
        if(count($responseGroup) < 1){
            // TODO FIXME: proper exception
            throw new ZfExtended_Exception('Response of termtagger did not contain data for the segment ID '.$tags->getSegmentId());
        }
        if(!$tags->hasSource()){
            throw new ZfExtended_Exception('Passed segment tags did not contain a source '.$tags->getSegmentId());
        }
        $responseFields = $this->groupResponseByField($responseGroup);
        $sourceText = null;
        if($tags->hasOriginalSource()){
            if(!array_key_exists('SourceOriginal', $responseFields)){
                // TODO FIXME: proper exception
                throw new ZfExtended_Exception('Response of termtagger did not contain data for the original source for the segment ID '.$tags->getSegmentId());
            }
            $source = $tags->getOriginalSource();
            $source->setTagsByText($responseFields[$source->getTermtaggerName()]->source);
        }
        foreach($tags->getTargets() as $target){ /* @var $target editor_Segment_FieldTags */
            $field = $target->getTermtaggerName();
            if($sourceText === null){
                $sourceText = $responseFields[$field]->source;
            }
            if(!array_key_exists($field, $responseFields)){
                // TODO FIXME: proper exception
                throw new ZfExtended_Exception('Response of termtagger did not contain the field "'.$field.'" for the segment ID '.$tags->getSegmentId());
            }
            $target->setTagsByText($responseFields[$field]->target);
        }
        $source = $tags->getSource();
        $source->setTagsByText($sourceText);
    }
    /**
     * In case of multiple target-fields in one segment, there are multiple responses for the same segment.
     * This function groups this different responses under the same segmentId
     *
     * @param array $responses
     * @return array
     */
    private function groupResponseById($responses) {
        $result = [];
        foreach ($responses as $response) {
            if(!array_key_exists($response->id, $result)){
                $result[$response->id] = [];
            }
            $result[$response->id][] = $response;
        }
        return $result;
    }
    /**
     *
     * @param array $responseGroup
     * @return array
     */
    private function groupResponseByField($responseGroup) {
        $result = [];
        foreach ($responseGroup as $fieldData) {
            $result[$fieldData->field] = $fieldData;
        }
        return $result;
    }
}
