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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */

/**
 * Parsed mit editor_Models_Import_FileParser_Sdlxliff geparste Dateien für den Export
 *
 * INFO: Do not join any xml elements with new lines. This produces problems when the exported
 * file is imported in other systems!
 */
class editor_Models_Export_FileParser_Sdlxliff extends editor_Models_Export_FileParser
{
    private bool $isTrackChangesPluginActive;

    private array $revisions = [];

    private editor_Models_Import_FileParser_XmlParser $xmlParser;

    public function __construct(editor_Models_Task $task, int $fileId, string $path, array $options = [])
    {
        $this->isTrackChangesPluginActive = Zend_Registry::get('PluginManager')->isActive('TrackChanges');
        parent::__construct($task, $fileId, $path, $options);

        $this->xmlParser = new editor_Models_Import_FileParser_XmlParser();

        $this->registerTrackChangesMarkupTransform($task);
    }

    private function registerTrackChangesMarkupTransform(editor_Models_Task $task): void
    {
        if (!$this->isTrackChangesPluginActive) {
            return;
        }

        $trackChangeIdToUserName = array_column(
            ZfExtended_Factory::get(editor_Models_TaskUserTracking::class)->getByTaskGuid($task->getTaskGuid()),
            'userName',
            'id'
        );
        $this->xmlParser->registerElement(
            'ins, del',
            fn($tag, $attr, $key) => $this->xmlParser->replaceChunk($key, ''),
            function (string $tag, int $key, array $opener) use ($trackChangeIdToUserName): void {
                $attrs = $opener['attributes'];
                $uuid = ZfExtended_Utils::uuid();
                $ins = 'ins' === $opener['tag'];

                $revTag = sprintf(
                    '<rev-def id="%s"%s author="%s" date="%s" />',
                    $uuid,
                    $ins ? '' : ' type="Delete"',
                    $trackChangeIdToUserName[$attrs['data-usertrackingid']],
                    DateTime::createFromFormat('Y-m-d\TH:i:sO', $attrs['data-timestamp'])->format('m/d/Y H:i:s')
                );

                $this->revisions[] = $revTag;

                $mrk = sprintf(
                    '<mrk mtype="x-sdl-%s" sdl:revid="%s">%s</mrk>',
                    $ins ? 'added' : 'deleted',
                    $uuid,
                    $this->xmlParser->getRange($opener['openerKey'] + 1, $key - 1, true)
                );
                $this->xmlParser->replaceChunk($opener['openerKey'] + 1, $mrk);
                $this->xmlParser->replaceChunk($key, '');
            }
        );
    }

    protected function classNameDifftagger(): ?editor_Models_Export_DiffTagger
    {
        return $this->isTrackChangesPluginActive ? null : new editor_Models_Export_DiffTagger_Sdlxliff();
    }

    protected function getEditedSegment(?editor_Segment_Export $segmentExport): string
    {
        if (!$segmentExport) {
            return '';
        }

        // This removes all segment tags but the ones needed for export
        return $segmentExport->process($this->isTrackChangesPluginActive && ($this->options['diff'] ?? false));
    }

    /**
     * Rekonstruiert in einem Segment die ursprüngliche Form der enthaltenen Tags
     *
     */
    protected function parseSegment($segment): string
    {
        $segment = preg_replace('"<img[^>]*>"', '', $segment);

        if ($this->isTrackChangesPluginActive) {
            return $this->xmlParser->parse(parent::parseSegment($segment));
        }

        return parent::parseSegment($segment);
    }

