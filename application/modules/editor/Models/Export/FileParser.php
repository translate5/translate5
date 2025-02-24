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

use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\Segment\TransUnitHash;
use MittagQI\Translate5\Task\FileTranslation\FileTranslationType;
use MittagQI\Translate5\Task\Import\SkeletonFile;

/**
 * Enthält Methoden zum Fileparsing für den Export
 */
abstract class editor_Models_Export_FileParser
{
    use editor_Models_Export_FileParser_MQMTrait;

    /**
     * @var string
     */
    protected $_exportFile = null;

    protected string $skeletonFile = '';

    /**
     * @var integer
     */
    protected $_fileId = null;

    /**
     * @var editor_Models_Segment aktuell bearbeitetes Segment
     */
    protected $_segmentEntity = null;

    /**
     * contains a limited amount of loaded segments
     * @var array
     */
    protected $segmentCache = [];

    /**
     * @var string Klassenname des Difftaggers
     */
    protected $_classNameDifftagger = null;

    /**
     * fluent container of flags controlling the export parser, may be set from config or via param or whatever
     * @var array
     */
    protected $options = [
        'diff' => false,
    ];

    /**
     * @var editor_Models_Task current task
     */
    protected $_task;

    /**
     * @var string
     */
    protected $_taskGuid;

    /**
     * @var Zend_Config
     */
    protected $config;

    /**
     * @var string path including filename, on which the exported file will be saved
     */
    protected $path;

    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;

    /**
     * Disables the MQM Export if needed
     * @var boolean
     */
    protected $disableMqmExport = false;

    /**
     * each array element contains the comments for one segment
     * the array-index is set to an ID for the comments
     * @var array
     */
    protected $comments;

    /**
     * @var editor_Models_Segment_UtilityBroker
     */
    protected $utilities;

    /**
     * @var ZfExtended_EventManager
     */
    protected $events;

    /**
     * contains the length of the last content returned by getSegmentContent
     * @var integer
     */
    protected $lastSegmentLength = 0;

    /**
     * collected segmentNrs with tag missing or to much tags compared to the source
     * @var array
     */
    protected $faultySegments = [];

    /**
     * @var ZfExtended_Logger
     */
    protected $log;

    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager;

    protected TransUnitHash $transunitHash;

    protected SkeletonFile $skeletonFileInstance;

    protected ContentProtector $contentProtector;

    protected ?editor_Models_Export_DiffTagger $_diffTagger;

    /**
     * @param string $path The absolute path to the file where the content is written to
     * @param array $options see $this->options for available options
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws editor_Models_ConfigException
     * @throws editor_Models_Export_FileParser_Exception
     */
    public function __construct(editor_Models_Task $task, int $fileId, string $path, array $options = [])
    {
        $this->_fileId = $fileId;
        $this->_diffTagger = $this->classNameDifftagger();
        $this->options = array_replace($this->options, $options);
        $this->_task = $task;
        $this->_taskGuid = $task->getTaskGuid();
        $this->path = $path;
        $this->config = $task->getConfig();
        $this->transunitHash = ZfExtended_Factory::get(TransUnitHash::class, [$this->config, $fileId]);
        $this->log = Zend_Registry::get('logger')->cloneMe('editor.export.fileparser');
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();

        $this->utilities = ZfExtended_Factory::get('editor_Models_Segment_UtilityBroker');
        $this->contentProtector = ContentProtector::create($this->utilities->whitespace);

        $this->segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        $this->segmentFieldManager->initFields($this->_taskGuid);

        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', [get_class($this)]);

        $this->skeletonFileInstance = ZfExtended_Factory::get(SkeletonFile::class, [$task]);
    }

    abstract protected function classNameDifftagger(): ?editor_Models_Export_DiffTagger;

    /**
     * Gibt eine zu exportierende Datei bereits korrekt für den Export geparsed zurück
     *
     * @return string file
     */
    protected function getFile()
    {
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $file->load($this->_fileId);

        $this->loadSkeleton($file);
        $this->parse();

        return $this->_exportFile = $this->convertEncoding($file, $this->_exportFile);
    }

    public function saveFile()
    {
        file_put_contents($this->path, $this->getFile());
    }

    /**
     * returns the collected segments with tag errors
     * These will only be evaluated, if $options['checkFaultySegments'] is set
     * @return array
     */
    public function getFaultySegments()
    {
        return $this->faultySegments;
    }

