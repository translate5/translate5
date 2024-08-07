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

use editor_Models_Segment_TrackChangeTag as TrackChangeTag;
use MittagQI\ZfExtended\Tools\Markup;

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 *
 * /**
 */
class editor_Models_Import_FileParser_Sdlxliff_TransunitParser
{
    /**
     * The collected mrk source tags of one transunit
     * @var array
     */
    protected $sourceEmptyMrkTags = [];

    /**
     * The collected mrk source content of one transunit
     * @var array
     */
    protected $sourceMrkContent = [];

    /**
     * The collected mrk target content of one transunit
     * @var array
     */
    protected $targetMrkContent = [];

    /**
     * The collected mrk target position indizes in the transunit
     * @var array
     */
    protected $targetMrkChunkIndex = [];

    /**
     * @var editor_Models_Import_FileParser_XmlParser
     */
    protected $xmlparser = [];

    /**
     * Marks if the target was empty
     * @var boolean
     */
    protected $wasEmptyTarget = false;

    /**
     * counts the other content chunks
     * @var boolean
     */
    protected $countOtherContent = 0;

    /**
     * collected comment references of one segment
     * @var array
     */
    protected $comments = [];

    /**
     * collected comments one transUnit
     * @var array
     */
    protected $unitComments = [];

    /**
     * Some chunks must be removed for segment saving but restored for skeleton saving, such chunks are saved here
     * @var array
     */
    protected $maskedSourceChunks = [];

    /**
     * Transuntit ID
     * @var string
     */
    protected $transunitId = null;

    private editor_Models_TaskUserTracking $taskUserTracking;

    private bool $useStrictEscaping;

    /**
     * @param array<string, array{id: string, taskOpenerNumber: string}> $authorToTrackChangeIdAndNr
     */
    public function __construct(
        private Zend_Config $config,
        private editor_Models_Task $task,
        private bool $isTrackChangesActive,
        private array $authorToTrackChangeIdAndNr = []
    ) {
        $this->xmlparser = ZfExtended_Factory::get(editor_Models_Import_FileParser_XmlParser::class);
        $this->taskUserTracking = ZfExtended_Factory::get(editor_Models_TaskUserTracking::class);
        // experimental config: strict escaping for the import stream
        $this->useStrictEscaping = $config->runtimeOptions->segment->useStrictEscaping;
    }

    protected function init()
    {
        $this->wasEmptyTarget = false;
        $this->sourceEmptyMrkTags = [];
        $this->sourceMrkContent = [];
        $this->targetMrkContent = [];
        $this->targetMrkChunkIndex = [];
        $this->comments = [];
        $this->unitComments = [];
        $this->unitComments[editor_Models_Import_FileParser_Sdlxliff::TRANS_UNIT] = [];
        $this->countOtherContent = 0;
        $this->maskedSourceChunks = [];
    }