    /**
     * sets $this->comments[$guid] = '<cmt-def id="'.$guid.'"><Comments><Comment severity="Medium" user="userName"
     * date="2016-07-21T19:40:01.80725+02:00" version="1.0">comment content</Comment>...</Comments></cmt-def>';
     * @param int $segmentId
     * @return string $id of comments index in $this->comments | null if no comments exist
     */
    protected function injectComments(int $segmentId, string $segment, string $field)
    {
        $commentModel = ZfExtended_Factory::get('editor_Models_Comment');
        /* @var $commentModel editor_Models_Comment */
        $comments = $commentModel->loadBySegmentAndTaskPlain($segmentId, $this->_taskGuid);

        //we may only run this function once per segment, so restrict to $field target
        if (empty($comments) || $field != 'target') {
            return $segment;
        }

        $commentMeta = ZfExtended_Factory::get('editor_Models_Comment_Meta');
        /* @var $commentMeta editor_Models_Comment_Meta */

        $tag = '<Comment severity="%1$s" user="%2$s" date="%3$s" version="%4$s">%5$s</Comment>';

        //since we can not preserve mrksof comments on text parts only, all target comments always relate to the whole target
        //there fore one guid for all target comments of one segment is needed
        $targetGuid = ZfExtended_Utils::uuid();
        $cmtDefContainer = [];
        $hasTargetComments = false;
        foreach ($comments as $comment) {
            $tagParams = [];
            $guid = $this->processOneComment($comment, $tagParams, $commentMeta);
            if (empty($guid)) {
                //target comment
                $hasTargetComments = true;
                $guidToUse = $targetGuid;
            } else {
                //source comment
                $guidToUse = $guid;
            }
            settype($cmtDefContainer[$guidToUse], 'array');
            $cmtDefContainer[$guidToUse][] = vsprintf($tag, $tagParams);
        }

        //group all Comments by guid into one cmt-def
        foreach ($cmtDefContainer as $guid => $comments) {
            $this->comments[$guid] = '<cmt-def id="' . $guid . '"><Comments>' . implode('', $comments) . '</Comments></cmt-def>';
        }

        if ($hasTargetComments) {
            return '<mrk mtype="x-sdl-comment" sdl:cid="' . $targetGuid . '">' . $segment . '</mrk>';
        }
        //if only source comments, we don't have to add the markers since in source they exist already
        return $segment;
    }

    /**
     * fills the given tagparams array with content for Comment tag generation
     * returns the original source comment guid or empty string if target comment
     * @param array $comment
     * @param array $tagParams
     * @param editor_Models_Comment_Meta $commentMeta
     * @return string original source comment guid or empty string if target comment or no source comment guid found
     */
    protected function processOneComment(array $comment, array &$tagParams, editor_Models_Comment_Meta $commentMeta): string
    {
        $modifiedObj = new DateTime($comment['modified']);
        //if the +0200 at the end makes trouble use the following
        //gmdate('Y-m-d\TH:i:s\Z', $modified->getTimestamp());
        $tagParams = [
            'severity' => 'Medium',
            'user' => htmlspecialchars($comment['userName']),
            'date' => $modifiedObj->format($modifiedObj::ATOM),
            'version' => '1.0',
            'comment' => htmlspecialchars($comment['comment'], ENT_XML1, 'UTF-8'),
        ];
        if ($comment['userGuid'] !== editor_Models_Import_FileParser_Sdlxliff::USERGUID) {
            return '';
        }
        try {
            $commentMeta->loadByCommentId($comment['id']);
            $tagParams['severity'] = $commentMeta->getSeverity();
            $tagParams['version'] = $commentMeta->getVersion();

            // currently only source and transUnit comments may (and must since in skeleton) reuse there original id
            if (in_array(
                $commentMeta->getAffectedField(),
                [
                    editor_Models_Import_FileParser_Sdlxliff::SOURCE,
                    editor_Models_Import_FileParser_Sdlxliff::TRANS_UNIT
                ]
            )) {
                return $commentMeta->getOriginalId();
            }
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            //do nothing if no meta found, assume it is a target comment then → return empty guid
        }
        return '';
    }

    /**
     * dedicated to write the match-Rate to the right position in the target format
     * @param array $file that contains file as array as splitted by parse function
     * @param int $i position of current segment in the file array
     * @return array
     */
    protected function writeMatchRate(array $file, int $i)
    {
        $matchRate = $this->_segmentEntity->getMatchRate();
        // in case the match-rate is 0, do not generate the percent tag
        if ($matchRate < 1) {
            return $file;
        }

        $mid = $this->_segmentEntity->getMid();
        $segPart =& $file[$i + 1];
        //example string
        //<sdl:seg-defs><sdl:seg id="16" conf="Translated" origin="tm" origin-system="Bosch_Ruoff_de-DE-en-US" percent="100"
        if (preg_match('#<sdl:seg[^>]* id="' . $mid . '"[^>]*percent="\d+"#', $segPart) === 1) {
            //if percent attribute is already defined
            $segPart = preg_replace('#(<sdl:seg[^>]* id="' . $mid . '"[^>]*percent=)"\d+"#', '\\1"' . $matchRate . '"', $segPart);
            return $file;
        }
        $segPart = preg_replace('#(<sdl:seg[^>]* id="' . $mid . '" *)#', '\\1 percent="' . $matchRate . '" ', $segPart);
        return $file;
    }

