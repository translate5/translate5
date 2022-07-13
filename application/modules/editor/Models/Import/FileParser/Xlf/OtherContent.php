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
 * Handles OtherContent (recognition and length calculation) on XLIFF import
 * OtherContent is content outside of MRK type seg tags in a segment containing such MRKs
 * In our initial interpretation of the XLIFF standard this should not be possible that there is content apart of whitespace and tags,
 * but some other systems don't care about that and create content inbetween / out of the segmented MRK content
 */
class editor_Models_Import_FileParser_Xlf_OtherContent {
    const T5_MRK_TAG = 't5:mrk-import';
    const OTC_MID_PREFIX = 'OTC-';

    /**
     * Container for plain text content in target tags
     * index for the first data is always 0, the others have the mid of the previuos MRK tag
     * @var editor_Models_Import_FileParser_Xlf_OtherContent_Data[]
     */
    protected array $otherContentTarget = [];

    /**
     * Container for plain text content in source tags
     * index for the first data is always 0, the others have the mid of the previuos MRK tag
     * @var editor_Models_Import_FileParser_Xlf_OtherContent_Data[]
     */
    protected array $otherContentSource = [];
    
    /**
     * Flag if we should preserve whitespace or not
     * @var boolean
     */
    protected bool $preserveWhitespace = false;
    
    /**
     * Flag if we should use source or target other content 
     * @var boolean
     */
    protected bool $useSource = false;
    
    /**
     * @var editor_Models_Import_FileParser_Xlf_ContentConverter
     */
    protected editor_Models_Import_FileParser_Xlf_ContentConverter $contentConverter;
    
    /**
     * @var editor_Models_Segment
     */
    protected editor_Models_Segment $segmentBareInstance;
    
    /**
     * @var editor_Models_Segment_Meta
     */
    protected editor_Models_Segment_Meta $segmentMetaBareInstance;
    
    /**
     * @var editor_Models_Task
     */
    protected editor_Models_Task $task;
    
    /**
     * @var int
     */
    protected int $fileId;
    
    /**
     * @var editor_Models_Import_FileParser_XmlParser
     */
    protected editor_Models_Import_FileParser_XmlParser $xmlparser;
    
    /**
     * contains the additional unit length 
     * @var integer
     */
    protected int $additionalUnitLength = 0;

    /**
     * @var int[]
     */
    private ?array $sourceElementBoundary = null;

    /**
     * @var int[]
     */
    private ?array $targetElementBoundary = null;

    private array $midsToBeImported = [];
    private array $orphanedTags = [];

    /**
     * Constructor
     * @param editor_Models_Import_FileParser_Xlf_ContentConverter $converter
     * @param editor_Models_Segment $segment
     * @param editor_Models_Task $task
     * @param int $fileId
     */
    public function __construct(editor_Models_Import_FileParser_Xlf_ContentConverter $converter, editor_Models_Segment $segment, editor_Models_Task $task, int $fileId) {
        $this->task = $task;
        $this->contentConverter = $converter;
        $this->segmentBareInstance = $segment;
        $this->segmentMetaBareInstance = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        $this->fileId = $fileId;
    }
    
    /**
     * inits othercontent class for each transunit on the start, inits containser for data gathering
     * @param editor_Models_Import_FileParser_XmlParser $parser
     */
    public function initOnUnitStart(editor_Models_Import_FileParser_XmlParser $parser) {
        //we init the xmlparser for each transunit from outside
        $this->xmlparser = $parser;
        $this->initSource(); //reset otherContent for new source
        $this->initTarget(); //reset otherContent for new target
        $this->additionalUnitLength = 0;
        $this->midsToBeImported = [];
    }
    
    /**
     * inits othercontent class for each transunit on the end of the unit, before processing starts
     * @param bool $useSource
     * @param bool $preserveWhitespace
     */
    public function initOnUnitEnd(bool $useSource, bool $preserveWhitespace) {
        $this->useSource = $useSource;
        $this->preserveWhitespace = $preserveWhitespace;

        //CRUCIAL - source must be called before target! due tag numbering in contentconverter
        $this->prepareContentPreserved(true);
        $this->prepareContentPreserved(false);
    }