    /**
     * übernimmt das eigentliche FileParsing
     *
     * - setzt an Stelle von <lekTargetSeg... wieder das überarbeitete Targetsegment ein
     * - befüllt $this->_exportFile
     */
    protected function parse()
    {
        $file = preg_split('#<lekTargetSeg([^>]+)/>#', $this->skeletonFile, flags: PREG_SPLIT_DELIM_CAPTURE);

        $count = count($file) - 1;
        for ($i = 1; $i < $count;) {
            $file[$i] = $this->preProcessReplacement($file[$i]);
            $matches = [];
            if (! preg_match('#^\s*id="([^"]+)"\s*(field="([^"]+)"\s*)?$#', $file[$i], $matches)) {
                //Error in Export-Fileparsing. instead of a id="INT" and a optional field="STRING" attribute the following content was extracted: "{content}"
                throw new editor_Models_Export_FileParser_Exception('E1086', [
                    'task' => $this->_task,
                    'content' => $file[$i],
                ]);
            }

            //check $matches[1] for integer (segmentId) if empty throw an exception
            settype($matches[1], 'int');
            if (empty($matches[1])) {
                //Error in Export-Fileparsing. instead of a id="INT" and a optional field="STRING" attribute the following content was extracted: "{content}"
                throw new editor_Models_Export_FileParser_Exception('E1087', [
                    'task' => $this->_task,
                    'content' => $file[$i],
                ]);
            }

            //alternate column is optional, use target as default
            if (isset($matches[3])) {
                $field = $matches[3];
            } else {
                $field = editor_Models_SegmentField::TYPE_TARGET;
            }

            $file[$i] = $this->getSegmentContent($matches[1], $field);

            $file = $this->writeBySegmentMetadata($file, $i);

            if ($this->config->runtimeOptions->editor->export->exportComments) {
                $file[$i] = $this->injectComments($matches[1], $file[$i], $field);
            }

            $i = $i + 2;
        }
        $this->_exportFile = implode('', $file);
    }

    /**
     * for injecting comment markers into the content which was replaced from placeholder
     * for overwriting purposes
     * @return string $id of comments index in $this->comments | null if no comments exist
     */
    protected function injectComments(int $segmentId, string $segment, string $field)
    {
        return $segment;
    }

    /**
     * pre processor for the extracted lekTargetSeg attributes
     * for overwriting purposes only
     * @param string $attributes
     * @return string
     */
    protected function preProcessReplacement($attributes)
    {
        return $attributes;
    }

    /**
     * dedicated to write the match-Rate to the right position in the target format
     * for overwriting purposes only
     * @param array $file that contains file as array as splitted by parse function
     * @param int $i position of current segment in the file array
     * @return array
     */
    protected function writeMatchRate(array $file, int $i)
    {
        return $file;
    }

    protected function writeBySegmentMetadata(array $file, int $i): array
    {
        return $this->writeMatchRate($file, $i);
    }

    /**
     * the browser adds non-breaking-spaces instead of normal spaces, if the user
     * types more than one space directly after eachother. For the GUI this
     * makes sense, because this way the whitespace can be presented in the
     * correct visual form to the user (normal spaces would be shown as one
     * space in HTML). For the export they have to be reconverted to normal
     * spaces
     *
     * @param string $segment
     * @return string $segment
     */
    protected function revertNonBreakingSpaces($segment)
    {
        //replacing nbsp introduced by browser back to multiple spaces
        return preg_replace('#\x{00a0}#u', ' ', $segment);
    }