    /**
     * Parse the given SDLXLIFF transunit, gets the needed data and returns the transunit with placeholders
     * @param array<string, array{author: string, date: DateTime, workflowStep: string}> $revIdToUserDataMap
     */
    public function parse(string $transUnit, callable $segmentSaver, array $revIdToUserDataMap): string
    {
        $this->init();
        $transUnit = $this->handleEmptyTarget($transUnit);

        $this->registerTrackChangesHandler($revIdToUserDataMap);
        $this->initMrkHandler();
        $this->transunitId = null;

        //parse the trans-unit
        //trigger segment save on the end of an transunit
        $this->xmlparser->registerElement(
            'trans-unit',
            null,
            function ($tag, $key, $opener) use (&$transUnit, $segmentSaver) {
                if (empty($this->sourceMrkContent)) {
                    //without any source mrk tag we can do nothing
                    return;
                }

                //if there were no target mrks, we have to insert them into the skeleton file
                if (empty($this->targetMrkContent)) {
                    //add them into the transUnit and in the skeleton file
                    $transUnit = str_replace('</target>', join('', $this->sourceEmptyMrkTags) . '</target>', $transUnit);
                }
                // exception if source and target segment count does not match
                elseif (count($this->sourceMrkContent) !== count($this->targetMrkContent)) {
                    throw new editor_Models_Import_FileParser_Sdlxliff_Exception('E1009', [
                        'filename' => $this->_fileName ?? 'not provided',
                        'task' => $this->task ?? 'not provided',
                        'transunit' => $transUnit,
                    ]);
                }

                $this->transunitId = $this->xmlparser->getAttribute($opener['attributes'], 'id');

                // get tranUnit level comments
                $transUnitComments = $this->unitComments[editor_Models_Import_FileParser_Sdlxliff::TRANS_UNIT] ?? [];

                //in the old parser, the mid's of source and target mrks were not compared, so we do not that here either:
                $mrkMids = array_keys($this->sourceMrkContent);
                $this->sourceMrkContent = array_values($this->sourceMrkContent);
                $this->targetMrkContent = array_values($this->targetMrkContent);
                $this->targetMrkChunkIndex = array_values($this->targetMrkChunkIndex);

                //we loop over the found mrk MIDs and save the according content and get the placeholder
                foreach ($mrkMids as $idx => $mid) {
                    $sourceContent = $this->sourceMrkContent[$idx];
                    $targetContent = $this->targetMrkContent[$idx] ?? null;

                    // merge the found comments in the unitComments array (mapped per segment) and the transunit comments
                    // which are on transunit level and are not mapped to any segment
                    $unitComments = array_merge($this->unitComments[$mid] ?? [], $transUnitComments);

                    if ($this->wasEmptyTarget || empty($targetContent) && $targetContent !== "0") {
                        $placeHolder = $segmentSaver($mid, $sourceContent, null, $unitComments);
                    } else {
                        $placeHolder = $segmentSaver($mid, $sourceContent, $targetContent, $unitComments);
                    }

                    // If no placeholder was generated, continue to the next iteration
                    if (is_null($placeHolder)) {
                        continue;
                    }

                    $startMrk = $this->targetMrkChunkIndex[$idx][0];
                    $endMrk = $this->targetMrkChunkIndex[$idx][1];

                    //add the placeholders to the transunit:
                    //empty mrk was a single tag:
                    if ($startMrk === $endMrk) {
                        //add the end </mrk> tag to the placeholder and replace itself with the new placeholder
                        $placeHolder = preg_replace('#[\s]*/>$#', '>', $this->xmlparser->getChunk($startMrk))
                            . $placeHolder . '</mrk>';
                        $this->xmlparser->replaceChunk($startMrk, $placeHolder);

                        continue;
                    }

                    //normally a mrk has a start and an end tag
                    //add the end </mrk> tag to the placeholder and replace itself with the new placeholder
                    $placeHolder .= $this->xmlparser->getChunk($endMrk);
                    $this->xmlparser->replaceChunk($endMrk, $placeHolder);

                    //remove the original content
                    $this->xmlparser->replaceChunk($startMrk + 1, '', $endMrk - $startMrk - 1);
                }
                //restore chunks removed for parsing, but needed for skeleton
                foreach ($this->maskedSourceChunks as $key => $chunk) {
                    $this->xmlparser->replaceChunk($key, $chunk);
                }
            }
        );

        return $this->xmlparser->parse($transUnit);
    }