    /**
     * Prepare the other contents with preserved whitespace, returning already split the convert content on MRK boundaries
     * @param bool $source
     */
    private function prepareContentPreserved(bool $source)
    {
        $data = $source ? $this->otherContentSource : $this->otherContentTarget;
        $containerBoundary = $source ? $this->sourceElementBoundary : $this->targetElementBoundary;

        if(empty($data) || empty($containerBoundary)) {
            return;
        }


        //in source always, and on target only if source empty
        $resetTagNumbers = $source || empty($this->otherContentSource);

        $concatContent = $this->xmlparser->join($this->convertBoundaryToContent($containerBoundary, $data));
        $content = $this->contentConverter->convert($concatContent, $resetTagNumbers, $this->preserveWhitespace);

        //add the other data container for the first content BEFORE the first MRK:
        $firstOtherContent = new editor_Models_Import_FileParser_Xlf_OtherContent_Data(0, $containerBoundary[0], reset($data)->startMrkIdx);
        if($source) {
            array_unshift($this->otherContentSource, $firstOtherContent);
            $data = $this->otherContentSource;
        }
        else {
            array_unshift($this->otherContentTarget, $firstOtherContent);
            $data = $this->otherContentTarget;
        }

        $content = $this->splitAtMrk($content);

        $this->checkAndPrepareContent($content, $data);
        $this->protectOrphanedTags($data);
        $this->fillOtherData($content, $data);
    }

    /**
     * Adds the editable other contents to the outer containers in XLF parser, so that they are imported
     * @param array $sourceProcessOrder
     * @param array $currentSource
     * @param array $currentTarget
     */
    public function injectEditableOtherContent(array &$sourceProcessOrder, array &$currentSource, array &$currentTarget) {
        $mids = array_unique($this->midsToBeImported);
        foreach($mids as $mid) {
            $newMid = self::OTC_MID_PREFIX.$mid;

            if(array_key_exists($mid, $this->otherContentSource)) {
                $currentSource[$newMid] = $this->otherContentSource[$mid];
            }
            if(array_key_exists($mid, $this->otherContentTarget)) {
                $currentTarget[$newMid] = $this->otherContentTarget[$mid];
            }

            // the first other content does not belong to an mid and must be always added as first segment
            if($mid === 0) {
                array_unshift($sourceProcessOrder, $newMid);
                continue;
            }

            //if the mrk mid exists in sourceProcessOrder, we add the other content behind it
            $found = array_search($mid, $sourceProcessOrder);
            if($found === false) {
                array_splice($sourceProcessOrder, $found, 0, $newMid);
                continue;
            }
            $sourceProcessOrder[] = $newMid;
        }
    }

    /**
     * Checks if the given MID belongs to an other content fragment
     * @param string $mid
     * @return bool
     */
    public function isOtherContent(string $mid): bool
    {
        return str_starts_with($mid, self::OTC_MID_PREFIX);
    }


    /**
     * Loop over the given array with mrk boundaries and convert them to text portions, adding temporary place holders for the MRKs itself
     * containing the content outside of the MRK boundaries
     * @param array $containerBoundary
     * @param array $boundaries
     * @return array
     */
    private function convertBoundaryToContent(array $containerBoundary, array $boundaries): array {
        $newSource = [];
        $otherContentStart = $containerBoundary[0];
        //the content between the source/target start tag and the first MRK is added to the beginning,
        // its the not MRK related additional unit content, so we use just index 0
        $indexToAdd = 0;

        foreach($boundaries as $mid => $boundary) {
            /** @var editor_Models_Import_FileParser_Xlf_OtherContent_Data $boundary */
            // on the first run through the loop the content between start of parent and MRK is added
            $newSource[$indexToAdd] = $this->xmlparser->getRange($otherContentStart + 1, $boundary->startMrkIdx - 1, true);
            $newSource[$indexToAdd] .= '<'.self::T5_MRK_TAG.' mid="'.$mid.'"/>';
            $indexToAdd = $mid;
            $otherContentStart = $boundary->endMrkIdx;
        }
        //add the content between the last MRK and the end of the parent segment
        $newSource[$indexToAdd] = $this->xmlparser->getRange($otherContentStart + 1, $containerBoundary[1] - 1, true);
        return $newSource;
    }