    /**
     * returns the segment content for the given segmentId and field. Adds optional diff markup, and handles tags.
     * @param int $segmentId
     * @param string $field fieldname to get the content from
     */
    protected function getSegmentContent(int|string $segmentId, string $field): string
    {
        $this->_segmentEntity = $segment = $this->getSegment($segmentId);
        /* @var $segment editor_Models_Segment */
        $segmentMeta = $segment->meta();

        // for non editable sources the edited field is empty, so we have to fetch the original
        $useEdited = ! ($field == editor_Models_SegmentField::TYPE_SOURCE && ! $this->segmentFieldManager->isEditable($field));
        // if the auto-qa is deactivated, this flag should be true to anable dynamic searching for faults
        // TODO FIXME: when auto-QA is not active for segment errors, faulty tags will only be detected, not repaired. This behaviour must be discussed and the variable-naming curently says something else
        $findFaultyTags = array_key_exists('checkFaultySegments', $this->options) && $this->options['checkFaultySegments'] === true;
        // as stated above, not to fix if we search for faults seems to be pretty schizophrenic
        $fixFaultyTags = ! $findFaultyTags || ($this->_task->getTaskType()->__toString() === FileTranslationType::ID);

        $segmentExport = $segment->getFieldExport($field, $this->_task, $useEdited, $fixFaultyTags, $findFaultyTags);

        $edited = $this->getEditedSegment($segmentExport);

        if ($segmentExport != null) {
            if ($segmentExport->hasFaultyTags()) {
                $this->faultySegments[] = [
                    'id' => $segmentId,
                    'field' => $field,
                    'segmentNrInTask' => $segment->getSegmentNrInTask(),
                ];
            }
            if ($segmentExport->hasFixedFaultyTags()) {
                // TODO INSTANTTRANSLATE: If we need a remark in the instant translate frontend, that there were errors automatically fixed, this has to be initiated here
                error_log('Task ' . $this->_task->getTaskGuid() . ' Export: Internal Tag Faults have been fixed automatically for segment ' . $segmentId);
            }
        }

        //count length after removing removeTrackChanges and removeTermTags
        // so that the same remove must not be done again inside of textLength
        //also add additionalMrkLength to the segment length for final length calculation
        $this->lastSegmentLength = $segment->textLengthByMeta(
            $edited,
            $segmentMeta,
            (int) $segment->getFileId(),
            str_contains($field, editor_Models_SegmentField::TYPE_SOURCE)
        ) + (int) $segmentMeta->getAdditionalMrkLength();

        $edited = $this->parseSegment($edited);
        $edited = $this->revertNonBreakingSpaces($edited);

        if (! $this->options['diff']) {
            return $this->unprotectContent($edited, str_contains($field, editor_Models_SegmentField::TYPE_SOURCE));
        }
        $segmentOriginal = $segment->getFieldExport($field, $this->_task, false, false);
        // This removes all segment tags but the ones needed for export
        $original = ($segmentOriginal == null) ? '' : $segmentOriginal->process();

        $original = $this->parseSegment($original);

        $result = $edited;

        if (null !== $this->_diffTagger) {
            try {
                $result = $this->_diffTagger->diffSegment(
                    $original,
                    $edited,
                    $segment->getTimestamp(),
                    $segment->getUserName()
                );
            } catch (Exception $e) {
                throw new editor_Models_Export_FileParser_Exception('E1088', [
                    'task' => $this->_task,
                    'fileId' => $this->_fileId,
                ], $e);
            }
        }

        // unprotectWhitespace must be done after diffing!
        return $this->unprotectContent($result, str_contains($field, 'source'));
    }

    protected function getEditedSegment(?editor_Segment_Export $segmentExport): string
    {
        if (! $segmentExport) {
            return '';
        }

        // This removes all segment tags but the ones needed for export
        return $segmentExport->process();
    }

    /**
     * loads the segment to the given Id, caches a limited count of segments internally
     * to prevent loading again while switching between fields
     * @param int $segmentId
     * @return editor_Models_Segment
     */
    protected function getSegment($segmentId)
    {
        if (isset($this->segmentCache[$segmentId])) {
            return $this->segmentCache[$segmentId];
        }
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segment->load($segmentId);
        $this->segmentCache[$segmentId] = $segment;
        //we keep a max of 50 segments, this should be enough
        if (count($this->segmentCache) > 50) {
            reset($this->segmentCache);
            $firstKey = key($this->segmentCache);
            unset($this->segmentCache[$firstKey]);
        }

        return $segment;
    }

    /**
     * Loads the skeleton file from the disk and stores it internally
     * @throws ZfExtended_Exception
     */
    protected function loadSkeleton(editor_Models_File $file)
    {
        //since its not called in a loop, we just instance the SkeletonFile here
        $this->skeletonFile = $this->skeletonFileInstance->loadFromDisk($file);
    }

    /**
     * Rekonstruiert in einem Segment die ursprüngliche Form der enthaltenen Tags
     *
     * @param string $segment
     * @return string $segment
     */
    protected function parseSegment($segment)
    {
        return $this->utilities->internalTag->restore($segment);
    }

    /**
     * converts $this->_exportFile back to the original encoding registered in the LEK_files
     */
    protected function convertEncoding(editor_Models_File $file, string $data): string
    {
        $enc = $file->getEncoding();
        if (is_null($enc) || $enc === '' || strtolower($enc) === 'utf-8') {
            return $data;
        }

        return iconv('utf-8', $enc, $data);
    }

    /**
     * Exports a single segment content, without MQM support!
     * Term Tags remains in the content and are not touched.
     *
     * @param string $segment
     * @return string
     */
    public function exportSingleSegmentContent($segment, bool $isSource)
    {
        //processing of term tags is done after using this method!
        $this->disableMqmExport = true;
        $segment = $this->parseSegment($segment);
        $segment = $this->revertNonBreakingSpaces($segment);

        return $this->unprotectContent($segment, $isSource);
    }

    /**
     * Some internal tags are standing for placeholder tags, this placeholder tags must also converted back
     */
    protected function unprotectContent(string $segment, bool $isSource): string
    {
        return $this->contentProtector->unprotect($segment, $isSource);
    }
}