    protected function initMrkHandler(): void
    {
        $this->xmlparser->registerElement(
            'trans-unit > target mrk[mtype=x-sdl-comment]',
            function ($tag, $attr, $key) {
                //we have to remove the comment mrks, otherwise they are translated to internal reference internal tags,
                // which then mess up the TM
                $this->xmlparser->replaceChunk($key, '');
                $commentId = $this->xmlparser->getAttribute($attr, 'sdl:cid');
                // we collect the comment IDs and add a text container for the selected content there:
                $this->comments[$commentId] = [
                    'text' => [],
                    'field' => editor_Models_Import_FileParser_Sdlxliff::TARGET,
                ];
            },
            function ($tag, $key, $opener) {
                $this->xmlparser->replaceChunk($key, '');
            }
        );

        $this->xmlparser->registerElement(
            'trans-unit > seg-source mrk[mtype=x-sdl-comment]',
            function ($tag, $attr, $key) {
                //restore comments later only if import comments is enabled
                if ($this->config->runtimeOptions->import->sdlxliff->importComments) {
                    $this->maskedSourceChunks[$key] = $this->xmlparser->getChunk($key);
                }
                $this->xmlparser->replaceChunk($key, '');
                $commentId = $this->xmlparser->getAttribute($attr, 'sdl:cid');
                // we collect the comment IDs and add a text container for the selected content there:
                $this->comments[$commentId] = [
                    'text' => [],
                    'field' => editor_Models_Import_FileParser_Sdlxliff::SOURCE,
                ];
            },
            function ($tag, $key, $opener) {
                //restore comments later only if import comments is enabled
                if ($this->config->runtimeOptions->import->sdlxliff->importComments) {
                    $this->maskedSourceChunks[$key] = $this->xmlparser->getChunk($key);
                }
                $this->xmlparser->replaceChunk($key, '');
            }
        );

        $this->xmlparser->registerOther(function ($other, $key) {
            //if other is empty or is deleted text we do not count and track it
            if ((empty($other) && $other !== "0") || $this->xmlparser->getParent('mrk[mtype=x-sdl-deleted]')) {
                return;
            }
            // we count the chunks of othercontent inside the mtype="seg" mrk.
            if ($this->xmlparser->getParent('target mrk[mtype=seg]')) {
                $this->countOtherContent++;
            }

            //mrk[mtype=x-sdl-comment] can be nested
            $parentsTarget = $this->xmlparser->getParents('target mrk[mtype=x-sdl-comment]');
            $parentsSource = $this->xmlparser->getParents('seg-source mrk[mtype=x-sdl-comment]');
            $parents = array_merge($parentsTarget, $parentsSource);
            foreach ($parents as $parent) {
                $commentId = $this->xmlparser->getAttribute($parent['attributes'], 'sdl:cid');
                $this->comments[$commentId]['text'][] = $other;
            }
        });

        //Start segment mrk mtype="seg" handler
        $this->xmlparser->registerElement('trans-unit > target mrk[mtype=seg]', null, function ($tag, $key, $opener) {
            //reset the other content counter when we enter a segment
            $mid = $this->xmlparser->getAttribute($opener['attributes'], 'mid');
            $this->targetMrkChunkIndex[$mid] = [$opener['openerKey'], $key];
            $this->targetMrkContent[$mid] = $this->getXmlParserRange($opener['openerKey'] + 1, $key - 1);
            foreach ($this->comments as $key => $comment) {
                //we have to find out if the comment was for the whole segment or only a part of it
                if (count($comment['text']) == $this->countOtherContent) {
                    //we set the comment to true, that means comment on whole segment not only some word(s)
                    $this->comments[$key]['text'] = true;
                }
            }
            $this->unitComments[$mid] = $this->comments;
            $this->comments = [];
            $this->countOtherContent = 0; //we have to reset the otherContent counter on the end of each seg mrk
        });

        //end segment mrk mtype="seg" handler
        $this->xmlparser->registerElement(
            'trans-unit > seg-source mrk[mtype=seg]',
            null,
            function ($tag, $key, $opener) {
                $mid = $this->xmlparser->getAttribute($opener['attributes'], 'mid');
                $this->sourceEmptyMrkTags[$mid] = $this->xmlparser->getChunk($opener['openerKey'])
                    . $this->xmlparser->getChunk($key);
                $this->sourceMrkContent[$mid] = $this->getXmlParserRange($opener['openerKey'] + 1, $key - 1);
                $this->countOtherContent = 0; //we have to reset the otherContent counter on the end of each seg mrk
            }
        );

        // Collect all transunit comments from the current transUnit and store the collected comments in $unitComments
        // array under specific array key. Here is an example how this transUnit looks like and where the comment is
        // located (sdl:cmt element)
        //
        //      <trans-unit id="193e8534-644c-461d-af1a-18439f614428">
        //          <source>Startdatum muss vor dem Enddatum liegen</source>
        //          <seg-source>
        //              <mrk mtype="seg" mid="792">Startdatum muss vor dem Enddatum liegen</mrk>
        //          </seg-source>
        //          <target>
        //              <mrk mtype="seg" mid="792">La fecha de inicio debe ser anterior a la fecha de finalización</mrk>
        //          </target>
        //          <sdl:seg-defs>
        //                  <sdl:seg id="792" locked="true" conf="ApprovedSignOff">
        //                  <sdl:rep id="imitYwuCUK0gAxJ42cYr14syF0Y="/>
        //                  <sdl:value key="SegmentIdentityHash">imitYwuCUK0gAxJ42cYr14syF0Y=</sdl:value>
        //                  <sdl:value key="SDL:OriginalTranslationHash">678336267</sdl:value>
        //              </sdl:seg>
        //          </sdl:seg-defs>
        //          <sdl:cmt id="8bb03318-b551-46e8-9420-b4efa869e319"/>
        //      </trans-unit>
        $this->xmlparser->registerElement('trans-unit > sdl:cmt', null, function ($tag, $key, $opener) {
            $commentId = $this->xmlparser->getAttribute($opener['attributes'], 'id');
            // store the element
            $this->unitComments[editor_Models_Import_FileParser_Sdlxliff::TRANS_UNIT][$commentId] = [
                'field' => editor_Models_Import_FileParser_Sdlxliff::TRANS_UNIT,
            ];
        });
    }