    /**
     * splits the converted content back as array
     * where the keys are the corresponding MRK IDs from the main otherContent array
     *
     * @param array $content
     * @return array
     */
    private function splitAtMrk(array $content): array {
        $mid = 0;
        $result = [];
        $current = [];
        $matches = [];
        $tags = [];
        $this->orphanedTags = [];
        foreach($content as $chunk) {
            //collect the mids of each tag
            if($chunk instanceof editor_Models_Import_FileParser_Tag) {
                $objHash = spl_object_hash($chunk);
                $tags[$objHash] = $mid;

                //if we get the second tag of a pair, and both are in different other content containers: <mrk /><g><mrk /></g>
                if(!is_null($chunk->partner) && ($partnerHash = spl_object_hash($chunk->partner)) && isset($tags[$partnerHash]) && $mid != $tags[$partnerHash]) {
                    // then they should be rendered as single tag
                    $chunk->setSingle();
                    $chunk->renderTag();
                    $chunk->partner->setSingle();
                    $chunk->partner->renderTag();
                    //collect them as orphaned tag with their mid for latter processing
                    $this->orphanedTags[$objHash] = ['tag' => $chunk, 'mid' => $mid];
                    $this->orphanedTags[$partnerHash] = ['tag' => $chunk->partner, 'mid' => $tags[$partnerHash]];
                }
            }

            if(preg_match('#^<'.self::T5_MRK_TAG.' mid="([^"]+)"/>$#', $chunk, $matches)) {
                // save collected chunks into a container
                $result[$mid] = $current;

                //define new container for next chunks
                $mid = $matches[1];
                $current = [];
                //we do not collect the T5_MRK_TAG itself
                continue;
            }
            $current[] = $chunk;
        }
        //at the end collect the rest
        $result[$mid] = $current;
        return $result;
    }
    
    /**
     * Inits the target other content with an empty array
     */
    public function initTarget() {
        $this->otherContentTarget = [];
    }
    
    /**
     * Inits the source other content with an empty array
     */
    public function initSource() {
        $this->otherContentSource = [];
    }

    /**
     * Stores the seg-source start and end idx internally, enables MRK outside content check
     * @param int $start
     * @param int $end
     */
    public function setSourceBoundary(int $start, int $end) {
        $this->sourceElementBoundary = [$start, $end];
    }

    /**
     * Stores the target start and end idx internally, enables MRK outside content check
     * @param int $start
     * @param int $end
     */
    public function setTargetBoundary(int $start, int $end) {
        $this->targetElementBoundary = [$start, $end];
    }

    /**
     * add a new other content value to a mid
     * @param string $mid
     * @param int $startIdx
     * @param int $endIdx
     */
    public function addTarget(string $mid, int $startIdx, int $endIdx) {
        $this->otherContentTarget[$mid] = new editor_Models_Import_FileParser_Xlf_OtherContent_Data($mid, $startIdx, $endIdx);
    }

    /**
     * add a new mrk boundary (mrk start/end index)
     * @param string $mid
     * @param int $startIdx
     * @param int $endIdx
     */
    public function addSource(string $mid, int $startIdx, int $endIdx) {
        $this->otherContentSource[$mid] = new editor_Models_Import_FileParser_Xlf_OtherContent_Data($mid, $startIdx, $endIdx);
    }

    /**
     * Adds the length of a ignored segment to the length calculation
     * @param array $content
     * @param editor_Models_Import_FileParser_SegmentAttributes $attributes
     */
    public function addIgnoredSegmentLength(array $content, editor_Models_Import_FileParser_SegmentAttributes $attributes) {
        //we have to convert the ignored content to translate5 content, otherwise the length would not be correct (for example content in <ph> tags would be counted then too)
        $contentLength = $this->segmentBareInstance->textLengthByImportattributes($this->xmlparser->join($content), $attributes, $this->task->getTaskGuid(), $this->fileId);
        $this->additionalUnitLength += $contentLength;
        
        //we add the additional mrk length of the ignored segment to the additionalUnitLength too
        $this->additionalUnitLength += $attributes->additionalMrkLength;
    }
    
