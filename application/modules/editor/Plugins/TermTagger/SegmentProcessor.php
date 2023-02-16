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
 * TODO FIXME: Rework the tag-handling code to use the modern OOP TagSequence-API instead of regex-based tag-helpers
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
                case editor_Models_Terminology_Models_TermModel::TRANSSTAT_NOT_FOUND:
                    if($isSourceField){
                        return editor_Plugins_TermTagger_QualityProvider::NOT_FOUND_IN_TARGET;
                    }
                    break;
                    
                case editor_Models_Terminology_Models_TermModel::TRANSSTAT_NOT_DEFINED:
                    if($isSourceField){
                        return editor_Plugins_TermTagger_QualityProvider::NOT_DEFINED_IN_TARGET;
                    }
                    break;
                    
                case editor_Models_Terminology_Models_TermModel::STAT_SUPERSEDED:
                case editor_Models_Terminology_Models_TermModel::STAT_DEPRECATED:
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
    private editor_Models_Task $task;

    /**
     * @var editor_Plugins_TermTagger_Configuration
     */
    private editor_Plugins_TermTagger_Configuration $config;

    /**
     * @var ZfExtended_Logger
     */
    private ZfExtended_Logger $logger;

    /**
     * @var string
     */
    private string $processingMode;

    /**
     * @var boolean
     */
    private bool $isWorkerThread;

    /**
     * @var editor_Plugins_TermTagger_Service
     */
    private editor_Plugins_TermTagger_Service $termtaggerService;

    /**
     * @var editor_Plugins_TermTagger_Service_Data
     */
    private editor_Plugins_TermTagger_Service_Data $serviceData;

    /**
     * @var editor_Plugins_TermTagger_RecalcTransFound
     */
    private editor_Plugins_TermTagger_RecalcTransFound $recalcTransFound;

    /**
     * @var editor_Models_Segment_TermTag
     */
    protected editor_Models_Segment_TermTag $termTagHelper;

    /**
     * @var editor_Models_Segment_TermTagTrackChange
     */
    protected editor_Models_Segment_TermTagTrackChange $termTagTrackChangeHelper;

    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected editor_Models_Segment_InternalTag $internalTagHelper;

    /**
     * @var editor_Models_Segment_TrackChangeTag
     */
    protected editor_Models_Segment_TrackChangeTag $generalTrackChangesHelper;

    /**
     * Two corresponding arrays to hold replaced tags.
     * Tags must be replaced in every text-element before send to the TermTagger-Server,
     * because TermTagger can not handle with already TermTagged-text.
     */
    private array $replacedTagsNeedles = [];
    private array $replacedTagsReplacements = [];

    /**
     * Holds a counter for replacedTags to make needles unique
     * @var integer
     */
    private int $replaceCounter = 1;

    /**
     * Container for segment data needed before and after tagging
     * @var array
     */
    private array $segments = [];
    
    public function __construct(editor_Models_Task $task, editor_Plugins_TermTagger_Configuration $config, string $processingMode, bool $isWorkerThread){
        $this->task = $task;
        $this->config = $config;
        $this->processingMode = $processingMode;
        $this->isWorkerThread = $isWorkerThread;
        $this->logger = Zend_Registry::get('logger')->cloneMe(editor_Plugins_TermTagger_Configuration::getLoggerDomain($processingMode));
        $this->termtaggerService = editor_Plugins_TermTagger_Bootstrap::createService('termtagger');
        $this->recalcTransFound = ZfExtended_Factory::get(editor_Plugins_TermTagger_RecalcTransFound::class, [ $this->task ]);
        // various outdated tag-helpers - use TagSequence/FieldTags based code instead
        $this->termTagHelper = ZfExtended_Factory::get(editor_Models_Segment_TermTag::class);
        $this->internalTagHelper = ZfExtended_Factory::get(editor_Models_Segment_InternalTag::class);
        $this->termTagTrackChangeHelper = ZfExtended_Factory::get(editor_Models_Segment_TermTagTrackChange::class);
        $this->generalTrackChangesHelper = ZfExtended_Factory::get(editor_Models_Segment_TrackChangeTag::class);
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
        // creating the service-data model used by the termtagger-service
        $this->serviceData = $this->createServiceData($segmentsTags);
        // check TBX hash
        $this->checkTermTaggerTbx($slot, $this->serviceData->tbxFile);
        // request our service / tag the terms
        $result = $this->tagTerms($slot);
        // marks terms in the source-tags with transFound, if a translation is present in the target
        $taggedSegments = $this->markTransFound($result->segments);

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
     * @return editor_Plugins_TermTagger_Service_Data
     */
    public function getCommunicationsService(){
        return $this->serviceData;
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

    /**
     * Creates the server communication data-model for the current task and the given segment-tags
     * @param editor_Segment_Tags[] $segmentsTags
     * @return editor_Plugins_TermTagger_Service_Data
     */
    private function createServiceData(array $segmentsTags) : editor_Plugins_TermTagger_Service_Data {

        $serviceData = ZfExtended_Factory::get(editor_Plugins_TermTagger_Service_Data::class, array($this->task));

        foreach ($segmentsTags as $tags) { /* @var $tags editor_Segment_Tags */

            // should not happen but who knows in which processingMode the tags have been generated
            if(!$tags->hasSource()){
                throw new ZfExtended_Exception('Passed segment tags did not contain a source '.$tags->getSegmentId());
            }

            // this is somehow "doppelt gemoppelt"
            $typesToExclude = [editor_Plugins_TermTagger_QualityProvider::qualityType()];

            $source = $tags->getSource();
            $sourceText = $source->render();
            $firstTargetText = null;

            foreach($tags->getTargets() as $target) { /* @var $target editor_Segment_FieldTags */

                $targetText = $target->render($typesToExclude);
                $serviceData->addSegment($target->getSegmentId(), $target->getTermtaggerName(), $sourceText, $targetText);
                if($firstTargetText === null){
                    $firstTargetText = $targetText;
                }
            }
            if($tags->hasOriginalSource()){
                $sourceOriginal = $tags->getOriginalSource();
                $serviceData->addSegment($sourceOriginal->getSegmentId(), $sourceOriginal->getTermtaggerName(), $sourceOriginal->render($typesToExclude), $firstTargetText);
            }
        }
        return $serviceData;
    }


    /**
     * tag the terms via the communication & termtagger services
     * @return Zend_Http_Response or null on error
     */
    private function tagTerms(string $url) {  
        // encode the segments
        $this->encodeSegments();
        $timeout = $this->config->getRequestTimeout($this->isWorkerThread);
        // tag with the termtagger-service
        $segmentsData = $this->termtaggerService->tagTerms($url, $this->serviceData, $this->logger, $timeout);
        // decode the result
        return $this->decodeSegments($segmentsData);
    }

    /**
     * replaces ihe internal tags with img placeholders since the termtagger cannot deal with tags but with imgs
     * CRUCIAL: The img-tags meed to have a blank before the closing- marker like <img src="..." /> otherwise termtagger crashes
     */
    private function encodeSegments() {
        foreach ($this->serviceData->segments as &$segment) {
            $segment->source = $this->encodeSegment($segment, 'source');
            $segment->target = $this->encodeSegment($segment, 'target');
        }
    }

    /**
     * restores our internal tags from the delivered img tags
     *
     * @param stdClass $data
     * @param editor_Plugins_TermTagger_Service_Data $requests
     * @return stdClass
     */
    private function decodeSegments(stdClass $data) {
        foreach ($data->segments as &$segment) {
            $segment->source = $this->decodeSegment($segment, 'source');
            $segment->target = $this->decodeSegment($segment, 'target');
        }
        return $data;
    }

    private function encodeSegment(stdClass $segment, string $field): string
    {
        $trackChangeTag = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        /* @var $trackChangeTag editor_Models_Segment_TrackChangeTag */

        $text = $segment->$field;
        $matchContentRegExp = '/<div[^>]+class="(open|close|single).*?".*?\/div>/is';
        $tempMatches = [];
        preg_match_all($matchContentRegExp, $text, $tempMatches);

        foreach ($tempMatches[0] as $match) {
            $needle = '<img class="content-tag" src="'.$this->replaceCounter++.'" alt="TaggingError" />';
            $this->replacedTagsNeedles[] = $needle;
            $this->replacedTagsReplacements[] = $match;

            $text = str_replace($match, $needle, $text);
        }
        $text = preg_replace('/<div[^>]+>/is', '', $text);
        $text = preg_replace('/<\/div>/', '', $text);

        //protecting trackChanges del tags
        $text = $trackChangeTag->protect($text);
        //store the text with track changes
        $trackChangeTag->textWithTrackChanges = $text;
        $this->segments[$this->createUniqueKey($segment, $field)] = $trackChangeTag; //we have to store one instance per segment since it contains specific data for recreation

        // Now remove the stored TrackChange-Nodes from the text for termtagging (with the general helper to keep the original tags inside the specific instance)
        return $this->generalTrackChangesHelper->removeTrackChanges($text);
    }

    private function decodeSegment(stdClass $segment, string $field): string
    {
        $text = $segment->$field;
        if(empty($text) && $text !== '0') {
            return $text;
        }
        //fix TRANSLATE-713
        $text = str_replace('term-STAT_NOT_FOUND', 'term STAT_NOT_FOUND', $text);

        $trackChangeTag = $this->segments[$this->createUniqueKey($segment, $field)];
        /* @var $trackChangeTag editor_Models_Segment_TrackChangeTag */

        // remerge trackchanges and terms - don't do it if there were no INS/DEL!
        // TODO FIXME: if you do the above there are problems with trackchanges tags getting lost ...
        // if($trackChangeTag->hasOriginalTags()){
        if(true){
            $text = $this->termTagTrackChangeHelper->mergeTermsAndTrackChanges($text, $trackChangeTag->textWithTrackChanges);
            //check if content is valid XML, or if textual content has changed
            $oldFlagValue = libxml_use_internal_errors(true);
            // delete tags and internal tags are masked, thats ok for the check here
            $invalidXml = ! @simplexml_load_string('<container>'.$text.'</container>');
            libxml_use_internal_errors($oldFlagValue);
            $textNotEqual = strip_tags($text) !== strip_tags($segment->$field);
            if($invalidXml || $textNotEqual) {
                $this->logger->warn('E1132', 'Conflict in merging terminology and track changes: "{type}".', [
                    'type' => ($invalidXml?'Invalid XML,':'').($textNotEqual?' text changed by merge':''),
                    'task' => $this->serviceData->task,
                    'segmentId' => $segment->id,
                    'inputFromBrowser' => $trackChangeTag->unprotect($trackChangeTag->textWithTrackChanges),
                    'termTaggerResult' => $segment->$field,
                    'mergedResult' => $text,
                ]);
            }
            $text = $trackChangeTag->unprotect($text);
        }
        if (empty($this->replacedTagsNeedles)) {
            return $text;
        }
        $text = preg_replace('"&lt;img class=&quot;content-tag&quot; src=&quot;(\d+)&quot; alt=&quot;TaggingError&quot; /&gt;"', '<img class="content-tag" src="\\1" alt="TaggingError" />', $text);
        $text = str_replace($this->replacedTagsNeedles, $this->replacedTagsReplacements, $text);

        return $text;
    }

    /**
     * Creates a unique key to use as an array key to identify an encoded segment
     * @param stdClass $segment
     * @param string $field
     * @return string
     */
    private function createUniqueKey(stdClass $segment, string $field) : string
    {
        return $segment->field.'-'.$segment->id.'-'.$field;
    }

    /**
     * marks terms in the source with transFound, if translation is present in the target
     * and with transNotFound if not. A translation which is of type
     * editor_Models_Terminology_Models_TermModel::STAT_DEPRECATED or editor_Models_Terminology_Models_TermModel::STAT_SUPERSEDED
     * is handled as transNotFound
     *
     * @param array $segments array of stdClass. example: array(object(stdClass)#529 (4) {
     * @return array $segments
     */
    public function markTransFound(array $segments): array
    {
        /*
            ["field"] => string(10) "targetEdit"
            ["id"] => string(7) "4596006"
            ["source"] => string(35) "Die neue VORTEILE Motorenbroschüre"
            ["target"] => string(149) "Il nuovo dépliant PRODUCT INFO <div title="" class="term admittedTerm transNotFound stemmed" data-tbxid="term_00_1_IT_1_08795">motori</div>"),
            another object, ...
         */
        return $this->recalcTransFound->recalcList($segments);
    }

    /**
     * Checks if tbx-file with hash $tbxHash is loaded on the TermTagger-server behind $url.
     * If not already loaded, tries to load the tbx-file from the task.
     * Throws Exceptions if TBX could not be loaded!
     * @param string $url
     * @param string|null $tbxHash
     * @throws editor_Plugins_TermTagger_Exception_Abstract
     * @throws editor_Plugins_TermTagger_Exception_Open
     */
    private function checkTermTaggerTbx(string $url, ?string &$tbxHash)
    {
        try {
            // test if tbx-file is already loaded
            if (!empty($tbxHash) && $this->termtaggerService->ping($url, $tbxHash)) {
                return;
            }
            // getDataTbx also creates the TbxHash
            $tbx = $this->getTbxData();
            $tbxHash = $this->task->meta()->getTbxHash();
            $this->termtaggerService->loadTBX($url, $tbxHash, $tbx, $this->logger);
        }
        catch (editor_Plugins_TermTagger_Exception_Abstract $e) {
            $e->addExtraData([
                'task' => $this->task,
                'termTaggerUrl' => $url,
            ]);
            throw $e;
        }
    }
    /**
     * returns the TBX string to be loaded into the termtagger
     * @throws editor_Plugins_TermTagger_Exception_Open
     * @return string
     */
    private function getTbxData()
    {
        // try to load tbx-file to the TermTagger-server
        $tbxFileInfo = new SplFileInfo(editor_Models_Import_TermListParser_Tbx::getTbxPath($this->task));
        $tbxParser = ZfExtended_Factory::get(editor_Models_Import_TermListParser_Tbx::class);
        try {
            return $tbxParser->assertTbxExists($this->task, $tbxFileInfo);
        }
        catch (editor_Models_Term_TbxCreationException $e){
            //'E1116' => 'Could not load TBX into TermTagger: TBX hash is empty.',
            throw new editor_Plugins_TermTagger_Exception_Open('E1116', [], $e);
        }
    }
}