    /**
     * Retrieves a range as string from the XML-parser. If configured, the content (not the tags!) will be escaped
     */
    public function getXmlParserRange(int $startOffset, int $endOffset): string
    {
        $rangeContent = $this->xmlparser->getRange($startOffset, $endOffset, true);
        // experimental feature: Strict escaping for the segment input stream
        if ($this->useStrictEscaping) {
            return Markup::escapeImport($rangeContent);
        }

        return $rangeContent;
    }

    /**
     * @param array<string, array{author: string, date: DateTime, workflowStep: string}> $revIdToUserDataMap
     */
    private function registerTrackChangesHandler(array $revIdToUserDataMap)
    {
        if ($this->isTrackChangesActive) {
            $this->xmlparser->registerElement(
                'trans-unit g[sdl:end=false]',
                closer: function ($tag, $key, $opener) {
                    if (! $opener['isSingle']) {
                        $this->xmlparser->replaceChunk($key, '');
                    }
                }
            );
            $this->xmlparser->registerElement(
                'trans-unit g[sdl:start=false]',
                closer: function ($tag, $key, $opener) {
                    if (! $opener['isSingle']) {
                        $this->xmlparser->replaceChunk($opener['openerKey'], '');
                    }
                }
            );

            //replace track changes mrk with our tags
            $this->xmlparser->registerElement(
                'trans-unit mrk[mtype=x-sdl-added]',
                fn ($tag, $attr, $key) => $this->xmlparser->replaceChunk($key, ''),
                fn ($tag, $key, $opener) => $this->replaceTrackChangeTag($key, $opener, true, $revIdToUserDataMap)
            );
            $this->xmlparser->registerElement(
                'trans-unit mrk[mtype=x-sdl-deleted]',
                fn ($tag, $attr, $key) => $this->xmlparser->replaceChunk($key, ''),
                fn ($tag, $key, $opener) => $this->replaceTrackChangeTag($key, $opener, false, $revIdToUserDataMap)
            );

            return;
        }

        // If TrackChanges Plugin inactive:
        // remove sdl-added mrks, but leave the content
        $this->xmlparser->registerElement(
            'trans-unit mrk[mtype=x-sdl-added]',
            fn ($tag, $attr, $key) => $this->xmlparser->replaceChunk($key, ''),
            fn ($tag, $key) => $this->xmlparser->replaceChunk($key, '')
        );

        // remove sdl-deleted mrks and the content
        $this->xmlparser->registerElement(
            'trans-unit mrk[mtype=x-sdl-deleted]',
            //do not process the content of a deleted tag
            fn () => $this->xmlparser->disableHandlersUntilEndtag(),
            //remove the deleted tag and its content
            fn ($tag, $key, $opener) => $this->xmlparser->replaceChunk(
                $opener['openerKey'],
                '',
                $key - $opener['openerKey'] + 1
            )
        );
    }