    /**
     * calculates the additionalUnitLength for the whole transunit, needs at least a SegmentAttributes for several parameters
     *
     * The length of other content (whitespace and tags only outside/between mrk mtype seg tags) is also saved for length calculation
     * Assume the following <target>, where bef, betweenX and aft are assumed as whitespace
     * <target>bef<mrk>text 1</mrk>between1<mrk>text 2</mrk>between2<mrk>text 3</mrk>aft</target>
     *   the length of "bef", "between1", "between2" and "aft" is saved as "additionalUnitLength" to each segment
     *   the additionalUnitLength must then be only added once on each length calculation (where siblingData is used)
     * preserveWhitespace influences the otherContent:
     *   preserveWhitespace = true: length of otherContent is always the real length,
     *   preserveWhitespace = false: length of otherContent is always length of the condensed/padded whitespace between the MRK tags,
     * source and target MRK padding if MRKs are different in source vs target:
     *    if $useSourceOtherContent is true, this is no problem since there is no target to compare and add missing MRKs
     *    if its false and targetOtherContent is used: just use the target otherContent since padded target MRKs could
     *      not be edited and are not added as new MRKs in the target. So no otherContent must be considered here.
     *    This will change with implementing merging and splitting.
     * @param editor_Models_Import_FileParser_SegmentAttributes $attributes
     */
    public function updateAdditionalUnitLength(editor_Models_Import_FileParser_SegmentAttributes $attributes) {
        $otherContent = $this->useSource ? $this->otherContentSource : $this->otherContentTarget;
        $collectedContents = [];

        foreach($otherContent as $content) {
            //the contents which are imported can be ignored here, since the length comes from the segment then
            if($content->toBeImported || strlen($content->content) === 0) {
                continue;
            }
            $collectedContents[] = $content->content;
        }
        $collectedContents = join('', $collectedContents);
        if(strlen($collectedContents) > 0){
            //with the ability of editing content between MRKs and importing MRKs in a g tag pair,
            // the length is completely saved in $additionalUnitLength. The length per MRK is not filled anymore,
            // but the related code still remains for legacy tasks having there a value set
            $this->additionalUnitLength += $this->segmentBareInstance->textLengthByImportattributes($collectedContents, $attributes, $this->task->getTaskGuid(), $this->fileId);
            $this->segmentMetaBareInstance->updateAdditionalUnitLength($this->task->getTaskGuid(), $attributes->transunitId, $this->additionalUnitLength);
        }
    }
    
    /**
     * Merges the collected other contents with the placeholders of the saved segment content
     * @return string the placeholder string to be used in skeleton
     */
    public function mergeWithPlaceholders(array $placeHolders): string {
        // get the affected other content
        $otherContent = $this->useSource ? $this->otherContentSource : $this->otherContentTarget;
        $result = [];

        // merging with the placeholders
        // 1. pre-assumptions: the placeholders may contain only OTC-mrk or mrk- IDs. All other, like sub,
        //    are removed previously due different usage
        // 2. the order is given by the othercontent structure, so given placeholders are sorted to the other content
        foreach($otherContent as $mid => $data) {
            //we process always first the real MRKs placeholder
            $midsToTakeOver = [$mid];
            if($data->toBeImported) {
                //after that we take the OTC placeholder of the other content - if it was imported as segment
                $midsToTakeOver[] = self::OTC_MID_PREFIX.$mid;
            }

            //if there are usual MRK placeholders, add them after the othercontent chunks
            foreach($midsToTakeOver as $mid) {
                if(array_key_exists($mid, $placeHolders)){
                    $result[] = $placeHolders[$mid];
                    unset($placeHolders[$mid]);
                }
            }

            // if it was not processed as segment, we just take the original content - but after the real MRK (if any)
            if(!$data->toBeImported) {
                $result[] = $data->contentOriginal;
            }
        }

        // 3. merge and join all the collected data, add the remaining placeholders to the end
        return join(array_merge($result, $placeHolders));
    }

    /**
     * prepares whitespace on content inbetween mrk tags, only to be used with preserveWhitespace = false
     * @param string $content
     * @param bool $remove if true multiple whitespaces are removed, if false, they are condensed to one whitespace
     * @return string
     */
    protected function condenseWhitespace(string $content, bool $remove = false): string
    {
        return preg_replace('/(^[\s]+)|([\s]+$)/', $remove ? '' : ' ', $content);
    }