    /**
     * Gibt eine zu exportierende Datei bereits korrekt für den Export geparsed zurück
     *
     * @return string file
     */
    protected function getFile()
    {
        parent::getFile();
        $this->_exportFile = preg_replace('"(<mrk[^>]*[^/])></mrk>"i', '\\1/>', $this->_exportFile);
        $this->injectRevisions();
        $this->injectCommentsHead();
        $this->fixLockSegmentTags();
        return $this->_exportFile;
    }

    /**
     * Generiert die Revisionshistorie für den head der sdlxliff-Datei
     * Beispiel einer Revision: <rev-def id="b37e487f-2c70-4259-84e0-677d8c01f5b8" type="Delete"
     * author="christine.schulze" date="10/23/2012 10:25:04" />
     * @return string
     */
    protected function generateRevisions()
    {
        if ($this->isTrackChangesPluginActive) {
            return implode('', $this->revisions);
        }

        $createRevision = function ($rev, $tagType = null) {
            $delete = '';
            if ($tagType == 'delete') {
                $delete = ' type="Delete"';
            }
            return '<rev-def id="' . $rev['guid'] . '"' . $delete . ' author="' .
                $rev['username'] . '" date="' . date('m/d/Y H:i:s', strtotime($rev['timestamp'])) . '" />';
        };
        $revisions = "";
        foreach ($this->_diffTagger->_additions as $rev) {
            $revisions .= $createRevision($rev);
        }
        foreach ($this->_diffTagger->_deletions as $rev) {
            $revisions .= $createRevision($rev, 'delete');
        }
        return $revisions;
    }

    /**
     *
     */
    protected function injectCommentsHead()
    {
        if (!empty($this->comments)) {
            $commentsAsString = implode('', $this->comments);
            if (strpos($this->_exportFile, '</cmt-defs>') !== false) {
                $this->_exportFile = str_replace('</cmt-defs>', $commentsAsString . '</cmt-defs>', $this->_exportFile);
            } elseif (strpos($this->_exportFile, '<cmt-meta-defs>') !== false) {
                $this->_exportFile = str_replace('<cmt-meta-defs>', '<cmt-defs>' . $commentsAsString . '</cmt-defs><cmt-meta-defs>', $this->_exportFile);
            } elseif (strpos($this->_exportFile, '</doc-info>') !== false) {
                $this->_exportFile = str_replace('</doc-info>', '<cmt-defs>' . $commentsAsString . '</cmt-defs></doc-info>', $this->_exportFile);
            } else {
                $this->_exportFile =
                    preg_replace('"(<xliff[^>]*xmlns:sdl=\")([^\"]*)(\"[^>]*>)"',
                        '\\1\\2\\3<doc-info xmlns="\\2"><cmt-defs>' .
                        $commentsAsString . '</cmt-defs></doc-info>', $this->_exportFile);
            }
        }
    }

    /**
     * Injiziert die Revisionshistorie in den head der sdlxliff-Datei
     */
    protected function injectRevisions()
    {
        $revisions = $this->generateRevisions();
        if ($revisions != '') {
            if (strpos($this->_exportFile, '</rev-defs>') !== false) {
                $this->_exportFile = str_replace('</rev-defs>', $revisions . '</rev-defs>', $this->_exportFile);
            } elseif (strpos($this->_exportFile, '</doc-info>') !== false) {
                $this->_exportFile = str_replace('</doc-info>', '<rev-defs>' . $revisions . '</rev-defs></doc-info>', $this->_exportFile);
            } else {
                $this->_exportFile =
                    preg_replace('"(<xliff[^>]*xmlns:sdl=\")([^\"]*)(\"[^>]*>)"',
                        '\\1\\2\\3<doc-info xmlns="\\2"><rev-defs>' .
                        $revisions . '</rev-defs></doc-info>', $this->_exportFile);
            }
        }
    }

    /**
     * Repair the locked tag references
     * @return void
     * @throws ReflectionException
     */
    private function fixLockSegmentTags(): void
    {
        if (strpos($this->_exportFile, 'xid="lockTU_') !== false) {
            $repair = new editor_Models_Export_FileParser_Sdlxliff_RepairLockedReferences(
                ZfExtended_Factory::get(editor_Models_Import_FileParser_XmlParser::class),
                $this->_exportFile
            );
            $this->_exportFile = $repair->repair();
        }
    }
}