    /**
     * @param array<string, array{author: string, date: DateTime, workflowStep: string}> $revIdToUserDataMap
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    private function replaceTrackChangeTag(int $key, array $opener, bool $ins, array $revIdToUserDataMap): void
    {
        $authorData = $revIdToUserDataMap[$opener['attributes']['sdl:revid']];
        $author = $authorData['author'];

        if (! isset($this->authorToTrackChangeIdAndNr[$author])) {
            $this->authorToTrackChangeIdAndNr[$author] = $this->taskUserTracking->createTaskUserTrackingEntry(
                editor_Models_TaskUserTrackingDto::fromUsername($this->task->getTaskGuid(), $author)
            );
        }

        $nodeName = $ins ? TrackChangeTag::NODE_NAME_INS : TrackChangeTag::NODE_NAME_DEL;

        $trackChangeTag = new TrackChangeTag();
        $trackChangeTag->userColorNr = $this->authorToTrackChangeIdAndNr[$author]['taskOpenerNumber'];
        $trackChangeTag->userTrackingId = $this->authorToTrackChangeIdAndNr[$author]['id'];
        $trackChangeTag->attributeWorkflowstep = $authorData['workflowStep'];

        $this->xmlparser->replaceChunk(
            $opener['openerKey'],
            $trackChangeTag->createTrackChangeNodeOpener($nodeName, $authorData['date'])
        );
        $this->xmlparser->replaceChunk($key, "</$nodeName>");
    }

    /**
     * if there is no or an empty target, easiest way to prepare it,
     *   is by cloning the source content and then ignore the so created content on parsing
     */
    protected function handleEmptyTarget(string $transUnit): string
    {
        //if there is no target or an empty target we have to insert it
        if (strpos($transUnit, '<target') === false) {
            $transUnit = str_replace('</seg-source>', '</seg-source>' . '<target></target>', $transUnit);
        } else {
            //some versions of SDL Studio adds empty <target/> tags which must be converted then to
            $transUnit = preg_replace('#<target[^>]*/>#', '<target></target>', $transUnit);
        }

        //we fill the target with the source content
        return preg_replace_callback('#<target>\s*</target>#', function () use ($transUnit) {
            $this->wasEmptyTarget = true;
            //we split the transunit at the seg-source boundaries which gives as 3 elements, we return the one in the middle.
            $source = preg_split('#<[/]{0,1}seg-source[^>]*>#', $transUnit);

            return '<target>' . $source[1] . '</target>';
        }, $transUnit);
    }

    /***
     * Collect all transunit comments from the given transUnit and return the collected array.
     * The collected comments by this function, are on transunit level, and they can not be
     * detected by registered element parsers above. Here is an example of such comment:
     *
     * <trans-unit id="193e8534-644c-461d-af1a-18439f614428">
     *     <source>Startdatum muss vor dem Enddatum liegen</source>
     *         <seg-source>
     *             <mrk mtype="seg" mid="792">Startdatum muss vor dem Enddatum liegen</mrk>
     *         </seg-source>
     *     <target>
     *         <mrk mtype="seg" mid="792">La fecha de inicio debe ser anterior a la fecha de finalización</mrk>
     *     </target>
     *     <sdl:seg-defs>
     *             <sdl:seg id="792" locked="true" conf="ApprovedSignOff">
     *             <sdl:rep id="imitYwuCUK0gAxJ42cYr14syF0Y="/>
     *             <sdl:value key="SegmentIdentityHash">imitYwuCUK0gAxJ42cYr14syF0Y=</sdl:value>
     *             <sdl:value key="SDL:OriginalTranslationHash">678336267</sdl:value>
     *         </sdl:seg>
     *     </sdl:seg-defs>
     *     <sdl:cmt id="8bb03318-b551-46e8-9420-b4efa869e319"/>
     * </trans-unit>
     * @param string $transUnit
     * @return array
     */
    private function handleTransunitComments(string $transUnit): array
    {
        $collected = [];
        preg_match_all('/<sdl:cmt\s+id="([^"]+)"/', $transUnit, $matches);
        if (! empty($matches)) {
            foreach ($matches[1] as $commentId) {
                $collected[$commentId] = [
                    'field' => editor_Models_Import_FileParser_Sdlxliff::TRANS_UNIT,
                ];
            }
        }

        return $collected;
    }

    /**
     * returns the found trans-unit id
     */
    public function getTransunitId(): ?string
    {
        return $this->transunitId;
    }
}