    /**
     * loops over content, checks if there is importable content and condense whitespace
     * stores the results in the data objects in $data
     *
     * @param array $content
     * @param array $data
     */
    private function checkAndPrepareContent(array $content, array $data)
    {
        $mids = array_keys($content);
        $lastIdx = end($mids);

        foreach ($content as $mid => $chunks) {
            // before the first and after the last mrk, whitespace should be removed (if not preserved)
            $removeWhitespace = ($mid === 0 || $lastIdx === $mid);
            //after condensing, check for content to be imported / could be also before...
            $toBeImported = false;

            foreach ($chunks as $idx => $chunk) {
                //ignore chunk if it is a tag
                if ($chunk instanceof editor_Models_Import_FileParser_Tag || str_starts_with($chunk, '<') && str_ends_with($chunk, '>')) {
                    continue;
                }
                if (!$this->preserveWhitespace) {
                    //the whitespace between MRKs should be condensed to one whitespace,
                    $chunks[$idx] = $chunk = $this->condenseWhitespace($chunk, $removeWhitespace);
                }
                //if remaining chunk is containing other content as whitespace, add it to the import list
                if (!$toBeImported && strlen($chunk) > 0 && preg_match('/[^\s]+/', $chunk)) {
                    //collect the mids to be imported for further processing
                    $toBeImported = true;
                    $this->midsToBeImported[] = $mid;
                }
            }

            $data[$mid]->toBeImported = $toBeImported;
            $data[$mid]->content = $this->xmlparser->join($chunks);
            $data[$mid]->contentChunks = [];
            $data[$mid]->contentChunksOriginal = [];
        }
    }

    /**
     * @param array $data
     */
    private function protectOrphanedTags(array $data)
    {
        foreach ($this->orphanedTags as $orphan) {
            /** @var editor_Models_Import_FileParser_Tag $tag */
            $tag = $orphan['tag'];
            $mid = $orphan['mid'];
            //ignore real single tags or if whole content is going to be imported anyway
            if (is_null($tag->partner) || $data[$mid]->toBeImported) {
                continue;
            }
            $partnerHash = spl_object_hash($tag->partner);
            $partnerMid = $this->orphanedTags[$partnerHash]['mid'] ?? null;

            //if partner mid is be imported (but me not)
            if ($data[$partnerMid]->toBeImported ?? false) {
                // convert me to a single tag placeholder to be stored in the skeleton for later restoring on export
                // otherwise for example a </g> may be stored in the SKEL (the <g> is in the imported segment) which
                // would be invalid XML then
                $tag->originalContent = '<t5:placeholder data-content="' . base64_encode($tag->originalContent) . '" />';
            }
        }
    }

    /**
     * @param array $content
     * @param array $data
     */
    private function fillOtherData(array $content, array $data): void
    {
        foreach ($content as $mid => $chunks) {
            foreach ($chunks as $chunk) {
                if ($chunk instanceof editor_Models_Import_FileParser_Tag) {
                    $data[$mid]->contentChunks[] = $chunk->__toString();
                    $data[$mid]->contentChunksOriginal[] = $chunk->originalContent;
                } else {
                    $data[$mid]->contentChunks[] = $chunk;
                    $data[$mid]->contentChunksOriginal[] = $chunk;
                }
                $data[$mid]->contentOriginal = $this->xmlparser->join($data[$mid]->contentChunksOriginal);
            }
        }
    }
}

class editor_Models_Import_FileParser_Xlf_OtherContent_Data {
    public string $mid;
    public int $startMrkIdx;
    public int $endMrkIdx;

    /**
     * Contains the content as string with internal tags,
     *  with or without preserved whitespace, depending on the same name flag,
     * @var string
     */
    public string $content = '';

    /**
     * Contains the above content as chunks - so no reparse is needed
     * @var array
     */
    public array $contentChunks = [];

    /**
     * Contains the above content as original chunks - so no reparse is needed
     *  original means: internal tags are converted back - but all after the multiple whitespaces were condensed
     * @var array
     */
    public array $contentChunksOriginal = [];

    /**
     * Contains the above content as original content in one string
     * @var string
     */
    public string $contentOriginal = '';

    /**
     * Flag if current element should be imported
     * @var bool
     */
    public bool $toBeImported = false;

    /**
     * @param string $mid
     * @param int $startIdx
     * @param int $endIdx
     */
    public function __construct(string $mid, int $startIdx, int $endIdx) {
        $this->mid = $mid;
        $this->startMrkIdx = $startIdx;
        $this->endMrkIdx = $endIdx;
    }
}